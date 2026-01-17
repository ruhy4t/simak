<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class MigrateToB2 extends Command
{
    protected $signature = 'storage:migrate-to-b2 {--dry-run : Show what would be uploaded without actually uploading}';
    protected $description = 'Migrate files from local backup to Backblaze B2';

    public function handle()
    {
        $backupPath = storage_path('app/dokumen_backup');

        if (!File::exists($backupPath)) {
            $this->error("Backup folder not found: {$backupPath}");
            return 1;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("DRY RUN MODE - No files will be uploaded\n");
        }

        // Get all PDF files recursively
        $files = File::allFiles($backupPath);
        $pdfFiles = array_filter($files, fn($file) => strtolower($file->getExtension()) === 'pdf');

        $this->info("Found " . count($pdfFiles) . " PDF files to migrate\n");

        $uploaded = 0;
        $failed = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar(count($pdfFiles));
        $progressBar->start();

        foreach ($pdfFiles as $file) {
            // Get relative path from backup folder
            $relativePath = str_replace($backupPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize for B2

            // Skip 'dokumen/' subfolder if present - use direct path
            if (str_starts_with($relativePath, 'dokumen/')) {
                $relativePath = substr($relativePath, 8);
            }

            try {
                if (!$dryRun) {
                    // Upload to B2 directly (will overwrite if exists)
                    $content = File::get($file->getPathname());
                    Storage::disk('b2')->put($relativePath, $content);
                }

                $uploaded++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed: {$relativePath} - " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Migration Summary:");
        $this->info("  Uploaded: {$uploaded}");
        $this->info("  Skipped (already exists): {$skipped}");
        $this->info("  Failed: {$failed}");

        if ($dryRun) {
            $this->warn("\nThis was a dry run. Run without --dry-run to actually upload.");
        }

        return 0;
    }
}
