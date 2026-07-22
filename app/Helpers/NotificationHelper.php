<?php

namespace App\Helpers;

use App\Jobs\SendTelegramJob;
use App\Models\Setting;
use App\Services\TelegramDeliveryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationHelper
{
    /**
     * Send a transactional notification immediately.
     */
    public static function sendTelegram(string $chatId, string $message): bool
    {
        try {
            app(TelegramDeliveryService::class)->deliver($chatId, $message);

            return true;
        } catch (Throwable $e) {
            Log::error('Telegram notification failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Queue bulk/non-urgent Telegram notifications for the scheduled worker.
     */
    public static function queueTelegram(string $chatId, string $message): bool
    {
        try {
            SendTelegramJob::dispatch($chatId, $message);

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to dispatch Telegram job: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send notification to all Dinas users about new submission
     * Uses immediate sending so new submissions are not delayed by the daily worker.
     */
    public static function notifyDinasNewSubmission(object $mutasi, string $type = 'keluar'): void
    {
        Log::info("notifyDinasNewSubmission called for type: {$type}");

        $dinasUsers = DB::table('users')
            ->whereIn('role', ['Dinas', 'SuperAdmin'])
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->where('is_active', 1)
            ->get();

        Log::info('Found '.$dinasUsers->count().' Dinas users with Telegram chat ID');

        if ($dinasUsers->isEmpty()) {
            Log::warning('No Dinas users found with Telegram chat ID - skipping notification');

            return;
        }

        $message = self::buildNewSubmissionMessage($mutasi, $type);
        $successCount = 0;
        $failCount = 0;

        foreach ($dinasUsers as $user) {
            Log::info("Sending notification to {$user->username} (chat_id: {$user->telegram_chat_id})");

            $result = self::sendTelegram($user->telegram_chat_id, $message);

            if ($result) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        Log::info("Telegram notifications complete: {$successCount} success, {$failCount} failed");
    }

    /**
     * Build message for new submission (to Dinas)
     */
    public static function buildNewSubmissionMessage(object $mutasi, string $type = 'keluar'): string
    {
        $jenis = $type === 'masuk' ? 'Mutasi Masuk' : 'Mutasi Keluar';
        $id = self::escape((string) ($mutasi->id_mutasi ?? '-'));
        $pengusul = self::escape((string) ($mutasi->pengusul ?? '-'));
        $sekolah = self::escape((string) ($type === 'masuk'
            ? ($mutasi->nama_sekolah_tujuan ?? '-')
            : ($mutasi->nama_sekolah_asal ?? '-')));
        $isResubmission = (int) ($mutasi->revision_count ?? 0) > 0;
        $heading = $isResubmission ? 'PERBAIKAN DIKIRIM ULANG' : 'PENGAJUAN BARU';
        $activity = $isResubmission ? 'Dikirim ulang setelah perbaikan' : 'Pengajuan baru';

        return "📥 <b>{$heading}</b>\n\n"
            ."📋 Jenis: {$jenis}\n"
            ."🆔 ID: {$id}\n"
            ."🏫 Sekolah: {$sekolah}\n"
            ."👤 Pengusul: {$pengusul}\n\n"
            ."🔄 Aktivitas: <b>{$activity}</b>\n"
            ."⏳ Status: <b>Menunggu Verifikasi</b>\n\n"
            .'Silakan login ke SIMAK untuk memverifikasi.';
    }

    /**
     * Build message for status change (to Pengusul)
     */
    public static function buildStatusChangeMessage(object $mutasi, string $status, string $type = 'keluar'): string
    {
        $jenis = $type === 'masuk' ? 'Mutasi Masuk' : 'Mutasi Keluar';
        $id = self::escape((string) ($mutasi->id_mutasi ?? '-'));
        $namaSiswa = self::escape((string) ($mutasi->nama_murid ?? $mutasi->nama_siswa ?? 'Murid'));
        $escapedStatus = self::escape($status);

        $statusEmoji = match ($status) {
            'Disetujui Dinas' => '✅',
            'Perlu Perbaikan' => '⚠️',
            'Proses TTD' => '✍️',
            'Selesai' => '🎉',
            default => '📋'
        };

        $message = "{$statusEmoji} <b>UPDATE STATUS MUTASI</b>\n\n"
            ."📋 Jenis: {$jenis}\n"
            ."🆔 ID: {$id}\n"
            ."👤 Murid: {$namaSiswa}\n\n"
            ."📊 Status: <b>{$escapedStatus}</b>\n";

        // Add specific instructions based on status
        switch ($status) {
            case 'Disetujui Dinas':
                $message .= "\n✅ Data Anda telah disetujui!\nMohon tunggu penerbitan surat izin.";
                break;
            case 'Perlu Perbaikan':
                $catatan = self::escape((string) ($mutasi->catatan_berkas ?? ''));
                $message .= "\n⚠️ Data perlu diperbaiki.\n";
                if ($catatan) {
                    $message .= "\n📝 Catatan:\n{$catatan}\n";
                }

                // Add service schedule info
                $scheduleInfo = self::getServiceScheduleInfo();
                if ($scheduleInfo) {
                    $message .= "\n🕐 Jam Layanan Perbaikan:\n{$scheduleInfo}\n";
                }

                $message .= "\nSilakan login ke SIMAK untuk memperbaiki data.";
                break;
            case 'Proses TTD':
                $message .= "\n✍️ Surat sedang dalam proses tanda tangan.\nMohon tunggu konfirmasi selanjutnya.";
                break;
            case 'Selesai':
                $nomor = self::escape((string) ($mutasi->nomor_surat_izin ?? '-'));
                $message .= "\n📄 Nomor Surat: {$nomor}\n"
                    ."\n🎉 Surat sudah selesai!\n"
                    .'Dapat diambil ke <b>Seksi Kurikulum SMP Bidang SMP Dinas Pendidikan Kota Bogor</b> atau bisa cek berkala di SIMAK untuk unduh/cetak surat.';
                break;
        }

        $message .= "\n\n---\nSIMAK - Sistem Informasi Mutasi";

        return $message;
    }

    /**
     * Send notification to pengusul about status change
     */
    public static function notifyPengusul(object $mutasi, string $status, string $type = 'keluar'): void
    {
        $pengusul = DB::table('users')
            ->where('username', $mutasi->pengusul)
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->first();

        if (! $pengusul) {
            return;
        }

        $message = self::buildStatusChangeMessage($mutasi, $status, $type);
        self::sendTelegram($pengusul->telegram_chat_id, $message);
    }

    /**
     * Notify the proposer only when the persisted mutation status changes.
     */
    public static function notifyPengusulOnStatusChange(
        object $mutasi,
        ?string $previousStatus,
        string $newStatus,
        string $type = 'keluar'
    ): void {
        if ($previousStatus === $newStatus) {
            Log::info('Telegram status notification skipped because status is unchanged.', [
                'id_mutasi' => $mutasi->id_mutasi ?? null,
                'status' => $newStatus,
            ]);

            return;
        }

        self::notifyPengusul($mutasi, $newStatus, $type);
    }

    /**
     * Get service schedule info from Settings for notification messages
     */
    public static function getServiceScheduleInfo(): ?string
    {
        try {
            $dayNames = [
                0 => 'Minggu',
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
            ];

            // Get allowed days
            $allowedDays = Setting::getAllowedDays();
            if (empty($allowedDays)) {
                return null;
            }

            $allowedDayNames = array_map(fn ($d) => $dayNames[$d] ?? '', $allowedDays);
            $daysText = implode(', ', array_filter($allowedDayNames));

            $info = "📅 Hari: {$daysText}";

            // Add working hours if enabled
            if (Setting::isHourRestrictionEnabled()) {
                $startTime = Setting::get('working_hours_start', '08:00');
                $endTime = Setting::get('working_hours_end', '16:00');
                $info .= "\n⏰ Jam: {$startTime} - {$endTime} WIB";
            }

            return $info;
        } catch (\Exception $e) {
            Log::warning('Failed to get service schedule: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Sanitize phone number for Indonesia format
     */
    public static function sanitizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) < 10) {
            return null;
        }
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Process Telegram webhook for /start command
     * Returns user's chat ID
     */
    public static function processWebhook(array $update): ?array
    {
        if (! isset($update['message'])) {
            return null;
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';
        $firstName = $message['from']['first_name'] ?? 'User';

        if (! $chatId) {
            return null;
        }

        // Handle /start command
        if (str_starts_with($text, '/start')) {
            $escapedFirstName = self::escape((string) $firstName);
            $responseText = "👋 <b>Halo, {$escapedFirstName}!</b>\n\n"
                ."🆔 <b>CHAT ID ANDA:</b>\n\n"
                ."👉 <code>{$chatId}</code> 👈\n\n" // Emphasize with arrows and code block
                ."📋 <b>CARA MENGHUBUNGKAN:</b>\n"
                ."1. Salin Chat ID di atas\n"
                ."2. Login ke Aplikasi SIMAK\n"
                ."3. Masuk ke Menu <b>Profil Saya</b>\n"
                ."4. Tempel di kolom <b>Telegram Chat ID</b>\n"
                ."5. Klik Simpan\n\n"
                ."✅ <i>Akun Anda akan terhubung untuk menerima notifikasi otomatis.</i>\n\n"
                ."---\n<b>SIMAK - Dinas Pendidikan Kota Bogor</b>";

            self::sendTelegram((string) $chatId, $responseText);

            return [
                'chat_id' => $chatId,
                'first_name' => $firstName,
            ];
        }

        return null;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
