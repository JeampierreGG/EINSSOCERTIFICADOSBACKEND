<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación Disponible — EINSSO</title>
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
        .highlight-box {
            background: linear-gradient(135deg, #f8f9ff 0%, #eef0ff 100%);
            border-left: 4px solid #282975;
            border-radius: 0 10px 10px 0;
            padding: 18px 20px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #444;
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

        /* ── INSTRUCCIONES ── */
        .instructions-section {
            margin: 28px 0;
        }
        .instructions-title {
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
            margin-bottom: 12px;
            padding: 12px 16px;
            background-color: #f8f9ff;
            border-radius: 8px;
            font-size: 14px;
            color: #444;
        }
        .step-icon { color: #282975; font-weight: bold; }

        /* ── ALERTA PLAZO ── */
        .alert-box {
            background-color: #fff5f5;
            border: 1px solid #feb2b2;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 28px 0;
        }
        .alert-title {
            color: #c53030;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .alert-text {
            font-size: 14px;
            color: #742a2a;
        }
        .deadline-highlight {
            color: #c53030;
            font-weight: 700;
        }

        /* ── BOTÓN ── */
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
            box-shadow: 0 4px 16px rgba(40, 41, 117, 0.35);
        }

        /* ── DESPEDIDA ── */
        .success-msg {
            text-align: center;
            font-size: 15px;
            color: #282975;
            font-weight: 700;
            margin: 28px 0;
            font-style: italic;
        }

        /* ── COORDINACIÓN ── */
        .coordination {
            text-align: center;
            margin: 24px 0;
            padding: 18px;
            border-top: 1px solid #eaeef8;
        }
        .coordination p {
            font-size: 14px;
            color: #282975;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .wa-link {
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
        {{-- HEADER --}}
        <div class="header">
            <img src="{{ $message->embed(public_path('logos/einsso-a.png')) }}" alt="EINSSO" />
            <div class="header-title">Evaluación</div>
        </div>

        {{-- BANNER --}}
        <div class="banner">
            <div class="banner-icon">📝</div>
            <h1>¡Evaluación Disponible!</h1>
            <p>{{ $evaluation->title }}</p>
        </div>

        <div class="content">
            <p class="greeting">Buenas noches estimado/a, <strong>{{ $enrollment->user?->name }}</strong>,</p>

            <div class="highlight-box">
                Ya puedes acceder a la <strong>Evaluación: {{ $evaluation->title }}</strong>. Esta es tu oportunidad para demostrar lo aprendido y reforzar tus conocimientos. ¡Estamos seguros de que harás un excelente trabajo!
            </div>

            {{-- IMAGEN --}}
            @if($reminderImageUrl)
                <div class="reminder-image">
                    <img src="{{ $reminderImageUrl }}" alt="Evaluación" />
                </div>
            @endif

            {{-- INSTRUCCIONES --}}
            <div class="instructions-section">
                <div class="instructions-title">¿Cómo ingresar a la evaluación?</div>
                <div class="step">
                    <span class="step-icon">1.</span>
                    <span>Haz clic en el botón <strong>Aula Virtual</strong> e inicia sesión con tu correo registrado.</span>
                </div>
                <div class="step">
                    <span class="step-icon">2.</span>
                    <span>Ve a <strong>Mi cuenta > Mis cursos</strong>, y haz clic en <strong>Continuar</strong>.</span>
                </div>
                <div class="step">
                    <span class="step-icon">3.</span>
                    <span>Ingresa al apartado <strong>Evaluaciones</strong> y selecciona <strong>Empezar</strong> en la evaluación.</span>
                </div>
                <div class="step">
                    <span class="step-icon">4.</span>
                    <span>Recuerda hacer clic en <strong>Enviar</strong> para finalizar tu evaluación.</span>
                </div>
            </div>

            {{-- ALERTA PLAZO --}}
            @php
                \Carbon\Carbon::setLocale('es');
                $endDate = $evaluation->end_date;
                $formattedDeadline = $endDate ? $endDate->translatedFormat('l d \d\e F') . ' a las ' . $endDate->format('h:i a') : 'la fecha indicada';
            @endphp
            <div class="alert-box">
                <div class="alert-title">📢 ¡Atención participante!</div>
                <p class="alert-text">
                    Tienes plazo para completar tu evaluación hasta el <span class="deadline-highlight">{{ $formattedDeadline }}</span>. ⏳
                </p>
            </div>

            <p style="text-align: center; font-size: 14px; color: #555; margin-bottom: 24px;">
                No dejes pasar el tiempo y asegúrate de cumplir con este importante paso. ¡Tú puedes lograrlo! ✅
            </p>

            <div class="btn-wrapper">
                <a href="{{ $courseUrl }}" class="btn-campus">🎓 &nbsp; Aula Virtual</a>
            </div>

            <div class="success-msg">
                ¡Éxitos en tu evaluación! <br>
                ¡Confía en tus habilidades y demuestra todo lo aprendido!
            </div>

            {{-- COORDINACIÓN --}}
            <div class="coordination">
                <p style="text-decoration: underline; text-underline-offset: 3px;">Coordinación Académica</p>
                <a href="https://wa.me/51974496337" class="wa-link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#128C7E" width="18" height="18" style="display:inline-block;vertical-align:middle;">
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
