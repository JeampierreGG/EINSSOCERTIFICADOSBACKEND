<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    public function show($id)
    {
        $evaluation = Evaluation::with(['questionsRelation.options'])->findOrFail($id);
        
        $questions = [];

        // Check if using relational tables
        if ($evaluation->questionsRelation->isNotEmpty()) {
            $questions = $evaluation->questionsRelation->map(function($q) {
                return [
                    'id' => (string)$q->id,
                    'text' => $q->question_text,
                    'options' => $q->options->map(function($opt) {
                        return [
                            'id' => (string)$opt->id,
                            'text' => $opt->option_text,
                            // Do not expose is_correct
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();
        } else {
            // Fallback to old JSON column
            $rawQuestions = $evaluation->questions ?? [];
            if (is_string($rawQuestions)) {
                $rawQuestions = json_decode($rawQuestions, true) ?? [];
            }
            $questions = collect($rawQuestions)->map(function($q) {
                if (isset($q['options'])) {
                    $q['options'] = collect($q['options'])->map(function($opt) {
                         $optCopy = $opt;
                         unset($optCopy['is_correct']); 
                         return $optCopy;
                    })->toArray();
                }
                return $q;
            })->toArray();
        }

        $data = $evaluation->toArray();
        // Remove relation key to keep it clean if desired, but not strictly necessary
        unset($data['questions_relation']);
        
        $data['questions'] = $questions;
        $data['time_limit_minutes'] = $evaluation->time_limit ?? 60;
        // Check for User Extension (Extra Attempts)
        $user = Auth::guard('sanctum')->user();
        $extraAttempts = 0;
        if ($user) {
             $extension = \App\Models\EvaluationUserExtension::where('user_id', $user->id)
                ->where('evaluation_id', $id)
                ->first();
             if ($extension) {
                 $extraAttempts = $extension->extra_attempts;
             }
        }

        $data['attempts_max'] = $evaluation->attempts + $extraAttempts;

        // Override End Date if extended
        if (isset($extension) && $extension->extended_end_date) {
            $data['end_date'] = $extension->extended_end_date;
        }

        return response()->json($data);
    }

    public function attempts($id)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $attempts = EvaluationAttempt::where('evaluation_id', $id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($attempts->map(function($att) {
            // Logic for approval threshold (e.g. >= 12)
            $att->approved = $att->score >= 12;
            return $att;
        }));
    }

    public function start(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $evaluation = Evaluation::findOrFail($id);

        // Validar intentos máximos (si attempts > 0 es limitado, 0 o null ilimitado?)
        // Asumiremos que si es > 0 es el límite.
        if ($evaluation->attempts > 0) {
            $extension = \App\Models\EvaluationUserExtension::where('user_id', $user->id)
                ->where('evaluation_id', $id)
                ->first();
            $extraAttempts = $extension ? $extension->extra_attempts : 0;
            $totalAllowed = $evaluation->attempts + $extraAttempts;

            $count = EvaluationAttempt::where('user_id', $user->id)
                ->where('evaluation_id', $id)
                ->count();
            
            if ($count >= $totalAllowed) {
                return response()->json(['message' => 'Has alcanzado el límite de intentos para esta evaluación.'], 403);
            }
        }

        // Calcular número de intento
        $maxAttempt = EvaluationAttempt::where('user_id', $user->id)
            ->where('evaluation_id', $id)
            ->max('attempt_number');
        
        $nextAttemptNumber = ($maxAttempt ?? 0) + 1;

        // Crear nuevo intento
        $attempt = EvaluationAttempt::create([
            'user_id' => $user->id,
            'evaluation_id' => $id,
            'course_id' => $evaluation->course_id,
            'attempt_number' => $nextAttemptNumber,
            // 'started_at' => now(), // Si tuviéramos columna started_at
        ]);

        return response()->json(['attempt' => $attempt]);
    }

    public function finish(Request $request, $id, $attemptId)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $attempt = EvaluationAttempt::where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // If already finished, return the existing result (Idempotency)
        if ($attempt->completed_at) {
             return response()->json([
                'attempt' => $attempt,
                'score' => $attempt->score ?? 0,
                // We assume 0 for checks on re-fetch if not stored elsewhere
                'correct_count' => 0, 
                'total_questions' => 0, 
                'message' => 'Evaluación ya finalizada anteriormente.'
            ]);
        }

        $inputAnswers = $request->input('answers', []);
        Log::info("Evaluation Finish Attempt {$attemptId}. user_id: {$user->id}. Answers Payload: " . json_encode($inputAnswers));

        $evaluation = Evaluation::with(['questionsRelation.options'])->findOrFail($id);

        // Check if we are running in Relational Mode
        if ($evaluation->questionsRelation->isEmpty()) {
            Log::warning("Evaluation {$id} has NO relational questions. Cannot save to evaluation_answers table. Falling back to LEGACY JSON grading.");
            // We cannot save detailed answers without question IDs.
            // Proceed with Legacy Logic (copied from previous fallback)
            $rawQuestions = $evaluation->questions ?? [];
            if (is_string($rawQuestions)) {
                $rawQuestions = json_decode($rawQuestions, true) ?? [];
            }
            $totalQuestions = count($rawQuestions);
            $correctCount = 0;

            foreach ($rawQuestions as $q) {
                $qid = (string)($q['id'] ?? ''); 
                if (isset($inputAnswers[$qid])) {
                    $selectedOptId = (string)$inputAnswers[$qid];
                    $options = $q['options'] ?? [];
                    foreach ($options as $opt) {
                        $isCorrect = !empty($opt['is_correct']); 
                        if ((string)($opt['id'] ?? '') === $selectedOptId && $isCorrect) {
                             $correctCount++;
                             break;
                        }
                    }
                }
            }
            
            // Calculate Score Legacy
            $score = ($totalQuestions > 0) ? ($correctCount / $totalQuestions) * 20 : 0;
            
            $attempt->score = round($score, 2);
            $attempt->completed_at = now();
            $attempt->save();

            return response()->json([
                'attempt' => $attempt,
                'score' => $attempt->score,
                'correct_count' => $correctCount,
                'total_questions' => $totalQuestions,
                'message' => 'Evaluación finalizada (Modo Legado - JSON).'
            ]);
        }

        // Relational Mode with Transaction
        try {
            return DB::transaction(function () use ($attempt, $evaluation, $inputAnswers) {
                $correctCount = 0;
                $totalQuestions = $evaluation->questionsRelation->count();
                $savedAnswersCount = 0;

                foreach ($evaluation->questionsRelation as $q) {
                    $qid = (string)$q->id;
                    $selectedOptId = null;
                    $isCorrect = false;

                    // Robust key checking
                    if (isset($inputAnswers[$qid]) || array_key_exists($qid, $inputAnswers)) {
                        $rawOptId = trim((string)$inputAnswers[$qid]);
                        
                        // Find option carefully
                        $selectedOption = $q->options->first(function($opt) use ($rawOptId) {
                            return (string)$opt->id === $rawOptId;
                        });

                        if ($selectedOption) {
                            $selectedOptId = $selectedOption->id;
                            $isCorrect = (bool)$selectedOption->is_correct;
                        }
                    }

                    // Create Answer Record
                    \App\Models\EvaluationAnswer::create([
                        'evaluation_attemps_id' => $attempt->id,
                        'evaluation_question_id' => $q->id,
                        'evaluation_option_id' => $selectedOptId,
                        'user_id' => Auth::id(),
                    ]);
                    $savedAnswersCount++;

                    if ($isCorrect) {
                        $correctCount++;
                    }
                }

                // Calculate Score
                $score = ($totalQuestions > 0) ? ($correctCount / $totalQuestions) * 20 : 0;

                $attempt->score = round($score, 2);
                $attempt->completed_at = now();
                $attempt->save();

                Log::info("Evaluation {$evaluation->id} finished by User {$attempt->user_id}. Score: {$attempt->score}");

                return response()->json([
                    'attempt' => $attempt,
                    'score' => $attempt->score,
                    'correct_count' => $correctCount,
                    'total_questions' => $totalQuestions,
                    'message' => 'Evaluación finalizada con éxito'
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Error finishing evaluation: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar la evaluación.'], 500);
        }
    }
}
