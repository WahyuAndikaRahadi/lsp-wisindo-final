<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_assessment_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->date('appeal_date');
            $table->text('appeal_reasons');
            $table->json('contested_units')->nullable();
            $table->enum('status', ['SUBMITTED', 'UNDER_REVIEW', 'ACCEPTED', 'REJECTED'])->default('SUBMITTED');
            $table->text('decision_notes')->nullable();
            $table->date('decision_date')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('appeals_committee_chair')->nullable();
            $table->text('assessor_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_assessment_appeals');
    }
};
