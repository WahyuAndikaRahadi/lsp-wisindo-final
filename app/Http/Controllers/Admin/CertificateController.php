<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\Schedule;
use App\Models\Scheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    /**
     * Display a listing of schedules with competent candidates ready for certificates.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $schemeId = $request->query('scheme_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $schedulesQuery = Schedule::with(['scheme', 'tuk'])
            ->withCount([
                'assessments as total_candidates',
                'assessments as competent_candidates' => function ($q) {
                    $q->where('final_result', 'KOMPETEN');
                },
                'certificates as uploaded_certificates_count',
            ])
            ->latest('date');

        if ($schemeId) {
            $schedulesQuery->where('scheme_id', $schemeId);
        }

        if ($search) {
            $schedulesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('tuk_name', 'like', "%{$search}%");
            });
        }

        if (! empty($startDate) && ! empty($endDate)) {
            $from = min($startDate, $endDate);
            $to = max($startDate, $endDate);
            $schedulesQuery->whereBetween('date', [$from, $to]);
        } elseif (! empty($startDate)) {
            $schedulesQuery->whereDate('date', '>=', $startDate);
        } elseif (! empty($endDate)) {
            $schedulesQuery->whereDate('date', '<=', $endDate);
        }

        $schedules = $schedulesQuery->paginate(15)->withQueryString();
        $schemes = Scheme::active()->orderBy('name')->get();

        if ($request->ajax()) {
            return view('admin.certificates._table', compact('schedules', 'search', 'schemeId', 'startDate', 'endDate'));
        }

        return view('admin.certificates.index', compact('schedules', 'schemes', 'search', 'schemeId', 'startDate', 'endDate'));
    }

    /**
     * Manage certificates for a specific schedule.
     */
    public function scheduleCertificates(Schedule $schedule): View
    {
        $schedule->load([
            'scheme',
            'tuk',
            'assessments' => function ($q) {
                $q->with(['asesi.profile', 'certificate'])
                    ->where('final_result', 'KOMPETEN');
            },
        ]);

        $competentAssessments = $schedule->assessments;

        return view('admin.certificates.schedule-certificates', compact('schedule', 'competentAssessments'));
    }

    /**
     * Store or update a certificate for a competent candidate.
     */
    public function store(Request $request, Schedule $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'assessment_id' => 'required|exists:t_assessments,id',
            'certificate_number' => 'required|string|max:100',
            'blanko_number' => 'required|string|max:100',
            'issued_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:issued_date',
            'certificate_file' => 'nullable|file|mimes:pdf|max:10240',
        ], [
            'certificate_number.required' => 'Nomor sertifikat kompetensi wajib diisi.',
            'blanko_number.required' => 'Nomor blanko BNSP wajib diisi.',
            'issued_date.required' => 'Tanggal penerbitan sertifikat wajib diisi.',
            'valid_until.required' => 'Masa berlaku sertifikat wajib diisi.',
            'valid_until.after_or_equal' => 'Masa berlaku harus sama atau setelah tanggal dibuat/terbit.',
            'certificate_file.mimes' => 'Berkas sertifikat harus berformat PDF resmi.',
            'certificate_file.max' => 'Ukuran berkas PDF maksimal 10 MB.',
        ]);

        $assessment = Assessment::where('id', $validated['assessment_id'])
            ->where('schedule_id', $schedule->id)
            ->firstOrFail();

        if ($assessment->final_result !== 'KOMPETEN') {
            return back()->with('error', 'Sertifikat hanya dapat diterbitkan untuk asesi yang dinyatakan KOMPETEN (K).');
        }

        $certificate = Certificate::where('assessment_id', $assessment->id)->first();

        if (! $certificate && ! $request->hasFile('certificate_file')) {
            return back()->with('error', 'Berkas PDF sertifikat wajib diunggah.');
        }

        $filePath = $certificate?->file_path;
        $originalName = $certificate?->original_filename;

        if ($request->hasFile('certificate_file')) {
            // Delete old file if present
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            $file = $request->file('certificate_file');
            $originalName = $file->getClientOriginalName();
            $filename = 'cert_'.Str::slug($schedule->code).'_'.$assessment->asesi_id.'_'.time().'.pdf';
            $filePath = $file->storeAs('certificates', $filename, 'public');
        }

        Certificate::updateOrCreate(
            ['assessment_id' => $assessment->id],
            [
                'schedule_id' => $schedule->id,
                'asesi_id' => $assessment->asesi_id,
                'scheme_id' => $schedule->scheme_id,
                'certificate_number' => trim($validated['certificate_number']),
                'blanko_number' => trim($validated['blanko_number']),
                'issued_date' => $validated['issued_date'],
                'valid_until' => $validated['valid_until'],
                'file_path' => $filePath,
                'original_filename' => $originalName,
                'uploaded_by' => auth()->id(),
            ]
        );

        $asesiName = $assessment->asesi?->full_name ?? 'Asesi';

        return redirect()->route('admin.certificates.schedule', $schedule->id)
            ->with('success', "Sertifikat kompetensi untuk {$asesiName} berhasil diunggah dan terkirim ke asesi.");
    }

    /**
     * Delete an uploaded certificate.
     */
    public function destroy(Certificate $certificate): RedirectResponse
    {
        $scheduleId = $certificate->schedule_id;
        $asesiName = $certificate->asesi?->full_name ?? 'Asesi';

        if ($certificate->file_path && Storage::disk('public')->exists($certificate->file_path)) {
            Storage::disk('public')->delete($certificate->file_path);
        }

        $certificate->delete();

        return redirect()->route('admin.certificates.schedule', $scheduleId)
            ->with('success', "Sertifikat kompetensi atas nama {$asesiName} berhasil dihapus.");
    }

    /**
     * Stream PDF for preview in modal / browser.
     */
    public function preview(Certificate $certificate): Response
    {
        if (! $certificate->file_path || ! Storage::disk('public')->exists($certificate->file_path)) {
            abort(404, 'Berkas fisik sertifikat tidak ditemukan pada server.');
        }

        $path = Storage::disk('public')->path($certificate->file_path);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.($certificate->original_filename ?? 'sertifikat.pdf').'"',
        ]);
    }

    /**
     * Download the certificate PDF file.
     */
    public function download(Certificate $certificate): BinaryFileResponse
    {
        if (! $certificate->file_path || ! Storage::disk('public')->exists($certificate->file_path)) {
            abort(404, 'Berkas fisik sertifikat tidak ditemukan pada server.');
        }

        $path = Storage::disk('public')->path($certificate->file_path);
        $cleanName = 'Sertifikat_'.Str::slug($certificate->scheme?->code ?? 'BNSP').'_'.Str::slug($certificate->asesi?->full_name ?? 'Asesi').'.pdf';

        return response()->download($path, $cleanName);
    }
}
