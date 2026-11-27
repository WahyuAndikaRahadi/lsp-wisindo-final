<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSuperAdminLinkedSignatureTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminLsp;

    private string $sampleSignature;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sampleSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $this->superAdmin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
            'full_name' => 'Bambang Super Admin',
            'email' => 'superadmin@lsp-wisindo.test',
            'password' => Hash::make('password123'),
        ]);

        $this->adminLsp = User::factory()->create([
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
            'full_name' => 'Siti Admin LSP',
            'email' => 'adminlsp@lsp-wisindo.test',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_super_admin_can_update_profile_and_master_signature(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.profile.update'), [
            'full_name' => 'Bambang Super Admin, S.T., M.Kom.',
            'email' => 'superadmin.updated@lsp-wisindo.test',
            'phone' => '081234567890',
            'signature_data' => $this->sampleSignature,
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $response->assertSessionHas('success');

        $this->superAdmin->refresh();
        $this->assertEquals('Bambang Super Admin, S.T., M.Kom.', $this->superAdmin->full_name);
        $this->assertEquals($this->sampleSignature, $this->superAdmin->profile?->signature_data);
    }

    public function test_super_admin_signature_is_automatically_synced_to_admin_lsp_profile(): void
    {
        // 1. Super Admin updates master signature
        $this->actingAs($this->superAdmin)->post(route('admin.profile.update'), [
            'full_name' => $this->superAdmin->full_name,
            'email' => $this->superAdmin->email,
            'signature_data' => $this->sampleSignature,
        ]);

        // 2. Admin LSP profile must now have the exact same signature
        $this->adminLsp->refresh();
        $this->assertNotNull($this->adminLsp->profile);
        $this->assertEquals($this->sampleSignature, $this->adminLsp->profile->signature_data);
    }

    public function test_admin_lsp_sees_disabled_and_locked_signature_linked_to_super_admin(): void
    {
        // Give Super Admin a master signature
        UserProfile::updateOrCreate(
            ['user_id' => $this->superAdmin->id],
            ['signature_data' => $this->sampleSignature]
        );

        $response = $this->actingAs($this->adminLsp)->get(route('admin.profile.edit'));

        $response->assertOk();
        $response->assertSee('Terhubung ke Super Admin');
        $response->assertSee('Dikelola oleh Super Admin');
        $response->assertSee('Mode Terkunci (Hanya Dapat Diubah Super Admin)');
        $response->assertSee('Simpan Profil');
        $response->assertDontSee('Simpan Profil & Tanda Tangan');
        $response->assertSee('Terkunci');
    }

    public function test_admin_lsp_can_update_biodata_and_password_without_overwriting_signature(): void
    {
        // Give Super Admin a master signature
        UserProfile::updateOrCreate(
            ['user_id' => $this->superAdmin->id],
            ['signature_data' => $this->sampleSignature]
        );

        // Admin LSP attempts to pass an empty or malicious signature string in request
        $response = $this->actingAs($this->adminLsp)->post(route('admin.profile.update'), [
            'full_name' => 'Siti Admin LSP Baru',
            'email' => 'siti.baru@lsp-wisindo.test',
            'phone' => '089876543210',
            'jabatan' => 'Koordinator Administrasi Uji',
            'signature_data' => 'attacker_hacked_signature',
            'current_password' => 'password123',
            'new_password' => 'newsecretpassword123',
            'new_password_confirmation' => 'newsecretpassword123',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $response->assertSessionHas('success');

        $this->adminLsp->refresh();
        $this->assertEquals('Siti Admin LSP Baru', $this->adminLsp->full_name);
        $this->assertEquals('siti.baru@lsp-wisindo.test', $this->adminLsp->email);
        $this->assertEquals('089876543210', $this->adminLsp->phone);
        $this->assertTrue(Hash::check('newsecretpassword123', $this->adminLsp->password));

        // Signature must STILL be the Super Admin's master signature, NOT the attempted replacement
        $this->assertEquals($this->sampleSignature, $this->adminLsp->profile?->signature_data);
    }
}
