<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\AkWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Schedule::with('scheme', 'assessors', 'creator')
            ->withCount('assessments')
            ->latest('date');

        if ($user && $user->role === 'TUK_ADMIN') {
            $user->loadMissing('tuk');
            $query->where(function ($q) use ($user) {
                if ($user->tuk_id) {
                    $q->where('tuk_id', $user->tuk_id);
                }
                if ($user->tuk?->name) {
                    $q->orWhere('tuk_name', $user->tuk->name);
                }
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('scheme_id')) {
            $query->where('scheme_id', $request->input('scheme_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        $schedules = $query->paginate(15)->withQueryString();
        $schemes = Scheme::orderBy('name')->get();

        if ($request->ajax()) {
            return view('admin.jadwal._table', compact('schedules'));
        }

        return view('admin.jadwal.index', compact('schedules', 'schemes'));
    }

    public function create(): View
    {
        $schemes = Scheme::active()->get();
        $assessors = User::where('role', 'ASESOR')->where('status', 'ACTIVE')->orderBy('full_name', 'asc')->get();

        return view('admin.jadwal.create', compact('schemes', 'assessors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scheme_id' => 'required|exists:m_schemes,id',
            'tuk_name' => 'nullable|string|max:255',
            'tuk_address' => 'nullable|string|max:500',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'estimated_candidates' => 'nullable|integer|min:0',
            'surat_tugas_number' => 'nullable|string|max:100',
            'surat_tugas_date' => 'nullable|date',
            'letter_city' => 'nullable|string|max:100',
            'director_name' => 'nullable|string|max:100',
            'selected_instruments' => 'nullable|array',
            'notes' => 'nullable|string',
            'assessor_ids' => 'nullable|array',
            'assessors' => 'nullable|array',
            'code' => 'nullable|string|max:50',
        ]);

        $code = $request->input('code') ?: strtoupper(Str::random(8));

        $schedule = Schedule::create([
            'name' => $validated['name'],
            'scheme_id' => $validated['scheme_id'],
            'tuk_name' => $validated['tuk_name'] ?? null,
            'tuk_address' => ! empty($validated['tuk_address']) ? $validated['tuk_address'] : '-',
            'date' => $validated['date'],
            'end_date' => ! empty($validated['end_date']) ? $validated['end_date'] : $validated['date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'estimated_candidates' => $validated['estimated_candidates'] ?? 0,
            'surat_tugas_number' => $validated['surat_tugas_number'] ?? null,
            'surat_tugas_date' => ! empty($validated['surat_tugas_date']) ? $validated['surat_tugas_date'] : $validated['date'],
            'letter_city' => ! empty($validated['letter_city']) ? $validated['letter_city'] : 'Bekasi',
            'director_name' => ! empty($validated['director_name']) ? $validated['director_name'] : 'RIDWAN',
            'selected_instruments' => $validated['selected_instruments'] ?? ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
            'notes' => $validated['notes'] ?? null,
            'code' => $code,
            'status' => 'ACTIVE',
            'created_by' => auth()->id(),
        ]);

        if (empty($schedule->surat_tugas_number)) {
            $schedule->updateQuietly([
                'surat_tugas_number' => $schedule->generateDefaultSuratTugasNumber(),
            ]);
        }

        $assessorList = $request->input('assessor_ids', $request->input('assessors', []));
        if (! empty($assessorList)) {
            $schedule->assessors()->sync($assessorList);
        }

        return redirect()->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil dibuat.');
    }

    public function show(Request $request, Schedule $jadwal): View
    {
        $jadwal->load('scheme', 'assessors');

        $assessmentsQuery = $jadwal->assessments()
            ->with(['asesi.profile', 'assessor', 'latestAppeal']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $assessmentsQuery->whereHas('asesi', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($pq) use ($search) {
                        $pq->where('nik', 'like', "%{$search}%");
                    });
            });
        }

        $assessments = $assessmentsQuery->latest('registered_at')->paginate(15)->withQueryString();
        $jadwal->setRelation('assessments', $assessments);

        if ($request->ajax()) {
            return view('admin.jadwal._peserta_table', compact('jadwal', 'assessments'));
        }

        return view('admin.jadwal.show', compact('jadwal', 'assessments'));
    }

    public function edit(Schedule $jadwal): View
    {
        $schemes = Scheme::active()->get();
        $assessors = User::where('role', 'ASESOR')->where('status', 'ACTIVE')->orderBy('full_name', 'asc')->get();
        $jadwal->load('assessors');

        return view('admin.jadwal.edit', compact('jadwal', 'schemes', 'assessors'));
    }

    public function update(Request $request, Schedule $jadwal): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scheme_id' => 'required|exists:m_schemes,id',
            'tuk_name' => 'nullable|string|max:255',
            'tuk_address' => 'nullable|string|max:500',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'estimated_candidates' => 'nullable|integer|min:0',
            'surat_tugas_number' => 'nullable|string|max:100',
            'surat_tugas_date' => 'nullable|date',
            'letter_city' => 'nullable|string|max:100',
            'director_name' => 'nullable|string|max:100',
            'selected_instruments' => 'nullable|array',
            'instruments' => 'nullable|array',
            'notes' => 'nullable|string',
            'assessor_ids' => 'nullable|array',
        ]);

        $instruments = $request->input('selected_instruments', $request->input('instruments', $jadwal->selected_instruments ?? ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05']));
        $validated['selected_instruments'] = $instruments;
        unset($validated['instruments']);

        if (empty($validated['surat_tugas_number'])) {
            $validated['surat_tugas_number'] = $jadwal->surat_tugas_number ?: $jadwal->generateDefaultSuratTugasNumber();
        }
        if (empty($validated['end_date'])) {
            $validated['end_date'] = $validated['date'];
        }
        if (empty($validated['tuk_address'])) {
            $validated['tuk_address'] = '-';
        }
        if (empty($validated['letter_city'])) {
            $validated['letter_city'] = 'Bekasi';
        }
        if (empty($validated['director_name'])) {
            $validated['director_name'] = 'RIDWAN';
        }

        $jadwal->update($validated);

        if ($request->has('assessor_ids')) {
            $jadwal->assessors()->sync($request->input('assessor_ids', []));
        }

        return redirect()->route('admin.jadwal.show', $jadwal->id)
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function suratTugas(Schedule $jadwal): View
    {
        $jadwal->load(['scheme', 'assessors', 'assessments']);

        return view('asesor.jadwal.surat-tugas', [
            'schedule' => $jadwal,
            'assessors' => $jadwal->assessors,
            'backUrl' => route('admin.jadwal.show', $jadwal->id),
        ]);
    }

    public function destroy(Schedule $jadwal): RedirectResponse
    {
        if ($jadwal->assessments()->exists()) {
            $jadwal->update(['status' => 'FINISHED']);

            return redirect()->route('admin.jadwal.index')
                ->with('warning', 'Jadwal tidak dapat dihapus permanen karena sudah memiliki peserta terdaftar. Status jadwal telah ditutup (FINISHED).');
        }

        $jadwal->assessors()->detach();
        $jadwal->delete();

        return redirect()->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    public function reviewRegistration(Assessment $assessment, Request $request): RedirectResponse
    {
        $status = strtoupper((string) ($request->input('admin_status') ?? $request->input('status') ?? 'APPROVED'));
        $notes = $request->input('admin_notes') ?? $request->input('notes');

        app(AkWorkflowService::class)->approveCandidateRegistration(
            $assessment,
            $status,
            $notes,
            auth()->id()
        );

        return back()->with('success', "Status pendaftaran asesi {$assessment->asesi->full_name} berhasil diperbarui menjadi {$status}.");
    }

    public function assignAssessor(Assessment $assessment, Request $request): RedirectResponse
    {
        $request->validate([
            'assessor_id' => ['required', 'exists:users,id'],
        ]);

        $schedule = $assessment->schedule;
        if (! $schedule || ! $schedule->assessors()->where('users.id', $request->assessor_id)->exists()) {
            return back()->with('error', 'Asesor yang dipilih tidak ditugaskan pada jadwal ini.');
        }

        $assessment->update([
            'assessor_id' => $request->assessor_id,
        ]);

        $assessorName = User::find($request->assessor_id)?->full_name ?? 'Asesor';

        return back()->with('success', "Asesi {$assessment->asesi->full_name} berhasil ditugaskan kepada {$assessorName}.");
    }

    public function batchReviewRegistration(Schedule $jadwal, Request $request): RedirectResponse
    {
        $status = strtoupper((string) ($request->input('admin_status') ?? 'APPROVED'));
        $notes = $request->input('admin_notes', 'Persetujuan batch verifikasi berkas admin');
        $workflowService = app(AkWorkflowService::class);
        $approvedCount = 0;
        $skippedCandidates = [];

        $jadwal->load('assessments.proofs', 'assessments.asesi');

        foreach ($jadwal->assessments as $assessment) {
            // When approving, only allow candidates with complete proofs (KTP + Pas Foto + Bukti Dasar)
            if ($status === 'APPROVED') {
                if (! $workflowService->isCandidateDocumentComplete($assessment)) {
                    $skippedCandidates[] = $assessment->asesi->full_name ?? "Asesi #{$assessment->id}";

                    continue;
                }
            }

            if (($assessment->admin_status ?? 'PENDING') !== $status) {
                $workflowService->approveCandidateRegistration(
                    $assessment,
                    $status,
                    $notes,
                    auth()->id()
                );
                $approvedCount++;
            }
        }

        if (count($skippedCandidates) > 0) {
            $skippedNames = implode(', ', $skippedCandidates);

            return back()->with('success', "Berhasil menyetujui {$approvedCount} peserta dengan berkas lengkap.")
                ->with('warning', 'Sebanyak '.count($skippedCandidates)." peserta dilewati karena berkas belum lengkap (KTP, Pas Foto, atau Bukti Dasar): {$skippedNames}.");
        }

        return back()->with('success', "Berhasil memperbarui status {$approvedCount} pendaftar menjadi {$status}.");
    }

    public function markAbsent(Assessment $assessment, Request $request): RedirectResponse
    {
        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        $notes = $request->input('notes') ?? $request->input('final_notes');
        app(AkWorkflowService::class)->markCandidateAbsent($assessment, $notes, auth()->id());

        return back()->with('success', "Asesi {$assessment->asesi->full_name} berhasil ditandai TIDAK HADIR.");
    }

    /**
     * Keluarkan asesi dari jadwal dan bersihkan seluruh data pengerjaan asesmen terkait.
     */
    public function removeCandidate(Schedule $jadwal, Assessment $assessment): RedirectResponse
    {
        if ($assessment->schedule_id !== $jadwal->id) {
            abort(404, 'Data asesi tidak ditemukan pada jadwal ini.');
        }

        $asesiName = $assessment->asesi?->full_name ?? "Asesi #{$assessment->id}";

        DB::transaction(function () use ($jadwal, $assessment) {
            // 1. Delete uploaded proof files
            foreach ($assessment->proofs as $proof) {
                if (! empty($proof->file_path) && Storage::disk('local')->exists($proof->file_path)) {
                    Storage::disk('local')->delete($proof->file_path);
                }
            }
            $assessment->proofs()->delete();

            // 2. Delete IA evidence files (e.g. IA01B report sheet evidence)
            foreach ($assessment->iaResponses as $ia) {
                $evidencePath = $ia->data_json['evidence_image_path'] ?? null;
                if ($evidencePath && Storage::disk('local')->exists($evidencePath)) {
                    Storage::disk('local')->delete($evidencePath);
                }
            }
            $assessment->iaResponses()->delete();

            // 3. Delete other child records
            $assessment->apl02Items()->delete();
            $assessment->ak02Items()->delete();
            $assessment->ak03Items()->delete();
            $assessment->ak07Items()->delete();
            $assessment->appeals()->delete();

            // 4. Delete certificate if any
            if ($assessment->certificate) {
                if (! empty($assessment->certificate->file_path) && Storage::disk('local')->exists($assessment->certificate->file_path)) {
                    Storage::disk('local')->delete($assessment->certificate->file_path);
                }
                $assessment->certificate()->delete();
            }

            // 5. Delete assessment record
            $assessment->delete();

            // 6. Invalidate schedule MUK PDF cache so BA, attendance lists, etc. regenerate without this candidate
            $cacheDir = storage_path('app/muk-cache');
            if (File::isDirectory($cacheDir)) {
                $pattern = $cacheDir."/dyn_{$jadwal->id}_*";
                foreach (File::glob($pattern) ?: [] as $cachedFile) {
                    File::delete($cachedFile);
                }
            }
        });

        return back()->with('success', "Asesi {$asesiName} berhasil dikeluarkan dari jadwal.");
    }

    /**
     * Buka kembali jadwal atau tutup dan kunci jadwal resmi (Admin Control).
     */
    public function toggleLock(Schedule $jadwal, AkWorkflowService $workflowService): RedirectResponse
    {
        if ($jadwal->is_closed) {
            $jadwal->update([
                'is_closed' => false,
                'reopened_at' => now(),
                'status' => 'ACTIVE',
            ]);

            return back()->with('success', "Jadwal {$jadwal->name} berhasil dibuka kembali. Seluruh data asesmen tetap aman tersimpan dan formulir kini dapat ditinjau atau diperbaiki.");
        }

        $readiness = $workflowService->isScheduleReadyForClosure($jadwal);
        if (! $readiness['is_ready']) {
            return back()->with('error', "Jadwal belum dapat ditutup: Masih ada {$readiness['uncompleted_count']} asesi yang belum selesai asesmennya.");
        }

        $jadwal->update([
            'is_closed' => true,
            'closed_at' => now(),
            'reopened_at' => null,
            'status' => 'FINISHED',
        ]);

        return back()->with('success', "Jadwal {$jadwal->name} berhasil ditutup dan dikunci resmi.");
    }
}
