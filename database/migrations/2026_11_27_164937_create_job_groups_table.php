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
        Schema::create('m_job_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('m_schemes')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('name');
            $table->timestamps();

            $table->unique(['scheme_id', 'sequence']);
        });

        Schema::table('m_competency_units', function (Blueprint $table) {
            $table->foreignId('job_group_id')
                ->nullable()
                ->after('scheme_id')
                ->constrained('m_job_groups')
                ->nullOnDelete();
            $table->unsignedInteger('job_group_sequence')
                ->nullable()
                ->after('sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_competency_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_group_id');
            $table->dropColumn('job_group_sequence');
        });

        Schema::dropIfExists('m_job_groups');
    }
};
