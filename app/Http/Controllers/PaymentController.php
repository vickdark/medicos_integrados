<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exports\PaymentsExport;
use App\Exports\TableExporter;
use App\Http\Requests\ExportTableRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\TableQueryRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Patient;
use App\Models\Payment;
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
            ->with('patient')
            ->paginate(self::TABLE_PAGE_SIZE)
            ->withQueryString()
            ->through(fn (Payment $payment): array => (new PaymentResource($payment))->resolve($request));

        return Inertia::render('payments/Index', [
            'payments' => $payments,
            'totals' => [
                'paid' => (float) (clone $filteredPayments)->reorder()->where('status', PaymentStatus::Paid)->sum('amount'),
                'pending' => (float) (clone $filteredPayments)->reorder()->where('status', PaymentStatus::Pending)->sum('amount'),
            ],
            'filters' => $request->filters(),
            'statuses' => PaymentStatus::options(),
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
            ->latest('paid_at')
            ->latest('id');
    }

    /**
     * Show the form to record a payment.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Payment::class);

        $selectedPatient = Patient::query()->find($request->integer('patient_id'));

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
}
