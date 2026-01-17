<<<<<<< HEAD
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Models\Holiday;
use App\Helpers\NotificationHelper;
use Carbon\Carbon;

/**
 * Middleware for running daily tasks without external cron
 * 
 * Runs 10 minutes after Dinas/SuperAdmin login, once per day:
 * 1. Send revision reminders via Telegram
 * 2. Auto-cancel mutations older than 30 days with "Perlu Perbaikan" status
 */
class DailyTasksMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only run for Dinas/SuperAdmin
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            return $response;
        }

        // Check if we should run (non-blocking, after response)
        $this->tryRunDailyTasks($user);

        return $response;
    }

    /**
     * Try to run daily tasks if conditions are met
     */
    private function tryRunDailyTasks($user): void
    {
        try {
            $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
            $cacheKey = 'daily_tasks_last_run';

            // Check if already ran today
            $lastRun = Cache::get($cacheKey);
            if ($lastRun === $today) {
                return; // Already ran today
            }

            // Check if 10 minutes since login
            $loginTime = session('login_time');
            if (!$loginTime) {
                // Store login time if not set
                session(['login_time' => Carbon::now('Asia/Jakarta')]);
                return;
            }

            $minutesSinceLogin = Carbon::parse($loginTime)->diffInMinutes(Carbon::now('Asia/Jakarta'));
            if ($minutesSinceLogin < 10) {
                return; // Wait 10 minutes after login
            }

            // Check if working day
            $dayOfWeek = Carbon::now('Asia/Jakarta')->dayOfWeek;
            $allowedDays = Setting::getAllowedDays();
            if (!in_array($dayOfWeek, $allowedDays)) {
                // Not a working day, but mark as "ran" to prevent repeated checks
                Cache::put($cacheKey, $today, now()->addHours(24));
                return;
            }

            // Check if holiday
            if (Holiday::isHoliday($today)) {
                Cache::put($cacheKey, $today, now()->addHours(24));
                return;
            }

            // Run the tasks
            Log::info("Daily tasks triggered by {$user->username}");

            // Task 1: Send revision reminders
            $this->sendRevisionReminders();

            // Task 2: Auto-cancel old mutations (30+ days)
            $this->autoCancelOldMutations();

            // Mark as ran today
            Cache::put($cacheKey, $today, now()->addHours(24));

            Log::info("Daily tasks completed successfully");

        } catch (\Exception $e) {
            Log::error("Daily tasks error: " . $e->getMessage());
        }
    }

    /**
     * Send Telegram reminders to users with pending revisions
     */
    private function sendRevisionReminders(): void
    {
        $sentCount = 0;

        // Process Mutasi Masuk
        $sentCount += $this->sendRemindersForType('masuk');

        // Process Mutasi Keluar
        $sentCount += $this->sendRemindersForType('keluar');

        Log::info("Revision reminders sent to {$sentCount} users");
    }

    private function sendRemindersForType(string $type): int
    {
        $table = $type === 'masuk' ? 'mutasi_masuk' : 'mutasi_keluar';
        $sentCount = 0;

        $mutations = DB::table($table)
            ->where('status', 'Perlu Perbaikan')
            ->get();

        foreach ($mutations as $mutasi) {
            $pengusul = DB::table('users')
                ->where('username', $mutasi->pengusul)
                ->whereNotNull('telegram_chat_id')
                ->where('telegram_chat_id', '!=', '')
                ->first();

            if ($pengusul) {
                $message = $this->buildReminderMessage($mutasi, $type);

                try {
                    NotificationHelper::sendTelegram($pengusul->telegram_chat_id, $message);
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to send reminder to {$pengusul->username}: " . $e->getMessage());
                }
            }
        }

        return $sentCount;
    }

    private function buildReminderMessage(object $mutasi, string $type): string
    {
        $typeLabel = $type === 'masuk' ? 'Masuk' : 'Keluar';
        $catatan = $mutasi->catatan_berkas ?? 'Silakan periksa dokumen Anda';
        $tanggal = Carbon::parse($mutasi->tanggal_pengajuan)->format('d/m/Y');

        // Calculate days since revision requested
        $daysSinceUpdate = Carbon::parse($mutasi->updated_at)->diffInDays(Carbon::now());
        $daysRemaining = max(0, 30 - $daysSinceUpdate);

        return "⏰ <b>PENGINGAT: Perbaikan Dokumen</b>\n\n"
            . "📋 Mutasi {$typeLabel}: <code>{$mutasi->id_mutasi}</code>\n"
            . "📅 Diajukan: {$tanggal}\n"
            . "⚠️ Sisa waktu: <b>{$daysRemaining} hari</b>\n\n"
            . "📝 <b>Catatan Dinas:</b>\n{$catatan}\n\n"
            . "🔔 Mohon segera perbaiki. Data akan otomatis dibatalkan setelah 30 hari.";
    }

    /**
     * Auto-cancel mutations that have been in "Perlu Perbaikan" for 30+ days
     */
    private function autoCancelOldMutations(): void
    {
        $threshold = Carbon::now('Asia/Jakarta')->subDays(30);
        $cancelledCount = 0;

        // Process Mutasi Masuk
        $cancelledCount += $this->autoCancelForType('masuk', $threshold);

        // Process Mutasi Keluar  
        $cancelledCount += $this->autoCancelForType('keluar', $threshold);

        Log::info("Auto-cancelled {$cancelledCount} old mutations");
    }

    private function autoCancelForType(string $type, Carbon $threshold): int
    {
        $table = $type === 'masuk' ? 'mutasi_masuk' : 'mutasi_keluar';
        $siswaTable = $type === 'masuk' ? 'mutasi_masuk_siswa' : null;
        $cancelledCount = 0;

        $oldMutations = DB::table($table)
            ->where('status', 'Perlu Perbaikan')
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($oldMutations as $mutasi) {
            try {
                DB::beginTransaction();

                // Notify pengusul about cancellation
                $this->notifyCancellation($mutasi, $type);

                // Delete associated files
                $this->deleteFilesForMutation($mutasi, $type);

                // Delete siswa records if Mutasi Masuk
                if ($siswaTable) {
                    // Delete siswa files first
                    $siswaList = DB::table($siswaTable)->where('id_mutasi', $mutasi->id_mutasi)->get();
                    foreach ($siswaList as $siswa) {
                        $this->deleteSiswaFiles($siswa);
                    }
                    DB::table($siswaTable)->where('id_mutasi', $mutasi->id_mutasi)->delete();
                }

                // Delete the mutation record
                DB::table($table)->where('id_mutasi', $mutasi->id_mutasi)->delete();

                DB::commit();
                $cancelledCount++;

                Log::info("Auto-cancelled mutation: {$mutasi->id_mutasi}");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to auto-cancel {$mutasi->id_mutasi}: " . $e->getMessage());
            }
        }

        return $cancelledCount;
    }

    private function notifyCancellation(object $mutasi, string $type): void
    {
        $pengusul = DB::table('users')
            ->where('username', $mutasi->pengusul)
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->first();

        if ($pengusul) {
            $typeLabel = $type === 'masuk' ? 'Masuk' : 'Keluar';
            $message = "❌ <b>MUTASI DIBATALKAN OTOMATIS</b>\n\n"
                . "📋 Mutasi {$typeLabel}: <code>{$mutasi->id_mutasi}</code>\n\n"
                . "⚠️ Data ini telah dibatalkan dan dihapus karena tidak ada perbaikan dalam 30 hari.\n\n"
                . "📝 Jika masih diperlukan, silakan ajukan ulang.";

            try {
                NotificationHelper::sendTelegram($pengusul->telegram_chat_id, $message);
            } catch (\Exception $e) {
                Log::error("Failed to notify cancellation: " . $e->getMessage());
            }
        }
    }

    private function deleteFilesForMutation(object $mutasi, string $type): void
    {
        $fileColumns = ['file_surat_permohonan', 'file_arsip_scan'];

        // Add type-specific file columns
        if ($type === 'keluar') {
            $fileColumns = array_merge($fileColumns, [
                'file_surat_tanda_siswa',
                'file_rapor',
                'file_ijazah_sd',
                'file_surat_pindah',
                'file_surat_dapodik'
            ]);
        }

        foreach ($fileColumns as $col) {
            if (!empty($mutasi->$col)) {
                $this->deleteFile($mutasi->$col);
            }
        }
    }

    private function deleteSiswaFiles(object $siswa): void
    {
        $fileColumns = [
            'file_surat_pindah',
            'file_surat_dapodik',
            'file_rekomendasi',
            'file_ijazah_sd',
            'file_rapor',
            'file_surat_penyaluran',
            'file_rekam_didik'
        ];

        foreach ($fileColumns as $col) {
            if (!empty($siswa->$col)) {
                $this->deleteFile($siswa->$col);
            }
        }
    }

    private function deleteFile(string $path): void
    {
        $relativePath = ltrim($path, '/');
        if (strpos($relativePath, 'dokumen/') === 0) {
            $relativePath = substr($relativePath, 8);
        }

        $fullPath = storage_path('app/dokumen_rahasia/' . $relativePath);

        if (file_exists($fullPath)) {
            try {
                unlink($fullPath);
            } catch (\Exception $e) {
                Log::error("Failed to delete file: {$path}");
            }
        }
    }
}
=======
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Models\Holiday;
use App\Helpers\NotificationHelper;
use Carbon\Carbon;

