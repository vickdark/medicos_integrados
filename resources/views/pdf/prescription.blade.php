@php
    $signature = $consultation->doctor->signatureDataUri();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Receta médica · {{ $consultation->patient->full_name }}</title>
    <style>
        @page { margin: 34px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .top { width: 100%; border-bottom: 3px solid {{ $brand }}; padding-bottom: 12px; margin-bottom: 18px; }
        .top td { vertical-align: top; }
        .brand { color: {{ $brand }}; font-size: 18px; font-weight: bold; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 20px; margin: 0; color: #111827; }
        .muted { color: #6b7280; }
        h2 { font-size: 10px; text-transform: uppercase; color: #6b7280; letter-spacing: .6px; margin: 0 0 4px; }
        .boxes { width: 100%; margin-bottom: 16px; }
        .boxes td { vertical-align: top; width: 50%; padding-right: 14px; }
        .name { font-size: 13px; font-weight: bold; }
        .diagnosis { background: {{ $brandTint }}; border-left: 3px solid {{ $brand }}; padding: 6px 10px; margin-bottom: 16px; }
        .rx { font-size: 26px; color: {{ $brand }}; font-weight: bold; margin: 0 0 6px; }
        .med { border-bottom: 1px solid #e5e7eb; padding: 8px 0; page-break-inside: avoid; }
        .med-name { font-size: 13px; font-weight: bold; }
        .med-detail { margin-top: 2px; }
        .med-detail span { display: inline-block; margin-right: 14px; }
        .instructions { margin-top: 3px; color: #374151; font-style: italic; }
        .treatment { margin-top: 16px; }
        .signature { margin-top: 70px; text-align: center; page-break-inside: avoid; }
        .signature .line { width: 240px; border-top: 1px solid #111827; margin: 0 auto 4px; }
        .invalid { border: 2px solid #b91c1c; background: #fef2f2; color: #7f1d1d; border-radius: 4px; padding: 8px 12px; margin-bottom: 16px; }
        .invalid strong { display: block; font-size: 12px; margin-bottom: 2px; }
        .watermark { position: fixed; top: 38%; left: 8%; width: 84%; text-align: center; font-size: 64px; font-weight: bold; color: #b91c1c; opacity: .12; transform: rotate(-30deg); }
        .unsigned { border: 2px solid #b91c1c; background: #fef2f2; color: #7f1d1d; border-radius: 4px; padding: 8px 12px; margin-bottom: 16px; }
        .unsigned strong { display: block; font-size: 12px; margin-bottom: 2px; }
        .signature-image { height: 60px; max-width: 220px; margin: 0 auto -6px; display: block; }
        .verify { width: 100%; margin-top: 28px; border: 1px solid #e5e7eb; border-radius: 6px; page-break-inside: avoid; }
        .verify td { vertical-align: middle; padding: 8px 10px; }
        .verify .qr { width: 96px; }
        .verify .qr img { width: 88px; height: 88px; }
        .verify .code { font-family: DejaVu Sans Mono, monospace; font-size: 13px; font-weight: bold; letter-spacing: 1px; }
        footer { position: fixed; bottom: -14px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    @unless ($isOfficial)
        <div class="watermark">SIN VALIDEZ</div>
    @endunless
    @if ($isOfficial && ! $signature)
        <div class="watermark">SIN FIRMA</div>
    @endif
    <table class="top">
        <tr>
            <td>
                <div class="brand">{{ config('app.name') }}</div>
                <div class="muted">Centro médico integral</div>
            </td>
            <td class="doc-title">
                <h1>RECETA MÉDICA</h1>
                <div><strong>N.º RX-{{ str_pad((string) $consultation->id, 6, '0', STR_PAD_LEFT) }}</strong></div>
                <div class="muted">{{ $consultation->consulted_at->translatedFormat('d \d\e F \d\e Y') }}</div>
            </td>
        </tr>
    </table>

    @unless ($isOfficial)
        <div class="invalid">
            <strong>Este documento no es válido</strong>
            Es solo una copia de consulta para el administrador del sistema y no puede usarse como receta.
            Para más información, consulte con el médico responsable: {{ $consultation->doctor->user->name }}.
        </div>
    @endunless

    @if ($isOfficial && ! $signature)
        <div class="unsigned">
            <strong>Documento no válido por falta de firma del médico</strong>
            {{ $consultation->doctor->user->name }} no ha registrado su firma en el sistema. El documento será válido cuando la registre en Configuración → Perfil profesional.
        </div>
    @endif

    <table class="boxes">
        <tr>
            <td>
                <h2>Paciente</h2>
                <div class="name">{{ $consultation->patient->full_name }}</div>
                @if ($consultation->patient->document_number)
                    <div>Documento: {{ $consultation->patient->document_number }}</div>
                @endif
                @if ($consultation->patient->birth_date)
                    <div class="muted">{{ $consultation->patient->birth_date->age }} años</div>
                @endif
            </td>
            <td>
                <h2>Médico</h2>
                <div class="name">{{ $consultation->doctor->user->name }}</div>
                <div>{{ $consultation->doctor->specialty->name }}</div>
                <div class="muted">Registro médico: {{ $consultation->doctor->license_number }}</div>
            </td>
        </tr>
    </table>

    @if ($consultation->diagnosis)
        <div class="diagnosis">
            <h2>Diagnóstico</h2>
            @if ($consultation->primaryDiagnosis)
                <strong>{{ $consultation->primaryDiagnosis->code }}</strong> · {{ $consultation->primaryDiagnosis->description }}<br>
            @endif
            {{ $consultation->diagnosis }}
        </div>
    @endif

    <div class="rx">Rp.</div>

    @foreach ($consultation->prescriptions as $prescription)
        <div class="med">
            <div class="med-name">{{ $loop->iteration }}. {{ $prescription->medication }}</div>
            <div class="med-detail">
                <span><strong>Dosis:</strong> {{ $prescription->dosage }}</span>
                <span><strong>Frecuencia:</strong> {{ $prescription->frequency }}</span>
                @if ($prescription->duration)
                    <span><strong>Duración:</strong> {{ $prescription->duration }}</span>
                @endif
            </div>
            @if ($prescription->instructions)
                <div class="instructions">{{ $prescription->instructions }}</div>
            @endif
        </div>
    @endforeach

    @if ($consultation->treatment)
        <div class="treatment">
            <h2>Indicaciones generales</h2>
            {{ $consultation->treatment }}
        </div>
    @endif

    <div class="signature">
        @if ($signature)
            <img src="{{ $signature }}" alt="Firma" class="signature-image">
        @endif
        <div class="line"></div>
        <strong>{{ $consultation->doctor->user->name }}</strong><br>
        <span class="muted">{{ $consultation->doctor->specialty->name }} · Registro médico {{ $consultation->doctor->license_number }}</span>
    </div>

    @if ($isOfficial && ! empty($verification))
        <table class="verify">
            <tr>
                <td class="qr"><img src="{{ $verification['qr'] }}" alt="Código QR de verificación"></td>
                <td>
                    <h2>Verificación del documento</h2>
                    Escanee el código QR o ingrese a <strong>{{ $verification['base_url'] }}</strong> con el código
                    <div class="code">{{ $verification['code'] }}</div>
                    <span class="muted">para comprobar que este documento fue emitido por {{ config('app.name') }} y no ha sido alterado.</span>
                </td>
            </tr>
        </table>
    @endif

    <footer>
        @if ($isOfficial)
            Emitida el {{ $generatedAt->translatedFormat('d/m/Y g:i A') }} · {{ config('app.name') }}
        @else
            Copia de consulta sin validez · {{ $generatedAt->translatedFormat('d/m/Y g:i A') }} · {{ config('app.name') }}
        @endif
    </footer>
</body>
</html>
