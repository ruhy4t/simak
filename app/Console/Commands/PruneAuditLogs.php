<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;

class PruneAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:prune {--days=90 : Number of days to retain logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune audit logs older than a specific number of days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('Days must be greater than 0');
            return 1;
        }

        $cutoffDate = now()->subDays($days);

        $this->info("Pruning logs older than {$cutoffDate->toDateTimeString()} ({$days} days)...");

        // Use DB directly for speed on large datasets or Model if events needed (none here)
        // Ensure created_at exists (Migration added it)
        try {
            $count = AuditLog::where('created_at', '<', $cutoffDate)->delete();
            $this->info("Deleted {$count} old audit logs.");
        } catch (\Exception $e) {
            $this->error("Failed to prune: " . $e->getMessage());
            // Fallback for tables without created_at populated yet
            $this->warn("Ensure the audit_logs table has been migrated to include timestamps.");
        }

        return 0;
    }
}
