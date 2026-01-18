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
        $backupPath = storage_path('app/dokumen_rahasia');

        if (!File::exists($backupPath)) {
            $this->error("Backup folder not found: {$backupPath}");
            return 1;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("DRY RUN MODE - No files will be uploaded\n");
        }

        // Get all files recursively
        $files = File::allFiles($backupPath);
        $allowedExtensions = ['pdf', 'webp', 'jpg', 'jpeg', 'png'];
        $filesToMigrate = array_filter($files, fn($file) => in_array(strtolower($file->getExtension()), $allowedExtensions));

        $this->info("Found " . count($filesToMigrate) . " files (PDF/Images) to migrate\n");

        $uploaded = 0;
        $failed = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar(count($filesToMigrate));
        $progressBar->start();

        foreach ($filesToMigrate as $file) {
            // Get relative path from backup folder
            $relativePath = str_replace($backupPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize for B2

            // Skip 'dokumen/' subfolder if present - use direct path
            if (str_starts_with($relativePath, 'dokumen/')) {
                $relativePath = substr($relativePath, 8);
            }

            $uploadedPaths[] = $relativePath;

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

        // CLEANUP ORPHANS
        if (!$dryRun && $uploaded > 0) {
            $this->info("Scanning B2 for orphaned files to remove...");
            $b2Files = Storage::disk('b2')->allFiles();
            $deleted = 0;

            $bar = $this->output->createProgressBar(count($b2Files));
            $bar->start();

            foreach ($b2Files as $b2File) {
                if (!in_array($b2File, $uploadedPaths)) {
                    // Try to avoid deleting if it matches but with/without leading slash? 
                    // Flysystem returns relative paths nicely.
                    try {
                        Storage::disk('b2')->delete($b2File);
                        $deleted++;
                    } catch (\Exception $e) {
                        $this->error("Failed to delete: {$b2File}");
                    }
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
            $this->info("Deleted {$deleted} orphaned files.");
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
