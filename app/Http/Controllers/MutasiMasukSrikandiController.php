<?php

namespace App\Http\Controllers;

use App\Services\PdfService;
use App\Services\WordDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MutasiMasukSrikandiController extends Controller
{
    public function __invoke(
        string $id,
        PdfService $pdfService,
        WordDocumentService $wordService
    ): BinaryFileResponse|RedirectResponse {
        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        abort_if(! $mutasi, 404);

        if (! $pdfService->canPrint($mutasi->status)) {
            return back()->with('error', 'Surat hanya dapat diunduh setelah status Proses TTD atau Selesai.');
        }

        $mutasiData = $pdfService->prepareMutasiMasukData($mutasi);
        $students = $pdfService->getMutasiMasukSiswaList($id);
        $path = $wordService->createMutasiMasukSrikandi($mutasiData, $students);

        return response()->download(
            $path,
            $wordService->mutasiMasukSrikandiFilename($mutasiData, $students->count()),
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        )->deleteFileAfterSend(true);
    }
}
