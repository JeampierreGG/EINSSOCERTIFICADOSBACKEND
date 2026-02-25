<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pago</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .header { background-color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; color: #282975; border-bottom: 2px solid #282975; }
        .header img { max-height: 80px; }
        .content { padding: 30px 20px; color: #333333; line-height: 1.6; }
        .content h2 { color: #282975; font-size: 24px; margin-bottom: 20px; text-align: center; }
        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
        .details-table th, .details-table td { padding: 12px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        /* ── FOOTER ── */
        .footer {
            background-color: #f8f9ff;
            border-top: 1px solid #eaeef8;
            padding: 20px 32px;
            text-align: center;
        }
        .footer p { font-size: 12px; color: #999; margin-bottom: 4px; }
        .footer strong { color: #282975; }
        .btn { display: inline-block; background-color: #00B2A1; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        .alert { background-color: #f8f9fa; border-left: 4px solid #00B2A1; padding: 15px; margin: 20px 0; font-size: 14px; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @php
                $settings = \App\Models\SystemSetting::first();
                $logoSrc = 'https://einssoconsultores.com/logos/einsso-a.png'; // Fallback seguro online
                
                if ($settings && $settings->header_logo) {
                    try {
                        // Intentar obtener el path absoluto del disco (usualmente 'public' o el default)
                        // Filament suele guardar en el disco definido en su config, asumimos 'public' o 's3' según env.
                        // Si estamos en local/prod con filesystem 'public':
                         $disk = config('filesystems.default'); // o 'public'
                         if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($settings->header_logo)) {
                             // Obtener ruta física para embed
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
            <h2>¡Hemos recibido tu pago!</h2>
            <p>Hola <strong>{{ $payment->user ? $payment->user->name : $payment->payer_first_name }}</strong>,</p>
            <p>Gracias por realizar tu pago. Hemos registrado tu transacción correctamente y se encuentra en proceso de validación por nuestro equipo administrativo.</p>
            
            <div class="alert">
                <strong>Número de Transacción:</strong> {{ $payment->transaction_code }}<br>
                <strong>Fecha y Hora:</strong> {{ $payment->created_at->format('d/m/Y H:i:s') }}
            </div>

            <table class="details-table">
                <tr>
                    <th>Concepto</th>
                    <td>
                        @php
                            $items = is_string($payment->items) ? json_decode($payment->items, true) : $payment->items;
                        @endphp
                        {{ $items['title'] ?? 'Certificación' }}
                    </td>
                </tr>
                <tr>
                    <th>Curso/Programa</th>
                    <td>{{ $payment->course ? $payment->course->title : ($items['course_title'] ?? 'N/A') }}</td>
                </tr>
                <tr>
                    <th>Monto</th>
                    <td>S/ {{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Método de Pago</th>
                    <td>{{ $payment->paymentMethod ? $payment->paymentMethod->name : 'Transferencia' }}</td>
                </tr>
            </table>

            <p style="text-align: center;">
                Puedes revisar el estado de tu proceso de adquisición del certificado en la sección de Mis pagos en la plataforma.
            </p>
            
            <div style="text-align: center;">
                <a href="https://einssoconsultores.com/mis-pagos" class="btn">Ver Estado del Pago</a>
            </div>
        </div>
        {{-- ===== FOOTER ===== --}}
        <div class="footer">
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
            <p>&copy; {{ date('Y') }} <strong>EINSSO Consultores</strong>. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
