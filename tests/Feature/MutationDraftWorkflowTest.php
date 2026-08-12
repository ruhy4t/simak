<?php

namespace Tests\Feature;

use App\Http\Controllers\MutasiController;
use App\Http\Controllers\MutasiMasukController;
use App\Models\User;
use App\Services\TemporaryUploadService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MutationDraftWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('mutasi_keluar', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->string('nama_murid');
            $table->string('nisn', 10);
            $table->string('nis')->nullable();
            $table->string('kelas');
            $table->string('jenis_kelamin');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('npsn_sekolah_asal', 8);
            $table->string('nama_sekolah_asal');
            $table->text('alamat_sekolah_asal');
            $table->string('npsn_sekolah_tujuan', 8);
            $table->string('nama_sekolah_tujuan');
            $table->text('alamat_sekolah_tujuan')->nullable();
            $table->string('provinsi_tujuan');
            $table->string('kabupaten_kota_tujuan');
            $table->text('alasan_pindah');
            $table->string('nomor_surat_sekolah')->nullable();
            $table->date('tanggal_surat_sekolah')->nullable();
            $table->boolean('is_dinas_entry')->default(false);
            $table->boolean('ceklis_surat_sekolah')->default(false);
            $table->boolean('ceklis_berkas_dapodik')->default(false);
            $table->boolean('ceklis_sekolah_tujuan')->default(false);
            $table->boolean('ceklis_rapor')->default(false);
            $table->string('file_suket_keluar')->nullable();
            $table->string('file_suket_dapo')->nullable();
            $table->string('file_suket_diterima')->nullable();
            $table->string('file_rapor')->nullable();
            $table->string('status');
            $table->string('pengusul');
            $table->timestamps();
        });

        Schema::create('satuan_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 8)->unique();
            $table->string('nama');
            $table->text('alamat_lengkap')->nullable();
            $table->string('alamat_jalan')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->boolean('is_local')->default(false);
        });

        Schema::create('mutasi_masuk', function (Blueprint $table) {
            $table->string('id_mutasi')->primary();
            $table->date('tanggal_pengajuan');
            $table->integer('jumlah_siswa')->default(1);
            $table->string('nomor_surat');
            $table->date('tanggal_surat');
            $table->string('file_surat_permohonan')->nullable();
            $table->string('npsn_sekolah_tujuan', 8);
            $table->string('nama_sekolah_tujuan');
            $table->text('alamat_sekolah_tujuan');
            $table->string('status');
            $table->string('pengusul');
            $table->boolean('is_dinas_entry')->default(false);
            $table->timestamps();
        });

        $user = new User([
            'username' => 'operator-draft',
            'role' => 'Operator',
            'npsn' => '12345678',
            'nama_sekolah' => 'SMP Pengujian',
            'alamat' => 'Kota Bogor',
            'status' => 'Aktif',
        ]);
        $user->setAttribute('id', 42);
        Auth::setUser($user);
    }

    protected function tearDown(): void
    {
        Auth::forgetUser();
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_outgoing_draft_can_be_saved_without_final_fields_or_extra_schema_column(): void
    {
        Storage::fake('private_docs');
        $pdf = UploadedFile::fake()->createWithContent('surat-keluar.pdf', "%PDF-1.4\n% test\n");
        $request = $this->jsonRequest('/mutasi-keluar/draft', [
            'npsn_sekolah_asal' => '12345678',
            'nama_sekolah_asal' => 'SMP Pengujian',
            'alamat_sekolah_asal' => 'Kota Bogor',
            'tujuan_luar_negeri' => '1',
            'provinsi_tujuan' => 'Luar Negeri',
            'kabupaten_kota_tujuan' => 'Luar Negeri',
        ], ['file_suket_keluar' => $pdf]);

        $response = app(MutasiController::class)->saveDraft($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertMatchesRegularExpression(
            '/^MK'.now()->format('Ymd').'\d{4}$/',
            DB::table('mutasi_keluar')->value('id_mutasi')
        );
        $this->assertDatabaseHas('mutasi_keluar', [
            'status' => 'Draft',
            'pengusul' => 'operator-draft',
            'npsn_sekolah_asal' => '12345678',
            'provinsi_tujuan' => 'Luar Negeri',
        ]);
        $this->assertFalse(Schema::hasColumn('mutasi_keluar', 'tujuan_luar_negeri'));

        $savedPath = DB::table('mutasi_keluar')->value('file_suket_keluar');
        $this->assertNotEmpty($savedPath);
        Storage::disk('private_docs')->assertExists($savedPath);
    }

    public function test_outgoing_draft_reports_invalid_fields_instead_of_database_error(): void
    {
        $request = $this->jsonRequest('/mutasi-keluar/draft', [
            'nama_murid' => 'Budi Santoso',
            'kelas' => 'X',
            'tanggal_lahir' => 'bukan-tanggal',
            'npsn_sekolah_asal' => '12345678',
            'nama_sekolah_asal' => 'SMP Pengujian',
            'alamat_sekolah_asal' => 'Kota Bogor',
        ]);

        $response = app(MutasiController::class)->saveDraft($request);
        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Data draft belum valid.', $payload['message']);
        $this->assertArrayHasKey('kelas', $payload['errors']);
        $this->assertArrayHasKey('tanggal_lahir', $payload['errors']);
        $this->assertStringContainsString('Kelas', $payload['errors']['kelas'][0]);
        $this->assertDatabaseCount('mutasi_keluar', 0);
    }

    public function test_incoming_nisn_lookup_prefills_from_completed_bogor_outgoing_mutation(): void
    {
        DB::table('satuan_pendidikan')->insert([
            'npsn' => '20220385',
            'nama' => 'SMP Negeri 1 Bogor',
            'alamat_lengkap' => 'Jl. Ir. H. Djuanda No. 16',
            'kota' => 'Kota Bogor',
            'provinsi' => 'Jawa Barat',
            'is_local' => true,
        ]);
        $this->insertOutgoingMutation([
            'id_mutasi' => 'MK-PREFILL-1',
            'nisn' => '0012345678',
            'status' => 'Selesai',
            'npsn_sekolah_asal' => '20220385',
        ]);

        $response = app(MutasiMasukController::class)->lookupStudentByNisn('0012345678');
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertTrue($payload['found']);
        $this->assertSame('MK-PREFILL-1', $payload['data']['source_id']);
        $this->assertSame('Budi Santoso', $payload['data']['nama_murid']);
        $this->assertSame('SMP Negeri 1 Bogor', $payload['data']['nama_sekolah_asal']);
        $this->assertSame('Kota Bogor', $payload['data']['kabupaten_kota_asal']);
    }

    public function test_incoming_nisn_lookup_rejects_unfinished_or_non_bogor_sources(): void
    {
        DB::table('satuan_pendidikan')->insert([
            [
                'npsn' => '20220385',
                'nama' => 'SMP Negeri 1 Bogor',
                'kota' => 'Kota Bogor',
                'provinsi' => 'Jawa Barat',
                'is_local' => true,
            ],
            [
                'npsn' => '20299999',
                'nama' => 'SMP Luar Kota',
                'kota' => 'Kabupaten Bogor',
                'provinsi' => 'Jawa Barat',
                'is_local' => false,
            ],
        ]);
        $this->insertOutgoingMutation([
            'id_mutasi' => 'MK-PENDING',
            'nisn' => '0012345678',
            'status' => 'Menunggu Verifikasi Dinas',
            'npsn_sekolah_asal' => '20220385',
        ]);
        $this->insertOutgoingMutation([
            'id_mutasi' => 'MK-APPROVED',
            'nisn' => '0011111111',
            'status' => 'Disetujui Dinas',
            'npsn_sekolah_asal' => '20220385',
        ]);
        $this->insertOutgoingMutation([
            'id_mutasi' => 'MK-SIGNING',
            'nisn' => '0022222222',
            'status' => 'Proses TTD',
            'npsn_sekolah_asal' => '20220385',
        ]);
        $this->insertOutgoingMutation([
            'id_mutasi' => 'MK-NON-BOGOR',
            'nisn' => '0098765432',
            'status' => 'Selesai',
            'npsn_sekolah_asal' => '20299999',
        ]);

        foreach (['0012345678', '0011111111', '0022222222', '0098765432'] as $nisn) {
            $payload = app(MutasiMasukController::class)
                ->lookupStudentByNisn($nisn)
                ->getData(true);

            $this->assertTrue($payload['success']);
            $this->assertFalse($payload['found']);
            $this->assertNull($payload['data']);
        }
    }

    public function test_single_student_incoming_draft_can_be_saved_with_partial_header(): void
    {
        $request = $this->jsonRequest('/mutasi-masuk/draft', [
            'jumlah_siswa' => 1,
            'npsn_sekolah_tujuan' => '12345678',
            'nama_sekolah_tujuan' => 'SMP Pengujian',
            'alamat_sekolah_tujuan' => 'Kota Bogor',
        ]);

        $response = app(MutasiMasukController::class)->createDraft($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertMatchesRegularExpression(
            '/^MM'.now()->format('Ymd').'\d{4}$/',
            $response->getData(true)['id_mutasi']
        );
        $this->assertTrue($response->getData(true)['success']);
        $this->assertDatabaseHas('mutasi_masuk', [
            'jumlah_siswa' => 1,
            'tanggal_surat' => '1900-01-02',
            'status' => 'Draft',
            'pengusul' => 'operator-draft',
        ]);
    }

    public function test_incoming_student_draft_reports_every_missing_required_field(): void
    {
        $draftResponse = app(MutasiMasukController::class)->createDraft(
            $this->jsonRequest('/mutasi-masuk/draft', [
                'jumlah_siswa' => 1,
                'npsn_sekolah_tujuan' => '12345678',
                'nama_sekolah_tujuan' => 'SMP Pengujian',
                'alamat_sekolah_tujuan' => 'Kota Bogor',
            ])
        );
        $draftId = $draftResponse->getData(true)['id_mutasi'];

        $response = app(MutasiMasukController::class)->addStudentToDraft(
            $this->jsonRequest("/mutasi-masuk/draft/{$draftId}/siswa", [
                'student_index' => 0,
                'nama_murid' => 'Afiqa Febiana Ayundi',
                'nisn' => '123',
            ]),
            $draftId
        );
        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Data murid belum lengkap atau belum valid.', $payload['message']);
        $this->assertArrayHasKey('nisn', $payload['errors']);
        $this->assertArrayHasKey('tanggal_lahir', $payload['errors']);
        $this->assertArrayHasKey('tanggal_diterima', $payload['errors']);
        $this->assertArrayHasKey('nama_sekolah_asal', $payload['errors']);
        $this->assertStringContainsString('NISN', $payload['errors']['nisn'][0]);
        $this->assertStringContainsString('Tanggal lahir', $payload['errors']['tanggal_lahir'][0]);
        $this->assertStringNotContainsString('hubungi administrator', json_encode($payload));
    }

    public function test_incoming_header_upload_is_promoted_and_kept_by_draft(): void
    {
        Storage::fake('local');
        Storage::fake('private_docs');

        $temporaryUploads = app(TemporaryUploadService::class);
        $temporaryPath = $temporaryUploads->createPath('surat-permohonan.pdf', Auth::id());
        Storage::disk('local')->put($temporaryPath, "%PDF-1.4\n% incoming draft\n");

        $request = $this->jsonRequest('/mutasi-masuk/draft', [
            'jumlah_siswa' => 1,
            'npsn_sekolah_tujuan' => '12345678',
            'nama_sekolah_tujuan' => 'SMP Pengujian',
            'alamat_sekolah_tujuan' => 'Kota Bogor',
            'file_surat_permohonan' => $temporaryPath,
        ]);

        $response = app(MutasiMasukController::class)->createDraft($request);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame(
            DB::table('mutasi_masuk')->value('file_surat_permohonan'),
            $response->getData(true)['file_surat_permohonan']
        );
        $savedPath = DB::table('mutasi_masuk')->value('file_surat_permohonan');
        $this->assertNotEmpty($savedPath);
        Storage::disk('private_docs')->assertExists($savedPath);
        Storage::disk('local')->assertMissing($temporaryPath);
    }

    public function test_incoming_draft_mode_is_derived_from_authenticated_role(): void
    {
        $operatorRequest = $this->jsonRequest('/mutasi-masuk/draft', [
            'jumlah_siswa' => 1,
            'npsn_sekolah_tujuan' => '12345678',
            'nama_sekolah_tujuan' => 'SMP Pengujian',
            'alamat_sekolah_tujuan' => 'Kota Bogor',
            'is_dinas_entry' => '1',
        ]);

        $operatorResponse = app(MutasiMasukController::class)->createDraft($operatorRequest);

        $this->assertTrue($operatorResponse->getData(true)['success']);
        $this->assertDatabaseHas('mutasi_masuk', [
            'id_mutasi' => $operatorResponse->getData(true)['id_mutasi'],
            'is_dinas_entry' => 0,
        ]);
    }

    public function test_incoming_draft_rejects_untrusted_persistent_file_reference(): void
    {
        $request = $this->jsonRequest('/mutasi-masuk/draft', [
            'jumlah_siswa' => 1,
            'npsn_sekolah_tujuan' => '12345678',
            'nama_sekolah_tujuan' => 'SMP Pengujian',
            'alamat_sekolah_tujuan' => 'Kota Bogor',
            'file_surat_permohonan' => 'mutasi_masuk/dokumen-pengguna-lain.pdf',
        ]);

        $response = app(MutasiMasukController::class)->createDraft($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
        $this->assertDatabaseCount('mutasi_masuk', 0);
    }

    public function test_outgoing_temporary_upload_is_promoted_and_kept_by_draft(): void
    {
        Storage::fake('local');
        Storage::fake('private_docs');

        $temporaryUploads = app(TemporaryUploadService::class);
        $temporaryPath = $temporaryUploads->createPath('dapodik.pdf', Auth::id());
        Storage::disk('local')->put($temporaryPath, "%PDF-1.4\n% draft upload\n");

        $request = $this->jsonRequest('/mutasi-keluar/draft', [
            'npsn_sekolah_asal' => '12345678',
            'nama_sekolah_asal' => 'SMP Pengujian',
            'alamat_sekolah_asal' => 'Kota Bogor',
            'file_suket_dapo' => $temporaryPath,
        ]);

        $response = app(MutasiController::class)->saveDraft($request);

        $this->assertTrue($response->getData(true)['success']);
        $savedPath = DB::table('mutasi_keluar')->value('file_suket_dapo');
        $this->assertNotEmpty($savedPath);
        Storage::disk('private_docs')->assertExists($savedPath);
        Storage::disk('local')->assertMissing($temporaryPath);
    }

    public function test_draft_routes_are_open_while_final_submission_remains_workday_restricted(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());
        $incomingCreate = $routes->first(fn ($route) => $route->getName() === 'mutasi.masuk.draft.create');
        $incomingFinalize = $routes->first(fn ($route) => $route->getName() === 'mutasi.masuk.draft.finalize');
        $outgoingDraft = $routes->first(fn ($route) => $route->getName() === 'mutasi.keluar.draft.save');

        $this->assertNotContains('workday', $incomingCreate->gatherMiddleware());
        $this->assertNotContains('workday', $outgoingDraft->gatherMiddleware());
        $this->assertContains('workday', $incomingFinalize->gatherMiddleware());

        $incomingScript = file_get_contents(public_path('js/mutasi-masuk.js'));
        $this->assertStringContainsString('createDraftIfNeeded(true)', $incomingScript);
        $this->assertStringContainsString("uploadedHeader.dataset.tempPath = 'false'", $incomingScript);
        $this->assertSame(1, substr_count($incomingScript, "form.addEventListener('submit'"));
        $this->assertStringContainsString('if (draftId && totalSiswa > 1)', $incomingScript);
        $this->assertStringContainsString('if (draftCreationPromise) return draftCreationPromise;', $incomingScript);
        $this->assertStringContainsString('if (isFinalizing) return;', $incomingScript);

        $outgoingView = file_get_contents(resource_path('views/mutasi/keluar/create.blade.php'));
        $this->assertStringContainsString("route('api.upload-temp')", $outgoingView);
        $this->assertStringContainsString('File sudah tersimpan di draft', $outgoingView);

        $outgoingController = file_get_contents(app_path('Http/Controllers/MutasiController.php'));
        $this->assertStringContainsString('->lockForUpdate()', $outgoingController);
        $this->assertStringContainsString("->whereNotIn('status', ['Draft', 'Dibatalkan', 'Ditolak'])", $outgoingController);
    }

    private function insertOutgoingMutation(array $overrides = []): void
    {
        DB::table('mutasi_keluar')->insert(array_merge([
            'id_mutasi' => 'MK-TEST-1',
            'tanggal_pengajuan' => '2026-07-15',
            'nama_murid' => 'Budi Santoso',
            'nisn' => '0012345678',
            'nis' => '12345',
            'kelas' => 'VIII',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bogor',
            'tanggal_lahir' => '2012-01-02',
            'npsn_sekolah_asal' => '20220385',
            'nama_sekolah_asal' => 'SMP Negeri 1 Bogor',
            'alamat_sekolah_asal' => 'Kota Bogor',
            'npsn_sekolah_tujuan' => '20200001',
            'nama_sekolah_tujuan' => 'SMP Tujuan',
            'provinsi_tujuan' => 'Jawa Barat',
            'kabupaten_kota_tujuan' => 'Kota Bandung',
            'alasan_pindah' => 'Mengikuti orang tua',
            'status' => 'Selesai',
            'pengusul' => 'operator-draft',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function jsonRequest(string $uri, array $data, array $files = []): Request
    {
        $request = Request::create($uri, 'POST', $data, [], $files);
        $request->headers->set('Accept', 'application/json');
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => Auth::user());

        return $request;
    }
}
