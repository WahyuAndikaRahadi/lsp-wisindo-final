<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\AssessmentProof;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileController extends Controller
{
    public function showProof(Request $request, AssessmentProof $proof): StreamedResponse
    {
        $proof->loadMissing('assessment.schedule.assessors');
        $assessment = $proof->assessment;

        $this->authorizeAccess($request->user(), $assessment);

        return $this->serveFile($proof->file_path, $proof->original_filename);
    }

    public function showEvidence(Request $request, AssessmentIaResponse $response): StreamedResponse
    {
        $response->loadMissing('assessment.schedule.assessors');
        $assessment = $response->assessment;

        $this->authorizeAccess($request->user(), $assessment);

        $data = is_array($response->data_json)
            ? $response->data_json
            : (json_decode($response->data_json ?? '[]', true) ?: []);

        $path = $data['evidence_image_path'] ?? null;
        if (! $path) {
            abort(404, 'Berkas bukti belum diunggah.');
        }

        $filename = $data['evidence_original_name'] ?? basename($path);

        return $this->serveFile($path, $filename);
    }

    protected function authorizeAccess(User $user, ?Assessment $assessment): void
    {
        if (! $assessment) {
            abort(404, 'Data asesmen tidak ditemukan.');
        }

        // Admins & Komite Teknis have full audit / review access
        if (in_array($user->role, ['SUPER_ADMIN', 'ADMIN_LSP', 'KOMITE_TEKNIS'], true)) {
            return;
        }

        // Asesi can only view their own files
        if ($assessment->asesi_id === $user->id) {
            return;
        }

        // Assigned Assessor (direct or via schedule)
        if ($assessment->assessor_id === $user->id) {
            return;
        }

        if ($assessment->schedule && $assessment->schedule->assessors->contains('id', $user->id)) {
            return;
        }

        // TUK Admin for their own TUK
        if ($user->role === 'TUK_ADMIN' && $user->tuk_id && $assessment->schedule && $assessment->schedule->tuk_id === $user->tuk_id) {
            return;
        }

        abort(403, 'Anda tidak memiliki hak akses untuk melihat berkas ini.');
    }

    protected function serveFile(string $path, ?string $filename = null): StreamedResponse
    {
        $disk = 'local';
        if (! Storage::disk('local')->exists($path)) {
            if (Storage::disk('public')->exists($path)) {
                $disk = 'public';
            } else {
                abort(404, 'Berkas fisik tidak ditemukan di sistem penyimpanan.');
            }
        }

        $filename = $filename ?: basename($path);

        return Storage::disk($disk)->response($path, $filename, [
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
        ]);
    }
}
