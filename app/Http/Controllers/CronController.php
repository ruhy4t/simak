<<<<<<< HEAD
<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Holiday;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controller for handling cron jobs via HTTP endpoint
 * Used for shared hosting without SSH access
 */
class CronController extends Controller
{
    /**
     * Send revision reminders to pengusul with 'Perlu Perbaikan' status
     * 
     * URL: /cron/reminder/{token}
     * Triggered by external cron service (cron-job.org)
     */
    public function revisionReminder(string $token)
    {
        // 1. Validate token
        $expectedToken = config('services.cron.token');
        if (empty($expectedToken) || $token !== $expectedToken) {
            Log::warning("Cron: Invalid token attempt");
            abort(403, 'Invalid token');
        }

        // 2. Check if today is a working day
        $today = Carbon::now('Asia/Jakarta');
        $dayOfWeek = $today->dayOfWeek; // 0=Sunday, 1=Monday, etc.

        $allowedDays = Setting::getAllowedDays();
        if (!in_array($dayOfWeek, $allowedDays)) {
            return response()->json([
                'status' => 'skipped',
                'reason' => 'Not a working day',
                'day' => $dayOfWeek
            ]);
        }

        // 3. Check if today is a holiday
        if (Holiday::isHoliday($today->format('Y-m-d'))) {
            return response()->json([
                'status' => 'skipped',
                'reason' => 'Holiday',
                'date' => $today->format('Y-m-d')
            ]);
        }

        // 4. Send reminders for both mutation types
        $sentCount = 0;
        $sentCount += $this->sendReminders('masuk');
        $sentCount += $this->sendReminders('keluar');

        Log::info("Cron: Revision reminders sent to {$sentCount} users");

        return response()->json([
            'status' => 'success',
            'sent_count' => $sentCount,
            'executed_at' => $today->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Send reminders for a specific mutation type
     */
    private function sendReminders(string $type): int
    {
        $table = $type === 'masuk' ? 'mutasi_masuk' : 'mutasi_keluar';
        $sentCount = 0;

        $mutations = DB::table($table)
            ->where('status', 'Perlu Perbaikan')
            ->get();

        foreach ($mutations as $mutasi) {
            // Get pengusul's telegram chat ID
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
                    Log::error("Cron: Failed to send reminder to {$pengusul->username}: " . $e->getMessage());
                }
            }
        }

        return $sentCount;
    }

    /**
     * Build the reminder message
     */
    private function buildReminderMessage(object $mutasi, string $type): string
    {
        $typeLabel = $type === 'masuk' ? 'Masuk' : 'Keluar';
        $catatan = $mutasi->catatan_berkas ?? 'Silakan periksa dokumen Anda';
        $tanggal = Carbon::parse($mutasi->tanggal_pengajuan)->format('d/m/Y');

        return "⏰ <b>PENGINGAT: Perbaikan Dokumen</b>\n\n"
            . "📋 Mutasi {$typeLabel}: <code>{$mutasi->id_mutasi}</code>\n"
            . "📅 Diajukan: {$tanggal}\n\n"
            . "📝 <b>Catatan Dinas:</b>\n{$catatan}\n\n"
            . "⚠️ Mohon segera perbaiki dan kirim ulang dokumen Anda.";
    }
}
=======
<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Holiday;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controller for handling cron jobs via HTTP endpoint
 * Used for shared hosting without SSH access
 */
class CronController extends Controller
{
    /**
     * Send revision reminders to pengusul with 'Perlu Perbaikan' status
     * 
     * URL: /cron/reminder/{token}
     * Triggered by external cron service (cron-job.org)
     */
    public function revisionReminder(string $token)
    {
        // 1. Validate token
        $expectedToken = config('services.cron.token');
        if (empty($expectedToken) || $token !== $expectedToken) {
            Log::warning("Cron: Invalid token attempt");
            abort(403, 'Invalid token');
        }

        // 2. Check if today is a working day
        $today = Carbon::now('Asia/Jakarta');
        $dayOfWeek = $today->dayOfWeek; // 0=Sunday, 1=Monday, etc.

        $allowedDays = Setting::getAllowedDays();
        if (!in_array($dayOfWeek, $allowedDays)) {
            return response()->json([
                'status' => 'skipped',
                'reason' => 'Not a working day',
                'day' => $dayOfWeek
            ]);
        }

        // 3. Check if today is a holiday
        if (Holiday::isHoliday($today->format('Y-m-d'))) {
            return response()->json([
                'status' => 'skipped',
                'reason' => 'Holiday',
                'date' => $today->format('Y-m-d')
            ]);
        }

        // 4. Send reminders for both mutation types
        $sentCount = 0;
        $sentCount += $this->sendReminders('masuk');
        $sentCount += $this->sendReminders('keluar');

        Log::info("Cron: Revision reminders sent to {$sentCount} users");

        return response()->json([
            'status' => 'success',
            'sent_count' => $sentCount,
            'executed_at' => $today->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Send reminders for a specific mutation type
     */
    private function sendReminders(string $type): int
    {
        $table = $type === 'masuk' ? 'mutasi_masuk' : 'mutasi_keluar';
        $sentCount = 0;

        $mutations = DB::table($table)
            ->where('status', 'Perlu Perbaikan')
            ->get();

        foreach ($mutations as $mutasi) {
            // Get pengusul's telegram chat ID
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
                    Log::error("Cron: Failed to send reminder to {$pengusul->username}: " . $e->getMessage());
                }
            }
        }

        return $sentCount;
    }

    /**
     * Build the reminder message
     */
    private function buildReminderMessage(object $mutasi, string $type): string
    {
        $typeLabel = $type === 'masuk' ? 'Masuk' : 'Keluar';
        $catatan = $mutasi->catatan_berkas ?? 'Silakan periksa dokumen Anda';
        $tanggal = Carbon::parse($mutasi->tanggal_pengajuan)->format('d/m/Y');

        return "⏰ <b>PENGINGAT: Perbaikan Dokumen</b>\n\n"
            . "📋 Mutasi {$typeLabel}: <code>{$mutasi->id_mutasi}</code>\n"
            . "📅 Diajukan: {$tanggal}\n\n"
            . "📝 <b>Catatan Dinas:</b>\n{$catatan}\n\n"
            . "⚠️ Mohon segera perbaiki dan kirim ulang dokumen Anda.";
    }
}
>>>>>>> 9466a07 (Upload project pertama kali)
