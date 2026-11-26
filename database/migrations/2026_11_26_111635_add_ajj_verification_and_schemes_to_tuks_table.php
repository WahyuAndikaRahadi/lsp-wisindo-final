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
        Schema::table('m_tuks', function (Blueprint $table) {
            if (! Schema::hasColumn('m_tuks', 'code')) {
                $table->string('code', 50)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('m_tuks', 'type')) {
                $table->enum('type', ['SEWAKTU', 'TEMPAT_KERJA', 'MANDIRI'])->default('SEWAKTU')->after('name');
            }
            if (! Schema::hasColumn('m_tuks', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('m_tuks', 'postal_code')) {
                $table->string('postal_code', 10)->nullable()->after('address');
            }
            if (! Schema::hasColumn('m_tuks', 'kode_provinsi')) {
                $table->string('kode_provinsi', 10)->nullable()->after('postal_code');
            }
            if (! Schema::hasColumn('m_tuks', 'nama_provinsi')) {
                $table->string('nama_provinsi', 100)->nullable()->after('kode_provinsi');
            }
            if (! Schema::hasColumn('m_tuks', 'kode_kota')) {
                $table->string('kode_kota', 10)->nullable()->after('nama_provinsi');
            }
            if (! Schema::hasColumn('m_tuks', 'nama_kota')) {
                $table->string('nama_kota', 100)->nullable()->after('kode_kota');
            }
            if (! Schema::hasColumn('m_tuks', 'pic_name')) {
                $table->string('pic_name')->nullable()->after('nama_kota');
            }
            if (! Schema::hasColumn('m_tuks', 'pic_phone')) {
                $table->string('pic_phone', 30)->nullable()->after('pic_name');
            }
            if (! Schema::hasColumn('m_tuks', 'pic_position')) {
                $table->string('pic_position', 100)->nullable()->after('pic_phone');
            }
            if (! Schema::hasColumn('m_tuks', 'verification_status')) {
                $table->enum('verification_status', ['UNSUBMITTED', 'PENDING_VERIFICATION', 'VERIFIED', 'REVISION'])
                    ->default('UNSUBMITTED')
                    ->after('is_active');
            }
            if (! Schema::hasColumn('m_tuks', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verification_status');
            }
            if (! Schema::hasColumn('m_tuks', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_notes');
            }
            if (! Schema::hasColumn('m_tuks', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('m_tuks', 'room_photos')) {
                $table->json('room_photos')->nullable()->after('verified_by');
            }
            if (! Schema::hasColumn('m_tuks', 'internet_data_photos')) {
                $table->json('internet_data_photos')->nullable()->after('room_photos');
            }
            if (! Schema::hasColumn('m_tuks', 'internet_connection_photos')) {
                $table->json('internet_connection_photos')->nullable()->after('internet_data_photos');
            }
            if (! Schema::hasColumn('m_tuks', 'equipment_photos')) {
                $table->json('equipment_photos')->nullable()->after('internet_connection_photos');
            }
            if (! Schema::hasColumn('m_tuks', 'room_video_path')) {
                $table->string('room_video_path', 500)->nullable()->after('equipment_photos');
            }
            if (! Schema::hasColumn('m_tuks', 'schema_documents')) {
                $table->json('schema_documents')->nullable()->after('room_video_path');
            }
        });

        if (! Schema::hasTable('m_tuk_schemes')) {
            Schema::create('m_tuk_schemes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tuk_id')->constrained('m_tuks')->cascadeOnDelete();
                $table->foreignId('scheme_id')->constrained('m_schemes')->cascadeOnDelete();
                $table->boolean('is_verified')->default(true);
                $table->timestamps();

                $table->unique(['tuk_id', 'scheme_id']);
            });
        }

        Schema::table('t_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('t_schedules', 'meeting_link')) {
                $table->string('meeting_link', 500)->nullable()->after('estimated_candidates');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('t_schedules', 'meeting_link')) {
                $table->dropColumn('meeting_link');
            }
        });

        Schema::dropIfExists('m_tuk_schemes');

        Schema::table('m_tuks', function (Blueprint $table) {
            $cols = [
                'code', 'type', 'email', 'postal_code', 'kode_provinsi', 'nama_provinsi',
                'kode_kota', 'nama_kota', 'pic_name', 'pic_phone', 'pic_position',
                'verification_status', 'verification_notes', 'verified_at', 'verified_by',
                'room_photos', 'internet_data_photos', 'internet_connection_photos',
                'equipment_photos', 'room_video_path', 'schema_documents',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('m_tuks', $col)) {
                    if ($col === 'verified_by') {
                        $table->dropConstrainedForeignId('verified_by');
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
