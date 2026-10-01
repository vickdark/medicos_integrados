<?php

namespace App\Actions\Verification;

use App\Enums\CarePriority;
use App\Enums\ClinicalDocumentType;
use App\Enums\SickLeaveOrigin;
use App\Models\ClinicalDocument;
use App\Models\ClinicalHistoryExport;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Carbon\CarbonImmutable;

/**
 * Verification codes printed with a QR on the official PDFs, so anyone who
 * receives a prescription or clinical document can check on the clinic's site
 * that it was really issued and has not been altered.
 *
 * The public result never includes the diagnosis and masks the patient's data:
 * it only shows what is needed to compare it with the paper.
 */
class DocumentVerifier
{
    public const DOCUMENT_PREFIX = 'D';

    public const PRESCRIPTION_PREFIX = 'R';

    public const HISTORY_PREFIX = 'H';

    /**
     * Characters that cannot be confused when typed by hand (no 0/O, 1/I).
     */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 12;

    private const QR_SIZE = 240;

    /**
     * A new random code such as "D7KQ-4M2X-P9RT". The first letter tells whether
     * it belongs to a clinical document or to a prescription.
     */
    public static function newCode(string $prefix): string
    {
        $code = $prefix;

        while (strlen($code) < self::CODE_LENGTH) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return implode('-', str_split($code, 4));
    }

    /**
     * Put a code typed by hand in its canonical form: upper case, grouped by four.
     */
    public static function normalize(string $code): string
    {
        $clean = preg_replace('/[^A-Z0-9]/', '', strtoupper($code)) ?? '';

        return implode('-', str_split($clean, 4));
    }

    /**
     * The code of the consultation's prescription, created the first time.
     */
    public function prescriptionCode(Consultation $consultation): string
    {
        if (! $consultation->prescription_verification_code) {
            $consultation->forceFill(['prescription_verification_code' => self::newCode(self::PRESCRIPTION_PREFIX)])->save();
        }

        return $consultation->prescription_verification_code;
    }

    /**
     * Code, public URL and QR image to print on an official PDF.
     *
     * @return array{code: string, url: string, base_url: string, qr: string}
     */
    public function stamp(string $code): array
    {
        $url = route('verification.show', ['code' => $code]);
        $png = (new Writer(new GDLibRenderer(self::QR_SIZE, 1)))->writeString($url);

        return [
            'code' => $code,
            'url' => $url,
            'base_url' => route('verification.index'),
            'qr' => 'data:image/png;base64,'.base64_encode($png),
        ];
    }

