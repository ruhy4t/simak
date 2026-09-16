<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RevokeMutationApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->withoutMiddleware();
        foreach (['masuk', 'keluar'] as $type) {
            Schema::create('mutasi_'.$type, function (Blueprint $table) {
                $table->string('id_mutasi')->primary();
                $table->string('status');
                $table->string('file_rapor')->nullable();
                $table->text('catatan_berkas')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_approval_can_be_revoked_without_changing_documents_or_notes(): void
    {
        foreach (['Dinas', 'SuperAdmin'] as $role) {
            $this->actingAs(new User(['role' => $role, 'username' => 'reviewer']));
            foreach (['masuk', 'keluar'] as $type) {
                DB::table('mutasi_'.$type)->insert([
                    'id_mutasi' => $role, 'status' => 'Disetujui Dinas',
                    'file_rapor' => 'documents/rapor.pdf', 'catatan_berkas' => 'Catatan awal',
                ]);
                $this->post(route('mutasi.'.$type.'.revoke-approval', $role))
                    ->assertRedirect(route('mutasi.'.$type.'.show', $role))
                    ->assertSessionHas('success');
                $this->assertDatabaseHas('mutasi_'.$type, [
                    'id_mutasi' => $role, 'status' => 'Menunggu Verifikasi Dinas',
                    'file_rapor' => 'documents/rapor.pdf', 'catatan_berkas' => 'Catatan awal',
                ]);
            }
        }
    }

    public function test_other_statuses_and_repeated_requests_are_rejected(): void
    {
        $this->actingAs(new User(['role' => 'Dinas', 'username' => 'reviewer']));
        foreach (['masuk', 'keluar'] as $type) {
            foreach (['Draft', 'Menunggu Verifikasi Dinas', 'Perlu Perbaikan', 'Proses TTD', 'Selesai', 'Dibatalkan'] as $status) {
                DB::table('mutasi_'.$type)->updateOrInsert(['id_mutasi' => 'test'], ['status' => $status]);
                $this->post(route('mutasi.'.$type.'.revoke-approval', 'test'))->assertSessionHas('error');
                $this->assertDatabaseHas('mutasi_'.$type, ['id_mutasi' => 'test', 'status' => $status]);
            }
            $this->post(route('mutasi.'.$type.'.revoke-approval', 'missing'))->assertNotFound();
        }
    }

    public function test_other_roles_cannot_revoke_approval(): void
    {
        foreach (['Operator', 'Sekolah'] as $role) {
            $this->actingAs(new User(['role' => $role]));
            foreach (['masuk', 'keluar'] as $type) {
                $this->post(route('mutasi.'.$type.'.revoke-approval', 'test'))->assertForbidden();
            }
        }
    }
}
