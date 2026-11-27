<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CompetencyUnit;
use App\Models\Kuk;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\Setting;
use App\Models\Tuk;
use App\Models\UnitElement;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeederAndWorkflowResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_seeding_creates_master_data_and_zero_workflow_rows(): void
    {
        // Execute clean seeding
        Artisan::call('db:seed');

        // 1. Verify Master & Account Data Exists
        $this->assertGreaterThanOrEqual(16, User::count());
        $this->assertGreaterThanOrEqual(5, Scheme::count());
        $this->assertGreaterThan(0, CompetencyUnit::count());
        $this->assertGreaterThan(0, UnitElement::count());
        $this->assertGreaterThan(0, Kuk::count());
        $this->assertGreaterThan(0, QuestionBank::count());
        $this->assertGreaterThanOrEqual(1, Setting::count());

        // Verify production account roles exist (Admin & Asesor only, zero dummy Asesi/TUK)
        $this->assertNotNull(User::where('role', 'SUPER_ADMIN')->first());
        $this->assertNotNull(User::where('role', 'ADMIN_LSP')->first());
        $this->assertNotNull(User::where('role', 'ASESOR')->first());
        $this->assertNotNull(User::where('role', 'TUK_ADMIN')->first());

        // 2. Verify ALL Workflow Tables are Exactly EMPTY (0 rows)
        $workflowTables = [
            't_schedules',
            't_schedule_assessors',
            't_assessments',
            't_assessment_proofs',
            't_assessment_apl02_items',
            't_assessment_ak02_items',
            't_assessment_ak03_items',
            't_assessment_ak07_items',
            't_assessment_ia_responses',
            't_assessment_appeals',
        ];

        foreach ($workflowTables as $table) {
            $this->assertSame(
                0,
                DB::table($table)->count(),
                "Tabel workflow [{$table}] harus berisi 0 baris setelah db:seed, tetapi ditemukan data."
            );
        }
    }

    public function test_lsp_reset_workflow_command_clears_workflow_tables_and_preserves_master_tables(): void
    {
        $this->seed();

        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first() ?? User::create([
            'full_name' => 'Asesi Test',
            'email' => 'asesi.test@example.com',
            'phone' => '08999999999',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'password' => bcrypt('password'),
        ]);
        $scheme = Scheme::first();

        // Create dummy workflow transactional data
        $schedule = Schedule::create([
            'code' => 'SCH-RESET-TEST',
            'name' => 'Jadwal yang akan di-reset',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA02'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'scheme_id' => $scheme->id,
            'status' => 'IN_PROGRESS',
            'apl_status' => 'SUBMITTED',
            'admin_status' => 'APPROVED',
        ]);

        $this->assertGreaterThan(0, Schedule::count());
        $this->assertGreaterThan(0, Assessment::count());
        $userCountBefore = User::count();
        $schemeCountBefore = Scheme::count();

        // Execute command with --force
        $exitCode = Artisan::call('lsp:reset-workflow', ['--force' => true]);
        $this->assertSame(Command::SUCCESS, $exitCode);

        // Assert all workflow tables are reset to 0
        $this->assertSame(0, Schedule::count());
        $this->assertSame(0, Assessment::count());
        $this->assertSame(0, DB::table('t_schedule_assessors')->count());

        // Assert master data is strictly preserved
        $this->assertSame($userCountBefore, User::count());
        $this->assertSame($schemeCountBefore, Scheme::count());
    }

    public function test_lsp_reset_workflow_command_refuses_to_run_in_production_without_force(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $exitCode = Artisan::call('lsp:reset-workflow');
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function test_user_seeder_works_in_production_with_seed_password(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        putenv('SEED_PASSWORD=@Kompeten86');
        $_ENV['SEED_PASSWORD'] = '@Kompeten86';

        (new UserSeeder)->run();
        $this->assertDatabaseHas('users', ['email' => 'adminlsp@gmail.com']);
        $this->assertDatabaseHas('users', ['email' => 'superadmin@gmail.com']);
    }
}
