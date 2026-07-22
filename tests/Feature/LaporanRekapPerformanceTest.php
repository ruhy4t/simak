<?php

namespace Tests\Feature;

use App\Http\Controllers\LaporanController;
use App\Models\SatuanPendidikan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class LaporanRekapPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('mutasi_masuk', function (Blueprint $table): void {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('npsn_sekolah_tujuan');
            $table->string('status');
        });
        Schema::create('mutasi_masuk_siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('id_mutasi');
            $table->string('kelas');
            $table->string('jenis_kelamin');
        });
        Schema::create('mutasi_keluar', function (Blueprint $table): void {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('npsn_sekolah_asal');
            $table->string('kelas');
            $table->string('jenis_kelamin');
            $table->string('status');
            $table->softDeletes();
        });

        DB::table('mutasi_masuk')->insert([
            ['id_mutasi' => 'M-JAN', 'tanggal_pengajuan' => '2026-01-20', 'npsn_sekolah_tujuan' => '12345678', 'status' => 'Selesai'],
            ['id_mutasi' => 'M-MAR', 'tanggal_pengajuan' => '2026-03-05', 'npsn_sekolah_tujuan' => '12345678', 'status' => 'Selesai'],
        ]);
        DB::table('mutasi_masuk_siswa')->insert([
            ['id_mutasi' => 'M-JAN', 'kelas' => 'VII', 'jenis_kelamin' => 'L'],
            ['id_mutasi' => 'M-MAR', 'kelas' => 'IX', 'jenis_kelamin' => 'P'],
        ]);
        DB::table('mutasi_keluar')->insert([
            'id_mutasi' => 'K-FEB',
            'tanggal_pengajuan' => '2026-02-10',
            'npsn_sekolah_asal' => '12345678',
            'kelas' => 'VIII',
            'jenis_kelamin' => 'P',
            'status' => 'Selesai',
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_school_rekap_aggregates_the_complete_range_in_two_queries(): void
    {
        $school = new SatuanPendidikan([
            'npsn' => '12345678',
            'jumlah_siswa_7_l' => 10,
            'jumlah_siswa_7_p' => 11,
            'jumlah_siswa_8_l' => 12,
            'jumlah_siswa_8_p' => 13,
            'jumlah_siswa_9_l' => 14,
            'jumlah_siswa_9_p' => 15,
        ]);
        $user = new User(['role' => 'Operator', 'npsn' => '12345678']);
        $user->setRelation('sekolah', $school);

        DB::enableQueryLog();
        $method = new ReflectionMethod(LaporanController::class, 'getRekapData');
        $report = $method->invoke(app(LaporanController::class), '2026-01-15', '2026-03-10', $user);

        $aggregateQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'mutasi_masuk')
                || str_contains($query['query'], 'mutasi_keluar'));

        $this->assertCount(2, $aggregateQueries);
        $this->assertCount(3, $report);
        $this->assertSame(1, $report[0]['masuk']);
        $this->assertSame(1, $report[1]['keluar']);
        $this->assertSame(1, $report[2]['masuk']);
    }
}
