<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    private function createSecurityFixtures(): array
    {
        $superAdmin = User::where('role', 'SUPER_ADMIN')->first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $asesor1 = User::where('role', 'ASESOR')->first();
        $asesor2 = User::create([
            'full_name' => 'Asesor Kedua',
            'email' => 'asesor2.'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $asesi1 = User::where('role', 'ASESI')->first();
        $asesi2 = User::create([
            'full_name' => 'Asesi Korban IDOR',
            'email' => 'asesi2.'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $scheme = Scheme::first();

        // Schedule 1 assigned ONLY to Asesor 1
        $schedule1 = Schedule::create([
            'code' => 'SCH-SEC-1-'.uniqid(),
            'name' => 'Schedule Keamanan 1',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule1->assessors()->attach($asesor1->id);

        // Schedule 2 assigned ONLY to Asesor 2
        $schedule2 = Schedule::create([
            'code' => 'SCH-SEC-2-'.uniqid(),
            'name' => 'Schedule Keamanan 2',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Bandung',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule2->assessors()->attach($asesor2->id);

        // Assessment 1: Asesi 1 in Schedule 1 (Assigned to Asesor 1)
        $assessment1 = Assessment::create([
            'schedule_id' => $schedule1->id,
            'asesi_id' => $asesi1->id,
            'assessor_id' => $asesor1->id,
            'scheme_id' => $scheme->id,
            'status' => 'IN_PROGRESS',
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
        ]);

        // Assessment 2: Asesi 2 in Schedule 2 (Assigned to Asesor 2)
        $assessment2 = Assessment::create([
            'schedule_id' => $schedule2->id,
            'asesi_id' => $asesi2->id,
            'assessor_id' => $asesor2->id,
            'scheme_id' => $scheme->id,
            'status' => 'IN_PROGRESS',
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
        ]);

        return compact(
            'superAdmin',
            'admin',
            'asesor1',
            'asesor2',
            'asesi1',
            'asesi2',
            'schedule1',
            'schedule2',
            'assessment1',
            'assessment2'
        );
    }

    public function test_asesi_cannot_access_other_candidates_assessment_idor(): void
    {
        $f = $this->createSecurityFixtures();
        $attacker = $f['asesi1'];
        $victimAssessment = $f['assessment2'];

        // 1. Cannot view APL of other candidate
        $resApl = $this->actingAs($attacker)->get(route('asesi.assessment.apl', $victimAssessment));
        $resApl->assertStatus(403);

        // 2. Cannot submit APL for other candidate
        $resAplPost = $this->actingAs($attacker)->post(route('asesi.assessment.apl.store', $victimAssessment), []);
        $resAplPost->assertStatus(403);

        // 3. Cannot view Ruang Uji hub of other candidate
        $resHub = $this->actingAs($attacker)->get(route('asesi.assessment.ruang-uji', $victimAssessment));
        $resHub->assertStatus(403);

        // 4. Cannot sign AK01 for other candidate
        $resSign = $this->actingAs($attacker)->post(route('asesi.assessment.ak01.sign', $victimAssessment), []);
        $resSign->assertStatus(403);

        // 5. Cannot view or submit CBT IA05 for other candidate
        $resCbt = $this->actingAs($attacker)->get(route('asesi.assessment.instrument.ia05', $victimAssessment));
        $resCbt->assertStatus(403);

        $resCbtPost = $this->actingAs($attacker)->post(route('asesi.assessment.instrument.ia05.submit', $victimAssessment), []);
        $resCbtPost->assertStatus(403);

        // 6. Cannot view AK03 or submit appeal for other candidate
        $resAk03 = $this->actingAs($attacker)->get(route('asesi.assessment.ak03', $victimAssessment));
        $resAk03->assertStatus(403);

        $resAppeal = $this->actingAs($attacker)->get(route('asesi.assessment.appeal', $victimAssessment));
        $resAppeal->assertStatus(403);
    }

    public function test_asesor_cannot_access_unassigned_schedules_or_candidates_idor(): void
    {
        $f = $this->createSecurityFixtures();
        $unassignedAsesor = $f['asesor1'];
        $schedule2 = $f['schedule2'];
        $assessment2 = $f['assessment2'];

        // 1. Cannot view AK.02 candidate list of unassigned schedule
        $resList = $this->actingAs($unassignedAsesor)->get(route('asesor.schedule.ak02', $schedule2));
        $resList->assertStatus(403);

        // 2. Cannot view Ruang Uji hub of unassigned schedule
        $resHub = $this->actingAs($unassignedAsesor)->get(route('asesor.schedule.ruang-uji', $schedule2));
        $resHub->assertStatus(403);

        // 3. Cannot review APL of unassigned candidate
        $resApl = $this->actingAs($unassignedAsesor)->get(route('asesor.apl.review', $assessment2));
        $resApl->assertStatus(403);

        $resAplDecide = $this->actingAs($unassignedAsesor)->post(route('asesor.apl.decide', $assessment2), [
            'decision' => 'APPROVED',
        ]);
        $resAplDecide->assertStatus(403);

        // 4. Cannot view or update AK02 for unassigned candidate
        $resAk02 = $this->actingAs($unassignedAsesor)->get(route('asesor.assessment.ak02', $assessment2));
        $resAk02->assertStatus(403);

        $resAk02Update = $this->actingAs($unassignedAsesor)->post(route('asesor.assessment.ak02.update', $assessment2), []);
        $resAk02Update->assertStatus(403);

        // 5. Cannot view or submit reports for unassigned schedule
        $resReports = $this->actingAs($unassignedAsesor)->get(route('asesor.schedule.reports', $schedule2));
        $resReports->assertStatus(403);
    }

    public function test_admin_cannot_escalate_to_superadmin_via_proxy_impersonation(): void
    {
        $f = $this->createSecurityFixtures();
        $admin = $f['admin'];
        $superAdmin = $f['superAdmin'];
        $asesor = $f['asesor1'];

        // 1. Escalating to Super Admin is rejected with 403
        $resEscalate = $this->actingAs($admin)->post(route('proxy.switch', $superAdmin));
        $resEscalate->assertStatus(403);
        $this->assertEquals($admin->id, auth()->id());

        // 2. Self impersonation is safely redirected with warning
        $resSelf = $this->actingAs($admin)->post(route('proxy.switch', $admin));
        $resSelf->assertRedirect();
        $resSelf->assertSessionHas('warning');

        // 3. Legitimate impersonation of Asesor succeeds
        $resLegit = $this->actingAs($admin)->post(route('proxy.switch', $asesor));
        $resLegit->assertRedirect(route('asesor.jadwal.index'));
        $this->assertEquals($asesor->id, auth()->id());
        $this->assertEquals($admin->id, session('admin_impersonator_id'));

        // 4. Switch-back returns safely to Admin
        $resBack = $this->post(route('proxy.switch-back'));
        $resBack->assertRedirect(route('admin.proxy.index'));
        $this->assertEquals($admin->id, auth()->id());
        $this->assertNull(session('admin_impersonator_id'));
    }

    public function test_dangerous_files_and_oversized_uploads_are_rejected(): void
    {
        $f = $this->createSecurityFixtures();
        $asesi = $f['asesi1'];
        $assessment = $f['assessment1'];
        $assessment->update(['apl_status' => 'DRAFT']);

        // 1. PHP executable file rejected
        $badFile = UploadedFile::fake()->create('exploit.php', 100, 'application/x-php');
        $resPhp = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'proofs' => ['KTP' => $badFile],
        ]);
        $resPhp->assertSessionHasErrors(['proofs.KTP']);

        // 2. Shell script file rejected
        $badSh = UploadedFile::fake()->create('script.sh', 50, 'application/x-sh');
        $resSh = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'proofs' => ['PAS_FOTO' => $badSh],
        ]);
        $resSh->assertSessionHasErrors(['proofs.PAS_FOTO']);

        // 3. Oversized file (> 15MB) rejected
        $oversized = UploadedFile::fake()->create('huge_portfolio.pdf', 16000, 'application/pdf');
        $resLarge = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'proofs' => ['IJAZAH_SMK' => $oversized],
        ]);
        $resLarge->assertSessionHasErrors(['proofs.IJAZAH_SMK']);
    }

    public function test_signature_validation_enforces_base64_image_format_and_size_limits(): void
    {
        $f = $this->createSecurityFixtures();
        $asesi = $f['asesi1'];

        // 1. XSS string rejected as signature
        $resXss = $this->actingAs($asesi)->post(route('asesi.profile.update'), [
            'signature_data' => '<script>alert("XSS")</script>',
        ]);
        $resXss->assertSessionHasErrors(['signature_data']);

        // 2. Plain text string rejected
        $resPlain = $this->actingAs($asesi)->post(route('asesi.profile.update'), [
            'signature_data' => 'bukan_data_url_gambar',
        ]);
        $resPlain->assertSessionHasErrors(['signature_data']);

        // 3. Oversized signature (> 500 KB) rejected
        $largeBase64Payload = 'data:image/png;base64,'.base64_encode(str_repeat('A', 550000));
        $resOversized = $this->actingAs($asesi)->post(route('asesi.profile.update'), [
            'signature_data' => $largeBase64Payload,
        ]);
        $resOversized->assertSessionHasErrors(['signature_data']);

        // 4. Valid PNG base64 data URL accepted
        $validPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $resValid = $this->actingAs($asesi)->post(route('asesi.profile.update'), [
            'signature_data' => $validPng,
        ]);
        $resValid->assertRedirect(route('asesi.profile.edit'));
        $resValid->assertSessionHas('success');
    }
}
