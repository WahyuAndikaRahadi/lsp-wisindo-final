<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--prefix= : Custom filename prefix for the backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a complete SQL database backup for LSP PrO Indo';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $service): int
    {
        $this->info('Starting database backup process...');

        $prefix = $this->option('prefix');

        try {
            $backup = $service->createBackup($prefix ? (string) $prefix : null);

            $this->info(sprintf('Backup successfully generated: %s', $backup['filename']));
            $this->info(sprintf('Location: %s', $backup['path']));
            $this->info(sprintf('Size: %s', $backup['size_formatted']));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to create database backup: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
