<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
        header { border-bottom: 2px solid {{ $brand }}; padding-bottom: 8px; margin-bottom: 12px; }
        .brand { color: {{ $brand }}; font-size: 11px; font-weight: bold; }
        h1 { font-size: 16px; margin: 4px 0 2px; }
        .meta { color: #6b7280; }
        .filters { margin: 0 0 10px; color: #374151; }
        table { width: 100%; border-collapse: collapse; }
        th { background: {{ $brand }}; color: #fff; text-align: left; padding: 5px 6px; font-weight: bold; }
        td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tr:nth-child(even) td { background: #f9fafb; }
        .number { text-align: right; white-space: nowrap; }
        .empty { text-align: center; padding: 20px; color: #6b7280; }
        .notice { margin-top: 10px; color: #b45309; }
        footer { position: fixed; bottom: -16px; left: 0; right: 0; text-align: right; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    <header>
        <div class="brand">{{ config('app.name') }}</div>
        <h1>{{ $title }}</h1>
        <div class="meta">
            Generado el {{ $generatedAt->translatedFormat('d \d\e F \d\e Y, g:i A') }}
            @if ($generatedBy)
                por {{ $generatedBy }}
            @endif
            · {{ count($rows) }} {{ count($rows) === 1 ? 'registro' : 'registros' }}
        </div>
    </header>

    @if ($filters !== [])
        <p class="filters"><strong>Filtros:</strong> {{ implode(' · ', $filters) }}</p>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th @class(['number' => in_array($loop->index, $moneyColumns, true)])>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        @if (in_array($loop->index, $moneyColumns, true))
                            <td class="number">{{ number_format((float) $value, 2) }}</td>
                        @else
                            <td>{{ $value ?? '—' }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ count($headings) }}">No hay registros para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($isTruncated)
        <p class="notice">
            El PDF muestra solo los primeros {{ number_format($rowLimit) }} registros. Exporta a Excel para obtener el listado completo.
        </p>
    @endif

    <footer>Documento confidencial · {{ config('app.name') }}</footer>
</body>
</html>
