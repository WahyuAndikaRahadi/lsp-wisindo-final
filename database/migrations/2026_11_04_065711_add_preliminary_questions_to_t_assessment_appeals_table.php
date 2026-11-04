<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('t_assessment_appeals', function (Blueprint $table) {
            $table->boolean('is_process_explained')->default(true)->after('appeal_date');
            $table->boolean('is_discussed_with_assessor')->default(true)->after('is_process_explained');
            $table->boolean('involves_other_person')->default(false)->after('is_discussed_with_assessor');
            $table->string('other_person_name')->nullable()->after('involves_other_person');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_assessment_appeals', function (Blueprint $table) {
            $table->dropColumn([
                'is_process_explained',
                'is_discussed_with_assessor',
                'involves_other_person',
                'other_person_name',
            ]);
        });
    }
};
