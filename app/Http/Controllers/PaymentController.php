<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exports\PaymentsExport;
use App\Exports\TableExporter;
use App\Http\Requests\ExportTableRequest;
use App\Http\Requests\MarkPaymentPaidRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\TableQueryRequest;
use App\Http\Requests\VoidPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PaymentController extends Controller
{
    /**
     * Display the payments visible to the user.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('viewAny', Payment::class);

        $filteredPayments = $this->filteredQuery($request);

        $payments = (clone $filteredPayments)
            ->with(['patient', 'appointment.doctor.user'])
            ->paginate(self::TABLE_PAGE_SIZE)
            ->withQueryString()
            ->through(fn (Payment $payment): array => (new PaymentResource($payment))->resolve($request));

        $settling = Payment::query()
            ->visibleTo($request->user())
            ->with(['patient', 'appointment.doctor.user'])
            ->find($request->integer('pay'));

        return Inertia::render('payments/Index', [
            'payments' => $payments,
            'settling' => $settling ? (new PaymentResource($settling))->resolve($request) : null,
            'totals' => [
                'paid' => (float) (clone $filteredPayments)->reorder()->where('status', PaymentStatus::Paid)->sum('amount'),
                'pending' => (float) (clone $filteredPayments)->reorder()->where('status', PaymentStatus::Pending)->sum('amount'),
            ],
            'filters' => $request->filters(),
            'statuses' => PaymentStatus::options(),
            'methods' => PaymentMethod::options(),
            'can' => ['create' => $request->user()->can('create', Payment::class)],
        ]);
    }

    /**
     * Export the filtered payments to Excel or PDF.
     */
    public function export(ExportTableRequest $request, TableExporter $exporter): SymfonyResponse
    {
        Gate::authorize('viewAny', Payment::class);

        return $exporter->download(
            new PaymentsExport($this->filteredQuery($request), $request->filters()),
            $request->exportFormat(),
        );
    }

    /**
     * Payments visible to the user, narrowed by the table filters.
     *
     * @return Builder<Payment>
     */
    private function filteredQuery(TableQueryRequest $request): Builder
    {
        $term = $request->searchTerm();
        $status = PaymentStatus::tryFrom($request->string('status')->toString());

        return Payment::query()
            ->visibleTo($request->user())
            ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $query->where('concept', 'like', "%{$term}%")
                    ->orWhere('reference', 'like', "%{$term}%")
                    ->orWhereHas('patient', fn (Builder $query) => $query->search($term));
            }))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('paid_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('paid_at', '<=', $request->date('to')))
            ->orderByRaw('status = ? desc', [PaymentStatus::Pending->value])
            ->latest('paid_at')
            ->latest('id');
    }

    /**
     * Show the form to record a payment.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Payment::class);

        $prefillAppointment = Appointment::query()
            ->with(['patient', 'doctor.user'])
            ->find($request->integer('appointment_id'));

        $sourcePayment = $prefillAppointment
            ? null
            : Payment::query()->find($request->integer('payment_id'));

        $selectedPatient = $prefillAppointment?->patient
            ?? $sourcePayment?->patient
            ?? Patient::query()->find($request->integer('patient_id'));

        $prefill = match (true) {
            $prefillAppointment !== null => [
                'appointment_id' => $prefillAppointment->id,
                'concept' => 'Consulta médica · '.$prefillAppointment->doctor->user->name,
                'amount' => $prefillAppointment->doctor->consultation_fee,
                'method' => null,
            ],
            $sourcePayment !== null => [
                'appointment_id' => null,
                'concept' => $sourcePayment->concept,
                'amount' => $sourcePayment->amount,
                'method' => $sourcePayment->method->value,
            ],
            default => null,
        };

        return Inertia::render('payments/Create', [
            'patients' => Patient::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'document_number'])
                ->map(fn (Patient $patient): array => [
                    'id' => $patient->id,
                    'full_name' => $patient->full_name,
                    'document_number' => $patient->document_number,
                ]),
            'selectedPatientId' => $selectedPatient?->id,
            'prefill' => $prefill,
            'appointments' => $selectedPatient
                ? $selectedPatient->appointments()
                    ->with('doctor.user')
                    ->latest('scheduled_at')
                    ->limit(20)
                    ->get()
                    ->map(fn ($appointment): array => [
                        'id' => $appointment->id,
                        'label' => $appointment->scheduled_at->format('d/m/Y g:i A').' · '.$appointment->doctor->user->name.' · '.$appointment->reason,
                    ])
                : [],
            'methods' => PaymentMethod::options(),
            'statuses' => array_values(array_filter(
                PaymentStatus::options(),
                fn (array $option): bool => $option['value'] !== PaymentStatus::Voided->value,
            )),
        ]);
    }

    /**
     * Store the manual payment record.
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $payment = Payment::create([
            ...$validated,
            'paid_at' => $validated['status'] === PaymentStatus::Paid->value ? $validated['paid_at'] : null,
            'recorded_by' => $request->user()->id,
        ]);

        return to_route('patients.show', $payment->patient_id)->with('success', 'Pago registrado correctamente.');
    }

    /**
     * Show the details of a payment.
     */
    public function show(Request $request, Payment $payment): Response
    {
        Gate::authorize('view', $payment);

        $payment->load(['patient', 'appointment.doctor.user', 'recorder']);

        return Inertia::render('payments/Show', [
            'payment' => (new PaymentResource($payment))->resolve($request),
            'methods' => PaymentMethod::options(),
            'can' => [
                'view_patient' => $request->user()->can('view', $payment->patient),
            ],
        ]);
    }

    /**
     * Open the invoice of a paid payment as a PDF in the browser.
     */
    public function invoice(Payment $payment): SymfonyResponse
    {
        Gate::authorize('downloadInvoice', $payment);

        $payment->load(['patient', 'appointment.doctor.user', 'appointment.doctor.specialty']);
        $number = 'F-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);

        return Pdf::loadView('pdf.invoice', [
            'payment' => $payment,
            'number' => $number,
            'generatedAt' => now(),
        ])
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4')
            ->stream("factura-{$number}.pdf");
    }

    /**
     * Mark a pending payment as paid.
     */
    public function markPaid(MarkPaymentPaidRequest $request, Payment $payment): RedirectResponse
    {
        $payment->update([
            ...$request->validated(),
            'status' => PaymentStatus::Paid,
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Pago marcado como pagado.');
    }

    /**
     * Void a payment, keeping the reason in its notes.
     */
    public function void(VoidPaymentRequest $request, Payment $payment): RedirectResponse
    {
        $reason = 'Anulado: '.$request->validated('reason');

        $payment->update([
            'status' => PaymentStatus::Voided,
            'notes' => filled($payment->notes) ? $payment->notes."\n".$reason : $reason,
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Pago anulado.');
    }
}
