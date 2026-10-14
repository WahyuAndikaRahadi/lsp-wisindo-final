<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255)->nullable();
            $table->foreignId('scheme_id')->constrained('m_schemes');
            $table->string('tuk_name', 255);
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->nullable();
            $table->json('selected_instruments');
            $table->text('notes')->nullable();
            $table->enum('status', ['DRAFT', 'ACTIVE', 'FINISHED'])->default('ACTIVE');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_schedules');
    }
};