/**
 * Middleware for running daily tasks without external cron
 * 
 * Runs 10 minutes after Dinas/SuperAdmin login, once per day:
 * 1. Send revision reminders via Telegram
 * 2. Auto-cancel mutations older than 30 days with "Perlu Perbaikan" status
 */
class DailyTasksMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only run for Dinas/SuperAdmin
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            return $response;
        }

        // Check if we should run (non-blocking, after response)
        $this->tryRunDailyTasks($user);

        return $response;
    }

    /**
     * Try to run daily tasks if conditions are met
     */
    private function tryRunDailyTasks($user): void
    {
        try {
            $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
            $cacheKey = 'daily_tasks_last_run';

            // Check if already ran today
            $lastRun = Cache::get($cacheKey);
            if ($lastRun === $today) {
                return; // Already ran today
            }

            // Check if 10 minutes since login
            $loginTime = session('login_time');
            if (!$loginTime) {
                // Store login time if not set
                session(['login_time' => Carbon::now('Asia/Jakarta')]);
                return;
            }

            $minutesSinceLogin = Carbon::parse($loginTime)->diffInMinutes(Carbon::now('Asia/Jakarta'));
            if ($minutesSinceLogin < 10) {
                return; // Wait 10 minutes after login
            }

            // Check if working day
            $dayOfWeek = Carbon::now('Asia/Jakarta')->dayOfWeek;
            $allowedDays = Setting::getAllowedDays();
            if (!in_array($dayOfWeek, $allowedDays)) {
                // Not a working day, but mark as "ran" to prevent repeated checks
                Cache::put($cacheKey, $today, now()->addHours(24));
                return;
            }

            // Check if holiday
            if (Holiday::isHoliday($today)) {
                Cache::put($cacheKey, $today, now()->addHours(24));
                return;
            }

            // Run the tasks
            Log::info("Daily tasks triggered by {$user->username}");

            // Task 1: Send revision reminders
            $this->sendRevisionReminders();

            // Task 2: Auto-cancel old mutations (30+ days)
            $this->autoCancelOldMutations();

            // Mark as ran today
            Cache::put($cacheKey, $today, now()->addHours(24));

            Log::info("Daily tasks completed successfully");

        } catch (\Exception $e) {
            Log::error("Daily tasks error: " . $e->getMessage());
        }
    }

    /**
     * Send Telegram reminders to users with pending revisions
     */
    private function sendRevisionReminders(): void
    {
        $sentCount = 0;

        // Process Mutasi Masuk
        $sentCount += $this->sendRemindersForType('masuk');

        // Process Mutasi Keluar
        $sentCount += $this->sendRemindersForType('keluar');

        Log::info("Revision reminders sent to {$sentCount} users");
    }

    private function sendRemindersForType(string $type): int
    {
        $table = $type === 'masuk' ? 'mutasi_masuk' : 'mutasi_keluar';
        $sentCount = 0;

        $mutations = DB::table($table)
            ->where('status', 'Perlu Perbaikan')
            ->get();

        foreach ($mutations as $mutasi) {
            $pengusul = DB::table('users')
                ->where('username', $mutasi->pengusul)
                ->whereNotNull('telegram_chat_id')
                ->where('telegram_chat_id', '!=', '')
                ->first();

            if ($pengusul) {
                $message = $this->buildReminderMessage($mutasi, $type);

                try {
                    NotificationHelper::sendTelegram($pengusul->telegram_chat_id, $message);
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to send reminder to {$pengusul->username}: " . $e->getMessage());
                }
            }
        }

        return $sentCount;
    }

    private function buildReminderMessage(object $mutasi, string $type): string
    {
        $typeLabel = $type === 'masuk' ? 'Masuk' : 'Keluar';
        $catatan = $mutasi->catatan_berkas ?? 'Silakan periksa dokumen Anda';
        $tanggal = Carbon::parse($mutasi->tanggal_pengajuan)->format('d/m/Y');

        // Calculate days since revision requested
        $daysSinceUpdate = Carbon::parse($mutasi->updated_at)->diffInDays(Carbon::now());
        $daysRemaining = max(0, 30 - $daysSinceUpdate);

        return "⏰ <b>PENGINGAT: Perbaikan Dokumen</b>\n\n"
            . "📋 Mutasi {$typeLabel}: <code>{$mutasi->id_mutasi}</code>\n"
            . "📅 Diajukan: {$tanggal}\n"
            . "⚠️ Sisa waktu: <b>{$daysRemaining} hari</b>\n\n"
            . "📝 <b>Catatan Dinas:</b>\n{$catatan}\n\n"
            . "🔔 Mohon segera perbaiki. Data akan otomatis dibatalkan setelah 30 hari.";
    }

    /**
     * Auto-cancel mutations that have been in "Perlu Perbaikan" for 30+ days
     */
    private function autoCancelOldMutations(): void
    {
        $threshold = Carbon::now('Asia/Jakarta')->subDays(30);
        $cancelledCount = 0;

        // Process Mutasi Masuk
        $cancelledCount += $this->autoCancelForType('masuk', $threshold);

        // Process Mutasi Keluar  
        $cancelledCount += $this->autoCancelForType('keluar', $threshold);

        Log::info("Auto-cancelled {$cancelledCount} old mutations");
    }

    private function autoCancelForType(string $type, Carbon $threshold): int
    {
        $table = $type === 'masuk' ? 'mutasi_masuk' : 'mutasi_keluar';
        $siswaTable = $type === 'masuk' ? 'mutasi_masuk_siswa' : null;
        $cancelledCount = 0;

        $oldMutations = DB::table($table)
            ->where('status', 'Perlu Perbaikan')
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($oldMutations as $mutasi) {
            try {
                DB::beginTransaction();

                // Notify pengusul about cancellation
                $this->notifyCancellation($mutasi, $type);

                // Delete associated files
                $this->deleteFilesForMutation($mutasi, $type);

                // Delete siswa records if Mutasi Masuk
                if ($siswaTable) {
                    // Delete siswa files first
                    $siswaList = DB::table($siswaTable)->where('id_mutasi', $mutasi->id_mutasi)->get();
                    foreach ($siswaList as $siswa) {
                        $this->deleteSiswaFiles($siswa);
                    }
                    DB::table($siswaTable)->where('id_mutasi', $mutasi->id_mutasi)->delete();
                }

                // Delete the mutation record
                DB::table($table)->where('id_mutasi', $mutasi->id_mutasi)->delete();

                DB::commit();
                $cancelledCount++;

                Log::info("Auto-cancelled mutation: {$mutasi->id_mutasi}");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to auto-cancel {$mutasi->id_mutasi}: " . $e->getMessage());
            }
        }

        return $cancelledCount;
    }

    private function notifyCancellation(object $mutasi, string $type): void
    {
        $pengusul = DB::table('users')
            ->where('username', $mutasi->pengusul)
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->first();

        if ($pengusul) {
            $typeLabel = $type === 'masuk' ? 'Masuk' : 'Keluar';
            $message = "❌ <b>MUTASI DIBATALKAN OTOMATIS</b>\n\n"
                . "📋 Mutasi {$typeLabel}: <code>{$mutasi->id_mutasi}</code>\n\n"
                . "⚠️ Data ini telah dibatalkan dan dihapus karena tidak ada perbaikan dalam 30 hari.\n\n"
                . "📝 Jika masih diperlukan, silakan ajukan ulang.";

            try {
                NotificationHelper::sendTelegram($pengusul->telegram_chat_id, $message);
            } catch (\Exception $e) {
                Log::error("Failed to notify cancellation: " . $e->getMessage());
            }
        }
    }

    private function deleteFilesForMutation(object $mutasi, string $type): void
    {
        $fileColumns = ['file_surat_permohonan', 'file_arsip_scan'];

        // Add type-specific file columns
        if ($type === 'keluar') {
            $fileColumns = array_merge($fileColumns, [
                'file_surat_tanda_siswa',
                'file_rapor',
                'file_ijazah_sd',
                'file_surat_pindah',
                'file_surat_dapodik'
            ]);
        }

        foreach ($fileColumns as $col) {
            if (!empty($mutasi->$col)) {
                $this->deleteFile($mutasi->$col);
            }
        }
    }

    private function deleteSiswaFiles(object $siswa): void
    {
        $fileColumns = [
            'file_surat_pindah',
            'file_surat_dapodik',
            'file_rekomendasi',
            'file_ijazah_sd',
            'file_rapor',
            'file_surat_penyaluran',
            'file_rekam_didik'
        ];

        foreach ($fileColumns as $col) {
            if (!empty($siswa->$col)) {
                $this->deleteFile($siswa->$col);
            }
        }
    }

    private function deleteFile(string $path): void
    {
        $relativePath = ltrim($path, '/');
        if (strpos($relativePath, 'dokumen/') === 0) {
            $relativePath = substr($relativePath, 8);
        }

        $fullPath = storage_path('app/dokumen_rahasia/' . $relativePath);

        if (file_exists($fullPath)) {
            try {
                unlink($fullPath);
            } catch (\Exception $e) {
                Log::error("Failed to delete file: {$path}");
            }
        }
    }
}
>>>>>>> 9466a07 (Upload project pertama kali)
