<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Matrícula — EINSSO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5;
            color: #333333;
            line-height: 1.6;
        }
        .wrapper { padding: 30px 15px; }
        .container {
            max-width: 620px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
        }

        /* ── HEADER ── */
        .header {
            background-color: #ffffff;
            padding: 24px 32px;
            text-align: center;
            border-bottom: 3px solid #282975;
        }
        .header img { max-height: 70px; width: auto; }
        .header-title {
            margin-top: 14px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #282975;
        }

        /* ── BANNER VERDE ── */
        .banner {
            background: linear-gradient(135deg, #282975 0%, #1a1a5e 100%);
            padding: 28px 32px;
            text-align: center;
        }
        .banner-icon { font-size: 40px; margin-bottom: 8px; }
        .banner h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .banner p { color: #c5c9f0; font-size: 14px; }

        /* ── CONTENIDO ── */
        .content { padding: 32px; }
        .greeting { font-size: 16px; color: #333; margin-bottom: 16px; }
        .course-highlight {
            color: #282975;
            font-weight: 700;
        }
        .date-highlight {
            color: #00B2A1;
            font-weight: 700;
        }
        .inspirational {
            margin: 20px 0;
            padding: 16px 20px;
            background: linear-gradient(135deg, #f8f9ff 0%, #eef0ff 100%);
            border-left: 4px solid #282975;
            border-radius: 0 8px 8px 0;
            font-size: 14px;
            color: #444;
            font-style: italic;
        }

        /* ── IMAGEN DE RECORDATORIO ── */
        .reminder-image {
            margin: 24px 0;
            text-align: center;
        }
        .reminder-image img {
            max-width: 100%;
            border-radius: 10px;
            border: 1px solid #e8e8e8;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        /* ── SECCIÓN ACCESO ── */
        .access-section {
            margin: 28px 0;
        }
        .access-section h2 {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            color: #282975;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eaeef8;
        }
        .step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            padding: 12px 16px;
            background-color: #f8f9ff;
            border-radius: 8px;
            border: 1px solid #e8ecf8;
        }
        .step-icon { font-size: 18px; flex-shrink: 0; margin-top: 2px; }
        .step-text { font-size: 14px; color: #444; }
        .step-text a { color: #282975; font-weight: 700; text-decoration: none; }
        .step-text strong { color: #282975; }

        /* ── DIVIDER ── */
        .divider {
            border: none;
            border-top: 1px solid #eaeef8;
            margin: 28px 0;
        }

        /* ── BLOQUE DUDAS ── */
        .doubt-section {
            background: linear-gradient(135deg, #fff8f0 0%, #fff3e6 100%);
            border: 1px solid #ffe0b2;
            border-radius: 10px;
            padding: 20px 24px;
            text-align: center;
        }
        .doubt-section .pin-icon { font-size: 24px; margin-bottom: 8px; }
        .doubt-section h3 {
            font-size: 15px;
            font-weight: 700;
            color: #e65100;
            margin-bottom: 8px;
        }
        .doubt-section p {
            font-size: 13px;
            color: #666;
            margin-bottom: 16px;
        }
        .btn-whatsapp {
            display: inline-block;
            background-color: #25D366;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        }

        /* ── FOOTER ── */
        .footer {
            background-color: #f8f9ff;
            border-top: 1px solid #eaeef8;
            padding: 20px 32px;
            text-align: center;
        }
        .footer p { font-size: 12px; color: #999; margin-bottom: 4px; }
        .footer strong { color: #282975; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        {{-- ===== HEADER: Logo + Título ===== --}}
        <div class="header">
            @php
                $settings = \App\Models\SystemSetting::first();
                $logoSrc  = 'https://einssoconsultores.com/logos/einsso-a.png';

                if ($settings && $settings->header_logo) {
                    try {
                        $logoPath = $settings->header_logo;
                        if (is_string($logoPath) && str_starts_with($logoPath, '[')) {
                            $logoPath = json_decode($logoPath, true)[0] ?? $logoPath;
                        }
                        if (is_array($logoPath)) { $logoPath = array_values($logoPath)[0] ?? null; }
                        if ($logoPath) {
                            $logoPath = ltrim(trim($logoPath), '/');
                            $logoData = \Illuminate\Support\Facades\Storage::disk('s3')->get($logoPath);
                            $mime     = \Illuminate\Support\Facades\Storage::disk('s3')->mimeType($logoPath);
                            $logoSrc  = $message->embedData($logoData, 'logo.png', $mime);
                        }
                    } catch (\Throwable $e) {}
                }
            @endphp
            <img src="{{ $logoSrc }}" alt="EINSSO Consultores" />
            <div class="header-title">Confirmación de Matrícula</div>
        </div>

        {{-- ===== BANNER ===== --}}
        <div class="banner">
            <div class="banner-icon">🎓</div>
            <h1>¡Bienvenido/a al curso!</h1>
            <p>Tu matrícula ha sido registrada exitosamente</p>
        </div>

        {{-- ===== CONTENIDO ===== --}}
        <div class="content">

            @php
                $course    = $enrollment->course;
                $userName  = $enrollment->user?->name ?? 'Estudiante';
                $startDate = $course?->start_date
                    ? \Carbon\Carbon::parse($course->start_date)->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y')
                    : null;
            @endphp

            <p class="greeting">
                Buen día estimado/a, <strong>{{ $userName }}</strong>,
            </p>

            <p style="font-size:15px; margin-bottom:16px; color:#444;">
                Es un gusto darte la bienvenida al curso
                <span class="course-highlight">{{ $course?->title ?? 'nuestro curso' }}</span>
                @if($startDate)
                    , que dará inicio el
                    <span class="date-highlight">{{ $startDate }}</span>.
                @endif
            </p>

            <div class="inspirational">
                💡 Cada gran logro comienza con la decisión de intentarlo. Este es tu momento para crecer, aprender y transformar tu carrera profesional. ¡Estamos emocionados de acompañarte en este camino!
            </div>

            {{-- Imagen del aula --}}
            @if(!empty($reminderImageUrl))
                <div class="reminder-image">
                    <img src="{{ $reminderImageUrl }}" alt="Aula Virtual — {{ $course?->title }}" />
                </div>
            @endif

            {{-- ===== ACCESO AL AULA VIRTUAL ===== --}}
            <div class="access-section">
                <h2>¿Cómo acceder al Aula Virtual?</h2>

                <div class="step">
                    <span class="step-icon">🔹</span>
                    <div class="step-text">
                        Inicia sesión en la plataforma con tu correo registrado.<br>
                        <a href="https://einssoconsultores.com/auth">👉 https://einssoconsultores.com/auth</a>
                    </div>
                </div>

                <div class="step">
                    <span class="step-icon">🔹</span>
                    <div class="step-text">
                        Selecciona el curso de <strong>{{ $course?->title ?? 'tu curso' }}</strong>.
                    </div>
                </div>

                <div class="step">
                    <span class="step-icon">🔹</span>
                    <div class="step-text">
                        Dentro del curso encontrarás el temario y materiales de estudio.
                    </div>
                </div>

                <div class="step">
                    <span class="step-icon">🔹</span>
                    <div class="step-text">
                        Los módulos se habilitarán según el calendario programado.
                    </div>
                </div>
            </div>

            <hr class="divider">

            {{-- ===== BLOQUE DUDAS + WHATSAPP ===== --}}
            @php
                $waMessage = urlencode('Hola, tengo una duda con respecto al curso de ' . ($course?->title ?? 'el curso'));
                $waLink    = 'https://wa.me/51974496337?text=' . $waMessage;
            @endphp

            <div class="doubt-section">
                <div class="pin-icon">📌</div>
                <h3>¿Tienes alguna duda sobre el curso?</h3>
                <p>
                    Queremos que comiences tu capacitación con total seguridad y sin inquietudes.<br>
                    Haz clic aquí y resuelve tus preguntas con nosotros:
                </p>
                <a href="{{ $waLink }}" class="btn-whatsapp">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"
                         width="18" height="18"
                         style="display:inline-block;vertical-align:middle;margin-right:8px;margin-bottom:2px;">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Tengo una duda
                </a>
            </div>

        </div>

        {{-- ===== FOOTER ===== --}}
        <div class="footer">
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
            <p>&copy; {{ date('Y') }} <strong>EINSSO Consultores</strong>. Todos los derechos reservados.</p>
        </div>

    </div>
</div>
</body>
</html>
