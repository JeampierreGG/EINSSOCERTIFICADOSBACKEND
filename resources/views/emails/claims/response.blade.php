<x-mail::message>
# Respuesta a su Reclamo

Estimado(a) **{{ $claim->nombres }} {{ $claim->apellido_paterno }} {{ $claim->apellido_materno }}**,

Por medio de la presente, hacemos de su conocimiento la respuesta a su **Hoja de Reclamación N° {{ $claim->ticket_code }}** registrada con fecha **{{ \Carbon\Carbon::parse($claim->created_at)->format('d/m/Y') }}**.

### Detalle de la Respuesta / Acciones Tomadas:

<div style="background-color: #f3f4f6; padding: 15px; border-left: 4px solid #00B2A1; margin-bottom: 20px;">
{{ $claim->respuesta_admin }}
</div>

Agradecemos su comunicación y reafirmamos nuestro compromiso de mejora continua en nuestros servicios.

Atentamente,<br>
**{{ config('app.name') }}**<br>
Área de Atención al Cliente
</x-mail::message>
