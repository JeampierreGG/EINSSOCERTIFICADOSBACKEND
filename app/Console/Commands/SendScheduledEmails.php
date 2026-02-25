<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CourseEnrollment;
use App\Models\CourseModule;
use App\Models\Evaluation;
use App\Mail\CourseOpening;
use App\Mail\EvaluationNotification;
use App\Mail\EvaluationReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendScheduledEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía correos programados: Apertura de curso, Notificación de evaluación y Recordatorios.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de envío de correos programados...');

        $this->sendCourseOpeningEmails();
        $this->sendEvaluationStartEmails();
        $this->sendEvaluationReminderEmails();

        $this->info('Proceso completado.');
    }

    /**
     * 1. Apertura de curso (CourseOpening)
     * Se envía cuando se cumple la fecha de disponibilidad del primer módulo.
     */
    private function sendCourseOpeningEmails()
    {
        $now = Carbon::now();
        $this->comment('Verificando aperturas de cursos...');

        // Buscamos inscripciones activas cuyo correo de apertura no se haya enviado
        $enrollments = CourseEnrollment::where('course_opening_sent', false)
            ->where('status', 'active')
            ->with(['course', 'user'])
            ->get();

        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            if (!$course) continue;

            // Obtener el primer módulo del curso (menor orden)
            $firstModule = CourseModule::where('course_id', $course->id)
                ->orderBy('order')
                ->first();

            if ($firstModule && $firstModule->enable_date) {
                // Si ya llegó la fecha de habilitación del primer módulo
                if (Carbon::parse($firstModule->enable_date)->lte($now)) {
                    Mail::to($enrollment->user->email)->queue(new CourseOpening($enrollment));
                    
                    $enrollment->course_opening_sent = true;
                    $enrollment->save();
                    
                    $this->line(" - Correo de apertura enviado para el curso: {$course->title} al usuario: {$enrollment->user->email}");
                }
            }
        }
    }

    /**
     * 2. Notificación de Evaluación (EvaluationNotification)
     * Se envía cuando se cumple la fecha y hora de inicio de las evaluaciones.
     */
    private function sendEvaluationStartEmails()
    {
        $now = Carbon::now();
        $this->comment('Verificando inicio de evaluaciones...');

        // Evaluaciones que ya iniciaron
        $evaluations = Evaluation::where('start_date', '<=', $now)
            ->where('end_date', '>', $now)
            ->get();

        foreach ($evaluations as $evaluation) {
            // Usuarios inscritos en este curso
            $enrollments = CourseEnrollment::where('course_id', $evaluation->course_id)
                ->where('status', 'active')
                ->with('user')
                ->get();

            foreach ($enrollments as $enrollment) {
                // Verificar si ya se envió la notificación de 'start' para esta evaluación y este usuario
                $alreadySent = DB::table('evaluation_notifications_sent')
                    ->where('enrollment_id', $enrollment->id)
                    ->where('evaluation_id', $evaluation->id)
                    ->where('notification_type', 'start')
                    ->exists();

                if (!$alreadySent) {
                    Mail::to($enrollment->user->email)->queue(new EvaluationNotification($enrollment, $evaluation));
                    
                    DB::table('evaluation_notifications_sent')->insert([
                        'enrollment_id' => $enrollment->id,
                        'evaluation_id' => $evaluation->id,
                        'notification_type' => 'start',
                        'sent_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    
                    $this->line(" - Notificación de inicio: {$evaluation->title} para {$enrollment->user->email}");
                }
            }
        }
    }

    /**
     * 3. Recordatorio de Evaluación (EvaluationReminder)
     * Se envía 24 horas antes de la fecha fin de la evaluación.
     */
    private function sendEvaluationReminderEmails()
    {
        $now = Carbon::now();
        $tomorrow = Carbon::now()->addDay();
        $this->comment('Verificando recordatorios de evaluación (24h antes)...');

        // Evaluaciones que terminan en las próximas 24 horas y que aún están vigentes
        $evaluations = Evaluation::where('end_date', '<=', $tomorrow)
            ->where('end_date', '>', $now)
            ->get();

        foreach ($evaluations as $evaluation) {
            $enrollments = CourseEnrollment::where('course_id', $evaluation->course_id)
                ->where('status', 'active')
                ->with('user')
                ->get();

            foreach ($enrollments as $enrollment) {
                // Verificar si ya se envió el 'reminder'
                $alreadySent = DB::table('evaluation_notifications_sent')
                    ->where('enrollment_id', $enrollment->id)
                    ->where('evaluation_id', $evaluation->id)
                    ->where('notification_type', 'reminder')
                    ->exists();

                if (!$alreadySent) {
                    Mail::to($enrollment->user->email)->queue(new EvaluationReminder($enrollment, $evaluation));
                    
                    DB::table('evaluation_notifications_sent')->insert([
                        'enrollment_id' => $enrollment->id,
                        'evaluation_id' => $evaluation->id,
                        'notification_type' => 'reminder',
                        'sent_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    
                    $this->line(" - Recordatorio de cierre: {$evaluation->title} para {$enrollment->user->email}");
                }
            }
        }
    }
}
