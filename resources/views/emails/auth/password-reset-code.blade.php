<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña — EINSSO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background-color: #f0f2f5; color: #333333; line-height: 1.6; }
        .wrapper { padding: 30px 15px; }
        .container { max-width: 620px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.10); }
        .header { background-color: #ffffff; padding: 24px 32px; text-align: center; border-bottom: 3px solid #282975; }
        .header img { max-height: 70px; width: auto; }
        .content { padding: 32px; text-align: center; }
        .code-box { margin: 40px auto; padding: 20px; border: 2px dashed #282975; border-radius: 12px; background-color: #f6f6fb; display: inline-block; }
        .code { font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #282975; }
        .footer { background-color: #f8f9ff; border-top: 1px solid #eaeef8; padding: 20px 32px; text-align: center; }
        .footer p { font-size: 12px; color: #999; margin-bottom: 4px; }
        .footer strong { color: #282975; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            @php
                $settings = \App\Models\SystemSetting::first();
                $logoSrc  = 'https://einssoconsultores.com/logos/einsso-a.png';
                if ($settings && $settings->header_logo) {
                    try {
                        $logoPath = ltrim(json_decode($settings->header_logo, true)[0] ?? $settings->header_logo, '/');
                        $logoData = \Illuminate\Support\Facades\Storage::disk('s3')->get($logoPath);
                        $logoSrc  = $message->embedData($logoData, 'logo.png', \Illuminate\Support\Facades\Storage::disk('s3')->mimeType($logoPath));
                    } catch (\Throwable $e) {}
                }
            @endphp
            <img src="{{ $logoSrc }}" alt="EINSSO" />
        </div>
        <div class="content">
            <h2 style="color: #282975; margin-bottom: 20px;">Recuperación de Contraseña</h2>
            <p>Hola,</p>
            <p>Has solicitado restablecer tu contraseña para acceder a la plataforma de <strong>{{ config('app.name') }}</strong>.</p>
            
            <p style="margin-top: 20px;">Usa el siguiente código de verificación para completar el proceso:</p>
            <div class="code-box">
                <span class="code">{{ $code }}</span>
            </div>
            
            <p style="font-size: 14px; color: #666; margin-top: 10px;">Este código expirará en 15 minutos. Si no has solicitado este cambio, puedes ignorar este correo de forma segura.</p>
            
            <p style="margin-top: 30px;">
                Atentamente,<br>
                <strong>{{ config('app.name') }}</strong>
            </p>
        </div>
        <div class="footer">
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
            <p>&copy; {{ date('Y') }} <strong>EINSSO Consultores</strong>. Todos los derechos reservados.</p>
        </div>
    </div>
</div>
</body>
</html>
