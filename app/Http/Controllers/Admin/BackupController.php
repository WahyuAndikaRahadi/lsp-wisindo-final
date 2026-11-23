<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(
        protected DatabaseBackupService $backupService
    ) {}

    /**
     * Display a listing of database backups.
     */
    public function index(): View
    {
        $backups = $this->backupService->listBackups();
        $stats = $this->backupService->getDatabaseStats();

        return view('admin.backups.index', compact('backups', 'stats'));
    }

    /**
     * Create and store a new database backup file.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $prefix = $request->input('prefix');
            $backup = $this->backupService->createBackup($prefix ? (string) $prefix : null);

            return redirect()->route('admin.backups.index')
                ->with('success', sprintf('Backup database berhasil dibuat: %s (%s).', $backup['filename'], $backup['size_formatted']));
        } catch (\Throwable $e) {
            return redirect()->route('admin.backups.index')
                ->with('error', 'Gagal membuat backup database: '.$e->getMessage());
        }
    }

    /**
     * Download an existing backup file.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $path = $this->backupService->getBackupPath($filename);

        if (! $path || ! file_exists($path)) {
            abort(404, 'File backup database tidak ditemukan.');
        }

        return response()->download($path, basename($filename), [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * Stream a live backup directly to the browser.
     */
    public function exportLive(): StreamedResponse
    {
        return $this->backupService->streamLiveBackup();
    }

    /**
     * Remove the specified backup file from storage.
     */
    public function destroy(string $filename): RedirectResponse
    {
        if ($this->backupService->deleteBackup($filename)) {
            return redirect()->route('admin.backups.index')
                ->with('success', sprintf('File backup %s berhasil dihapus.', basename($filename)));
        }

        return redirect()->route('admin.backups.index')
            ->with('error', 'File backup tidak ditemukan atau gagal dihapus.');
    }
}
