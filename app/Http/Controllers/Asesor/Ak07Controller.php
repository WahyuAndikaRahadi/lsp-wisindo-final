<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\AssessmentAk07Item;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class Ak07Controller extends Controller
{
    use AuthorizesAssessor;

    public function index(Schedule $schedule, Request $request): View|RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        $schedule->load('scheme', 'assessments.asesi.profile', 'assessments.ak07Items', 'assessors');

        $user = auth()->user();
        $isSingleAssessor = $schedule->assessors->count() === 1 && $schedule->assessors->first()?->id === $user?->id;

        $myAssessments = $schedule->assessments
            ->filter(function ($assessment) use ($user, $isSingleAssessor) {
                if (! $user || $user->role !== 'ASESOR') {
                    return true;
                }

                return (int) $assessment->assessor_id === (int) $user->id || (empty($assessment->assessor_id) && $isSingleAssessor);
            })
            ->values();

        if (! $schedule->isStepUnlocked('ak07')) {
            return redirect()->route('asesor.schedule.ak01', $schedule->id)
                ->with('warning', 'Lengkapi dan simpan persetujuan FR.AK.01 terlebih dahulu sebelum melanjutkan ke FR.AK.07.');
        }

        $jadwal = $schedule;
        $assessments = $myAssessments;
        $selectedAssessmentId = $request->query('assessment');

        return view('asesor.ak07', compact('schedule', 'jadwal', 'assessments', 'selectedAssessmentId'));
    }

    public function store(Schedule $schedule, Request $request): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if ($schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        $schedule->load('assessments', 'assessors');

        $user = auth()->user();
        $isSingleAssessor = $schedule->assessors->count() === 1 && $schedule->assessors->first()?->id === $user?->id;

        $myAssessments = $schedule->assessments
            ->filter(function ($assessment) use ($user, $isSingleAssessor) {
                if (! $user || $user->role !== 'ASESOR') {
                    return true;
                }

                return (int) $assessment->assessor_id === (int) $user->id || (empty($assessment->assessor_id) && $isSingleAssessor);
            })
            ->values();

        if (! $schedule->isStepUnlocked('ak07')) {
            return redirect()->route('asesor.schedule.ak01', $schedule->id)
                ->with('warning', 'Lengkapi dan simpan persetujuan FR.AK.01 terlebih dahulu sebelum menyimpan FR.AK.07.');
        }

        $rawIds = array_map('intval', (array) $request->input('assessment_ids', $request->input('asesi_ids', [])));
        $myAssessmentIds = $myAssessments->pluck('id')->toArray();
        $ids = ! empty($rawIds) ? array_values(array_intersect($rawIds, $myAssessmentIds)) : [];

        // If asesor submits without selecting any asesi or adjustments (unfilled/optional)
        if (empty($ids)) {
            foreach ($myAssessments as $assessment) {
                AssessmentAk07Item::firstOrCreate([
                    'assessment_id' => $assessment->id,
                ], [
                    'adjustment_category' => 'Standar',
                    'adjustment_detail' => 'Tidak ada penyesuaian khusus yang diperlukan (asesmen standar).',
                    'assessor_id' => auth()->id(),
                ]);
            }

            $schedule->update([
                'ak07_data' => [
                    'saved_at' => now()->toIso8601String(),
                    'assessor_id' => auth()->id(),
                    'adjustment_type' => 'STANDAR',
                ],
            ]);

            return redirect()->route('asesor.schedule.ruang-uji', $schedule->id)
                ->with('info', 'FR.AK07 ditetapkan tanpa penyesuaian khusus. Masuk ke Ruang Uji.');
        }

        $items = $request->input('items', []);
        $agreementStatus = $request->input('agreement_status');
        $agreementNotes = $request->input('agreement_notes');
        $legacyCategory = $request->input('category');
        $legacyNotes = $request->input('notes');

        $savedAny = false;

        DB::transaction(function () use (
            $ids,
            $items,
            $agreementStatus,
            $agreementNotes,
            $legacyCategory,
            $legacyNotes,
            &$savedAny
        ) {
            // Idempotency: clear previous AK07 items for selected assessments to avoid duplicates
            AssessmentAk07Item::whereIn('assessment_id', $ids)->delete();

            // Process 8-item table format if present
            if (! empty($items) && is_array($items)) {
                foreach ($items as $itemIndex => $itemData) {
                    $needed = strtoupper($itemData['needed'] ?? '');
                    if ($needed === 'YA') {
                        $selectedOptions = $itemData['options'] ?? [];
                        $customNote = trim($itemData['custom_note'] ?? '');
                        $descParts = [];
                        if (! empty($selectedOptions)) {
                            $descParts[] = implode('; ', $selectedOptions);
                        }
                        if ($customNote !== '') {
                            $descParts[] = "Catatan: {$customNote}";
                        }
                        $detailText = ! empty($descParts) ? implode(' | ', $descParts) : 'Penyesuaian wajar disetujui';

                        $categoryName = $itemData['title'] ?? "Karakteristik #{$itemIndex}";

                        foreach ($ids as $assessmentId) {
                            AssessmentAk07Item::updateOrCreate(
                                [
                                    'assessment_id' => $assessmentId,
                                    'adjustment_category' => substr($categoryName, 0, 100),
                                ],
                                [
                                    'adjustment_detail' => $detailText,
                                    'assessor_id' => auth()->id(),
                                ]
                            );
                        }
                        $savedAny = true;
                    }
                }
            }

            // Process agreement notes or legacy format
            if ($legacyCategory && $legacyNotes) {
                foreach ($ids as $assessmentId) {
                    AssessmentAk07Item::updateOrCreate(
                        [
                            'assessment_id' => $assessmentId,
                            'adjustment_category' => substr($legacyCategory, 0, 100),
                        ],
                        [
                            'adjustment_detail' => $legacyNotes,
                            'assessor_id' => auth()->id(),
                        ]
                    );
                }
                $savedAny = true;
            } elseif ($agreementStatus === 'YA' && ! empty($agreementNotes)) {
                foreach ($ids as $assessmentId) {
                    AssessmentAk07Item::updateOrCreate(
                        [
                            'assessment_id' => $assessmentId,
                            'adjustment_category' => 'Kesepakatan Asesor-Asesi',
                        ],
                        [
                            'adjustment_detail' => $agreementNotes,
                            'assessor_id' => auth()->id(),
                        ]
                    );
                }
                $savedAny = true;
            }

            if (! $savedAny) {
                foreach ($ids as $assessmentId) {
                    AssessmentAk07Item::firstOrCreate([
                        'assessment_id' => $assessmentId,
                    ], [
                        'adjustment_category' => 'Standar',
                        'adjustment_detail' => 'Tidak ada penyesuaian khusus yang diperlukan (asesmen standar).',
                        'assessor_id' => auth()->id(),
                    ]);
                }
            }
        });

        $schedule->update([
            'ak07_data' => [
                'saved_at' => now()->toIso8601String(),
                'assessor_id' => auth()->id(),
                'adjustment_type' => $savedAny ? 'CUSTOM' : 'STANDAR',
                'assessment_ids' => $ids,
            ],
        ]);

        $msg = $savedAny
            ? 'Penyesuaian yang wajar (FR.AK07) berhasil disimpan. Masuk ke Ruang Uji.'
            : 'FR.AK07 disimpan tanpa penyesuaian khusus. Masuk ke Ruang Uji.';

        return redirect()->route('asesor.schedule.ruang-uji', $schedule->id)->with('success', $msg);
    }
}
