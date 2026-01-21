<x-mail::message>
# Recuperación de Contraseña

Hola,

Has solicitado restablecer tu contraseña para acceder a la plataforma de **{{ config('app.name') }}**.

Usa el siguiente código de verificación para completar el proceso:

<div style="text-align: center; margin: 30px 0;">
    <span style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #282975; padding: 10px 20px; border: 2px dashed #282975; border-radius: 8px; background-color: #f6f6fb;">
        {{ $code }}
    </span>
</div>

Este código expirará en 15 minutos. Si no has solicitado este cambio, puedes ignorar este correo de forma segura.

Atentamente,<br>
**{{ config('app.name') }}**
</x-mail::message>
