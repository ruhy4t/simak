<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class NotificationHelper
{
    /**
     * Generate WhatsApp click-to-chat link (wa.me)
     * This is FREE and doesn't require API - user clicks link to open WA
     */
    public static function generateWhatsAppLink(string $phone, string $message): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        $encodedMessage = urlencode($message);
        return "https://wa.me/{$phone}?text={$encodedMessage}";
    }

    /**
     * Send Telegram notification via Bot API using cURL (no Guzzle required)
     */
    /**
     * Send Telegram notification via Bot API using cURL with IP fallback
     */
    public static function sendTelegram(string $chatId, string $message): bool
    {
        $botToken = config('services.telegram.bot_token');

        if (empty($botToken) || empty($chatId)) {
            Log::warning('Telegram notification skipped: missing bot token or chat ID');
            return false;
        }

        $postData = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        // Strategy 1: Standard Domain (Preferred)
        $urlDomain = "https://api.telegram.org/bot{$botToken}/sendMessage";

        // Strategy 2: Direct IP (Fallback for DNS blocking)
        $urlIp = "https://149.154.167.220/bot{$botToken}/sendMessage";

        if (self::executeCurl($urlDomain, $postData)) {
            return true;
        }

        Log::warning("Telegram: Domain connection failed, trying Direct IP fallback...");

        if (self::executeCurl($urlIp, $postData, ['Host: api.telegram.org'])) {
            return true;
        }

        Log::error("Telegram: All connection strategies failed.");
        return false;
    }

    /**
     * Execute cURL request
     */
    private static function executeCurl(string $url, array $postData, array $headers = []): bool
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postData),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::warning("Telegram cURL error ({$url}): {$curlError}");
                return false;
            }

            if ($httpCode === 200) {
                Log::info("Telegram sent successfully via " . parse_url($url, PHP_URL_HOST));
                return true;
            } else {
                Log::error("Telegram failed (HTTP {$httpCode}): {$response}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Telegram exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to all Dinas users about new submission
     * Uses synchronous sending for reliability on shared hosting
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

        Log::info("Found " . $dinasUsers->count() . " Dinas users with Telegram chat ID");

        if ($dinasUsers->isEmpty()) {
            Log::warning("No Dinas users found with Telegram chat ID - skipping notification");
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
        $id = $mutasi->id_mutasi ?? '-';
        $pengusul = $mutasi->pengusul ?? '-';
        $sekolah = $type === 'masuk'
            ? ($mutasi->nama_sekolah_tujuan ?? '-')
            : ($mutasi->nama_sekolah_asal ?? '-');

        return "📥 <b>PENGAJUAN BARU</b>\n\n"
            . "📋 Jenis: {$jenis}\n"
            . "🆔 ID: {$id}\n"
            . "🏫 Sekolah: {$sekolah}\n"
            . "👤 Pengusul: {$pengusul}\n\n"
            . "⏳ Status: <b>Menunggu Verifikasi</b>\n\n"
            . "Silakan login ke SIMAK untuk memverifikasi.";
    }

    /**
     * Notify Dinas about cancelled submission
     */
    public static function notifyDinasCancelledSubmission(object $mutasi, string $type = 'keluar', string $reason = ''): void
    {
        Log::info("notifyDinasCancelledSubmission called for {$mutasi->id_mutasi}");

        $dinasUsers = DB::table('users')
            ->whereIn('role', ['Dinas', 'SuperAdmin'])
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->where('is_active', 1)
            ->get();

        if ($dinasUsers->isEmpty()) {
            return;
        }

        $message = self::buildCancelledSubmissionMessage($mutasi, $type, $reason);

        foreach ($dinasUsers as $user) {
            self::sendTelegram($user->telegram_chat_id, $message);
        }
    }

    /**
     * Build message for cancelled submission
     */
    public static function buildCancelledSubmissionMessage(object $mutasi, string $type = 'keluar', string $reason = ''): string
    {
        $jenis = $type === 'masuk' ? 'Mutasi Masuk' : 'Mutasi Keluar';
        $id = $mutasi->id_mutasi ?? '-';
        $pengusul = $mutasi->pengusul ?? '-';
        $namaSiswa = $mutasi->nama_murid ?? $mutasi->nama_siswa ?? '-';
        $sekolah = $type === 'masuk'
            ? ($mutasi->nama_sekolah_tujuan ?? '-')
            : ($mutasi->nama_sekolah_asal ?? '-');

        $message = "🚫 <b>USULAN DIBATALKAN</b>\n\n"
            . "📋 Jenis: {$jenis}\n"
            . "🆔 ID: {$id}\n"
            . "👤 Siswa: {$namaSiswa}\n"
            . "🏫 Sekolah: {$sekolah}\n"
            . "📝 Pengusul: {$pengusul}\n\n"
            . "❌ Status: <b>Dibatalkan/Dihapus</b>";

        if (!empty($reason)) {
            $message .= "\n\n📝 Alasan: {$reason}";
        }

        $message .= "\n\n---\nSIMAK - Sistem Informasi Mutasi";

        return $message;
    }

    /**
     * Build message for status change (to Pengusul)
     */
    public static function buildStatusChangeMessage(object $mutasi, string $status, string $type = 'keluar'): string
    {
        $jenis = $type === 'masuk' ? 'Mutasi Masuk' : 'Mutasi Keluar';
        $id = $mutasi->id_mutasi ?? '-';
        $namaSiswa = $mutasi->nama_murid ?? $mutasi->nama_siswa ?? 'Siswa';

        $statusEmoji = match ($status) {
            'Disetujui Dinas' => '✅',
            'Perlu Perbaikan' => '⚠️',
            'Proses TTD' => '✍️',
            'Selesai' => '🎉',
            default => '📋'
        };

        $message = "{$statusEmoji} <b>UPDATE STATUS MUTASI</b>\n\n"
            . "📋 Jenis: {$jenis}\n"
            . "🆔 ID: {$id}\n"
            . "👤 Siswa: {$namaSiswa}\n\n"
            . "📊 Status: <b>{$status}</b>\n";

        // Add specific instructions based on status
        switch ($status) {
            case 'Disetujui Dinas':
                $message .= "\n✅ Data Anda telah disetujui!\nMohon tunggu penerbitan surat izin.";
                break;
            case 'Perlu Perbaikan':
                $catatan = $mutasi->catatan_berkas ?? '';
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
                $nomor = $mutasi->nomor_surat_izin ?? '-';
                $message .= "\n📄 Nomor Surat: {$nomor}\n"
                    . "\n🎉 Surat sudah selesai!\n"
                    . "Dapat diambil ke <b>Seksi Kurikulum SMP Bidang SMP Dinas Pendidikan Kota Bogor</b>.";
                break;
        }

        $message .= "\n\n---\nSIMAK - Sistem Informasi Mutasi";
        return $message;
    }

    /**
     * Build completion message (kept for backward compatibility)
     */
    public static function buildCompletionMessage(object $mutasi, string $type = 'keluar'): string
    {
        return self::buildStatusChangeMessage($mutasi, 'Selesai', $type);
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

        if (!$pengusul) {
            return;
        }

        $message = self::buildStatusChangeMessage($mutasi, $status, $type);
        self::sendTelegram($pengusul->telegram_chat_id, $message);
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
                6 => 'Sabtu'
            ];

            // Get allowed days
            $allowedDays = Setting::getAllowedDays();
            if (empty($allowedDays)) {
                return null;
            }

            $allowedDayNames = array_map(fn($d) => $dayNames[$d] ?? '', $allowedDays);
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
            Log::warning("Failed to get service schedule: " . $e->getMessage());
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
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }

    /**
     * Process Telegram webhook for /start command
     * Returns user's chat ID
     */
    public static function processWebhook(array $update): ?array
    {
        if (!isset($update['message'])) {
            return null;
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';
        $firstName = $message['from']['first_name'] ?? 'User';

        if (!$chatId) {
            return null;
        }

        // Handle /start command
        if (str_starts_with($text, '/start')) {
            $responseText = "👋 Halo, {$firstName}!\n\n"
                . "🆔 <b>Chat ID Anda:</b>\n<code>{$chatId}</code>\n\n"
                . "📋 <b>Cara Menggunakan:</b>\n"
                . "1. Copy Chat ID di atas\n"
                . "2. Login ke SIMAK\n"
                . "3. Buka Menu Profil\n"
                . "4. Paste Chat ID di kolom Telegram\n"
                . "5. Simpan\n\n"
                . "✅ Setelah itu, Anda akan menerima notifikasi status mutasi di Telegram!\n\n"
                . "---\nSIMAK - Dinas Pendidikan Kota Bogor";

            self::sendTelegram((string) $chatId, $responseText);

            return [
                'chat_id' => $chatId,
                'first_name' => $firstName,
            ];
        }

        return null;
    }
}