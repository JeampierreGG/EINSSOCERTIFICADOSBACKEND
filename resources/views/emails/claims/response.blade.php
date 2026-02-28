<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta a su Reclamo — EINSSO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background-color: #f0f2f5; color: #333333; line-height: 1.6; }
        .wrapper { padding: 30px 15px; }
        .container { max-width: 620px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.10); }
        .header { background-color: #ffffff; padding: 24px 32px; text-align: center; border-bottom: 3px solid #282975; }
        .header img { max-height: 70px; width: auto; }
        .content { padding: 32px; }
        .greeting { font-size: 16px; color: #333; margin-bottom: 18px; }
        .response-box { background-color: #f3f4f6; padding: 20px; border-left: 4px solid #00B2A1; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 15px; color: #444; }
        .footer { background-color: #f8f9ff; border-top: 1px solid #eaeef8; padding: 20px 32px; text-align: center; }
        .footer p { font-size: 12px; color: #999; margin-bottom: 4px; }
        .footer strong { color: #282975; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('logos/einsso-a.png')) }}" alt="EINSSO" />
        </div>
        <div class="content">
            <h2 style="color: #282975; margin-bottom: 20px; text-align: center;">Respuesta a su Reclamo</h2>
            <p class="greeting">Estimado(a) <strong>{{ $claim->nombres }} {{ $claim->apellido_paterno }} {{ $claim->apellido_materno }}</strong>,</p>
            <p>Por medio de la presente, hacemos de su conocimiento la respuesta a su <strong>Hoja de Reclamación N° {{ $claim->ticket_code }}</strong> registrada con fecha <strong>{{ \Carbon\Carbon::parse($claim->created_at)->format('d/m/Y') }}</strong>.</p>
            
            <h3 style="margin-top: 25px; font-size: 16px; color: #282975;">Detalle de la Respuesta / Acciones Tomadas:</h3>
            <div class="response-box">
                {!! nl2br(e($claim->respuesta_admin)) !!}
            </div>
            
            <p style="margin-top: 20px;">Agradecemos su comunicación y reafirmamos nuestro compromiso de mejora continua en nuestros servicios.</p>
            
            <p style="margin-top: 30px;">
                Atentamente,<br>
                <strong>{{ config('app.name') }}</strong><br>
                Área de Atención al Cliente
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
