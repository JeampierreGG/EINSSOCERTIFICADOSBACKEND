<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrega de Certificado</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f7f9fc; margin: 0; padding: 0; color: #333; }
        .wrapper { width: 100%; padding: 40px 0; }
        .container { max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; }
        
        .header { background-color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; color: #282975; border-bottom: 2px solid #282975; }
        .header img { max-height: 80px; }
        
        .content { padding: 40px 30px; }
        .greeting { font-size: 20px; font-weight: bold; color: #282975; margin-bottom: 20px; }
        .message { font-size: 16px; line-height: 1.6; color: #555; margin-bottom: 30px; }
        
        .certificate-box { background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 25px; text-align: center; margin-bottom: 30px; }
        .cert-icon { font-size: 40px; margin-bottom: 10px; }
        .cert-title { font-weight: bold; color: #15803d; font-size: 18px; margin-bottom: 5px; }
        .cert-code { font-family: monospace; color: #555; font-size: 14px; background: #fff; padding: 5px 10px; border-radius: 4px; display: inline-block; border: 1px solid #ddd; }
        
        .btn { display: inline-block; background-color: #00B2A1; color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 6px; font-weight: 600; font-size: 16px; transition: background-color 0.3s; box-shadow: 0 4px 6px rgba(0, 178, 161, 0.2); }
        .btn:hover { background-color: #009081; }
        
        .footer { background-color: #f8f9fa; padding: 25px; text-align: center; font-size: 13px; color: #888; border-top: 1px solid #eaeaea; }
        .social-links { margin-top: 15px; }
        .footer a { color: #282975; text-decoration: none; margin: 0 8px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                @php
                    $settings = \App\Models\SystemSetting::first();
                    $logoSrc = 'https://einssoconsultores.com/logos/einsso-a.png'; 
                    
                    if ($settings && $settings->header_logo) {
                        try {
                             $disk = config('filesystems.default');
                             if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($settings->header_logo)) {
                                 if ($disk === 's3') {
                                     $logoData = \Illuminate\Support\Facades\Storage::disk($disk)->get($settings->header_logo);
                                     $mime = \Illuminate\Support\Facades\Storage::disk($disk)->mimeType($settings->header_logo);
                                     $logoSrc = $message->embedData($logoData, 'logo.png', $mime);
                                 } else {
                                    $localPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($settings->header_logo);
                                    $logoSrc = $message->embed($localPath);
                                 }
                             }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Error embedding logo: ' . $e->getMessage());
                        }
                    }
                @endphp
                <img src="{{ $logoSrc }}" alt="EINSSO Consultores" style="max-height: 80px; width: auto;" />
            </div>
            <div class="content">
                <div class="greeting">Estimad@ {{ $certificate->user->name ?? $certificate->nombres }},</div>
                
                <div class="message">
                    <p>Es un placer saludarte. Nos complace informarte que tu proceso de certificación ha concluido exitosamente y tu certificado ha sido generado.</p>
                    <p>Adjunto a este correo encontrarás tu certificado digital. Este documento acredita tus competencias y el esfuerzo dedicado durante el programa.</p>
                </div>
                
                <div class="certificate-box">
                    <div class="cert-icon">🎓</div>
                    
                    @if($certificate->items->count() > 0)
                        {{-- MEGAPACK LOGIC --}}
                        <div class="cert-title" style="margin-bottom: 15px;">{{ $certificate->title }}</div>
                        <div style="text-align: left; background: #fff; padding: 15px; border-radius: 6px; border: 1px solid #ddd;">
                            <p style="margin: 0 0 10px 0; font-weight: bold; color: #555; text-align: center;">Códigos de Certificación:</p>
                            <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #444;">
                                @foreach($certificate->items as $item)
                                    <li style="margin-bottom: 8px;">
                                        <strong>{{ $item->title }}</strong>
                                        <br>
                                        <span style="font-family: monospace; color: #282975; background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">{{ $item->code }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        {{-- SOLO CERTIFICATE LOGIC --}}
                        <div class="cert-title">{{ $certificate->title }}</div>
                        <div class="cert-code">Código: {{ $certificate->code }}</div>
                    @endif

                    <br>
                    <p style="font-size: 14px; color: #666; margin-top: 15px; margin-bottom: 0;">Puedes descargar los archivos adjuntos o validarlos en nuestra plataforma.</p>
                </div>

                <div style="text-align: center;">
                    <a href="https://einssoconsultores.com/mis-certificados" class="btn">Ver en Mis Certificados</a>
                </div>
                
                <p style="margin-top: 30px; text-align: center; font-size: 14px; color: #777;">
                    ¡Felicitaciones por este logro profesional!
                </p>
            </div>
            <div class="footer">
                <p>EINSSO Consultores - Excelencia en Capacitación</p>
                <p>Si tienes alguna consulta, no dudes en contactarnos.</p>
                <div class="social-links">
                    <a href="#">Sitio Web</a> • <a href="#">Facebook</a> • <a href="#">LinkedIn</a>
                </div>
                <p style="margin-top: 15px; font-size: 11px;">Este correo contiene documentos importantes. Por favor guárdalo para futuras referencias.</p>
            </div>
        </div>
    </div>
</body>
</html>
