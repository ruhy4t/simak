<?php

namespace Tests\Unit;

use App\Services\WordDocumentService;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use Tests\TestCase;
use ZipArchive;

class WordDocumentServiceTest extends TestCase
{
    public function test_it_builds_descriptive_safe_transfer_filenames(): void
    {
        $service = app(WordDocumentService::class);

        $this->assertSame(
            'MK202607150001_Budi Santoso_SMP Negeri 1 Bogor.docx',
            $service->mutasiKeluarFilename($this->outgoingTransfer())
        );
        $this->assertSame(
            'MM202607150001_SMP Negeri 3 Bogor_4 Murid.docx',
            $service->mutasiMasukFilename($this->incomingTransfer(), 4)
        );

        $unsafe = (object) [
            'id_mutasi' => 'MK/2026:002',
            'nama_siswa' => 'Budi / Santoso',
            'sekolah_asal' => 'SMP: Bogor?',
        ];
        $this->assertSame('MK-2026-002_Budi - Santoso_SMP- Bogor.docx', $service->mutasiKeluarFilename($unsafe));
    }

    public function test_it_creates_native_docx_for_outgoing_transfer_letter(): void
    {
        $path = app(WordDocumentService::class)->createMutasiKeluar($this->outgoingTransfer(), $this->signer());

        $document = $this->assertNativeDocx($path, ['SURAT KETERANGAN', 'DATA SISWA', 'Budi Santoso']);
        $this->assertSingleLineZeroParagraphSpacing($path);
        $this->assertDocxTablesHaveNoVisibleBorders($path);
        $this->assertCount(1, $document->getSections());
        $this->assertRequestedMargins($document);

        unlink($path);
    }

    public function test_it_creates_native_docx_with_landscape_attachment_for_incoming_transfer(): void
    {
        $students = new Collection([(object) [
            'nama_murid' => 'Siti Aminah',
            'nisn' => '0012345678',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Bogor',
            'tanggal_lahir' => '2012-05-10',
            'kelas' => 'VIII',
            'npsn_sekolah_asal' => '20200001',
            'nama_sekolah_asal' => 'SMP Negeri Asal',
            'kabupaten_kota_asal' => 'Kabupaten Bogor',
            'provinsi_asal' => 'Jawa Barat',
            'tanggal_diterima' => '2026-07-15',
        ]]);

        $path = app(WordDocumentService::class)->createMutasiMasuk($this->incomingTransfer(), $students, $this->signer());

        $document = $this->assertNativeDocx($path, ['Izin Mutasi Siswa', 'DAFTAR SISWA MUTASI MASUK', 'Siti Aminah']);
        $this->assertSingleLineZeroParagraphSpacing($path);
        $this->assertDocxTablesHaveNoVisibleBorders($path);
        $this->assertCount(2, $document->getSections());
        $this->assertSame('landscape', $document->getSections()[1]->getStyle()->getOrientation());
        $this->assertRequestedMargins($document);

        unlink($path);
    }

    private function assertNativeDocx(string $path, array $expectedText): PhpWord
    {
        $this->assertFileExists($path);
        $this->assertSame('PK', file_get_contents($path, false, null, 0, 2));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
        $this->assertNotFalse($zip->locateName('word/document.xml'));
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        foreach ($expectedText as $text) {
            $this->assertStringContainsString($text, $documentXml);
        }

        return IOFactory::load($path, 'Word2007');
    }

    private function assertDocxTablesHaveNoVisibleBorders(string $path): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        preg_match_all('/<w:tblBorders>.*?<\/w:tblBorders>/s', $documentXml, $tableBorders);

        $this->assertNotEmpty($tableBorders[0]);
        foreach ($tableBorders[0] as $borderDefinition) {
            $this->assertSame(6, substr_count($borderDefinition, 'w:val="nil"'));
            $this->assertDoesNotMatchRegularExpression('/w:val="(?!nil)[^"]+"/', $borderDefinition);
        }
        $this->assertStringNotContainsString('<w:tcBorders>', $documentXml);
    }

    private function assertSingleLineZeroParagraphSpacing(string $path): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $stylesXml = (string) $zip->getFromName('word/styles.xml');
        $zip->close();

        $this->assertMatchesRegularExpression(
            '/<w:style[^>]+w:styleId="Normal".*?<w:spacing[^>]+w:before="0"[^>]+w:after="0"[^>]+w:line="240"/s',
            $stylesXml
        );
        $this->assertDoesNotMatchRegularExpression('/w:(?:before|after)="(?!0")[^"]+"/', $documentXml);
        $this->assertDoesNotMatchRegularExpression('/w:line="(?!240")[^"]+"/', $documentXml);
    }

    private function assertRequestedMargins(PhpWord $document): void
    {
        foreach ($document->getSections() as $section) {
            $style = $section->getStyle();

            $this->assertEqualsWithDelta(Converter::cmToTwip(1.5), $style->getMarginTop(), 1);
            $this->assertEqualsWithDelta(Converter::cmToTwip(3), $style->getMarginBottom(), 1);
            $this->assertEqualsWithDelta(Converter::cmToTwip(2.5), $style->getMarginLeft(), 1);
            $this->assertEqualsWithDelta(Converter::cmToTwip(2.5), $style->getMarginRight(), 1);
        }
    }

    private function signer(): object
    {
        return (object) [
            'nama' => 'Drs. Pejabat Dinas',
            'pangkat_golongan' => 'Pembina, IV/a',
            'nip' => '197001011999031001',
        ];
    }

    private function outgoingTransfer(): object
    {
        return (object) [
            'id_mutasi' => 'MK202607150001',
            'nomor_surat_izin' => '400.3/123-SMP',
            'sekolah_asal' => 'SMP Negeri 1 Bogor',
            'nomor_surat_keluar' => '421/10/SMP',
            'tanggal_surat_keluar' => '2026-07-10',
            'nama_siswa' => 'Budi Santoso',
            'tempat_lahir' => 'Bogor',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
            'kelas' => 'VIII',
            'nis' => '1234',
            'nisn' => '0012345678',
            'alamat_sekolah_asal' => 'Jalan Pendidikan No. 1',
            'npsn_tujuan' => '20200002',
            'sekolah_tujuan' => 'SMP Negeri 2 Bandung',
            'kota_tujuan' => 'Kota Bandung',
            'provinsi_tujuan' => 'Jawa Barat',
            'alasan_pindah' => 'Mengikuti orang tua',
            'tanggal_surat_izin' => '2026-07-15',
        ];
    }

    private function incomingTransfer(): object
    {
        return (object) [
            'id_mutasi' => 'MM202607150001',
            'nomor_surat_izin' => '400.3/124-SMP',
            'tanggal_surat_izin' => '2026-07-15',
            'sekolah_tujuan' => 'SMP Negeri 3 Bogor',
            'alamat_sekolah_tujuan' => 'Jalan Pajajaran No. 3',
            'nomor_surat_permohonan' => '421/11/SMP',
            'tanggal_surat_permohonan' => '2026-07-11',
        ];
    }
}
