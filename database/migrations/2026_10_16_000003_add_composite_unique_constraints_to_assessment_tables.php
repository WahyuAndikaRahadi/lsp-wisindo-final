<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Deduplicate t_assessment_proofs before adding unique composite
        $duplicateProofIds = DB::table('t_assessment_proofs as p1')
            ->join('t_assessment_proofs as p2', function ($join) {
                $join->on('p1.assessment_id', '=', 'p2.assessment_id')
                    ->on('p1.proof_type', '=', 'p2.proof_type')
                    ->whereColumn('p1.id', '<', 'p2.id');
            })
            ->pluck('p1.id');

        if ($duplicateProofIds->isNotEmpty()) {
            DB::table('t_assessment_proofs')->whereIn('id', $duplicateProofIds)->delete();
        }

        Schema::table('t_assessment_proofs', function (Blueprint $table) {
            $table->unique(['assessment_id', 'proof_type'], 't_assessment_proofs_assessment_proof_type_unique');
        });

        // 2. Deduplicate t_assessment_apl02_items before adding unique composite
        $duplicateApl02Ids = DB::table('t_assessment_apl02_items as a1')
            ->join('t_assessment_apl02_items as a2', function ($join) {
                $join->on('a1.assessment_id', '=', 'a2.assessment_id')
                    ->on('a1.kuk_id', '=', 'a2.kuk_id')
                    ->whereColumn('a1.id', '<', 'a2.id');
            })
            ->pluck('a1.id');

        if ($duplicateApl02Ids->isNotEmpty()) {
            DB::table('t_assessment_apl02_items')->whereIn('id', $duplicateApl02Ids)->delete();
        }

        Schema::table('t_assessment_apl02_items', function (Blueprint $table) {
            $table->unique(['assessment_id', 'kuk_id'], 't_assessment_apl02_items_assessment_kuk_unique');
        });

        // 3. Deduplicate t_assessment_ia_responses before adding unique composite
        $duplicateIaIds = DB::table('t_assessment_ia_responses as r1')
            ->join('t_assessment_ia_responses as r2', function ($join) {
                $join->on('r1.assessment_id', '=', 'r2.assessment_id')
                    ->on('r1.instrument_code', '=', 'r2.instrument_code')
                    ->whereColumn('r1.id', '<', 'r2.id');
            })
            ->pluck('r1.id');

        if ($duplicateIaIds->isNotEmpty()) {
            DB::table('t_assessment_ia_responses')->whereIn('id', $duplicateIaIds)->delete();
        }

        Schema::table('t_assessment_ia_responses', function (Blueprint $table) {
            $table->unique(['assessment_id', 'instrument_code'], 't_assessment_ia_responses_assessment_instrument_unique');
        });

        // 4. Deduplicate t_assessment_ak07_items before adding unique composite
        $duplicateAk07Ids = DB::table('t_assessment_ak07_items as k1')
            ->join('t_assessment_ak07_items as k2', function ($join) {
                $join->on('k1.assessment_id', '=', 'k2.assessment_id')
                    ->on('k1.adjustment_category', '=', 'k2.adjustment_category')
                    ->whereColumn('k1.id', '<', 'k2.id');
            })
            ->pluck('k1.id');

        if ($duplicateAk07Ids->isNotEmpty()) {
            DB::table('t_assessment_ak07_items')->whereIn('id', $duplicateAk07Ids)->delete();
        }

        Schema::table('t_assessment_ak07_items', function (Blueprint $table) {
            $table->unique(['assessment_id', 'adjustment_category'], 't_assessment_ak07_items_assessment_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_assessment_proofs', function (Blueprint $table) {
            $table->dropUnique('t_assessment_proofs_assessment_proof_type_unique');
        });

        Schema::table('t_assessment_apl02_items', function (Blueprint $table) {
            $table->dropUnique('t_assessment_apl02_items_assessment_kuk_unique');
        });

        Schema::table('t_assessment_ia_responses', function (Blueprint $table) {
            $table->dropUnique('t_assessment_ia_responses_assessment_instrument_unique');
        });

        Schema::table('t_assessment_ak07_items', function (Blueprint $table) {
            $table->dropUnique('t_assessment_ak07_items_assessment_category_unique');
        });
    }
};
