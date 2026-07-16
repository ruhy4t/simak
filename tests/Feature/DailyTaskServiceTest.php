<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramJob;
use App\Services\DailyTaskService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyTaskServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role');
            $table->string('nama_pengguna');
            $table->string('status');
            $table->boolean('is_active');
            $table->string('telegram_chat_id')->nullable();
            $table->timestamps();
        });

        foreach (['mutasi_masuk', 'mutasi_keluar'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->string('id_mutasi')->primary();
                $table->date('tanggal_pengajuan');
                $table->string('status');
                $table->string('pengusul');
                $table->text('catatan_berkas')->nullable();
                if ($tableName === 'mutasi_masuk') {
                    $table->string('file_surat_permohonan')->nullable();
                }
                $table->timestamps();
            });
        }

        Schema::create('mutasi_masuk_siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('id_mutasi');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_reminders_are_queued_across_multiple_chunks(): void
    {
        Queue::fake();
        $this->insertUser();
        $now = CarbonImmutable::parse('2026-07-16 06:00:00', 'Asia/Jakarta');

        $rows = [];
        for ($index = 1; $index <= 105; $index++) {
            $rows[] = $this->incomingMutation(sprintf('MM-%03d', $index), $now->subDay());
        }
        DB::table('mutasi_masuk')->insert($rows);

        $sent = app(DailyTaskService::class)->sendRevisionReminders($now);

        $this->assertSame(105, $sent);
        Queue::assertPushed(SendTelegramJob::class, 105);
    }

    public function test_only_expired_revisions_are_auto_cancelled(): void
    {
        $now = CarbonImmutable::parse('2026-07-16 06:00:00', 'Asia/Jakarta');
        DB::table('mutasi_masuk')->insert([
            $this->incomingMutation('MM-OLD', $now->subDays(31)),
            $this->incomingMutation('MM-NEW', $now->subDays(29)),
        ]);

        $cancelled = app(DailyTaskService::class)->autoCancelOldMutations($now);

        $this->assertSame(1, $cancelled);
        $this->assertDatabaseMissing('mutasi_masuk', ['id_mutasi' => 'MM-OLD']);
        $this->assertDatabaseHas('mutasi_masuk', ['id_mutasi' => 'MM-NEW']);
    }

    private function insertUser(): void
    {
        DB::table('users')->insert([
            'username' => 'operator-test',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'nama_pengguna' => 'Operator Test',
            'status' => 'Aktif',
            'is_active' => true,
            'telegram_chat_id' => '123456789',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function incomingMutation(string $id, CarbonImmutable $updatedAt): array
    {
        return [
            'id_mutasi' => $id,
            'tanggal_pengajuan' => '2026-06-01',
            'status' => 'Perlu Perbaikan',
            'pengusul' => 'operator-test',
            'catatan_berkas' => '<script>alert(1)</script>',
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ];
    }
}
