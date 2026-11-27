<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NavbarAndAccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesi;

    protected User $asesor;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asesi = User::factory()->create([
            'full_name' => 'Ani Rahayu Asesi',
            'email' => 'ani.asesi@example.com',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'password' => Hash::make('password123'),
        ]);

        UserProfile::create([
            'user_id' => $this->asesi->id,
            'nik' => '3201012345678901',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '2000-01-01',
            'jenis_kelamin' => 'P',
            'kebangsaan' => 'Indonesia',
            'alamat_rumah' => 'Jl. Merdeka No. 10',
            'pendidikan_terakhir' => 'SMA/Sederajat',
            'jabatan' => 'Staff',
            'signature_data' => 'data:image/png;base64,sample',
        ]);

        $this->asesor = User::factory()->create([
            'full_name' => 'Agil Ardiansyah Asesor',
            'email' => 'agil.asesor@example.com',
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'password' => Hash::make('password123'),
            'assessor_registration_number' => 'MET.000.002986 2023',
        ]);

        $this->admin = User::factory()->create([
            'full_name' => 'Admin LSP Wisindo',
            'email' => 'admin@example.com',
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_navbar_renders_profile_avatar_and_removes_date(): void
    {
        $response = $this->actingAs($this->asesi)->get(route('asesi.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="navbar-profile-button"', false);
        $response->assertSee('Ani Rahayu Asesi');
        $response->assertSee(route('account.edit'));
        $response->assertSee('Email &amp; Ganti Password', false);

        // Date element should no longer be present in header
        $currentDateString = now()->isoFormat('D MMMM Y');
        $response->assertDontSee('<span>'.$currentDateString.'</span>', false);
    }

    public function test_navbar_for_admin_links_akun_to_admin_profile_edit(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="navbar-profile-button"', false);
        $response->assertSee('Admin LSP Wisindo');
        $response->assertSee(route('admin.profile.edit'));
    }

    public function test_sidebar_does_not_contain_bottom_profile_and_logout(): void
    {
        $response = $this->actingAs($this->asesi)->get(route('asesi.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Sidebar Bottom: User Profile & Quick Actions', false);
        $response->assertDontSee('title="Keluar dari Sistem"', false);
    }

    public function test_asesi_and_asesor_can_view_account_page(): void
    {
        $responseAsesi = $this->actingAs($this->asesi)->get(route('account.edit'));
        $responseAsesi->assertStatus(200);
        $responseAsesi->assertSee('Pengaturan Akun Login');
        $responseAsesi->assertSee('Alamat Email Login');
        $responseAsesi->assertSee('Ubah Kata Sandi (Password)');
        $responseAsesi->assertSee($this->asesi->email);

        $responseAsesor = $this->actingAs($this->asesor)->get(route('account.edit'));
        $responseAsesor->assertStatus(200);
        $responseAsesor->assertSee('Pengaturan Akun Login');
        $responseAsesor->assertSee($this->asesor->email);
    }

    public function test_admin_is_redirected_to_admin_profile_edit_when_visiting_account_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('account.edit'));
        $response->assertRedirect(route('admin.profile.edit'));
    }

    public function test_user_can_update_email_only(): void
    {
        $response = $this->actingAs($this->asesi)->put(route('account.update'), [
            'email' => 'new.ani.email@example.com',
        ]);

        $response->assertRedirect(route('account.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->asesi->id,
            'email' => 'new.ani.email@example.com',
        ]);
    }

    public function test_user_can_update_email_and_password_successfully(): void
    {
        $response = $this->actingAs($this->asesor)->put(route('account.update'), [
            'email' => 'agil.new@example.com',
            'current_password' => 'password123',
            'new_password' => 'newsecretpass123',
            'new_password_confirmation' => 'newsecretpass123',
        ]);

        $response->assertRedirect(route('account.edit'));
        $response->assertSessionHas('success');

        $this->asesor->refresh();
        $this->assertSame('agil.new@example.com', $this->asesor->email);
        $this->assertTrue(Hash::check('newsecretpass123', $this->asesor->password));
    }

    public function test_password_update_requires_valid_current_password(): void
    {
        $response = $this->actingAs($this->asesi)->from(route('account.edit'))->put(route('account.update'), [
            'email' => $this->asesi->email,
            'current_password' => 'wrongpassword',
            'new_password' => 'newsecretpass123',
            'new_password_confirmation' => 'newsecretpass123',
        ]);

        $response->assertRedirect(route('account.edit'));
        $response->assertSessionHasErrors('current_password');

        $this->asesi->refresh();
        $this->assertTrue(Hash::check('password123', $this->asesi->password));
    }

    public function test_password_update_requires_matching_confirmation(): void
    {
        $response = $this->actingAs($this->asesi)->from(route('account.edit'))->put(route('account.update'), [
            'email' => $this->asesi->email,
            'current_password' => 'password123',
            'new_password' => 'newsecretpass123',
            'new_password_confirmation' => 'differentpassword',
        ]);

        $response->assertRedirect(route('account.edit'));
        $response->assertSessionHasErrors('new_password');
    }

    public function test_password_update_requires_minimum_eight_characters(): void
    {
        $response = $this->actingAs($this->asesi)->from(route('account.edit'))->put(route('account.update'), [
            'email' => $this->asesi->email,
            'current_password' => 'password123',
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ]);

        $response->assertRedirect(route('account.edit'));
        $response->assertSessionHasErrors('new_password');
    }

    public function test_email_must_be_unique(): void
    {
        $response = $this->actingAs($this->asesi)->from(route('account.edit'))->put(route('account.update'), [
            'email' => $this->asesor->email, // already used by asesor
        ]);

        $response->assertRedirect(route('account.edit'));
        $response->assertSessionHasErrors('email');
    }

    public function test_biodata_pages_do_not_contain_password_fields(): void
    {
        // Asesi biodata page
        $responseAsesi = $this->actingAs($this->asesi)->get(route('asesi.profile.edit'));
        $responseAsesi->assertStatus(200);
        $responseAsesi->assertDontSee('name="current_password"', false);
        $responseAsesi->assertDontSee('name="new_password"', false);
        $responseAsesi->assertDontSee('name="password"', false);

        // Asesor biodata page
        $responseAsesor = $this->actingAs($this->asesor)->get(route('asesor.profile.edit'));
        $responseAsesor->assertStatus(200);
        $responseAsesor->assertDontSee('name="current_password"', false);
        $responseAsesor->assertDontSee('name="new_password"', false);
        $responseAsesor->assertDontSee('name="password"', false);
    }
}
