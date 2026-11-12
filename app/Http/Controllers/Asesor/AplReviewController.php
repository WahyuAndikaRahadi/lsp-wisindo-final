<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Schedule;
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

        if ($assessment->schedule && $assessment->schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        if (in_array($status, ['APPROVED', 'REVISION_REQUESTED']) && ! in_array($assessment->apl_status, ['SUBMITTED', 'REVISION_REQUESTED', 'APPROVED'])) {
            return back()->with('error', 'Tidak dapat memproses permohonan APL yang belum diajukan oleh asesi (status masih DRAFT).');
        }

        if ($assessment->admin_status === 'REJECTED') {
            return back()->with('error', 'Pendaftaran asesi telah ditolak oleh Admin LSP.');
        }

        $notes = $request->input('notes') ?? $request->input('apl_revision_notes') ?? $request->input('catatan');

        DB::transaction(function () use ($assessment, $status, $notes) {
            $assessorId = $assessment->assessor_id ?? auth()->id();
            $now = now();

            $assessment->update([
                'apl_status' => $status,
                'apl_revision_notes' => $notes,
                'assessor_id' => $assessorId,
                'approved_at' => $status === 'APPROVED' ? $now : null,
                'admin_status' => $status === 'REJECTED' ? 'REJECTED' : 'APPROVED',
                'status' => $status === 'REJECTED' ? 'REJECTED' : $assessment->status,
            ]);

            if ($status === 'APPROVED') {
                $assessment->update([
                    'last_active_step' => 'ak01',
                ]);
            }
        });

        $msg = match ($status) {
            'APPROVED' => 'APL asesi berhasil disetujui.',
            'REJECTED' => 'Permohonan APL asesi berhasil ditolak dan asesi telah dikeluarkan dari jadwal asesmen ini.',
            default => 'Permintaan revisi APL berhasil dikirim ke asesi.',
        };

        return redirect()->route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'apl'])
            ->with('success', $msg);
    }

    public function bulkApprove(Schedule $schedule, Request $request): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if ($schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        $assessmentIds = $request->input('assessment_ids', []);
        if (empty($assessmentIds)) {
            return back()->with('error', 'Silakan pilih setidaknya satu asesi untuk disetujui APL-nya.');
        }

        $user = auth()->user();
        $isSingleAssessor = $schedule->assessors->count() === 1 && $schedule->assessors->first()?->id === $user->id;

        $assessments = $schedule->assessments()
            ->whereIn('id', $assessmentIds)
            ->where('admin_status', '!=', 'REJECTED')
            ->get()
            ->filter(function ($a) use ($user, $isSingleAssessor) {
                if ((int) $a->assessor_id === (int) $user->id) {
                    return true;
                }
                if (empty($a->assessor_id) && $isSingleAssessor) {
                    return true;
                }

                return false;
            });

        if ($assessments->isEmpty()) {
            return back()->with('error', 'Tidak ada asesi binaan yang valid untuk disetujui.');
        }

        $now = now();
        $approvedCount = 0;

        DB::transaction(function () use ($assessments, $user, $now, &$approvedCount) {
            foreach ($assessments as $assessment) {
                $assessorId = $assessment->assessor_id ?? $user->id;

                $assessment->update([
                    'apl_status' => 'APPROVED',
                    'apl_revision_notes' => null,
                    'assessor_id' => $assessorId,
                    'approved_at' => $now,
                    'admin_status' => 'APPROVED',
                    'last_active_step' => 'ak01',
                ]);

                $approvedCount++;
            }
        });

        return redirect()->route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => 'apl'])
            ->with('success', "Sebanyak {$approvedCount} asesi berhasil disetujui (verifikasi APL selesai).");
    }
}
