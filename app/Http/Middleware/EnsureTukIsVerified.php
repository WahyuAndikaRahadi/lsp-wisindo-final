<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTukIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isRole('TUK_ADMIN')) {
            abort(403, 'Akses khusus Admin TUK.');
        }

        $tuk = $user->tuk()->first();

        if (! $tuk || ! $tuk->isVerified()) {
            return redirect()->route('tuk.data.edit')
                ->with('warning', 'Tempat Uji Kompetensi (TUK) Anda belum terverifikasi oleh Admin LSP. Silakan lengkapi profil dan berkas verifikasi AJJ terlebih dahulu untuk dapat mengakses jadwal asesmen.');
        }

        return $next($request);
    }
}
