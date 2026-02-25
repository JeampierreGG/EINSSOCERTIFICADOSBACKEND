<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apertura del Curso — EINSSO</title>
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
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
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

        /* ── BANNER ── */
        .banner {
            background: linear-gradient(135deg, #282975 0%, #1a1a5e 100%);
            padding: 28px 32px;
            text-align: center;
        }
        .banner-icon { font-size: 42px; margin-bottom: 8px; }
        .banner h1 {
            color: #ffffff;
            font-size: 21px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .banner p { color: #c5c9f0; font-size: 14px; }

        /* ── CONTENIDO ── */
        .content { padding: 32px; }
        .greeting { font-size: 16px; color: #333; margin-bottom: 18px; }
        .intro-box {
            background: linear-gradient(135deg, #f8f9ff 0%, #eef0ff 100%);
            border-left: 4px solid #282975;
            border-radius: 0 10px 10px 0;
            padding: 18px 20px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #444;
            line-height: 1.7;
        }

        /* ── IMAGEN ── */
        .reminder-image {
            margin: 0 0 28px 0;
            text-align: center;
        }
        .reminder-image img {
            max-width: 100%;
            border-radius: 10px;
            border: 1px solid #e8e8e8;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        /* ── HORARIO ── */
        .schedule-section {
            margin-bottom: 28px;
        }
        .schedule-title {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            color: #282975;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eaeef8;
            text-decoration: underline;
            text-underline-offset: 4px;
        }
        .schedule-items {
            background: #f8f9ff;
            border: 1px solid #e0e4f5;
            border-radius: 10px;
            overflow: hidden;
        }
        .schedule-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            border-bottom: 1px solid #eaeef8;
        }
        .schedule-row:last-child { border-bottom: none; }
        .schedule-icon {
            font-size: 22px;
            flex-shrink: 0;
        }
        .schedule-info { flex: 1; }
        .schedule-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin-bottom: 2px;
        }
        .schedule-value {
            font-size: 16px;
            font-weight: 700;
            color: #282975;
        }

        /* ── BOTÓN AULA VIRTUAL ── */
        .btn-wrapper {
            text-align: center;
            margin: 28px 0;
        }
        .btn-campus {
            display: inline-block;
            background: linear-gradient(135deg, #282975 0%, #3d3db5 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 16px rgba(40, 41, 117, 0.35);
        }

        /* ── INSPIRACIÓN ── */
        .inspirational {
            margin: 28px 0;
            padding: 16px 20px;
            background: linear-gradient(135deg, #fff8f0 0%, #fff3e6 100%);
            border-left: 4px solid #00B2A1;
            border-radius: 0 10px 10px 0;
            font-size: 14px;
            color: #555;
            font-style: italic;
        }

        /* ── COORDINACIÓN ── */
        .coordination {
            text-align: center;
            margin: 24px 0;
            padding: 18px;
            border-top: 1px solid #eaeef8;
            border-bottom: 1px solid #eaeef8;
        }
        .coordination p {
            font-size: 14px;
            color: #282975;
            text-decoration: underline;
            text-underline-offset: 3px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .coordination .wa-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #128C7E;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
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

        {{-- ===== HEADER ===== --}}
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
            <div class="header-title">Apertura y 1ª Sesión en Vivo</div>
        </div>

        {{-- ===== BANNER ===== --}}
        <div class="banner">
            <div class="banner-icon">🚀</div>
            <h1>¡Hoy arrancamos!</h1>
            <p>Primera sesión en vivo de tu curso</p>
        </div>

        {{-- ===== CONTENIDO ===== --}}
        <div class="content">

            @php
                $course    = $enrollment->course;
                $userName  = $enrollment->user?->name ?? 'Estudiante';

                // Datos del primer módulo
                $module        = $firstModule;
                $enableDate    = $module?->enable_date;
                $classTime     = $module?->class_time;

                // Fecha en formato: Martes, 25 de Noviembre
                $formattedDate = null;
                if ($enableDate) {
                    \Carbon\Carbon::setLocale('es');
                    $carbon = \Carbon\Carbon::parse($enableDate)->locale('es');
                    $dayName   = ucfirst($carbon->translatedFormat('l'));      // Martes
                    $dayNumber = $carbon->format('j');                          // 25
                    $monthName = ucfirst($carbon->translatedFormat('F'));       // Noviembre
                    $formattedDate = "{$dayName}, {$dayNumber} de {$monthName}";
                }

                // Hora en formato legible: convert "HH:MM:SS" → "HH:MM a.m./p.m."
                $formattedTime = null;
                if ($classTime) {
                    try {
                        $timeParsed    = \Carbon\Carbon::createFromTimeString($classTime)->locale('es');
                        $formattedTime = $timeParsed->format('g:i') . ' ' . ($timeParsed->format('A') === 'AM' ? 'a.m.' : 'p.m.');
                    } catch (\Throwable) {
                        $formattedTime = $classTime;
                    }
                }

                // URL del Aula Virtual
                $courseSlug = $course?->slug ?? '';
            @endphp

            <p class="greeting">
                Buen día estimado/a, <strong>{{ $userName }}</strong>,
            </p>

            <div class="intro-box">
                🎓 <strong>¡Hoy arrancamos juntos esta nueva etapa de aprendizaje!</strong><br><br>
                Conectaremos en la <strong>Apertura y Primera Sesión en Vivo</strong> del curso, donde conocerás al docente, la dinámica del programa y empezaremos a trabajar los primeros conceptos esenciales que marcarán el rumbo de tu formación.<br><br>
                Te esperamos con toda la energía para comenzar este camino juntos. ¡Prepárate para aprender, crecer y transformar tu carrera profesional!
            </div>

            {{-- Imagen del primer módulo --}}
            @if(!empty($reminderImageUrl))
                <div class="reminder-image">
                    <img src="{{ $reminderImageUrl }}" alt="Apertura — {{ $course?->title }}" />
                </div>
            @endif

            {{-- ===== HORARIO ===== --}}
            @if($formattedDate || $formattedTime)
                <div class="schedule-section">
                    <div class="schedule-title">Horario</div>
                    <div class="schedule-items">

                        @if($formattedDate)
                        <div class="schedule-row">
                            <span class="schedule-icon">📅</span>
                            <div class="schedule-info">
                                <div class="schedule-label">Fecha</div>
                                <div class="schedule-value">{{ $formattedDate }}</div>
                            </div>
                        </div>
                        @endif

                        @if($formattedTime)
                        <div class="schedule-row">
                            <span class="schedule-icon">🕐</span>
                            <div class="schedule-info">
                                <div class="schedule-label">Hora</div>
                                <div class="schedule-value">{{ $formattedTime }} (Hora Perú)</div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            @endif

            {{-- ===== BOTÓN AULA VIRTUAL ===== --}}
            <div class="btn-wrapper">
                <a href="{{ $courseUrl }}" class="btn-campus">
                    🎓 &nbsp; Aula Virtual
                </a>
            </div>

            {{-- ===== MENSAJE INSPIRADOR ===== --}}
            <div class="inspirational">
                ✨ Cada sesión que eliges asistir es una inversión en tu futuro. El conocimiento que adquieras hoy será la base de los logros de mañana. ¡Mucho éxito en esta primera clase!
            </div>

            {{-- ===== COORDINACIÓN ACADÉMICA ===== --}}
            @php
                $waMessage = urlencode('Hola, tengo una consulta sobre el curso de ' . ($course?->title ?? 'el curso'));
                $waLink    = 'https://wa.me/51974496337?text=' . $waMessage;
            @endphp

            <div class="coordination">
                <p>Coordinación Académica</p>
                <a href="{{ $waLink }}" class="wa-link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#128C7E"
                         width="18" height="18"
                         style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    WhatsApp: +51 974 496 337
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
