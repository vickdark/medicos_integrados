<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $number }}</title>
    <style>
        @page { margin: 34px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .top { width: 100%; border-bottom: 3px solid #0d9488; padding-bottom: 12px; margin-bottom: 20px; }
        .top td { vertical-align: top; }
        .brand { color: #0d9488; font-size: 18px; font-weight: bold; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 20px; margin: 0; color: #111827; }
        .doc-title .number { font-size: 13px; color: #0d9488; font-weight: bold; }
        .paid { display: inline-block; border: 2px solid #059669; color: #059669; font-weight: bold; padding: 2px 10px; border-radius: 4px; margin-top: 6px; letter-spacing: 1px; }
        h2 { font-size: 10px; text-transform: uppercase; color: #6b7280; letter-spacing: .6px; margin: 0 0 4px; }
        .boxes { width: 100%; margin-bottom: 20px; }
        .boxes td { vertical-align: top; width: 50%; padding-right: 14px; }
        .name { font-size: 13px; font-weight: bold; }
        .muted { color: #6b7280; }
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.lines th { background: #0d9488; color: #fff; text-align: left; padding: 7px 8px; font-size: 10px; }
        table.lines td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .right { text-align: right; white-space: nowrap; }
        table.total { width: 45%; margin-left: 55%; border-collapse: collapse; }
        table.total td { padding: 6px 8px; }
        table.total tr.grand td { border-top: 2px solid #0d9488; font-size: 14px; font-weight: bold; }
        .notes { margin-top: 22px; padding: 8px 10px; background: #f9fafb; border-radius: 4px; }
        footer { position: fixed; bottom: -14px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <div class="brand">{{ config('app.name') }}</div>
                <div class="muted">Centro médico integral</div>
            </td>
            <td class="doc-title">
                <h1>FACTURA</h1>
                <div class="number">{{ $number }}</div>
                <div class="paid">PAGADO</div>
            </td>
        </tr>
    </table>

    <table class="boxes">
        <tr>
            <td>
                <h2>Facturado a</h2>
                <div class="name">{{ $payment->patient->full_name }}</div>
                @if ($payment->patient->document_number)
                    <div>Documento: {{ $payment->patient->document_number }}</div>
                @endif
                @if ($payment->patient->email)
                    <div class="muted">{{ $payment->patient->email }}</div>
                @endif
                @if ($payment->patient->phone)
                    <div class="muted">{{ $payment->patient->phone }}</div>
                @endif
            </td>
            <td>
                <h2>Detalle del pago</h2>
                <div>Fecha de pago: <strong>{{ $payment->paid_at?->translatedFormat('d \d\e F \d\e Y') }}</strong></div>
                <div>Método: {{ $payment->method->label() }}</div>
                @if ($payment->reference)
                    <div>Referencia: {{ $payment->reference }}</div>
                @endif
                <div class="muted">Emitida el {{ $generatedAt->translatedFormat('d/m/Y g:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="right">Importe</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $payment->concept }}</strong>
                    @if ($payment->appointment)
                        <div class="muted">
                            Cita del {{ $payment->appointment->scheduled_at->translatedFormat('d \d\e F \d\e Y, g:i A') }}
                            · {{ $payment->appointment->doctor->user->name }}
                            ({{ $payment->appointment->doctor->specialty->name }})
                        </div>
                    @endif
                </td>
                <td class="right">{{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="total">
        <tr class="grand">
            <td>Total pagado</td>
            <td class="right">{{ number_format((float) $payment->amount, 2) }}</td>
        </tr>
    </table>

    @if ($payment->notes)
        <div class="notes"><h2>Notas</h2>{{ $payment->notes }}</div>
    @endif

    <footer>{{ config('app.name') }} · Factura {{ $number }} · Gracias por su preferencia</footer>
</body>
</html>
