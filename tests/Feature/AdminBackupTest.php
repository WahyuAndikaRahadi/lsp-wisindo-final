<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function tearDown(): void
    {
        // Clean up test backup files from storage
        $service = app(DatabaseBackupService::class);
        $files = glob($service->getBackupDirectory().'/*test*.sql') ?: [];
        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.backups.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_backups(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();

        $this->actingAs($admin)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($asesor)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($asesi)->get(route('admin.backups.index'))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.backups.store'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.backups.export-live'))->assertForbidden();
    }

    public function test_superadmin_can_view_backups_page(): void
    {
        $superAdmin = User::where('role', 'SUPER_ADMIN')->first();

        $response = $this->actingAs($superAdmin)->get(route('admin.backups.index'));
        $response->assertOk();
        $response->assertSee('Backup Database Sistem');
        $response->assertSee('Download Langsung (.sql)');
        $response->assertSee('Buat Backup Baru');
    }

    public function test_superadmin_can_create_backup(): void
    {
        $superAdmin = User::where('role', 'SUPER_ADMIN')->first();

        $response = $this->actingAs($superAdmin)->post(route('admin.backups.store'), [
            'prefix' => 'test_feature_backup',
        ]);

        $response->assertRedirect(route('admin.backups.index'));
        $response->assertSessionHas('success');

        $service = app(DatabaseBackupService::class);
        $backups = $service->listBackups()->filter(fn ($b) => str_contains($b['filename'], 'test_feature_backup'));
        $this->assertNotEmpty($backups);

        $file = $backups->first();
        $this->assertFileExists($file['path']);
        $this->assertGreaterThan(0, $file['size_bytes']);

        $content = file_get_contents($file['path']);
        $this->assertStringContainsString('LSP Praktisi Otomotif Indonesia', $content);
        $this->assertStringContainsString('users', $content);
    }

    public function test_superadmin_can_download_backup(): void
    {
        $superAdmin = User::where('role', 'SUPER_ADMIN')->first();
        $service = app(DatabaseBackupService::class);
        $backup = $service->createBackup('test_dl_backup');

        $response = $this->actingAs($superAdmin)->get(route('admin.backups.download', $backup['filename']));
        $response->assertOk();
        $this->assertTrue($response->headers->get('content-disposition') !== null);
    }

    public function test_superadmin_can_stream_live_backup(): void
    {
        $superAdmin = User::where('role', 'SUPER_ADMIN')->first();

        $response = $this->actingAs($superAdmin)->get(route('admin.backups.export-live'));
        $response->assertOk();
        $this->assertTrue(str_contains((string) $response->headers->get('content-type'), 'sql'));
    }

    public function test_superadmin_can_delete_backup(): void
    {
        $superAdmin = User::where('role', 'SUPER_ADMIN')->first();
        $service = app(DatabaseBackupService::class);
        $backup = $service->createBackup('test_del_backup');

        $this->assertFileExists($backup['path']);

        $response = $this->actingAs($superAdmin)->delete(route('admin.backups.destroy', $backup['filename']));
        $response->assertRedirect(route('admin.backups.index'));
        $response->assertSessionHas('success');

        $this->assertFileDoesNotExist($backup['path']);
    }

    public function test_path_traversal_is_blocked_on_download(): void
    {
        $superAdmin = User::where('role', 'SUPER_ADMIN')->first();

        $response = $this->actingAs($superAdmin)->get(route('admin.backups.download', '../../.env'));
        $response->assertNotFound();
    }

    public function test_artisan_db_backup_command(): void
    {
        $this->artisan('db:backup', ['--prefix' => 'test_artisan_backup'])
            ->expectsOutputToContain('Starting database backup process')
            ->expectsOutputToContain('Backup successfully generated')
            ->assertExitCode(0);

        $service = app(DatabaseBackupService::class);
        $backups = $service->listBackups()->filter(fn ($b) => str_contains($b['filename'], 'test_artisan_backup'));
        $this->assertNotEmpty($backups);
    }
}
