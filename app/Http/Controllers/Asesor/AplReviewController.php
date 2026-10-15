<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AplReviewController extends Controller
{
    use AuthorizesAssessor;

    public function show(Assessment $assessment): View
    {
        $this->authorizeAssessorAssessment($assessment);

        $assessment->load([
            'asesi.profile',
            'schedule.scheme.competencyUnits.elements.kuks',
            'proofs',
            'apl02Items',
        ]);

        $profile = $assessment->asesi->profile;
        $scheme = $assessment->schedule->scheme;
        $proofs = $assessment->proofs;
        $apl02Items = $assessment->apl02Items->keyBy('kuk_id');

        return view('asesor.review-apl', compact(
            'assessment',
            'profile',
            'scheme',
            'proofs',
            'apl02Items'
        ));
    }

    public function decide(Assessment $assessment, Request $request): RedirectResponse
    {
        $this->authorizeAssessorAssessment($assessment);

        $status = strtoupper((string) ($request->input('apl_status') ?? $request->input('decision') ?? $request->input('action') ?? ''));
        if ($status === 'APPROVE') {
            $status = 'APPROVED';
        }
        if ($status === 'REVISION') {
            $status = 'REVISION_REQUESTED';
        }
        if ($status === 'REJECT') {
            $status = 'REJECTED';
        }

        if (! in_array($status, ['APPROVED', 'REVISION_REQUESTED', 'REJECTED'])) {
            return back()->withErrors(['apl_status' => 'Status keputusan tidak valid.']);
        }

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        if (in_array($status, ['APPROVED', 'REVISION_REQUESTED']) && ! in_array($assessment->apl_status, ['SUBMITTED', 'REVISION_REQUESTED', 'APPROVED'])) {
            return back()->with('error', 'Tidak dapat memproses permohonan APL yang belum diajukan oleh asesi (status masih DRAFT).');
        }

        if ($status === 'APPROVED' && $assessment->admin_status !== 'APPROVED') {
            return back()->with('error', 'Pendaftaran asesi belum disetujui oleh Admin LSP. Asesor baru dapat menyetujui APL setelah Admin menyetujui berkas pendaftaran.');
        }

        $notes = $request->input('notes') ?? $request->input('apl_revision_notes') ?? $request->input('catatan');

        DB::transaction(function () use ($assessment, $status, $notes) {
            $assessment->update([
                'apl_status' => $status,
                'apl_revision_notes' => $notes,
                'assessor_id' => auth()->id(),
                'approved_at' => $status === 'APPROVED' ? now() : null,
            ]);
        });

        if ($status === 'APPROVED') {
            $schedule = $assessment->schedule;
            $allApproved = $schedule && $schedule->assessments()->where('apl_status', '!=', 'APPROVED')->count() === 0;

            if ($allApproved) {
                return redirect()->route('asesor.ak07.create', [
                    'schedule' => $assessment->schedule_id,
                    'assessment' => $assessment->id,
                ])->with('success', 'Semua asesi dalam jadwal ini telah disetujui. Melanjutkan ke Formulir FR.AK07 (Penyesuaian Asesmen).');
            }

            return redirect()->route('asesor.jadwal.asesi', $assessment->schedule_id)
                ->with('success', 'APL asesi berhasil disetujui. Selesaikan verifikasi asesi lainnya sebelum melanjutkan ke FR.AK07.');
        }

        return redirect()->route('asesor.jadwal.asesi', $assessment->schedule_id)
            ->with('success', 'Keputusan review APL berhasil disimpan.');
    }
}
