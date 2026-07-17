<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyTaskService
{
    private const CHUNK_SIZE = 100;

    public function __construct(private readonly FileUploadService $files) {}

    /** @return array{status:string, sent_count:int, cancelled_count:int, executed_at:string, reason?:string} */
    public function run(?CarbonInterface $now = null): array
    {
        $now = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now('Asia/Jakarta');

        if (! in_array($now->dayOfWeek, Setting::getAllowedDays(), true)) {
            return $this->skipped($now, 'Not a working day');
        }

        if (Holiday::isHoliday($now->toDateString())) {
            return $this->skipped($now, 'Holiday');
        }

        // Telegram notifications are event-driven. Cron must never send
        // recurring reminders for unchanged mutation records.
        $sentCount = 0;
        $cancelledCount = $this->autoCancelOldMutations($now);

        Log::info('Daily mutation tasks completed.', [
            'sent_count' => $sentCount,
            'cancelled_count' => $cancelledCount,
        ]);

        return [
            'status' => 'success',
            'sent_count' => $sentCount,
            'cancelled_count' => $cancelledCount,
            'executed_at' => $now->toDateTimeString(),
        ];
    }

    public function autoCancelOldMutations(?CarbonInterface $now = null): int
    {
        $now = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now('Asia/Jakarta');
        $threshold = $now->subDays(30);

        return $this->autoCancelForTable('mutasi_masuk', 'masuk', $threshold)
            + $this->autoCancelForTable('mutasi_keluar', 'keluar', $threshold);
    }

    private function autoCancelForTable(string $table, string $type, CarbonImmutable $threshold): int
    {
        $cancelledCount = 0;

        DB::table($table)
            ->where('status', 'Perlu Perbaikan')
            ->where('updated_at', '<', $threshold)
            ->orderBy('id_mutasi')
            ->chunkById(self::CHUNK_SIZE, function ($mutations) use (&$cancelledCount, $table, $type): void {
                foreach ($mutations as $mutation) {
                    try {
                        DB::transaction(function () use ($mutation, $table, $type): void {
                            $this->deleteMutationFiles($mutation, $type);

                            if ($type === 'masuk') {
                                DB::table('mutasi_masuk_siswa')
                                    ->where('id_mutasi', $mutation->id_mutasi)
                                    ->orderBy('id')
                                    ->chunkById(self::CHUNK_SIZE, function ($students): void {
                                        foreach ($students as $student) {
                                            $this->deleteStudentFiles($student);
                                        }
                                    });

                                DB::table('mutasi_masuk_siswa')->where('id_mutasi', $mutation->id_mutasi)->delete();
                            }

                            DB::table($table)->where('id_mutasi', $mutation->id_mutasi)->delete();
                        });

                        $cancelledCount++;
                        Log::info('Old mutation auto-cancelled.', ['id_mutasi' => $mutation->id_mutasi]);
                    } catch (\Throwable $exception) {
                        Log::error('Failed to auto-cancel mutation.', [
                            'id_mutasi' => $mutation->id_mutasi,
                            'exception' => $exception,
                        ]);
                    }
                }
            }, 'id_mutasi', 'id_mutasi');

        return $cancelledCount;
    }

    private function deleteMutationFiles(object $mutation, string $type): void
    {
        $columns = $type === 'masuk'
            ? ['file_surat_permohonan', 'file_arsip_scan']
            : ['file_suket_keluar', 'file_suket_dapo', 'file_suket_diterima', 'file_rapor', 'file_arsip_scan'];

        $this->deletePaths($mutation, $columns);
    }

    private function deleteStudentFiles(object $student): void
    {
        $this->deletePaths($student, [
            'file_surat_pindah', 'file_surat_dapodik', 'file_rekomendasi', 'file_ijazah_sd',
            'file_rapor', 'file_surat_penyaluran', 'file_rekam_didik',
        ]);
    }

    /** @param list<string> $columns */
    private function deletePaths(object $record, array $columns): void
    {
        foreach ($columns as $column) {
            $path = $record->{$column} ?? null;
            if ($path) {
                $this->files->deleteOldFile((string) $path);
            }
        }
    }

    /** @return array{status:string, sent_count:int, cancelled_count:int, executed_at:string, reason:string} */
    private function skipped(CarbonImmutable $now, string $reason): array
    {
        return [
            'status' => 'skipped',
            'reason' => $reason,
            'sent_count' => 0,
            'cancelled_count' => 0,
            'executed_at' => $now->toDateTimeString(),
        ];
    }
}
