@php
    use App\Enums\CarePriority;
    use App\Enums\ClinicalDocumentType;
    use App\Enums\SickLeaveOrigin;

    $patient = $consultation->patient;
    $doctor = $consultation->doctor;
    $data = $document->data;
    $documentId = $patient->document_number
        ? trim(($patient->document_type?->value ?? '').' '.$patient->document_number)
        : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document->type->label() }} {{ $document->number }} · {{ $patient->full_name }}</title>
    <style>
        @page { margin: 34px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .top { width: 100%; border-bottom: 3px solid {{ $brand }}; padding-bottom: 12px; margin-bottom: 18px; }
        .top td { vertical-align: top; }
        .brand { color: {{ $brand }}; font-size: 18px; font-weight: bold; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 18px; margin: 0; color: #111827; text-transform: uppercase; }
        .muted { color: #6b7280; }
        h2 { font-size: 10px; text-transform: uppercase; color: #6b7280; letter-spacing: .6px; margin: 0 0 4px; }
        .boxes { width: 100%; margin-bottom: 16px; }
        .boxes td { vertical-align: top; width: 50%; padding-right: 14px; }
        .name { font-size: 13px; font-weight: bold; }
        .highlight { background: {{ $brandTint }}; border-left: 3px solid {{ $brand }}; padding: 6px 10px; margin-bottom: 14px; }
        .section { margin-bottom: 14px; page-break-inside: avoid; }
        .text { white-space: pre-line; }
        .facts { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .facts td { border: 1px solid #e5e7eb; padding: 6px 8px; }
        .facts td.label { width: 38%; background: #f9fafb; color: #374151; font-weight: bold; }
        ol.items { margin: 0; padding-left: 18px; }
        ol.items li { padding: 3px 0; border-bottom: 1px solid #f3f4f6; }
        .signatures { width: 100%; margin-top: 60px; page-break-inside: avoid; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; padding: 0 12px; }
        .signatures .line { border-top: 1px solid #111827; margin: 0 auto 4px; width: 220px; }
        .invalid { border: 2px solid #b91c1c; background: #fef2f2; color: #7f1d1d; border-radius: 4px; padding: 8px 12px; margin-bottom: 16px; }
        .invalid strong { display: block; font-size: 12px; margin-bottom: 2px; }
        .watermark { position: fixed; top: 38%; left: 8%; width: 84%; text-align: center; font-size: 64px; font-weight: bold; color: #b91c1c; opacity: .12; transform: rotate(-30deg); }
        footer { position: fixed; bottom: -14px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    @unless ($isOfficial)
        <div class="watermark">SIN VALIDEZ</div>
    @endunless

    <table class="top">
        <tr>
            <td>
                <div class="brand">{{ config('app.name') }}</div>
                <div class="muted">Centro médico integral</div>
            </td>
            <td class="doc-title">
                <h1>{{ $document->type->label() }}</h1>
                <div><strong>N.º {{ $document->number }}</strong></div>
                <div class="muted">{{ $document->created_at->translatedFormat('d \d\e F \d\e Y, g:i A') }}</div>
            </td>
        </tr>
    </table>

    @unless ($isOfficial)
        <div class="invalid">
            <strong>Este documento no es válido</strong>
            Es solo una copia de consulta. El documento válido es el que emite y firma el médico responsable:
            {{ $doctor->user->name }}.
        </div>
    @endunless

    <table class="boxes">
        <tr>
            <td>
                <h2>Paciente</h2>
                <div class="name">{{ $patient->full_name }}</div>
                @if ($documentId)
                    <div>Documento: {{ $documentId }}</div>
                @endif
                <div class="muted">
                    @if ($patient->birth_date){{ $patient->birth_date->age }} años @endif
                    @if ($patient->gender) · {{ $patient->gender->label() }} @endif
                </div>
                @if ($patient->insurer)
                    <div class="muted">{{ $patient->insurer->name }}@if ($patient->affiliation_type) · {{ $patient->affiliation_type->label() }}@endif</div>
                @endif
            </td>
            <td>
                <h2>Médico</h2>
                <div class="name">{{ $doctor->user->name }}</div>
                <div>{{ $doctor->specialty->name }}</div>
                <div class="muted">Registro médico: {{ $doctor->license_number }}</div>
                <div class="muted">Consulta del {{ $consultation->consulted_at->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    @if ($consultation->primaryDiagnosis && $document->type !== ClinicalDocumentType::InformedConsent)
        <div class="highlight">
            <h2>Diagnóstico</h2>
            <strong>{{ $consultation->primaryDiagnosis->code }}</strong> · {{ $consultation->primaryDiagnosis->description }}
            @foreach ($consultation->relatedDiagnoses as $related)
                <br><span class="muted">Relacionado: {{ $related->code }} · {{ $related->description }}</span>
            @endforeach
        </div>
    @endif


    @if ($document->type === ClinicalDocumentType::InformedConsent)
            <div class="section">
                <p>
                    Yo, <strong>{{ $patient->full_name }}</strong>@if ($documentId), identificado(a) con {{ $documentId }}@endif,
                    declaro que <strong>{{ $doctor->user->name }}</strong>, médico(a) tratante, me explicó en lenguaje claro
                    el procedimiento que se describe a continuación, sus riesgos, beneficios y alternativas; que pude hacer
                    preguntas y que fueron resueltas. Entiendo que puedo revocar este consentimiento en cualquier momento
                    antes del procedimiento.
                </p>
            </div>
            <div class="highlight">
                <h2>Procedimiento</h2>
                <strong>{{ $data['procedure'] }}</strong>
            </div>
            <div class="section"><h2>En qué consiste</h2><div class="text">{{ $data['description'] }}</div></div>
            <div class="section"><h2>Riesgos</h2><div class="text">{{ $data['risks'] }}</div></div>
            @if (! empty($data['benefits']))
                <div class="section"><h2>Beneficios esperados</h2><div class="text">{{ $data['benefits'] }}</div></div>
            @endif
            @if (! empty($data['alternatives']))
                <div class="section"><h2>Alternativas</h2><div class="text">{{ $data['alternatives'] }}</div></div>
            @endif
            <div class="section">
                <p>En constancia, autorizo la realización del procedimiento y firmo este documento.</p>
            </div>
            <table class="signatures">
                <tr>
                    <td>
                        <div class="line"></div>
                        <strong>{{ $patient->full_name }}</strong><br>
                        <span class="muted">Paciente o representante legal @if ($documentId) · {{ $documentId }} @endif</span>
                    </td>
                    <td>
                        <div class="line"></div>
                        <strong>{{ $doctor->user->name }}</strong><br>
                        <span class="muted">{{ $doctor->specialty->name }} · Registro médico {{ $doctor->license_number }}</span>
                    </td>
                </tr>
            </table>

    @elseif ($document->type === ClinicalDocumentType::SickLeave)
            <table class="facts">
                <tr><td class="label">Tipo</td><td>{{ ! empty($data['is_extension']) ? 'Prórroga' : 'Incapacidad inicial' }}</td></tr>
                <tr><td class="label">Origen</td><td>{{ SickLeaveOrigin::from($data['origin'])->label() }}</td></tr>
                <tr><td class="label">Fecha de inicio</td><td>{{ \Carbon\CarbonImmutable::parse($data['start_date'])->translatedFormat('d \d\e F \d\e Y') }}</td></tr>
                <tr><td class="label">Días de incapacidad</td><td><strong>{{ $data['days'] }}</strong> {{ (int) $data['days'] === 1 ? 'día' : 'días' }} calendario</td></tr>
                <tr><td class="label">Fecha de finalización</td><td>{{ $document->sickLeaveEndDate()->translatedFormat('d \d\e F \d\e Y') }}</td></tr>
            </table>
            @if (! empty($data['notes']))
                <div class="section"><h2>Observaciones</h2><div class="text">{{ $data['notes'] }}</div></div>
            @endif

    @elseif ($document->type === ClinicalDocumentType::Referral)
            <table class="facts">
                <tr><td class="label">Remitido a</td><td><strong>{{ $data['specialty'] }}</strong></td></tr>
                <tr><td class="label">Prioridad</td><td>{{ CarePriority::from($data['priority'])->label() }}</td></tr>
            </table>
            <div class="section"><h2>Motivo de la remisión</h2><div class="text">{{ $data['reason'] }}</div></div>
            @if (! empty($data['clinical_summary']))
                <div class="section"><h2>Resumen de la historia clínica</h2><div class="text">{{ $data['clinical_summary'] }}</div></div>
            @endif

    @elseif ($document->type === ClinicalDocumentType::ExamOrder)
            <table class="facts">
                <tr><td class="label">Prioridad</td><td>{{ CarePriority::from($data['priority'])->label() }}</td></tr>
            </table>
            <div class="section">
                <h2>Exámenes solicitados</h2>
                <ol class="items">
                    @foreach ($data['exams'] as $exam)
                        <li>{{ $exam }}</li>
                    @endforeach
                </ol>
            </div>
            @if (! empty($data['indications']))
                <div class="section"><h2>Indicaciones</h2><div class="text">{{ $data['indications'] }}</div></div>
            @endif
    @endif

    @if ($document->type !== ClinicalDocumentType::InformedConsent)
        <table class="signatures">
            <tr>
                <td></td>
                <td>
                    <div class="line"></div>
                    <strong>{{ $doctor->user->name }}</strong><br>
                    <span class="muted">{{ $doctor->specialty->name }} · Registro médico {{ $doctor->license_number }}</span>
                </td>
            </tr>
        </table>
    @endif

    <footer>
        {{ $document->number }} ·
        @if ($isOfficial)
            Emitido el {{ $document->created_at->translatedFormat('d/m/Y g:i A') }}
        @else
            Copia de consulta sin validez · {{ $generatedAt->translatedFormat('d/m/Y g:i A') }}
        @endif
        · {{ config('app.name') }}
    </footer>
</body>
</html>
