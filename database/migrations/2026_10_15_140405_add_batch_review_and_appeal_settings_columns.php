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
        if (Schema::hasTable('t_assessments')) {
            Schema::table('t_assessments', function (Blueprint $table) {
                if (! Schema::hasColumn('t_assessments', 'admin_reviewed_at')) {
                    $table->timestamp('admin_reviewed_at')->nullable()->after('admin_approved_at');
                }
                if (! Schema::hasColumn('t_assessments', 'admin_reviewer_id')) {
                    $table->foreignId('admin_reviewer_id')->nullable()->constrained('users')->nullOnDelete()->after('admin_id');
                }
            });
        }

        if (Schema::hasTable('t_assessment_appeals')) {
            Schema::table('t_assessment_appeals', function (Blueprint $table) {
                if (! Schema::hasColumn('t_assessment_appeals', 'appeals_committee_chair')) {
                    $table->string('appeals_committee_chair', 255)->nullable()->after('decided_by');
                }
            });
        }

        if (! Schema::hasTable('m_settings')) {
            Schema::create('m_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('description', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('t_assessments')) {
            Schema::table('t_assessments', function (Blueprint $table) {
                if (Schema::hasColumn('t_assessments', 'admin_reviewer_id')) {
                    $table->dropForeign(['admin_reviewer_id']);
                    $table->dropColumn('admin_reviewer_id');
                }
                if (Schema::hasColumn('t_assessments', 'admin_reviewed_at')) {
                    $table->dropColumn('admin_reviewed_at');
                }
            });
        }

        if (Schema::hasTable('t_assessment_appeals')) {
            Schema::table('t_assessment_appeals', function (Blueprint $table) {
                if (Schema::hasColumn('t_assessment_appeals', 'appeals_committee_chair')) {
                    $table->dropColumn('appeals_committee_chair');
                }
            });
        }

        Schema::dropIfExists('m_settings');
    }
};
