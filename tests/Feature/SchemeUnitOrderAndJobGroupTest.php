<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CompetencyUnit;
use App\Models\JobGroup;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\MukDynamicDocumentService;
use Database\Seeders\SchemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchemeUnitOrderAndJobGroupTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $asesi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SchemeSeeder::class);

        $this->superAdmin = User::create([
            'full_name' => 'Super Admin Test',
            'email' => 'superadmin_jg@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Test',
            'email' => 'asesi_jg@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_all_five_schemes_seed_canonical_unit_order_and_ordered_job_groups(): void
    {
        $expected = [
            'SKM-CDP-01' => [
                'unit_count' => 40,
                'first_unit' => 'I.55HDR00.177.3',
                'group_count' => 5,
                'first_group_name' => 'Keselamatan, Kesehatan Kerja & P3K',
                'first_group_first_unit' => 'I.55HDR00.068.3',
            ],
            'SKM-CC-02' => [
                'unit_count' => 29,
                'first_unit' => 'I.55HDR00.068.3',
                'group_count' => 3,
                'first_group_name' => 'Penerapan K3 dalam Berinteraksi dengan Pelanggan',
                'first_group_first_unit' => 'I.55HDR00.068.3',
            ],
            'SKM-FO-03' => [
                'unit_count' => 31,
                'first_unit' => 'I.55HDR00.147.3',
                'group_count' => 5,
                'first_group_name' => 'Kerja Sama Tim & Keberagaman Sosial',
                'first_group_first_unit' => 'I.55HDR00.148.3',
            ],
            'SKM-HK-04' => [
                'unit_count' => 24,
                'first_unit' => 'I.55HDR00.005.3',
                'group_count' => 5,
                'first_group_name' => 'Berinteraksi dengan Pelanggan',
                'first_group_first_unit' => 'I.55HDR00.148.3',
            ],
            'SKM-FB-05' => [
                'unit_count' => 33,
                'first_unit' => 'I.55HDR00.009.3',
                'group_count' => 5,
                'first_group_name' => 'Penanganan dasar dalam keselamatan dan keamanan',
                'first_group_first_unit' => 'I.55HDR00.153.3',
            ],
        ];

        foreach ($expected as $code => $exp) {
            $scheme = Scheme::where('code', $code)->firstOrFail();

            $units = $scheme->competencyUnits;
            $this->assertCount($exp['unit_count'], $units);
            $this->assertSame(range(1, $exp['unit_count']), $units->pluck('sequence')->all());
            $this->assertSame($exp['first_unit'], $units->first()->code);

            $groups = $scheme->jobGroups()->with('competencyUnits')->get();
            $this->assertCount($exp['group_count'], $groups);
            $this->assertSame(range(1, $exp['group_count']), $groups->pluck('sequence')->all());
            $this->assertSame($exp['first_group_name'], $groups->first()->name);
            $this->assertSame($exp['first_group_first_unit'], $groups->first()->competencyUnits->first()->code);

            $groupedUnitCount = $groups->sum(fn (JobGroup $g) => $g->competencyUnits->count());
            $this->assertSame($exp['unit_count'], $groupedUnitCount);
        }
    }

    public function test_dynamic_payloads_use_canonical_apl_order_and_mapa_job_groups(): void
    {
        $scheme = Scheme::where('code', 'SKM-CDP-01')->firstOrFail();
        $schedule = Schedule::create([
            'code' => 'SCH-JG-TEST',
            'name' => 'Jadwal Test Order',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Hotel Wisindo',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $this->superAdmin->id,
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $this->asesi->id,
            'status' => 'IN_PROGRESS',
            'apl_status' => 'APPROVED',
        ]);

        $service = app(MukDynamicDocumentService::class);

        $apl01 = $service->buildApl01Data($schedule, $assessment);
        $this->assertSame('I.55HDR00.177.3', $apl01['units'][0][0]);
        $this->assertSame('I.55HDR00.068.3', $apl01['units'][1][0]);

        $apl02 = $service->buildApl02Data($schedule, $assessment);
        $this->assertSame('I.55HDR00.177.3', $apl02['units'][0]['code']);
        $this->assertSame('I.55HDR00.068.3', $apl02['units'][1]['code']);

        $mapa01 = $service->buildMapa01Data($schedule, $assessment);
        $this->assertCount(5, $mapa01['job_groups']);
        $this->assertSame(1, $mapa01['job_groups'][0]['sequence']);
        $this->assertSame('Keselamatan, Kesehatan Kerja & P3K', $mapa01['job_groups'][0]['name']);
        $this->assertSame('I.55HDR00.068.3', $mapa01['job_groups'][0]['units'][0]['code']);
        $this->assertSame(5, $mapa01['job_groups'][4]['sequence']);
        $this->assertSame('Mengolah & Menyajikan Makanan', $mapa01['job_groups'][4]['name']);
    }

    public function test_super_admin_can_manage_job_groups_and_unit_ordering_while_asesi_is_forbidden(): void
    {
        $scheme = Scheme::where('code', 'SKM-HK-04')->firstOrFail();

        $this->actingAs($this->asesi)
            ->post(route('admin.schemes.job-groups.store', $scheme), ['name' => 'Unauthorized Group', 'sequence' => 99])
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.schemes.show', $scheme))
            ->assertOk()
            ->assertSee('Kelompok Pekerjaan Skema / MAPA')
            ->assertSee('Berinteraksi dengan Pelanggan');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.schemes.job-groups.store', $scheme), [
                'name' => 'Kelompok Tambahan',
                'sequence' => 6,
            ])
            ->assertRedirect(route('admin.schemes.show', $scheme));

        $newGroup = JobGroup::where('scheme_id', $scheme->id)->where('sequence', 6)->firstOrFail();

        $unit = CompetencyUnit::where('scheme_id', $scheme->id)->firstOrFail();
        $this->actingAs($this->superAdmin)
            ->put(route('admin.schemes.units.update', $unit), [
                'code' => $unit->code,
                'title' => $unit->title,
                'standard_reference' => $unit->standard_reference,
                'sequence' => $unit->sequence,
                'job_group_id' => $newGroup->id,
                'job_group_sequence' => 1,
            ])
            ->assertRedirect(route('admin.schemes.show', $scheme));

        $unit->refresh();
        $this->assertSame($newGroup->id, $unit->job_group_id);
        $this->assertSame(1, $unit->job_group_sequence);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.schemes.job-groups.destroy', $newGroup))
            ->assertRedirect(route('admin.schemes.show', $scheme));

        $this->assertNull($unit->fresh()->job_group_id);
    }
}
