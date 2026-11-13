<?php

namespace App\Http\Controllers\Asesi;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    /**
     * Display all certificates issued to the authenticated candidate.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = Certificate::with(['scheme', 'schedule', 'assessment.assessor'])
            ->where('asesi_id', $user->id)
            ->latest('issued_date');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhere('blanko_number', 'like', "%{$search}%")
                    ->orWhereHas('scheme', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $certificates = $query->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('asesi.certificates._table', compact('certificates'));
        }

        return view('asesi.certificates.index', compact('certificates'));
    }

    /**
     * Preview candidate's own certificate via modal or new tab.
     */
    public function preview(Certificate $certificate): Response
    {
        abort_if($certificate->asesi_id !== auth()->id(), 403, 'Akses tidak diizinkan.');

        if (! $certificate->file_path || ! Storage::disk('public')->exists($certificate->file_path)) {
            abort(404, 'Berkas sertifikat tidak ditemukan di server.');
        }

        $path = Storage::disk('public')->path($certificate->file_path);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.($certificate->original_filename ?? 'sertifikat.pdf').'"',
        ]);
    }

    /**
     * Download candidate's own official certificate PDF.
     */
    public function download(Certificate $certificate): BinaryFileResponse
    {
        abort_if($certificate->asesi_id !== auth()->id(), 403, 'Akses tidak diizinkan.');

        if (! $certificate->file_path || ! Storage::disk('public')->exists($certificate->file_path)) {
            abort(404, 'Berkas sertifikat tidak ditemukan di server.');
        }

        $path = Storage::disk('public')->path($certificate->file_path);
        $cleanName = 'Sertifikat_BNSP_'.Str::slug($certificate->scheme?->code ?? 'LSP').'_'.Str::slug(auth()->user()->full_name).'.pdf';

        return response()->download($path, $cleanName);
    }
}
