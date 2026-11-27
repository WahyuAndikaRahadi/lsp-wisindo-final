<?php

namespace Tests\Feature;

use App\Models\Tuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProxyFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $asesor;

    protected User $asesi;

    protected User $adminLsp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'full_name' => 'Super Administrator',
            'email' => 'superadmin@example.com',
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->asesor = User::factory()->create([
            'full_name' => 'Budi Santoso Asesor',
            'email' => 'budi.asesor@example.com',
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->asesi = User::factory()->create([
            'full_name' => 'Siti Asesi Mandiri',
            'email' => 'siti.asesi@example.com',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->adminLsp = User::factory()->create([
            'full_name' => 'Andi Petugas LSP',
            'email' => 'andi.lsp@example.com',
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_can_filter_proxy_users_by_search_name_or_email(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.proxy.index', [
            'search' => 'Budi Santoso',
        ]));

        $response->assertOk();
        $response->assertSee('Budi Santoso Asesor');
        $response->assertDontSee('Siti Asesi Mandiri');
        $response->assertDontSee('Andi Petugas LSP');

        $responseEmail = $this->actingAs($this->admin)->get(route('admin.proxy.index', [
            'search' => 'siti.asesi@example.com',
        ]));

        $responseEmail->assertOk();
        $responseEmail->assertSee('Siti Asesi Mandiri');
        $responseEmail->assertDontSee('Budi Santoso Asesor');
    }

    public function test_admin_can_filter_proxy_users_by_role(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.proxy.index', [
            'role' => 'ASESOR',
        ]));

        $response->assertOk();
        $response->assertSee('Budi Santoso Asesor');
        $response->assertDontSee('Siti Asesi Mandiri');
        $response->assertDontSee('Andi Petugas LSP');

        $responseAsesi = $this->actingAs($this->admin)->get(route('admin.proxy.index', [
            'role' => 'ASESI',
        ]));

        $responseAsesi->assertOk();
        $responseAsesi->assertSee('Siti Asesi Mandiri');
        $responseAsesi->assertDontSee('Budi Santoso Asesor');
    }

    public function test_admin_can_combine_proxy_role_and_search_filter(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.proxy.index', [
            'role' => 'ASESOR',
            'search' => 'Siti',
        ]));

        $response->assertOk();
        $response->assertSee('Tidak ada data pengguna yang sesuai dengan pencarian atau filter');
        $response->assertDontSee('Siti Asesi Mandiri');
        $response->assertDontSee('Budi Santoso Asesor');
    }

    public function test_proxy_mode_hides_logout_button_and_shows_kembali_button(): void
    {
        // 1. In normal session, logout form is visible
        $normalResponse = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $normalResponse->assertOk();
        $normalResponse->assertSee(route('logout'));

        // 2. Switch to proxy mode as asesor
        $this->actingAs($this->admin)->post(route('proxy.switch', $this->asesor->id));

        // 3. Now accessing any page with admin_impersonator_id session
        $proxyResponse = $this->actingAs($this->asesor)
            ->withSession(['admin_impersonator_id' => $this->admin->id])
            ->get(route('asesor.jadwal.index'));

        $proxyResponse->assertOk();
        // Logout route should NOT be present in sidebar
        $proxyResponse->assertDontSee(route('logout'));
        // Switch back route should be present
        $proxyResponse->assertSee(route('proxy.switch-back'));
        $proxyResponse->assertSee('Kembali');
    }

    public function test_logout_is_prevented_while_in_proxy_mode(): void
    {
        $response = $this->actingAs($this->asesi)
            ->withSession(['admin_impersonator_id' => $this->admin->id])
            ->post(route('logout'));

        $response->assertRedirect();
        $response->assertSessionHas('warning');
        $this->assertTrue(auth()->check());
    }

    public function test_navbar_logout_is_rendered_in_dropdown_and_sidebar_bottom_profile_is_removed(): void
    {
        // 1. Super Admin
        $superAdminRes = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $superAdminRes->assertOk();
        $superAdminContent = $superAdminRes->getContent();
        $this->assertStringContainsString('Backup Database (SQL)', $superAdminContent);
        $this->assertStringContainsString('id="navbar-profile-button"', $superAdminContent);
        $this->assertStringContainsString('action="'.route('logout').'"', $superAdminContent);
        $this->assertStringNotContainsString('Sidebar Bottom: User Profile & Quick Actions', $superAdminContent);

        // 2. Asesi
        $asesiRes = $this->actingAs($this->asesi)->get(route('asesi.dashboard'));
        $asesiRes->assertOk();
        $asesiContent = $asesiRes->getContent();
        $this->assertStringContainsString('Sertifikat Saya', $asesiContent);
        $this->assertStringContainsString('id="navbar-profile-button"', $asesiContent);
        $this->assertStringContainsString('action="'.route('logout').'"', $asesiContent);
        $this->assertStringNotContainsString('Sidebar Bottom: User Profile & Quick Actions', $asesiContent);

        // 3. Asesor
        $asesorRes = $this->actingAs($this->asesor)->get(route('asesor.jadwal.index'));
        $asesorRes->assertOk();
        $asesorContent = $asesorRes->getContent();
        $this->assertStringContainsString('Jadwal Penugasan Uji', $asesorContent);
        $this->assertStringContainsString('id="navbar-profile-button"', $asesorContent);
        $this->assertStringContainsString('action="'.route('logout').'"', $asesorContent);
        $this->assertStringNotContainsString('Sidebar Bottom: User Profile & Quick Actions', $asesorContent);

        // 4. Admin LSP (non Super Admin)
        $adminLspRes = $this->actingAs($this->adminLsp)->get(route('admin.dashboard'));
        $adminLspRes->assertOk();
        $adminLspContent = $adminLspRes->getContent();
        $this->assertStringContainsString('Switch Role (Proxy)', $adminLspContent);
        $this->assertStringNotContainsString('Backup Database (SQL)', $adminLspContent);
        $this->assertStringContainsString('id="navbar-profile-button"', $adminLspContent);
        $this->assertStringContainsString('action="'.route('logout').'"', $adminLspContent);
        $this->assertStringNotContainsString('Sidebar Bottom: User Profile & Quick Actions', $adminLspContent);
    }

    public function test_admin_can_filter_and_impersonate_tuk_admin(): void
    {
        $tuk = Tuk::create([
            'name' => 'TUK Hotel Nusantara',
            'code' => 'TUK-NUSA-01',
            'verification_status' => 'VERIFIED',
        ]);

        $tukAdmin = User::factory()->create([
            'full_name' => 'Bambang Admin TUK',
            'email' => 'bambang.tuk@example.com',
            'role' => 'TUK_ADMIN',
            'status' => 'ACTIVE',
            'tuk_id' => $tuk->id,
        ]);

        // Filter by TUK_ADMIN role
        $response = $this->actingAs($this->admin)->get(route('admin.proxy.index', [
            'role' => 'TUK_ADMIN',
        ]));

        $response->assertOk();
        $response->assertSee('Bambang Admin TUK');
        $response->assertSee('Admin TUK');
        $response->assertDontSee('Budi Santoso Asesor');

        // Impersonate TUK_ADMIN
        $switchRes = $this->actingAs($this->admin)->post(route('proxy.switch', $tukAdmin->id));
        $switchRes->assertRedirect(route('tuk.dashboard'));
        $this->assertSame($tukAdmin->id, auth()->id());
        $this->assertSame($this->admin->id, session('admin_impersonator_id'));
    }
}
