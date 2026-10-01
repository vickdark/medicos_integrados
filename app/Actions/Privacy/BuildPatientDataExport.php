<?php

namespace App\Actions\Privacy;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\DataSubjectRequest;
use App\Models\Patient;
use App\Models\Payment;

/**
 * Gathers the personal data the clinic holds about a patient, as the patient
 * can see it: the internal notes of the clinic staff are not included.
 */
class BuildPatientDataExport
{
    /**
     * @return array<string, mixed>
     */
    public function build(Patient $patient): array
    {
        $patient->load(['insurer', 'user']);

        return [
            'generated_at' => now()->toIso8601String(),
            'privacy_policy' => [
                'accepted_version' => $patient->user?->privacy_policy_version,
                'accepted_at' => $patient->user?->privacy_accepted_at?->toIso8601String(),
            ],
            'patient' => [
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'document_type' => $patient->document_type?->label(),
                'document_number' => $patient->document_number,
                'email' => $patient->email,
                'phone' => $patient->phone,
                'birth_date' => $patient->birth_date?->toDateString(),
                'gender' => $patient->gender?->label(),
                'address' => $patient->address,
                'blood_type' => $patient->blood_type,
                'insurer' => $patient->insurer?->name,
                'affiliation_type' => $patient->affiliation_type?->label(),
                'allergies' => $patient->allergies,
                'chronic_conditions' => $patient->chronic_conditions,
                'medical_background' => $patient->medical_background,
                'emergency_contact_name' => $patient->emergency_contact_name,
                'emergency_contact_phone' => $patient->emergency_contact_phone,
            ],
            'appointments' => $patient->appointments()->with('doctor.user')->orderBy('scheduled_at')->get()
                ->map(fn (Appointment $appointment): array => [
                    'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
                    'status' => $appointment->status->label(),
                    'doctor' => $appointment->doctor->user->name,
                    'reason' => $appointment->reason,
                ])->all(),
            'consultations' => $patient->consultations()->with(['doctor.user', 'primaryDiagnosis', 'prescriptions'])->orderBy('consulted_at')->get()
                ->map(fn (Consultation $consultation): array => [
                    'consulted_at' => $consultation->consulted_at->toIso8601String(),
                    'doctor' => $consultation->doctor->user->name,
                    'reason' => $consultation->reason,
                    'symptoms' => $consultation->symptoms,
                    'diagnosis' => $consultation->diagnosis,
                    'primary_diagnosis' => $consultation->primaryDiagnosis?->only(['code', 'description']),
                    'treatment' => $consultation->treatment,
                    'weight_kg' => $consultation->weight_kg,
                    'height_cm' => $consultation->height_cm,
                    'blood_pressure' => $consultation->blood_pressure,
                    'temperature_c' => $consultation->temperature_c,
                    'heart_rate' => $consultation->heart_rate,
                ])->all(),
            'payments' => $patient->payments()->orderBy('created_at')->get()
                ->map(fn (Payment $payment): array => [
                    'concept' => $payment->concept,
                    'amount' => $payment->amount,
                    'method' => $payment->method?->label(),
                    'status' => $payment->status->label(),
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ])->all(),
            'data_requests' => $patient->dataSubjectRequests()->orderBy('created_at')->get()
                ->map(fn (DataSubjectRequest $request): array => [
                    'type' => $request->type->label(),
                    'status' => $request->status->label(),
                    'requested_at' => $request->created_at?->toIso8601String(),
                    'due_at' => $request->due_at->toDateString(),
                    'responded_at' => $request->responded_at?->toIso8601String(),
                ])->all(),
        ];
    }
}
