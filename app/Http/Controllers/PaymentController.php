<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display the payments visible to the user.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Payment::class);

        $user = $request->user();

        $visiblePayments = Payment::query()->visibleTo($user);

        $payments = (clone $visiblePayments)
            ->with('patient')
            ->latest('paid_at')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Payment $payment): array => (new PaymentResource($payment))->resolve($request));

        return Inertia::render('payments/Index', [
            'payments' => $payments,
            'totals' => [
                'paid' => (float) (clone $visiblePayments)->where('status', PaymentStatus::Paid)->sum('amount'),
                'pending' => (float) (clone $visiblePayments)->where('status', PaymentStatus::Pending)->sum('amount'),
            ],
            'can' => ['create' => $user->can('create', Payment::class)],
        ]);
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
                        'label' => $appointment->scheduled_at->format('d/m/Y H:i').' · '.$appointment->doctor->user->name.' · '.$appointment->reason,
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
