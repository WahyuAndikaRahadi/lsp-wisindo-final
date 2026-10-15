<?php

namespace App\Http\Controllers\Asesi;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentApl02Item;
use App\Models\AssessmentProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AplController extends Controller
{
    public function show(Assessment $assessment): View
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $assessment->load([
            'schedule.scheme.competencyUnits.elements.kuks',
            'proofs',
            'apl02Items',
        ]);

        $profile = auth()->user()->profile;

        return view('asesi.apl', compact('assessment', 'profile'));
    }

    public function store(Assessment $assessment, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $proofCategories = [
            'IJAZAH_SMK' => 'PERSYARATAN_DASAR',
            'SURAT_SISWA_AKTIF' => 'PERSYARATAN_DASAR',
            'IJAZAH_SMA_SERTIFIKAT' => 'PERSYARATAN_DASAR',
            'PENGALAMAN_KERJA' => 'PERSYARATAN_DASAR',
            'KTP' => 'ADMINISTRATIF',
            'PAS_FOTO' => 'ADMINISTRATIF',
        ];

        foreach ($proofCategories as $type => $category) {
            $file = $request->file("proofs.{$type}")
                ?? $request->file(strtolower($type))
                ?? $request->file("proof_{$type}")
                ?? $request->file('proof_'.strtolower($type));

            if ($file) {
                $path = $file->store('proofs', 'public');
                AssessmentProof::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'proof_type' => $type],
                    [
                        'proof_category' => $category,
                        'file_path' => $path,
                        'original_filename' => $file->getClientOriginalName(),
                    ]
                );
            }
        }

        $savedProofTypes = $assessment->proofs()->pluck('proof_type')->toArray();
        $hasKtp = in_array('KTP', $savedProofTypes);
        $hasPasFoto = in_array('PAS_FOTO', $savedProofTypes);
        $hasDasar = (bool) array_intersect(
            ['IJAZAH_SMK', 'SURAT_SISWA_AKTIF', 'IJAZAH_SMA_SERTIFIKAT', 'PENGALAMAN_KERJA'],
            $savedProofTypes
        );

        if (! $hasKtp || ! $hasPasFoto || ! $hasDasar) {
            return back()->withErrors([
                'proofs' => 'Dokumen belum lengkap. KTP dan Pas Foto wajib diunggah, serta minimal 1 bukti persyaratan dasar.',
            ]);
        }

        $request->validate([
            'statement_integrity' => 'accepted',
        ], [
            'statement_integrity.accepted' => 'Pernyataan kejujuran dan integritas asesmen mandiri (FR.APL.02) wajib dicentang.',
        ]);

        $kukInputs = $request->input('kuks', $request->input('apl02', []));
        foreach ($kukInputs as $kukId => $data) {
            $val = is_array($data) ? ($data['assessment_value'] ?? $data['value'] ?? 'K') : $data;
            $proofType = is_array($data)
                ? ($data['selected_proof_type'] ?? $data['proof_type'] ?? null)
                : $request->input("proof_type.{$kukId}");

            if (in_array($val, ['K', 'BK'])) {
                AssessmentApl02Item::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'kuk_id' => $kukId],
                    [
                        'assessment_value' => $val,
                        'selected_proof_type' => $proofType,
                    ]
                );
            }
        }

        $assessment->update([
            'apl_status' => 'SUBMITTED',
            'last_active_step' => 'apl',
        ]);

        return redirect()->route('asesi.assessment.apl', $assessment)->with('success', 'Menunggu verifikasi asesor');
    }
}
