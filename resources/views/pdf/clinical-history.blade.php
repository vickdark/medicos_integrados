<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historia clínica · {{ $patient->full_name }}</title>
    <style>
        @page { margin: 30px 34px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        header { border-bottom: 2px solid {{ $brand }}; padding-bottom: 8px; margin-bottom: 12px; }
        .brand { color: {{ $brand }}; font-size: 11px; font-weight: bold; }
        h1 { font-size: 17px; margin: 4px 0 2px; }
        h2 { font-size: 11px; color: {{ $brand }}; text-transform: uppercase; letter-spacing: .5px; margin: 16px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        .meta { color: #6b7280; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data td { padding: 3px 6px 3px 0; vertical-align: top; }
        table.data td.label { color: #6b7280; width: 22%; }
        .consultation { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 10px; margin-bottom: 10px; page-break-inside: avoid; }
        .consultation-head { border-bottom: 1px solid #f3f4f6; padding-bottom: 5px; margin-bottom: 6px; }
        .date { font-weight: bold; font-size: 11px; }
        .doctor { color: #6b7280; }
        .field { margin: 0 0 5px; }
        .addendum { border-left: 3px solid #d97706; background: #fffbeb; padding: 4px 8px; margin: 6px 0; }
        .addendum-meta { color: #92400e; font-size: 8px; }
        .field strong { display: block; color: #374151; font-size: 9px; text-transform: uppercase; }
        .vitals { background: {{ $brandTint }}; border-radius: 3px; padding: 4px 6px; margin-bottom: 6px; color: {{ $brandDark }}; }
        table.meds { width: 100%; border-collapse: collapse; margin-top: 3px; }
        table.meds th { background: #f3f4f6; text-align: left; padding: 3px 5px; font-size: 9px; }
        table.meds td { padding: 3px 5px; border-bottom: 1px solid #f3f4f6; }
        .empty { text-align: center; color: #6b7280; padding: 18px; border: 1px dashed #d1d5db; border-radius: 4px; }
        .alert { color: #b91c1c; }
        footer { position: fixed; bottom: -22px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    <header>
        <div class="brand">{{ config('app.name') }}</div>
        <h1>Historia clínica</h1>
        <div class="meta">{{ $period }} · Generada el {{ $generatedAt->translatedFormat('d \d\e F \d\e Y, g:i A') }} por {{ $generatedBy }}</div>
    </header>

    <h2>Datos del paciente</h2>
    <table class="data">
        <tr>
            <td class="label">Nombre</td><td><strong>{{ $patient->full_name }}</strong></td>
            <td class="label">Documento</td><td>{{ $patient->document_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de nacimiento</td>
            <td>{{ $patient->birth_date ? $patient->birth_date->format('d/m/Y').' ('.$patient->birth_date->age.' años)' : '—' }}</td>
            <td class="label">Sexo</td><td>{{ $patient->gender?->label() ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Grupo sanguíneo</td><td>{{ $patient->blood_type ?: '—' }}</td>
            <td class="label">Teléfono</td><td>{{ $patient->phone ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Correo</td><td>{{ $patient->email ?: '—' }}</td>
            <td class="label">Dirección</td><td>{{ $patient->address ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Contacto de emergencia</td>
            <td colspan="3">{{ $patient->emergency_contact_name ?: '—' }}{{ $patient->emergency_contact_phone ? ' · '.$patient->emergency_contact_phone : '' }}</td>
        </tr>
    </table>

    <h2>Antecedentes médicos</h2>
    <p class="field"><strong class="alert">Alergias</strong>{{ $patient->allergies ?: 'Sin registro' }}</p>
    <p class="field"><strong>Enfermedades crónicas</strong>{{ $patient->chronic_conditions ?: 'Sin registro' }}</p>
    <p class="field"><strong>Antecedentes (quirúrgicos, familiares, etc.)</strong>{{ $patient->medical_background ?: 'Sin registro' }}</p>

    <h2>Consultas ({{ $consultations->count() }})</h2>

    @forelse ($consultations as $consultation)
        <div class="consultation">
            <div class="consultation-head">
                <span class="date">{{ $consultation->consulted_at->translatedFormat('d \d\e F \d\e Y, g:i A') }}</span>
                <span class="doctor"> · {{ $consultation->doctor->user->name }} ({{ $consultation->doctor->specialty->name }})</span>
            </div>

            @php
                $vitals = array_filter([
                    $consultation->weight_kg ? 'Peso: '.$consultation->weight_kg.' kg' : null,
                    $consultation->height_cm ? 'Talla: '.$consultation->height_cm.' cm' : null,
                    $consultation->blood_pressure ? 'Presión: '.$consultation->blood_pressure : null,
                    $consultation->temperature_c ? 'Temp.: '.$consultation->temperature_c.' °C' : null,
                    $consultation->heart_rate ? 'FC: '.$consultation->heart_rate.' lpm' : null,
                ]);
            @endphp
            @if ($vitals)
                <div class="vitals">{{ implode('  ·  ', $vitals) }}</div>
            @endif

            <p class="field"><strong>Motivo</strong>{{ $consultation->reason }}</p>
            @if ($consultation->symptoms)
                <p class="field"><strong>Síntomas</strong>{{ $consultation->symptoms }}</p>
            @endif
            @if ($consultation->primaryDiagnosis)
                <p class="field"><strong>CIE-10</strong>{{ $consultation->primaryDiagnosis->code }} · {{ $consultation->primaryDiagnosis->description }}@if ($consultation->diagnosis_type) ({{ $consultation->diagnosis_type->label() }})@endif</p>
                @if ($consultation->relatedDiagnoses->isNotEmpty())
                    <p class="field"><strong>Relacionados</strong>{{ $consultation->relatedDiagnoses->map(fn ($diagnosis) => $diagnosis->code.' · '.$diagnosis->description)->implode('; ') }}</p>
                @endif
            @endif
            <p class="field"><strong>Diagnóstico</strong>{{ $consultation->diagnosis }}</p>
            @if ($consultation->treatment)
                <p class="field"><strong>Tratamiento</strong>{{ $consultation->treatment }}</p>
            @endif
            @if ($includeNotes && $consultation->notes)
                <p class="field"><strong>Notas</strong>{{ $consultation->notes }}</p>
            @endif

            @foreach ($consultation->addenda as $addendum)
                <div class="addendum">
                    <div class="addendum-meta">
                        NOTA ACLARATORIA · {{ $addendum->section->label() }} · {{ $addendum->created_at->format('d/m/Y g:i A') }} · {{ $addendum->author_name }}
                    </div>
                    <div><strong>Motivo:</strong> {{ $addendum->reason }}</div>
                    <div>{{ $addendum->content }}</div>
                </div>
            @endforeach

            @if ($consultation->prescriptions->isNotEmpty())
                <strong style="font-size: 9px; color: #374151; text-transform: uppercase;">Receta</strong>
                <table class="meds">
                    <thead>
                        <tr><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Duración</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($consultation->prescriptions as $prescription)
                            <tr>
                                <td>{{ $prescription->medication }}@if ($prescription->instructions)<br><span class="meta">{{ $prescription->instructions }}</span>@endif</td>
                                <td>{{ $prescription->dosage }}</td>
                                <td>{{ $prescription->frequency }}</td>
                                <td>{{ $prescription->duration ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @empty
        <div class="empty">No hay consultas registradas en este período.</div>
    @endforelse

    <footer>Documento confidencial · {{ config('app.name') }} · {{ $patient->full_name }}</footer>
</body>
</html>
