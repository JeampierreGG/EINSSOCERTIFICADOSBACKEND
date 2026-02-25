<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/admin');

/*
|--------------------------------------------------------------------------
| Previsualización de correos (solo entorno local)
|--------------------------------------------------------------------------
| Accede en el navegador a: http://localhost:8000/mail-preview/{tipo}
| Tipos disponibles: enrollment, opening, evaluation, evaluation-reminder
*/
if (app()->environment('local')) {
    Route::get('/mail-preview/{type?}', function (string $type = 'enrollment') {
        // Tomamos un enrollment real para tener datos reales
        $enrollment = \App\Models\CourseEnrollment::with(['user', 'course'])
            ->latest()
            ->first();

        if (!$enrollment) {
            return '<h2 style="font-family:sans-serif;color:red;padding:20px">
                        ⚠️ No hay matrículas en la base de datos.<br>
                        <small>Crea al menos una matrícula para previsualizar el correo.</small>
                    </h2>';
        }

        return match($type) {
            'enrollment' => (new \App\Mail\EnrollmentConfirmation($enrollment))->render(),
            'opening'    => (new \App\Mail\CourseOpening($enrollment))->render(),
            'evaluation', 'evaluation-reminder' => (function() use ($type, $enrollment) {
                $evaluation = \App\Models\Evaluation::where('course_id', $enrollment->course_id)->first() 
                            ?? \App\Models\Evaluation::first();
                
                if (!$evaluation) {
                    return '<h1 style="font-family:sans-serif;color:orange;padding:20px">⚠️ Sin evaluaciones</h1>';
                }

                return $type === 'evaluation' 
                    ? (new \App\Mail\EvaluationNotification($enrollment, $evaluation))->render()
                    : (new \App\Mail\EvaluationReminder($enrollment, $evaluation))->render();
            })(),

            // Otros correos del sistema
            'payment' => (function() {
                $payment = \App\Models\Payment::latest()->first();
                return $payment ? (new \App\Mail\PaymentConfirmation($payment))->render() : '<h1>⚠️ No hay pagos</h1>';
            })(),

            'certificate' => (function() {
                $cert = \App\Models\Certificate::latest()->first();
                return $cert ? (new \App\Mail\CertificateSent($cert))->render() : '<h1>⚠️ No hay certificados</h1>';
            })(),

            'claim' => (function() {
                $claim = \App\Models\Claim::latest()->first();
                return $claim ? (new \App\Mail\ClaimResponseMail($claim))->render() : '<h1>⚠️ No hay reclamos</h1>';
            })(),

            'password-reset' => (new \App\Mail\PasswordResetCodeMail('123456'))->render(),

            default => response('<h2 style="font-family:sans-serif;padding:20px">
                                Tipo de correo <strong>' . e($type) . '</strong> no reconocido.<br>
                                Tipos disponibles: <code>enrollment</code>, <code>opening</code>, <code>evaluation</code>, <code>evaluation-reminder</code>, <code>payment</code>, <code>certificate</code>, <code>claim</code>, <code>password-reset</code>
                            </h2>', 404),
        };
    })->name('mail.preview');
}

