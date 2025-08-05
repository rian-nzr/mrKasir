<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup 
                            {--filename= : Custom filename for backup}
                            {--only-structure : Backup only table structure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup with seeded data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->option('filename') 
            ? $this->option('filename') 
            : 'backup-' . Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

        $onlyStructure = $this->option('only-structure');

        $this->info('Creating database backup...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        // Create backups directory if not exists
        $backupPath = storage_path('app/backups');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $filePath = $backupPath . '/' . $filename;

        // Build mysqldump command
        $command = sprintf(
            'mysqldump -h%s -P%s -u%s -p%s %s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            $onlyStructure ? '--no-data' : '',
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        // Execute command
        $returnVar = null;
        $output = null;
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $size = filesize($filePath);
            $sizeFormatted = $this->formatBytes($size);
            
            $this->info("✅ Database backup created successfully!");
            $this->info("📁 File: {$filename}");
            $this->info("📏 Size: {$sizeFormatted}");
            $this->info("📍 Location: {$filePath}");
            
            if ($onlyStructure) {
                $this->info("ℹ️  Structure only (no data)");
            } else {
                $this->info("ℹ️  Full backup (structure + data)");
            }

            return Command::SUCCESS;
        } else {
            $this->error("❌ Failed to create database backup!");
            $this->error("Make sure mysqldump is installed and accessible.");
            return Command::FAILURE;
        }
    }

    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
