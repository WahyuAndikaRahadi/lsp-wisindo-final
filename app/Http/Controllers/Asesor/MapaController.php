<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MapaController extends Controller
{
    use AuthorizesAssessor;

    public function index(Schedule $schedule, Request $request): View|RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if (! $schedule->isStepUnlocked('mapa')) {
            return redirect()->route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => 'apl'])
                ->with('warning', 'Harap selesaikan peninjauan seluruh berkas APL asesi terlebih dahulu sebelum mengisi FR.MAPA.01.');
        }

        $schedule->load(['scheme.competencyUnits', 'assessments.asesi.profile', 'assessors']);
        $assessments = $schedule->assessorAssessments();
        $selectedAssessmentId = $request->query('assessment');

        // Existing saved MAPA data
        $mapaData = $schedule->mapa01_data ?? [];

        // Exact options from BNSP FR.MAPA.01 standard
        $pendekatanOptions = [
            'Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan fasilitas praktek mampu telusur terhadap standar kompetensi',
            'Hasil pelatihan dan / atau pendidikan, dimana kurikulum belum berbasis kompetensi.',
            'Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yang dalam operasionalnya mampu telusur dengan standar kompetensi',
            'Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yang dalam operasionalnya belum berbasis kompetensi.',
            'Pelatihan / belajar mandiri atau otodidak.',
        ];

        $tujuanOptions = [
            'Sertifikasi',
            'Pengakuan Kompetensi Terkini (PKT)',
            'Rekognisi Pembelajaran Lampau',
            'Lainnya',
        ];

        $lingkunganOptions = [
            'Tempat kerja simulasi',
            'Tempat kerja nyata',
        ];

        $peluangBuktiOptions = [
            'Tersedia',
            'Terbatas',
        ];

        $hubunganStandarOptions = [
            'Bukti untuk mendukung asesmen',
            'Aktivitas kerja di tempat kerja Asesi',
            'Kegiatan Pembelajaran',
        ];

        $pelaksanaOptions = [
            'Lembaga Sertifikasi LSP Praktisi Otomotif Indonesia',
            'Organisasi Pelatihan',
            'Asesor Perusahaan',
        ];

        $orangRelevanOptions = [
            'Manajer sertifikasi',
            'Master Asesor / Master Trainer / Lead Asesor Kompetensi',
            'Manajer Pelatihan Lembaga Training terakreditasi / Lembaga Training terdaftar',
            'Manajer atau supervisor ditempat kerja',
        ];

        // Default integrated tasks if none saved
        $defaultIntegratedTasks = [
            'Melaksanakan Komunikasi di Tempat Kerja',
            'Melaksanakan Pemeliharaan Komponen',
            'Membaca Gambar Teknik',
            'Menggunakan Alat Ukur',
            'Menggunakan Peralatan dan Perlengkapan Tempat Kerja',
        ];

        return view('asesor.mapa', compact(
            'schedule',
            'assessments',
            'selectedAssessmentId',
            'mapaData',
            'pendekatanOptions',
            'tujuanOptions',
            'lingkunganOptions',
            'peluangBuktiOptions',
            'hubunganStandarOptions',
            'pelaksanaOptions',
            'orangRelevanOptions',
            'defaultIntegratedTasks'
        ));
    }

    public function store(Schedule $schedule, Request $request): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if ($schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        $request->validate([
            'pendekatan_asesi' => ['required', 'string'],
            'tujuan_asesmen' => ['required', 'string'],
            'konteks_lingkungan' => ['required', 'string'],
            'peluang_bukti' => ['required', 'string'],
            'hubungan_standar' => ['required', 'string'],
            'pelaksana_asesmen' => ['required', 'string'],
            'orang_relevan' => ['nullable', 'array'],
            'standar_tolok_ukur' => ['nullable', 'string', 'max:255'],
            'has_karakteristik' => ['nullable', 'in:0,1'],
            'karakteristik_detail' => ['nullable', 'string', 'max:1000'],
            'has_kontekstualisasi' => ['nullable', 'in:0,1'],
            'kontekstualisasi_detail' => ['nullable', 'string', 'max:1000'],
            'has_saran' => ['nullable', 'in:0,1'],
            'saran_detail' => ['nullable', 'string', 'max:1000'],
            'has_penyesuaian' => ['nullable', 'in:0,1'],
            'penyesuaian_detail' => ['nullable', 'string', 'max:1000'],
            'has_terintegrasi' => ['nullable', 'in:0,1'],
            'terintegrasi_detail' => ['nullable', 'string', 'max:1000'],
            'integrated_tasks' => ['nullable', 'array'],
            'assessment_ids' => ['nullable', 'array'],
        ]);

        $hasTerintegrasi = (string) $request->input('has_terintegrasi', '1') === '1';
        $integratedTasks = $hasTerintegrasi
            ? array_values(array_filter(array_map('trim', (array) $request->input('integrated_tasks', []))))
            : [];

        $mapaData = [
            // 1. Menentukan Pendekatan Asesmen
            'pendekatan_asesi' => $request->input('pendekatan_asesi'),
            'tujuan_asesmen' => $request->input('tujuan_asesmen'),
            'konteks_lingkungan' => $request->input('konteks_lingkungan'),
            'peluang_bukti' => $request->input('peluang_bukti'),
            'hubungan_standar' => $request->input('hubungan_standar'),
            'pelaksana_asesmen' => $request->input('pelaksana_asesmen'),
            'orang_relevan' => (array) $request->input('orang_relevan', []),
            'standar_tolok_ukur' => $request->input('standar_tolok_ukur') ?? $schedule->scheme?->name,

            // 2. Rencana Asesmen link note
            'rencana_asesmen_note' => 'Rencana asesmen dan pemilihan instrumen terintegrasi pada FR.AK.01',

            // 3. Mengidentifikasi Persyaratan Modifikasi dan Kontekstualisasi
            'has_karakteristik' => (string) $request->input('has_karakteristik', '0') === '1',
            'karakteristik_detail' => $request->input('karakteristik_detail'),
            'has_kontekstualisasi' => (string) $request->input('has_kontekstualisasi', '0') === '1',
            'kontekstualisasi_detail' => $request->input('kontekstualisasi_detail'),
            'has_saran' => (string) $request->input('has_saran', '0') === '1',
            'saran_detail' => $request->input('saran_detail'),
            'has_penyesuaian' => (string) $request->input('has_penyesuaian', '0') === '1',
            'penyesuaian_detail' => $request->input('penyesuaian_detail'),
            'has_terintegrasi' => $hasTerintegrasi,
            'terintegrasi_detail' => $request->input('terintegrasi_detail'),
            'integrated_tasks' => $integratedTasks,

            'updated_at' => now()->toIso8601String(),
            'updated_by_assessor_id' => auth()->id(),
        ];

        $schedule->load(['assessments', 'assessors']);
        $myAssessments = $schedule->assessorAssessments();

        $selectedIds = array_map('intval', (array) $request->input('assessment_ids', []));
        $targetAssessments = ! empty($selectedIds)
            ? $myAssessments->whereIn('id', $selectedIds)
            : $myAssessments;

        DB::transaction(function () use ($schedule, $targetAssessments, $mapaData) {
            foreach ($targetAssessments as $assessment) {
                $assessment->update([
                    'mapa01_data' => $mapaData,
                ]);
            }

            $schedule->update([
                'mapa01_data' => $mapaData,
            ]);
        });

        return redirect()->route('asesor.schedule.ak04', $schedule->id)
            ->with('success', 'FR.MAPA.01 Merencanakan Aktivitas dan Proses Asesmen berhasil disimpan. Melanjutkan ke FR.AK.04.');
    }
}
