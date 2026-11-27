<?php

namespace Tests\Feature;

use App\Models\Scheme;
use App\Models\SchemeCommittee;
use App\Models\User;
use Database\Seeders\SchemeCommitteeSeeder;
use Database\Seeders\SchemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminSchemeCommitteeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $sampleSignature;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sampleSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $this->admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
            'full_name' => 'Super Admin Test',
            'email' => 'superadmin@wisindo.test',
        ]);
    }

    public function test_scheme_committee_seeder_populates_all_schemes(): void
    {
        // Seed schemes first
        $this->seed(SchemeSeeder::class);
        $this->seed(SchemeCommitteeSeeder::class);

        $schemes = Scheme::with(['penyusun', 'validators'])->get();
        $this->assertCount(5, $schemes);

        foreach ($schemes as $scheme) {
            $this->assertGreaterThanOrEqual(2, $scheme->penyusun->count(), "Scheme {$scheme->code} must have at least 2 penyusun");
            $this->assertGreaterThanOrEqual(3, $scheme->validators->count(), "Scheme {$scheme->code} must have at least 3 validators");

            // Verify attributes
            foreach ($scheme->penyusun as $penyusun) {
                $this->assertEquals('PENYUSUN', $penyusun->role);
                $this->assertNotEmpty($penyusun->name);
                $this->assertStringStartsWith('data:image/', $penyusun->signature);
            }

            foreach ($scheme->validators as $validator) {
                $this->assertEquals('VALIDATOR', $validator->role);
                $this->assertNotEmpty($validator->name);
                $this->assertStringStartsWith('data:image/', $validator->signature);
            }
        }

        // Specifically test CDP scheme
        $cdp = Scheme::where('code', 'SKM-CDP-01')->firstOrFail();
        $this->assertCount(2, $cdp->penyusun);
        $this->assertCount(3, $cdp->validators);
        $this->assertTrue($cdp->penyusun->contains('name', 'Efnu Nugroho'));
        $this->assertTrue($cdp->validators->contains('name', 'Panca Nugraha'));
    }

    public function test_admin_can_view_scheme_with_committees_in_show_page(): void
    {
        $scheme = Scheme::create([
            'code' => 'SKM-TEST-01',
            'name' => 'Skema Pengujian Show',
            'scheme_type' => 'KLASTER',
            'version' => '1.0',
            'passing_grade_theory' => 75.00,
            'is_active' => true,
        ]);

        SchemeCommittee::create([
            'scheme_id' => $scheme->id,
            'role' => 'PENYUSUN',
            'name' => 'Budi Penyusun',
            'no_met' => 'MET.001.2023',
            'signature' => $this->sampleSignature,
            'sequence' => 1,
        ]);

        SchemeCommittee::create([
            'scheme_id' => $scheme->id,
            'role' => 'VALIDATOR',
            'name' => 'Siti Validator',
            'no_met' => 'MET.002.2023',
            'signature' => $this->sampleSignature,
            'sequence' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.schemes.show', $scheme->id));

        $response->assertOk();
        $response->assertSee('Tim Penyusun');
        $response->assertSee('Validator Dokumen MUK');
        $response->assertSee('Budi Penyusun');
        $response->assertSee('MET.001.2023');
        $response->assertSee('Siti Validator');
        $response->assertSee('MET.002.2023');
    }

    public function test_admin_can_create_scheme_with_multiple_penyusun_and_validators(): void
    {
        $payload = [
            'code' => 'SKM-NEW-01',
            'name' => 'Skema Baru Tim Komite',
            'scheme_type' => 'KKNI',
            'version' => '2026',
            'description' => 'Deskripsi skema baru',
            'passing_grade_theory' => 75,
            'is_active' => 1,
            'penyusun' => [
                [
                    'name' => 'Penyusun Satu',
                    'no_met' => 'MET.111.2026',
                    'signature' => $this->sampleSignature,
                ],
                [
                    'name' => 'Penyusun Dua',
                    'no_met' => 'MET.222.2026',
                    'signature' => $this->sampleSignature,
                ],
            ],
            'validator' => [
                [
                    'name' => 'Validator Satu',
                    'no_met' => 'MET.333.2026',
                    'signature' => $this->sampleSignature,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.schemes.store'), $payload);

        $scheme = Scheme::where('code', 'SKM-NEW-01')->firstOrFail();
        $response->assertRedirect(route('admin.schemes.show', $scheme->id));

        $this->assertDatabaseHas('m_schemes', [
            'code' => 'SKM-NEW-01',
            'name' => 'Skema Baru Tim Komite',
        ]);

        $this->assertDatabaseHas('m_scheme_committees', [
            'scheme_id' => $scheme->id,
            'role' => 'PENYUSUN',
            'name' => 'Penyusun Satu',
            'no_met' => 'MET.111.2026',
            'sequence' => 1,
        ]);

        $this->assertDatabaseHas('m_scheme_committees', [
            'scheme_id' => $scheme->id,
            'role' => 'PENYUSUN',
            'name' => 'Penyusun Dua',
            'no_met' => 'MET.222.2026',
            'sequence' => 2,
        ]);

        $this->assertDatabaseHas('m_scheme_committees', [
            'scheme_id' => $scheme->id,
            'role' => 'VALIDATOR',
            'name' => 'Validator Satu',
            'no_met' => 'MET.333.2026',
            'sequence' => 1,
        ]);

        $this->assertCount(2, $scheme->penyusun);
        $this->assertCount(1, $scheme->validators);
    }

    public function test_admin_can_create_scheme_with_uploaded_signature_file(): void
    {
        $fakeFile = UploadedFile::fake()->image('signature.png', 100, 50);

        $payload = [
            'code' => 'SKM-FILE-01',
            'name' => 'Skema Signature Upload',
            'scheme_type' => 'KLASTER',
            'is_active' => 1,
            'penyusun' => [
                [
                    'name' => 'Asesor File Upload',
                    'no_met' => 'MET.999.2026',
                    'signature_file' => $fakeFile,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.schemes.store'), $payload);

        $scheme = Scheme::where('code', 'SKM-FILE-01')->firstOrFail();
        $response->assertRedirect(route('admin.schemes.show', $scheme->id));

        $committee = SchemeCommittee::where('scheme_id', $scheme->id)
            ->where('role', 'PENYUSUN')
            ->firstOrFail();

        $this->assertEquals('Asesor File Upload', $committee->name);
        $this->assertNotNull($committee->signature);
        $this->assertStringStartsWith('data:image/png;base64,', $committee->signature);
    }

    public function test_admin_can_update_scheme_committees(): void
    {
        $scheme = Scheme::create([
            'code' => 'SKM-EDIT-01',
            'name' => 'Skema Sebelum Edit',
            'scheme_type' => 'KLASTER',
            'version' => '1.0',
            'passing_grade_theory' => 75.00,
            'is_active' => true,
        ]);

        SchemeCommittee::create([
            'scheme_id' => $scheme->id,
            'role' => 'PENYUSUN',
            'name' => 'Penyusun Lama',
            'no_met' => 'MET.OLD.001',
            'signature' => $this->sampleSignature,
            'sequence' => 1,
        ]);

        SchemeCommittee::create([
            'scheme_id' => $scheme->id,
            'role' => 'VALIDATOR',
            'name' => 'Validator Lama',
            'no_met' => 'MET.OLD.002',
            'signature' => $this->sampleSignature,
            'sequence' => 1,
        ]);

        $updatePayload = [
            'code' => 'SKM-EDIT-01',
            'name' => 'Skema Setelah Edit',
            'scheme_type' => 'OKUPASI',
            'is_active' => 1,
            'penyusun' => [
                [
                    'name' => 'Penyusun Baru A',
                    'no_met' => 'MET.NEW.001',
                    'signature' => $this->sampleSignature,
                ],
                [
                    'name' => 'Penyusun Baru B',
                    'no_met' => 'MET.NEW.002',
                    'signature' => $this->sampleSignature,
                ],
            ],
            'validator' => [
                [
                    'name' => 'Validator Baru A',
                    'no_met' => 'MET.NEW.003',
                    'signature' => $this->sampleSignature,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.schemes.update', $scheme->id), $updatePayload);

        $response->assertRedirect(route('admin.schemes.show', $scheme->id));

        $this->assertDatabaseMissing('m_scheme_committees', [
            'scheme_id' => $scheme->id,
            'name' => 'Penyusun Lama',
        ]);

        $this->assertDatabaseMissing('m_scheme_committees', [
            'scheme_id' => $scheme->id,
            'name' => 'Validator Lama',
        ]);

        $this->assertDatabaseHas('m_scheme_committees', [
            'scheme_id' => $scheme->id,
            'role' => 'PENYUSUN',
            'name' => 'Penyusun Baru A',
            'sequence' => 1,
        ]);

        $this->assertDatabaseHas('m_scheme_committees', [
            'scheme_id' => $scheme->id,
            'role' => 'PENYUSUN',
            'name' => 'Penyusun Baru B',
            'sequence' => 2,
        ]);

        $this->assertDatabaseHas('m_scheme_committees', [
            'scheme_id' => $scheme->id,
            'role' => 'VALIDATOR',
            'name' => 'Validator Baru A',
            'sequence' => 1,
        ]);
    }

    public function test_blank_committee_rows_are_filtered_out(): void
    {
        $payload = [
            'code' => 'SKM-BLANK-01',
            'name' => 'Skema Blank Row Test',
            'scheme_type' => 'KLASTER',
            'is_active' => 1,
            'penyusun' => [
                [
                    'name' => '  ',
                    'no_met' => '',
                    'signature' => null,
                ],
                [
                    'name' => 'Penyusun Valid',
                    'no_met' => 'MET.VALID.001',
                    'signature' => $this->sampleSignature,
                ],
            ],
            'validator' => [
                [
                    'name' => '',
                    'no_met' => 'MET.BLANK',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.schemes.store'), $payload);

        $scheme = Scheme::where('code', 'SKM-BLANK-01')->firstOrFail();
        $response->assertRedirect(route('admin.schemes.show', $scheme->id));

        $this->assertCount(1, $scheme->penyusun);
        $this->assertEquals('Penyusun Valid', $scheme->penyusun->first()->name);
        $this->assertEquals(1, $scheme->penyusun->first()->sequence);
        $this->assertCount(0, $scheme->validators);
    }
}