    /**
     * What the public page shows about the document with the given code, or null
     * when there is none.
     *
     * @return array{type: string, number: string, issued_at: string, signed: bool|null, doctor: array{name: string, specialty: string, license_number: string}|null, issued_by: string|null, patient: array{initials: string, document: string|null}, facts: list<array{label: string, value: string|list<string>}>}|null
     */
    public function find(string $code): ?array
    {
        $code = self::normalize($code);

        return match (substr($code, 0, 1)) {
            self::DOCUMENT_PREFIX => $this->describeDocument(
                ClinicalDocument::query()
                    ->with(['consultation.patient', 'consultation.doctor.user', 'consultation.doctor.specialty'])
                    ->firstWhere('verification_code', $code),
            ),
            self::PRESCRIPTION_PREFIX => $this->describePrescription(
                Consultation::query()
                    ->with(['patient', 'doctor.user', 'doctor.specialty', 'prescriptions'])
                    ->firstWhere('prescription_verification_code', $code),
            ),
            self::HISTORY_PREFIX => $this->describeHistory(
                ClinicalHistoryExport::query()
                    ->with('patient')
                    ->firstWhere('verification_code', $code),
            ),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function describeDocument(?ClinicalDocument $document): ?array
    {
        if ($document === null) {
            return null;
        }

        $data = $document->data;

        $facts = match ($document->type) {
            ClinicalDocumentType::InformedConsent => [
                ['label' => 'Procedimiento', 'value' => (string) $data['procedure']],
            ],
            ClinicalDocumentType::SickLeave => [
                ['label' => 'Tipo', 'value' => ! empty($data['is_extension']) ? 'Prórroga' : 'Incapacidad inicial'],
                ['label' => 'Origen', 'value' => SickLeaveOrigin::from($data['origin'])->label()],
                ['label' => 'Desde', 'value' => CarbonImmutable::parse($data['start_date'])->format('d/m/Y')],
                ['label' => 'Hasta', 'value' => (string) $document->sickLeaveEndDate()?->format('d/m/Y')],
                ['label' => 'Días', 'value' => (string) $data['days']],
            ],
            ClinicalDocumentType::Referral => [
                ['label' => 'Remitido a', 'value' => (string) $data['specialty']],
                ['label' => 'Prioridad', 'value' => CarePriority::from($data['priority'])->label()],
            ],
            ClinicalDocumentType::ExamOrder => [
                ['label' => 'Exámenes', 'value' => array_values((array) $data['exams'])],
                ['label' => 'Prioridad', 'value' => CarePriority::from($data['priority'])->label()],
            ],
        };

        return $this->describe(
            $document->type->label(),
            $document->number,
            CarbonImmutable::instance($document->created_at),
            $document->consultation->doctor,
            $document->consultation->patient,
            $facts,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function describePrescription(?Consultation $consultation): ?array
    {
        if ($consultation === null || $consultation->prescriptions->isEmpty()) {
            return null;
        }

        return $this->describe(
            'Receta médica',
            'RX-'.str_pad((string) $consultation->id, 6, '0', STR_PAD_LEFT),
            CarbonImmutable::instance($consultation->consulted_at),
            $consultation->doctor,
            $consultation->patient,
            [[
                'label' => 'Medicamentos',
                'value' => $consultation->prescriptions
                    ->map(fn (Prescription $prescription): string => collect([
                        $prescription->medication,
                        $prescription->dosage,
                        $prescription->frequency,
                        $prescription->duration,
                    ])->filter()->implode(' · '))
                    ->values()
                    ->all(),
            ]],
        );
    }

    /**
     * A clinical history extract lists the date and doctor of every consultation
     * it included, so pages added or removed can be noticed; never their content.
     *
     * @return array<string, mixed>|null
     */
    private function describeHistory(?ClinicalHistoryExport $export): ?array
    {
        if ($export === null) {
            return null;
        }

        $consultations = Consultation::query()
            ->whereKey($export->consultation_ids)
            ->with(['doctor.user', 'doctor.specialty'])
            ->withCount('addenda')
            ->orderBy('consulted_at')
            ->get();

        return $this->describe(
            'Historia clínica',
            $export->number,
            CarbonImmutable::instance($export->created_at),
            null,
            $export->patient,
            [
                ['label' => 'Período', 'value' => $export->period_label],
                ['label' => 'Consultas incluidas', 'value' => (string) $consultations->count()],
                ['label' => 'Tipo de copia', 'value' => $export->includes_notes
                    ? 'Interna, para el personal de la clínica (incluye notas internas)'
                    : 'Del paciente (sin notas internas)'],
                [
                    'label' => 'Detalle de las consultas',
                    'value' => $consultations
                        ->map(fn (Consultation $consultation): string => $consultation->consulted_at->format('d/m/Y g:i A')
                            .' · '.$consultation->doctor->user->name
                            .' ('.$consultation->doctor->specialty->name.')'
                            .($consultation->addenda_count ? ' · '.$consultation->addenda_count.' nota(s) aclaratoria(s)' : ''))
                        ->values()
                        ->all(),
                ],
            ],
            issuedBy: "{$export->issued_by_name} ({$export->issued_by_role})",
        );
    }

    /**
     * @param  list<array{label: string, value: string|list<string>}>  $facts
     * @return array<string, mixed>
     */
    private function describe(string $type, string $number, CarbonImmutable $issuedAt, ?Doctor $doctor, Patient $patient, array $facts, ?string $issuedBy = null): array
    {
        return [
            'type' => $type,
            'number' => $number,
            'issued_at' => $issuedAt->toIso8601String(),
            'signed' => $doctor?->hasSignature(),
            'doctor' => $doctor ? [
                'name' => $doctor->user->name,
                'specialty' => $doctor->specialty->name,
                'license_number' => $doctor->license_number,
            ] : null,
            'issued_by' => $issuedBy,
            'patient' => [
                'initials' => collect(preg_split('/\s+/', trim($patient->full_name)) ?: [])
                    ->filter()
                    ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)).'.')
                    ->implode(' '),
                'document' => $patient->document_number
                    ? trim(($patient->document_type?->value ?? '').' ****'.substr($patient->document_number, -4))
                    : null,
            ],
            'facts' => $facts,
        ];
    }
}
