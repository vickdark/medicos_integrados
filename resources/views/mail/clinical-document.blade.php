<x-mail::message>
# Hola, {{ $patientName }}

{{ $doctorName }} ({{ $specialty }}) te envía el documento **{{ $documentName }}** (N.º {{ $number }}) de tu consulta del {{ $date }}.

Lo encontrarás adjunto a este correo en formato PDF. Guárdalo o imprímelo para presentarlo donde corresponda.

Si tienes dudas, comunícate directamente con tu médico.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
