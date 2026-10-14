<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_schedule_assessors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('t_schedules')->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['schedule_id', 'assessor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_schedule_assessors');
    }
};
