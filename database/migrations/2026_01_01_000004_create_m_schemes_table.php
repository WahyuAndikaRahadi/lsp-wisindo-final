<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->enum('scheme_type', ['OKUPASI', 'KLASTER', 'KKNI']);
            $table->string('version', 20)->default('1.0');
            $table->text('description')->nullable();
            $table->decimal('passing_grade_theory', 5, 2)->default(75.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_schemes');
    }
};
