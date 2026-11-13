<?php

namespace Tests\Feature;

use App\Http\Controllers\MutasiController;
use App\Http\Controllers\MutasiMasukController;
use App\Models\User;
use App\Services\MutasiService;
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

        Schema::create('mutation_id_sequences', function (Blueprint $table) {
            $table->string('type')->primary();
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();
        });

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
            $table->string('file_arsip_scan')->nullable();
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
            $table->string('file_arsip_scan')->nullable();
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

    public function test_submission_preparation_saves_a_draft_without_success_flash_and_restores_it_on_create(): void
    {
        $request = $this->jsonRequest('/mutasi-keluar/draft', [
            'submit_after_save' => '1',
            'npsn_sekolah_asal' => '12345678',
            'nama_sekolah_asal' => 'SMP Pengujian',
            'alamat_sekolah_asal' => 'Kota Bogor',
        ]);

        $response = app(MutasiController::class)->saveDraft($request);
        $draftId = $response->getData(true)['id_mutasi'];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($request->session()->has('success'));

        $request->session()->flashInput(['draft_id' => $draftId]);
        $createRequest = Request::create('/mutasi-keluar/create', 'GET');
        $createRequest->setLaravelSession($request->session());
        $createRequest->setUserResolver(fn () => Auth::user());

        $view = app(MutasiController::class)->create($createRequest);

        $this->assertSame($draftId, $view->getData()['draft']->id_mutasi);
    }

    public function test_outgoing_draft_enforces_per_document_size_limit_for_temporary_uploads(): void
    {
        Storage::fake('local');
        $tempPath = 'temp_uploads/42/temp_oversized_01234567890123456789.pdf';
        Storage::disk('local')->put($tempPath, "%PDF-1.4\n" . str_repeat('0', 1024 * 1024));

        $request = $this->jsonRequest('/mutasi-keluar/draft', [
            'npsn_sekolah_asal' => '12345678',
            'nama_sekolah_asal' => 'SMP Pengujian',
            'alamat_sekolah_asal' => 'Kota Bogor',
            'file_suket_keluar' => $tempPath,
        ]);

        $response = app(MutasiController::class)->saveDraft($request);
        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('file_suket_keluar', $payload['errors']);
        $this->assertDatabaseCount('mutasi_keluar', 0);
    }

    public function test_incoming_mutation_id_is_not_reused_after_a_record_is_deleted(): void
    {
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'MM202608100116',
            'tanggal_pengajuan' => '2026-08-10',
            'jumlah_siswa' => 1,
            'nomor_surat' => 'TEST/116',
            'tanggal_surat' => '2026-08-10',
            'npsn_sekolah_tujuan' => '12345678',
            'nama_sekolah_tujuan' => 'SMP Lama',
            'alamat_sekolah_tujuan' => 'Bogor',
            'status' => 'Draft',
            'pengusul' => 'operator-draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(MutasiService::class);
        $firstReservedId = $service->generateMutasiMasukId();

        DB::table('mutasi_masuk')->where('id_mutasi', 'MM202608100116')->delete();

        $secondReservedId = $service->generateMutasiMasukId();

        $this->assertStringEndsWith('0117', $firstReservedId);
        $this->assertStringEndsWith('0118', $secondReservedId);
        $this->assertNotSame($firstReservedId, $secondReservedId);
        $this->assertDatabaseHas('mutation_id_sequences', [
            'type' => 'masuk',
            'last_value' => 118,
        ]);
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

    public function test_incoming_create_form_shows_save_draft_for_school(): void
    {
        Auth::user()->role = 'Sekolah';
        Auth::user()->telegram_chat_id = '123456789';

        $this->actingAs(Auth::user())
            ->withoutMiddleware(\App\Http\Middleware\EnsureInitialStudentCountsCompleted::class)
            ->get(route('mutasi.masuk.create'))
            ->assertOk()
            ->assertSee('id="btnSaveDraft"', false)
            ->assertSee('Simpan Draft');
    }

    public function test_school_and_operator_can_render_incoming_mutation_index_with_students(): void
    {
        Schema::create('mutasi_masuk_siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('id_mutasi');
            $table->string('nama_murid');
            $table->string('nisn');
            $table->string('npsn_sekolah_asal')->nullable();
            $table->string('nama_sekolah_asal')->nullable();
            $table->string('kabupaten_kota_asal')->nullable();
        });

        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'MM-INDEX-1',
            'tanggal_pengajuan' => '2026-11-13',
            'jumlah_siswa' => 1,
            'nomor_surat' => '001/SM/2026',
            'tanggal_surat' => '2026-11-13',
            'npsn_sekolah_tujuan' => '12345678',
            'nama_sekolah_tujuan' => 'SMP Pengujian',
            'alamat_sekolah_tujuan' => 'Kota Bogor',
            'status' => 'Menunggu Verifikasi Dinas',
            'pengusul' => 'operator-draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mutasi_masuk_siswa')->insert([
            'id_mutasi' => 'MM-INDEX-1',
            'nama_murid' => 'Murid Uji',
            'nisn' => '0012345678',
            'npsn_sekolah_asal' => '87654321',
            'nama_sekolah_asal' => 'Sekolah Asal',
            'kabupaten_kota_asal' => 'Kota Bogor',
        ]);

        $user = Auth::user();
        $user->telegram_chat_id = '123456789';

        foreach (['Sekolah', 'Operator'] as $role) {
            $user->role = $role;

            $this->actingAs($user)
                ->get(route('mutasi.masuk.index'))
                ->assertOk()
                ->assertSee('Murid Uji');
        }
    }

    public function test_school_without_telegram_cannot_open_either_mutation_form(): void
    {
        Auth::user()->role = 'Sekolah';

        $this->actingAs(Auth::user())
            ->withoutMiddleware(\App\Http\Middleware\EnsureInitialStudentCountsCompleted::class)
            ->get(route('mutasi.keluar.create'))
            ->assertRedirect(route('profile.index'))
            ->assertSessionHas('error');

        $this->get(route('mutasi.masuk.create'))
            ->assertRedirect(route('profile.index'))
            ->assertSessionHas('error');
    }

    public function test_school_without_telegram_cannot_create_either_mutation_draft(): void
    {
        Auth::user()->role = 'Sekolah';

        $this->actingAs(Auth::user())
            ->withoutMiddleware(\App\Http\Middleware\EnsureInitialStudentCountsCompleted::class)
            ->postJson(route('mutasi.keluar.draft.save'))
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('redirect', route('profile.index'));

        $this->postJson(route('mutasi.masuk.draft.create'))
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_school_cannot_modify_or_resume_another_users_incoming_draft(): void
    {
        $controller = app(MutasiMasukController::class);
        $response = $controller->createDraft($this->jsonRequest('/mutasi-masuk/draft', [
            'jumlah_siswa' => 1,
            'npsn_sekolah_tujuan' => '12345678',
            'nama_sekolah_tujuan' => 'SMP Pengujian',
            'alamat_sekolah_tujuan' => 'Kota Bogor',
        ]));
        $id = $response->getData(true)['id_mutasi'];
        Auth::user()->username = 'sekolah-lain';
        Auth::user()->role = 'Sekolah';
        $request = $this->jsonRequest('/mutasi-masuk/draft/'.$id, ['jumlah_siswa' => 2]);

        $this->assertSame(403, $controller->updateDraftHeader($request, $id)->getStatusCode());
        $this->assertSame(403, $controller->addStudentToDraft($request, $id)->getStatusCode());
        $this->assertSame(403, $controller->finalizeDraft($request, $id)->getStatusCode());
        $this->assertSame(route('mutasi.masuk.index'), $controller->resumeDraft($id)->getTargetUrl());
        $this->assertDatabaseHas('mutasi_masuk', [
            'id_mutasi' => $id,
            'pengusul' => 'operator-draft',
            'jumlah_siswa' => 1,
            'status' => 'Draft',
        ]);
    }

    public function test_foreign_students_without_nisn_can_be_saved_and_updated_by_each_role(): void
    {
        (require database_path('migrations/2024_01_01_000004_create_mutasi_masuk_siswa_table.php'))->up();
        (require database_path('migrations/2026_01_23_061500_add_checklist_files_to_mutasi_masuk_siswa_table.php'))->up();
        (require database_path('migrations/2026_09_07_000001_allow_nullable_nisn_for_incoming_students.php'))->up();

        foreach (['Sekolah', 'Operator', 'Dinas'] as $role) {
            Auth::user()->role = $role;
            $controller = app(MutasiMasukController::class);
            $draft = $controller->createDraft($this->jsonRequest('/mutasi-masuk/draft', [
                'jumlah_siswa' => 2,
                'npsn_sekolah_tujuan' => '12345678',
                'nama_sekolah_tujuan' => 'SMP Pengujian',
                'alamat_sekolah_tujuan' => 'Kota Bogor',
            ]));
            $this->assertSame(200, $draft->getStatusCode());
            $id = $draft->getData(true)['id_mutasi'];
            $data = [
                'student_index' => 0, 'nama_murid' => 'Murid Luar Negeri',
                'nisn' => '', 'nis' => '', 'asal_luar_negeri' => '1',
                'tempat_lahir' => 'Tokyo', 'tanggal_lahir' => '2012-01-02',
                'jenis_kelamin' => 'L', 'kelas' => 'VII', 'tanggal_diterima' => '2026-08-01',
                'nama_sekolah_asal' => 'Sekolah Tokyo', 'alamat_sekolah_asal' => 'Tokyo, Jepang',
                'provinsi_asal' => '', 'kabupaten_kota_asal' => '',
                'has_surat_pindah' => '0', 'has_surat_dapodik' => '0', 'has_surat_penyaluran' => '0',
            ];
            $save = fn (array $payload) => $controller->addStudentToDraft(
                $this->jsonRequest('/mutasi-masuk/draft/'.$id.'/siswa', $payload), $id
            );
            $response = $save($data);
            $this->assertSame(200, $response->getStatusCode(), json_encode($response->getData(true)));
            $this->assertDatabaseHas('mutasi_masuk_siswa', [
                'id_mutasi' => $id, 'nisn' => null, 'asal_luar_negeri' => 1,
                'provinsi_asal' => 'Luar Negeri', 'kabupaten_kota_asal' => 'Luar Negeri',
                'has_surat_pindah' => 0, 'has_surat_dapodik' => 0, 'has_surat_penyaluran' => 0,
            ]);
            $this->assertSame(200, $save(array_replace($data, [
                'student_index' => 1, 'nama_murid' => 'Murid Kedua',
            ]))->getStatusCode());
            $this->assertSame(200, $save(array_replace($data, [
                'nama_murid' => 'Murid Diperbarui', 'has_surat_penyaluran' => '1',
            ]))->getStatusCode());
            $this->assertSame(2, DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->count());
            $this->assertDatabaseHas('mutasi_masuk_siswa', [
                'id_mutasi' => $id, 'nama_murid' => 'Murid Diperbarui', 'has_surat_penyaluran' => 1,
            ]);
            foreach ([['asal_luar_negeri' => '0'], ['nisn' => '123']] as $invalid) {
                $response = $save(array_replace($data, $invalid));
                $this->assertSame(422, $response->getStatusCode());
                $this->assertArrayHasKey('nisn', $response->getData(true)['errors']);
            }
        }
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

    public function test_submitting_incoming_draft_keeps_id_files_and_does_not_duplicate_on_retry(): void
    {
        (require database_path('migrations/2024_01_01_000004_create_mutasi_masuk_siswa_table.php'))->up();
        (require database_path('migrations/2025_12_29_000001_add_rekam_didik_to_mutasi_masuk_siswa.php'))->up();
        Auth::user()->role = 'Dinas';
        $controller = app(MutasiMasukController::class);

        foreach ([1, 2] as $count) {
            $header = [
                'jumlah_siswa' => $count,
                'nomor_surat' => '123/Permohonan',
                'tanggal_surat' => '2026-01-05',
                'npsn_sekolah_tujuan' => '12345678',
                'nama_sekolah_tujuan' => 'SMP Tujuan',
                'alamat_sekolah_tujuan' => 'Bogor',
            ];
            $draft = $controller->createDraft($this->jsonRequest('/mutasi-masuk/draft', $header));
            $id = $draft->getData(true)['id_mutasi'];
            $path = "mutasi_masuk/{$id}_surat_permohonan.pdf";
            DB::table('mutasi_masuk')->where('id_mutasi', $id)->update(['file_surat_permohonan' => $path]);
            $student = [
                'nama_murid' => 'Murid Pengujian', 'nisn' => '0012345678', 'nis' => '123',
                'tempat_lahir' => 'Bogor', 'tanggal_lahir' => '2012-01-02',
                'jenis_kelamin' => 'L', 'kelas' => 'VIII', 'tanggal_diterima' => '2026-01-05',
                'nama_sekolah_asal' => 'SMP Asal', 'alamat_sekolah_asal' => 'Bandung',
                'provinsi_asal' => 'Jawa Barat', 'kabupaten_kota_asal' => 'Bandung',
            ];
            DB::table('mutasi_masuk_siswa')->insert($student + ['id_mutasi' => $id]);
            $payload = $header + [
                'draft_id' => $id, 'is_dinas_entry' => 1,
                'file_surat_permohonan' => $path,
                'siswa' => array_fill(0, $count, $student),
            ];
            $sequence = DB::table('mutation_id_sequences')->where('type', 'masuk')->value('last_value');
            $response = $controller->store($this->jsonRequest('/mutasi-masuk', $payload));
            $this->assertSame(route('mutasi.masuk.index'), $response->getTargetUrl());
            $this->assertDatabaseHas('mutasi_masuk', [
                'id_mutasi' => $id, 'status' => 'Disetujui Dinas', 'file_surat_permohonan' => $path,
            ]);
            $this->assertSame($count, DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->count());
            $this->assertSame($sequence, DB::table('mutation_id_sequences')->where('type', 'masuk')->value('last_value'));
            $total = DB::table('mutasi_masuk')->count();
            $controller->store($this->jsonRequest('/mutasi-masuk', $payload));
            $this->assertSame($total, DB::table('mutasi_masuk')->count());
            $this->assertSame($count, DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->count());

            $record = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
            $record->nomor_surat_izin = null;
            $record->tanggal_surat_izin = null;
            $letter = app(\App\Services\PdfService::class)->prepareMutasiMasukData($record);
            $this->assertSame($id, $letter->id_mutasi);
            $this->assertStringStartsWith($id.'_', app(\App\Services\WordDocumentService::class)->mutasiMasukFilename($letter, $count));
        }
    }

    public function test_outgoing_draft_deletion_removes_its_b2_file(): void
    {
        $this->useB2Storage();
        $path = 'mutasi_keluar/2026/10/MK-TEST-1.pdf';
        Storage::disk('b2')->put($path, 'private document');
        $this->insertOutgoingMutation([
            'status' => 'Draft',
            'file_suket_keluar' => $path,
        ]);

        app(MutasiController::class)->deleteDraft('MK-TEST-1');

        $this->assertDatabaseMissing('mutasi_keluar', ['id_mutasi' => 'MK-TEST-1']);
        $this->assertFalse(Storage::disk('b2')->exists($path));
    }

    public function test_incoming_draft_deletion_removes_student_and_temporary_files(): void
    {
        $this->useB2Storage();
        $this->createIncomingStudentTable();
        $headerPath = 'temp_uploads/42/temp_header_01234567890123456789.pdf';
        $studentPath = 'mutasi_masuk/2026/10/MM-TEST-1_student.pdf';
        Storage::disk('b2')->put($headerPath, 'temporary header');
        Storage::disk('b2')->put($studentPath, 'student document');
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'MM-TEST-1',
            'tanggal_pengajuan' => '2026-10-01',
            'jumlah_siswa' => 1,
            'nomor_surat' => '123/TEST',
            'tanggal_surat' => '2026-10-01',
            'file_surat_permohonan' => $headerPath,
            'npsn_sekolah_tujuan' => '12345678',
            'nama_sekolah_tujuan' => 'SMP Pengujian',
            'alamat_sekolah_tujuan' => 'Kota Bogor',
            'status' => 'Draft',
            'pengusul' => 'operator-draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mutasi_masuk_siswa')->insert([
            'id_mutasi' => 'MM-TEST-1',
            'file_rapor' => $studentPath,
        ]);

        app(MutasiMasukController::class)->deleteDraft(
            Request::create('/mutasi-masuk/draft/MM-TEST-1', 'DELETE'),
            'MM-TEST-1'
        );

        $this->assertDatabaseMissing('mutasi_masuk', ['id_mutasi' => 'MM-TEST-1']);
        $this->assertDatabaseMissing('mutasi_masuk_siswa', ['id_mutasi' => 'MM-TEST-1']);
        $this->assertFalse(Storage::disk('b2')->exists($headerPath));
        $this->assertFalse(Storage::disk('b2')->exists($studentPath));
    }

    public function test_outgoing_revision_can_be_permanently_deleted_from_b2(): void
    {
        $this->useB2Storage();
        $path = 'mutasi_keluar/2026/10/MK-TEST-1.pdf';
        Storage::disk('b2')->put($path, 'private document');
        $this->insertOutgoingMutation([
            'status' => 'Perlu Perbaikan',
            'file_suket_keluar' => $path,
        ]);

        app(MutasiController::class)->cancel(Request::create('/mutasi-keluar/MK-TEST-1/cancel', 'PUT'), 'MK-TEST-1');

        $this->assertDatabaseMissing('mutasi_keluar', ['id_mutasi' => 'MK-TEST-1']);
        $this->assertFalse(Storage::disk('b2')->exists($path));
    }

    public function test_incoming_revision_deletes_header_students_and_files_from_b2(): void
    {
        $this->useB2Storage();
        $this->createIncomingStudentTable();
        $headerPath = 'mutasi_masuk/2026/10/MM-TEST-1.pdf';
        $studentPath = 'mutasi_masuk/2026/10/MM-TEST-1_student.pdf';
        Storage::disk('b2')->put($headerPath, 'header document');
        Storage::disk('b2')->put($studentPath, 'student document');
        DB::table('mutasi_masuk')->insert([
            'id_mutasi' => 'MM-TEST-1',
            'tanggal_pengajuan' => '2026-10-01',
            'jumlah_siswa' => 1,
            'nomor_surat' => '123/TEST',
            'tanggal_surat' => '2026-10-01',
            'file_surat_permohonan' => $headerPath,
            'npsn_sekolah_tujuan' => '12345678',
            'nama_sekolah_tujuan' => 'SMP Pengujian',
            'alamat_sekolah_tujuan' => 'Kota Bogor',
            'status' => 'Perlu Perbaikan',
            'pengusul' => 'operator-draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mutasi_masuk_siswa')->insert([
            'id_mutasi' => 'MM-TEST-1',
            'file_rapor' => $studentPath,
        ]);

        app(MutasiMasukController::class)->cancel(
            Request::create('/mutasi-masuk/MM-TEST-1/cancel', 'PUT'),
            'MM-TEST-1'
        );

        $this->assertDatabaseMissing('mutasi_masuk', ['id_mutasi' => 'MM-TEST-1']);
        $this->assertDatabaseMissing('mutasi_masuk_siswa', ['id_mutasi' => 'MM-TEST-1']);
        $this->assertFalse(Storage::disk('b2')->exists($headerPath));
        $this->assertFalse(Storage::disk('b2')->exists($studentPath));
    }

    public function test_failed_file_cleanup_keeps_outgoing_submission_record(): void
    {
        Storage::fake('private_docs');
        $this->insertOutgoingMutation([
            'status' => 'Perlu Perbaikan',
            'file_suket_keluar' => '../outside.pdf',
        ]);

        app(MutasiController::class)->cancel(Request::create('/mutasi-keluar/MK-TEST-1/cancel', 'PUT'), 'MK-TEST-1');

        $this->assertDatabaseHas('mutasi_keluar', [
            'id_mutasi' => 'MK-TEST-1',
            'status' => 'Perlu Perbaikan',
        ]);
    }

    private function useB2Storage(): void
    {
        $this->app['env'] = 'production';
        config()->set('filesystems.disks.b2.key', 'test-key');
        Storage::fake('b2');
    }

    private function createIncomingStudentTable(): void
    {
        Schema::create('mutasi_masuk_siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('id_mutasi');
            $table->string('file_surat_pindah')->nullable();
            $table->string('file_surat_dapodik')->nullable();
            $table->string('file_rekomendasi')->nullable();
            $table->string('file_ijazah_sd')->nullable();
            $table->string('file_rapor')->nullable();
            $table->string('file_surat_penyaluran')->nullable();
            $table->string('file_rekam_didik')->nullable();
        });
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
