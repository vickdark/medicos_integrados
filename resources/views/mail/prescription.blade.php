<x-mail::message>
# Hola, {{ $patientName }}

{{ $doctorName }} ({{ $specialty }}) te envía la receta médica de tu consulta del {{ $date }}.

La encontrarás adjunta a este correo en formato PDF. Guárdala o imprímela para presentarla en la farmacia.

Si tienes dudas sobre las indicaciones, comunícate directamente con tu médico.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
