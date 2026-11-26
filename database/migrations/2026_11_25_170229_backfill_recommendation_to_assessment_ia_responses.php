<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('t_assessment_ia_responses')) {
            return;
        }

        DB::table('t_assessment_ia_responses')
            ->whereIn('instrument_code', ['IA01', 'IA03', 'IA08', 'IA09'])
            ->whereNotNull('data_json')
            ->orderBy('id')
            ->chunk(100, function ($responses) {
                foreach ($responses as $resp) {
                    $data = json_decode($resp->data_json, true) ?: [];
                    if (! isset($data['recommendation']) || empty($data['recommendation'])) {
                        $data['recommendation'] = 'K';
                        DB::table('t_assessment_ia_responses')
                            ->where('id', $resp->id)
                            ->update(['data_json' => json_encode($data)]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: backfilled recommendation data remains compatible.
    }
};
