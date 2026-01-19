<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evaluation_attempts', function (Blueprint $table) {
            $table->integer('attempt_number')->nullable()->after('evaluation_id');
        });

        // Loop through all attempts and assign attempt_number sequentially for each user/evaluation pair
        $attempts = DB::table('evaluation_attempts')
            ->orderBy('created_at')
            ->get();

        $counters = []; // Key: "user_ID_eval_ID" => count

        foreach ($attempts as $attempt) {
            $key = $attempt->user_id . '_' . $attempt->evaluation_id;
            if (!isset($counters[$key])) {
                $counters[$key] = 0;
            }
            $counters[$key]++;
            
            DB::table('evaluation_attempts')
                ->where('id', $attempt->id)
                ->update(['attempt_number' => $counters[$key]]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_attempts', function (Blueprint $table) {
            $table->dropColumn('attempt_number');
        });
    }
};
