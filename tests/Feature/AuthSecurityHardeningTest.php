<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\RegistrationTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_responses(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_register_enforces_password_complexity(): void
    {
        // 1. Less than 8 characters
        $resShort = $this->post('/register', [
            'full_name' => 'User Test',
            'email' => 'short@example.com',
            'password' => 'Pass1',
            'password_confirmation' => 'Pass1',
        ]);
        $resShort->assertSessionHasErrors(['password']);

        // 2. Letters only (no numbers)
        $resLetters = $this->post('/register', [
            'full_name' => 'User Test',
            'email' => 'letters@example.com',
            'password' => 'PasswordOnly',
            'password_confirmation' => 'PasswordOnly',
        ]);
        $resLetters->assertSessionHasErrors(['password']);

        // 3. Numbers only (no letters)
        $resNumbers = $this->post('/register', [
            'full_name' => 'User Test',
            'email' => 'numbers@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $resNumbers->assertSessionHasErrors(['password']);

        // 4. Valid password
        $resValid = $this->post('/register', [
            'full_name' => 'User Test',
            'email' => 'valid@example.com',
            'password' => 'Kompeten86',
            'password_confirmation' => 'Kompeten86',
            'registration_token' => RegistrationTokenService::getCurrentToken(),
        ]);
        $resValid->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'valid@example.com']);
    }

    public function test_login_rate_limiting_triggers_after_max_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'wrong@example.com',
                'password' => 'wrongpassword123',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt must be throttled with 429
        $throttledResponse = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword123',
        ]);
        $throttledResponse->assertStatus(429);
    }

    public function test_register_rate_limiting_triggers_after_max_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->app['auth']->guard()->logout();
            $this->post('/register', [
                'full_name' => 'Spam Bot '.$i,
                'email' => "bot{$i}@example.com",
                'password' => 'Kompeten86',
                'password_confirmation' => 'Kompeten86',
            ]);
        }

        $this->app['auth']->guard()->logout();

        // 6th attempt must be throttled
        $throttledResponse = $this->post('/register', [
            'full_name' => 'Spam Bot 6',
            'email' => 'bot6@example.com',
            'password' => 'Kompeten86',
            'password_confirmation' => 'Kompeten86',
        ]);
        $throttledResponse->assertStatus(429);
    }

    public function test_schedule_join_rate_limiting_triggers(): void
    {
        $asesi = User::factory()->create(['role' => 'ASESI']);
        $admin = User::factory()->create(['role' => 'ADMIN_LSP']);
        $scheme = Scheme::create([
            'code' => 'SKM-JOIN-01',
            'name' => 'Skema Join',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);
        $schedule = Schedule::create([
            'code' => 'SCH-JOIN-01',
            'name' => 'Jadwal Join',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
            'selected_instruments' => ['AK01'],
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($asesi)->post(route('asesi.jadwal.join'), [
                'schedule_id' => $schedule->id,
            ]);
        }

        // 11th attempt must be throttled with 429
        $throttledResponse = $this->actingAs($asesi)->post(route('asesi.jadwal.join'), [
            'schedule_id' => $schedule->id,
        ]);
        $throttledResponse->assertStatus(429);
    }

    public function test_multiple_different_users_from_same_ip_can_login_concurrently(): void
    {
        // Simulate a classroom / TUK with 15 students on the same IP (NAT)
        for ($i = 1; $i <= 15; $i++) {
            $user = User::factory()->create([
                'email' => "student{$i}@school.id",
                'password' => Hash::make('Kompeten86'),
                'role' => 'ASESI',
                'status' => 'ACTIVE',
            ]);

            $response = $this->post('/login', [
                'email' => "student{$i}@school.id",
                'password' => 'Kompeten86',
            ]);

            $response->assertRedirect();
            $this->app['auth']->guard()->logout();
        }
    }
}
