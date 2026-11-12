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
        Schema::table('m_question_banks', function (Blueprint $table) {
            if (! Schema::hasColumn('m_question_banks', 'option_a_image')) {
                $table->string('option_a_image')->nullable()->after('option_a');
            }
            if (! Schema::hasColumn('m_question_banks', 'option_b_image')) {
                $table->string('option_b_image')->nullable()->after('option_b');
            }
            if (! Schema::hasColumn('m_question_banks', 'option_c_image')) {
                $table->string('option_c_image')->nullable()->after('option_c');
            }
            if (! Schema::hasColumn('m_question_banks', 'option_d_image')) {
                $table->string('option_d_image')->nullable()->after('option_d');
            }
            $table->text('option_a')->nullable()->change();
            $table->text('option_b')->nullable()->change();
            $table->text('option_c')->nullable()->change();
            $table->text('option_d')->nullable()->change();
        });

        Schema::table('t_assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('t_assessments', 'has_rejection')) {
                $table->boolean('has_rejection')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_question_banks', function (Blueprint $table) {
            $columns = ['option_a_image', 'option_b_image', 'option_c_image', 'option_d_image'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('m_question_banks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('t_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('t_assessments', 'has_rejection')) {
                $table->dropColumn('has_rejection');
            }
        });
    }
};
