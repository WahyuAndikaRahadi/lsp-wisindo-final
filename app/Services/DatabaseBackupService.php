<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        $this->ensureDirectoryExists();
    }

    /**
     * Ensure the backup directory exists with a protective .gitignore.
     */
    protected function ensureDirectoryExists(): void
    {
        if (! is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $gitIgnorePath = $this->backupDir.'/.gitignore';
        if (! file_exists($gitIgnorePath)) {
            file_put_contents($gitIgnorePath, "*\n!.gitignore\n");
        }
    }

    /**
     * Get the absolute path to the backup directory.
     */
    public function getBackupDirectory(): string
    {
        return $this->backupDir;
    }

    /**
     * List all existing SQL backups sorted by newest first.
     *
     * @return Collection<int, array{filename: string, path: string, size_bytes: int, size_formatted: string, created_at: Carbon}>
     */
    public function listBackups(): Collection
    {
        $files = glob($this->backupDir.'/*.sql') ?: [];

        return collect($files)->map(function (string $path) {
            $filename = basename($path);
            $sizeBytes = filesize($path) ?: 0;
            $filemtime = filemtime($path) ?: time();

            return [
                'filename' => $filename,
                'path' => $path,
                'size_bytes' => $sizeBytes,
                'size_formatted' => $this->formatBytes($sizeBytes),
                'created_at' => Carbon::createFromTimestamp($filemtime, config('app.timezone', 'Asia/Jakarta')),
            ];
        })->sortByDesc('created_at')->values();
    }

    /**
     * Generate a new database backup file in the backup directory.
     *
     * @return array{filename: string, path: string, size_bytes: int, size_formatted: string, created_at: Carbon}
     */
    public function createBackup(?string $customPrefix = null): array
    {
        $prefix = $customPrefix ? preg_replace('/[^a-zA-Z0-9_-]/', '', $customPrefix) : 'lsp_proindo_backup';
        $filename = sprintf('%s_%s.sql', $prefix, now()->format('Y-m-d_His'));
        $targetPath = $this->backupDir.DIRECTORY_SEPARATOR.$filename;

        $fp = fopen($targetPath, 'w');
        if (! $fp) {
            throw new \RuntimeException('Gagal membuka file tujuan backup untuk penulisan.');
        }

        try {
            $this->dumpToStream($fp);
        } finally {
            fclose($fp);
        }

        $sizeBytes = filesize($targetPath) ?: 0;

        return [
            'filename' => $filename,
            'path' => $targetPath,
            'size_bytes' => $sizeBytes,
            'size_formatted' => $this->formatBytes($sizeBytes),
            'created_at' => now(),
        ];
    }

    /**
     * Stream a live backup directly to a response.
     */
    public function streamLiveBackup(): StreamedResponse
    {
        $filename = sprintf('lsp_proindo_backup_live_%s.sql', now()->format('Y-m-d_His'));

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            $this->dumpToStream($output);
            fclose($output);
        }, $filename, [
            'Content-Type' => 'application/sql',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Validate and retrieve the absolute path of a backup file.
     */
    public function getBackupPath(string $filename): ?string
    {
        $safeName = basename($filename);
        if (! str_ends_with(strtolower($safeName), '.sql')) {
            return null;
        }

        $fullPath = $this->backupDir.DIRECTORY_SEPARATOR.$safeName;

        return file_exists($fullPath) ? $fullPath : null;
    }

    /**
     * Delete a backup file safely.
     */
    public function deleteBackup(string $filename): bool
    {
        $path = $this->getBackupPath($filename);
        if ($path && file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Dump the database SQL structure and data to an open resource stream.
     *
     * @param  resource  $handle
     */
    public function dumpToStream($handle): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $dbName = (string) $connection->getDatabaseName();
        $pdo = $connection->getPdo();
        $now = now()->toDateTimeString();

        fwrite($handle, "-- =============================================================\n");
        fwrite($handle, "-- LSP Praktisi Otomotif Indonesia (LSP PrO Indo)\n");
        fwrite($handle, "-- Database Backup (.sql)\n");
        fwrite($handle, "-- Database       : {$dbName}\n");
        fwrite($handle, "-- Driver         : {$driver}\n");
        fwrite($handle, "-- Generated At   : {$now}\n");
        fwrite($handle, '-- Laravel Version: '.app()->version()."\n");
        fwrite($handle, '-- PHP Version    : '.PHP_VERSION."\n");
        fwrite($handle, "-- =============================================================\n\n");

        if ($driver === 'sqlite') {
            $this->dumpSqlite($handle, $pdo);
        } else {
            $this->dumpMysql($handle, $pdo);
        }

        fwrite($handle, "\n-- =============================================================\n");
        fwrite($handle, '-- Backup completed at: '.now()->toDateTimeString()."\n");
        fwrite($handle, "-- =============================================================\n");
    }

    /**
     * Dump MySQL / MariaDB database.
     *
     * @param  resource  $handle
     */
    protected function dumpMysql($handle, \PDO $pdo): void
    {
        fwrite($handle, "SET NAMES utf8mb4;\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

        $rawTables = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $tables = collect($rawTables)->map(fn ($t) => array_values((array) $t)[0])->values();

        foreach ($tables as $table) {
            $this->dumpMysqlTable($handle, $pdo, (string) $table);
        }

        fwrite($handle, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
    }

    /**
     * Dump single MySQL table structure and rows.
     *
     * @param  resource  $handle
     */
    protected function dumpMysqlTable($handle, \PDO $pdo, string $table): void
    {
        fwrite($handle, "\n-- -------------------------------------------------------------\n");
        fwrite($handle, "-- Table structure for table `{$table}`\n");
        fwrite($handle, "-- -------------------------------------------------------------\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

        $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
        $createSql = array_values((array) $createResult[0])[1] ?? '';
        fwrite($handle, $createSql.";\n\n");

        fwrite($handle, "-- Dumping data for table `{$table}`\n");
        fwrite($handle, "LOCK TABLES `{$table}` WRITE;\n");

        $batch = [];
        $columns = null;

        foreach (DB::table($table)->cursor() as $row) {
            $rowArray = (array) $row;
            if ($columns === null) {
                $columns = array_keys($rowArray);
            }

            $values = [];
            foreach ($rowArray as $val) {
                if ($val === null) {
                    $values[] = 'NULL';
                } elseif (is_bool($val)) {
                    $values[] = $val ? '1' : '0';
                } elseif (is_int($val) || is_float($val)) {
                    $values[] = (string) $val;
                } else {
                    $values[] = $pdo->quote($val);
                }
            }

            $batch[] = '('.implode(', ', $values).')';

            if (count($batch) >= 100) {
                $colList = implode('`, `', array_map('addslashes', $columns));
                fwrite($handle, "INSERT INTO `{$table}` (`{$colList}`) VALUES\n".implode(",\n", $batch).";\n");
                $batch = [];
            }
        }

        if (! empty($batch) && $columns !== null) {
            $colList = implode('`, `', array_map('addslashes', $columns));
            fwrite($handle, "INSERT INTO `{$table}` (`{$colList}`) VALUES\n".implode(",\n", $batch).";\n");
        }

        fwrite($handle, "UNLOCK TABLES;\n");
    }

    /**
     * Dump SQLite database for unit/feature test environments.
     *
     * @param  resource  $handle
     */
    protected function dumpSqlite($handle, \PDO $pdo): void
    {
        fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");

        $tables = collect(DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
            ->filter(fn ($t) => ! empty($t->name))
            ->values();

        foreach ($tables as $t) {
            $table = (string) $t->name;
            $createSql = (string) $t->sql;

            fwrite($handle, "\n-- -------------------------------------------------------------\n");
            fwrite($handle, "-- Table structure for table \"{$table}\"\n");
            fwrite($handle, "-- -------------------------------------------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS \"{$table}\";\n");
            fwrite($handle, $createSql.";\n\n");

            fwrite($handle, "-- Dumping data for table \"{$table}\"\n");

            $batch = [];
            $columns = null;

            foreach (DB::table($table)->cursor() as $row) {
                $rowArray = (array) $row;
                if ($columns === null) {
                    $columns = array_keys($rowArray);
                }

                $values = [];
                foreach ($rowArray as $val) {
                    if ($val === null) {
                        $values[] = 'NULL';
                    } elseif (is_bool($val)) {
                        $values[] = $val ? '1' : '0';
                    } elseif (is_int($val) || is_float($val)) {
                        $values[] = (string) $val;
                    } else {
                        $values[] = $pdo->quote($val);
                    }
                }

                $batch[] = '('.implode(', ', $values).')';

                if (count($batch) >= 100) {
                    $colList = implode('", "', array_map('addslashes', $columns));
                    fwrite($handle, "INSERT INTO \"{$table}\" (\"{$colList}\") VALUES\n".implode(",\n", $batch).";\n");
                    $batch = [];
                }
            }

            if (! empty($batch) && $columns !== null) {
                $colList = implode('", "', array_map('addslashes', $columns));
                fwrite($handle, "INSERT INTO \"{$table}\" (\"{$colList}\") VALUES\n".implode(",\n", $batch).";\n");
            }
        }

        fwrite($handle, "\nPRAGMA foreign_keys = ON;\n");
    }

    /**
     * Get statistics regarding database and backups.
     *
     * @return array{connection: string, database: string, tables_count: int, backups_count: int, total_space_formatted: string, last_backup_at: ?Carbon}
     */
    public function getDatabaseStats(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $dbName = (string) $connection->getDatabaseName();

        $tablesCount = 0;
        try {
            if ($driver === 'sqlite') {
                $tablesCount = count(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"));
            } else {
                $tablesCount = count(DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'));
            }
        } catch (\Throwable) {
            $tablesCount = 0;
        }

        $backups = $this->listBackups();
        $totalBytes = $backups->sum('size_bytes');

        return [
            'connection' => $driver,
            'database' => $dbName,
            'tables_count' => $tablesCount,
            'backups_count' => $backups->count(),
            'total_space_formatted' => $this->formatBytes($totalBytes),
            'last_backup_at' => $backups->first()['created_at'] ?? null,
        ];
    }

    /**
     * Format bytes into human-readable string.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $kb = $bytes / 1024;
        if ($kb < 1024) {
            return round($kb, $precision).' KB';
        }

        $mb = $kb / 1024;
        if ($mb < 1024) {
            return round($mb, $precision).' MB';
        }

        $gb = $mb / 1024;

        return round($gb, $precision).' GB';
    }
}
