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
        Schema::create('t_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('t_assessments')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('t_schedules')->cascadeOnDelete();
            $table->foreignId('asesi_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scheme_id')->constrained('m_schemes')->cascadeOnDelete();
            $table->string('certificate_number');
            $table->string('blanko_number');
            $table->date('issued_date');
            $table->date('valid_until');
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asesi_id', 'scheme_id']);
            $table->unique('assessment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_certificates');
    }
};
