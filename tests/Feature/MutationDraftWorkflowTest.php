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
        $this->assertTrue($response->getData(true)['success']);
        $this->assertDatabaseHas('mutasi_masuk', [
            'jumlah_siswa' => 1,
            'tanggal_surat' => '1900-01-02',
            'status' => 'Draft',
            'pengusul' => 'operator-draft',
        ]);
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

        $outgoingView = file_get_contents(resource_path('views/mutasi/keluar/create.blade.php'));
        $this->assertStringContainsString("route('api.upload-temp')", $outgoingView);
        $this->assertStringContainsString('File sudah tersimpan di draft', $outgoingView);
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
