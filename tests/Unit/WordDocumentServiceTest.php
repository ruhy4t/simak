<?php

namespace Tests\Unit;

use App\Services\WordDocumentService;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
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
        $this->assertSame(
            'MK202607150001_Budi Santoso_SMP Negeri 1 Bogor_SRIKANDI.docx',
            $service->mutasiKeluarSrikandiFilename($this->outgoingTransfer())
        );
        $this->assertSame(
            'MM202607150001_SMP Negeri 3 Bogor_4 Murid_SRIKANDI.docx',
            $service->mutasiMasukSrikandiFilename($this->incomingTransfer(), 4)
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

        $document = $this->assertNativeDocx($path, ['SURAT KETERANGAN', 'DATA MURID', 'Budi Santoso']);
        $this->assertSingleLineZeroParagraphSpacing($path);
        $this->assertDocxTablesHaveNoVisibleBorders($path);
        $this->assertCount(1, $document->getSections());
        $this->assertRequestedMargins($document);
        $this->assertWatermarks($path, [[150, 194]]);

        unlink($path);
    }

    public function test_it_creates_srikandi_outgoing_transfer_with_exact_placeholders(): void
    {
        $path = app(WordDocumentService::class)->createMutasiKeluarSrikandi($this->outgoingTransfer());

        $document = $this->assertNativeDocx($path, [
            'SURAT KETERANGAN',
            'Budi Santoso',
            'Telepon (0251) 8341101 Faksimile (0251) 8341101',
            'Situs web disdik.kotabogor.go.id',
            '${nomor_naskah}',
            '${tanggal_naskah}',
            '${ttd_pengirim}',
            '${nama_pengirim}',
            '${pangkat_pengirim}',
            '${nip_pengirim}',
        ]);
        $this->assertSrikandiSignatureSpacing($path);
        $this->assertDocxTablesHaveNoVisibleBorders($path);
        $this->assertCount(1, $document->getSections());
        $this->assertSrikandiOutgoingMargins($document);
        $this->assertSrikandiHeaderRule($path);
        $this->assertSrikandiOpeningIndent($path);
        $this->assertSame('Times New Roman', $document->getDefaultFontName());
        $this->assertSrikandiPlaceholdersOccurExactlyOnce($path);
        $this->assertWatermarks($path, [[150, 194]]);

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

        $document = $this->assertNativeDocx($path, ['Izin Mutasi Murid', 'DAFTAR MURID MUTASI MASUK', 'Siti Aminah']);
        $this->assertIncomingLetterLayout($path, false);
        $this->assertCount(2, $document->getSections());
        $this->assertSame('landscape', $document->getSections()[1]->getStyle()->getOrientation());
        $this->assertRequestedMargins($document);
        $this->assertWatermarks($path, [[150, 194], [210, 272]]);

        unlink($path);
    }

    public function test_it_creates_srikandi_incoming_transfer_from_the_two_section_template(): void
    {
        $students = new Collection([$this->incomingStudent()]);
        $path = app(WordDocumentService::class)->createMutasiMasukSrikandi($this->incomingTransfer(), $students);

        $document = $this->assertNativeDocx($path, [
            'DAFTAR MURID MUTASI MASUK',
            'Siti Aminah',
            '${nomor_naskah}',
            '${tanggal_naskah}',
            '${sifat}',
            '${hal}',
            '${jabatan_tujuan}',
            '${ttd_pengirim}',
            '${nama_pengirim}',
            '${pangkat_pengirim}',
            '${nip_pengirim}',
        ]);
        $this->assertIncomingLetterLayout($path, true);
        $this->assertCount(2, $document->getSections());
        $this->assertSame('landscape', $document->getSections()[1]->getStyle()->getOrientation());
        $this->assertRequestedMargins($document);
        $this->assertSame('Times New Roman', $document->getDefaultFontName());
        $this->assertIncomingSrikandiPlaceholderCounts($path);
        $this->assertWatermarks($path, [[150, 194], [210, 272]]);

        unlink($path);
    }

    public function test_watermark_logo_uses_low_opacity_without_changing_its_proportions(): void
    {
        $image = imagecreatefrompng(public_path('images/logo_Kota_Bogor_watermark.png'));

        $this->assertSame(474, imagesx($image));
        $this->assertSame(613, imagesy($image));
        $pixel = imagecolorat($image, 100, 100);
        $this->assertSame(114, ($pixel >> 24) & 0x7F);
    }

    public function test_transfer_documents_preserve_special_characters_as_valid_xml(): void
    {
        $escapingEnabled = Settings::isOutputEscapingEnabled();
        $outgoing = $this->outgoingTransfer();
        $outgoing->nama_siswa = 'Budi & Ani <Murid>';
        $student = $this->incomingStudent();
        $student->nama_murid = 'Budi & Ani <Murid>';
        $student->nama_sekolah_asal = 'SMP A &amp; B';
        $service = app(WordDocumentService::class);
        $paths = [
            $service->createMutasiKeluarSrikandi($outgoing),
            $service->createMutasiMasukSrikandi($this->incomingTransfer(), [$student]),
        ];

        foreach ($paths as $path) {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $xml = new \DOMDocument;
            $this->assertTrue($xml->loadXML((string) $zip->getFromName('word/document.xml')));
            $zip->close();
            $xpath = new \DOMXPath($xml);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $this->assertSame(1, $xpath->query('//w:t[text()="Budi & Ani <Murid>"]')->length);
            if ($path === $paths[1]) {
                $this->assertSame(1, $xpath->query('//w:t[text()="SMP A & B"]')->length);
            }
            unlink($path);
        }
        $this->assertSame($escapingEnabled, Settings::isOutputEscapingEnabled());
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

    private function assertStudentTableHasVisibleBorders(string $path): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('<w:tblBorders>', $documentXml);
        $this->assertStringContainsString('w:val="single"', $documentXml);
    }

    private function assertIncomingLetterLayout(string $path, bool $srikandi): void
    {
        $this->assertStudentTableHasVisibleBorders($path);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $xml = new \DOMDocument;
        $this->assertTrue($xml->loadXML($documentXml));
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $table = $xpath->query('//w:tbl[w:tr/w:tc/w:p/w:r/w:t="Nama Murid"]')->item(0);
        $this->assertNotNull($table);
        $this->assertEqualsWithDelta(
            16838 - Converter::cmToTwip(5),
            $xpath->evaluate('sum(w:tblGrid/w:gridCol/@w:w)', $table),
            1
        );
        $this->assertGreaterThanOrEqual(3, $xpath->query('.//w:br', $table)->length);
        $this->assertSame(1, $xpath->query('w:tr[position()>1]/w:trPr/w:cantSplit', $table)->length);
        $this->assertSame(8, $xpath->query('w:tr[last()]/w:tc/w:p/w:pPr/w:keepNext', $table)->length);
        $this->assertSame(6, $xpath->query('w:tblPr/w:tblBorders/*[@w:val="single" and @w:sz="8"]', $table)->length);

        $indent = $xpath->query('//w:p[w:r/w:t[starts-with(., "Berdasarkan surat dari Kepala")]]/w:pPr/w:ind')->item(0);
        $this->assertNotNull($indent);
        $this->assertSame('567', $xpath->evaluate('string(@w:firstLine)', $indent));
        $this->assertSame(0, $xpath->query('@w:hanging', $indent)->length);
        if ($srikandi) {
            $this->assertSame(2, $xpath->query('//w:p[w:r/w:t="${ttd_pengirim}"]/w:pPr/w:spacing[@w:before="580"]')->length);
            $this->assertSame(2, $xpath->query('//w:tr[w:tc/w:p/w:r/w:t="${ttd_pengirim}"]/w:trPr/w:cantSplit')->length);
        }
    }

    private function assertWatermarks(string $path, array $expectedDimensions): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $headerPaths = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (is_string($name) && preg_match('/^word\\/header\\d+\\.xml$/', $name) === 1) {
                $headerPaths[] = $name;
            }
        }
        sort($headerPaths, SORT_NATURAL);

        $this->assertCount(count($expectedDimensions), $headerPaths);
        foreach ($expectedDimensions as $index => [$width, $height]) {
            $headerXml = (string) $zip->getFromName($headerPaths[$index]);
            $this->assertStringContainsString('<v:shape', $headerXml);
            $this->assertStringContainsString('width:'.$width.'pt', $headerXml);
            $this->assertStringContainsString('height:'.$height.'pt', $headerXml);
            $this->assertStringContainsString('mso-position-horizontal:center', $headerXml);
            $this->assertStringContainsString('mso-position-horizontal-relative:page', $headerXml);
            $this->assertStringContainsString('mso-position-vertical:center', $headerXml);
            $this->assertStringContainsString('mso-position-vertical-relative:page', $headerXml);
            $this->assertStringContainsString('z-index:-2147483647', $headerXml);
        }
        $zip->close();
    }

    public function test_incoming_attachment_keeps_the_final_signature_with_its_student_rows(): void
    {
        foreach ([4 => [4], 5 => [5], 6 => [6], 7 => [6, 1]] as $count => $expectedRows) {
            $students = array_fill(0, $count, $this->incomingStudent());
            $path = app(WordDocumentService::class)->createMutasiMasukSrikandi($this->incomingTransfer(), $students);
            $document = IOFactory::load($path, 'Word2007');
            $this->assertCount(count($expectedRows) + 1, $document->getSections());

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $xml = new \DOMDocument;
            $this->assertTrue($xml->loadXML((string) $zip->getFromName('word/document.xml')));
            $zip->close();
            $xpath = new \DOMXPath($xml);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $tables = $xpath->query('//w:tbl[w:tr/w:tc/w:p/w:r/w:t="Nama Murid"]');
            $this->assertCount(count($expectedRows), $tables);
            foreach ($expectedRows as $index => $expected) {
                $this->assertSame($expected, $xpath->query('w:tr[position()>1]', $tables->item($index))->length);
            }
            unlink($path);
        }
    }

    private function assertSrikandiPlaceholdersOccurExactlyOnce(string $path): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        foreach ([
            '${nomor_naskah}',
            '${tanggal_naskah}',
            '${ttd_pengirim}',
            '${nama_pengirim}',
            '${pangkat_pengirim}',
            '${nip_pengirim}',
        ] as $placeholder) {
            $this->assertSame(1, substr_count($documentXml, $placeholder), $placeholder);
        }

        $this->assertStringNotContainsString('400.3/123-SMP', $documentXml);
        $this->assertStringNotContainsString('Drs. Pejabat Dinas', $documentXml);
    }

    private function assertIncomingSrikandiPlaceholderCounts(string $path): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        foreach ([
            '${nomor_naskah}' => 2,
            '${tanggal_naskah}' => 2,
            '${sifat}' => 1,
            '${hal}' => 1,
            '${jabatan_tujuan}' => 1,
            '${ttd_pengirim}' => 2,
            '${nama_pengirim}' => 2,
            '${pangkat_pengirim}' => 2,
            '${nip_pengirim}' => 2,
        ] as $placeholder => $expectedCount) {
            $this->assertSame($expectedCount, substr_count($documentXml, $placeholder), $placeholder);
        }

        $this->assertStringNotContainsString('400.3/124-SMP', $documentXml);
        $this->assertStringNotContainsString('Drs. Pejabat Dinas', $documentXml);
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

    private function assertSrikandiOutgoingMargins(PhpWord $document): void
    {
        $style = $document->getSections()[0]->getStyle();

        $this->assertEqualsWithDelta(Converter::cmToTwip(1), $style->getMarginTop(), 1);
        $this->assertEqualsWithDelta(Converter::cmToTwip(3), $style->getMarginBottom(), 1);
        $this->assertEqualsWithDelta(Converter::cmToTwip(2), $style->getMarginLeft(), 1);
        $this->assertEqualsWithDelta(Converter::cmToTwip(2), $style->getMarginRight(), 1);
    }

    private function assertSrikandiSignatureSpacing(string $path): void
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
        $xml = new \DOMDocument;
        $this->assertTrue($xml->loadXML($documentXml));
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ([
            'Bogor, ${tanggal_naskah}' => ['560', null],
            '${ttd_pengirim}' => ['580', '1080'],
            '${nama_pengirim}' => ['640', '560'],
            '${pangkat_pengirim}' => ['0', '560'],
            'NIP. ${nip_pengirim}' => ['0', '560'],
        ] as $text => [$before, $left]) {
            $paragraph = $xpath->query(sprintf('//w:p[w:r/w:t="%s"]', $text))->item(0);
            $this->assertNotNull($paragraph, $text);
            $this->assertSame($before, $xpath->evaluate('string(w:pPr/w:spacing/@w:before)', $paragraph), $text);
            if ($left !== null) {
                $this->assertSame($left, $xpath->evaluate('string(w:pPr/w:ind/@w:left)', $paragraph), $text);
            }
        }

        $this->assertSame(1, $xpath->query('//w:tr[w:tc/w:p/w:r/w:t="a.n."][w:tc/w:p/w:r/w:t="Kepala Dinas,"]')->length);
        $this->assertSame(1, $xpath->query('//w:tr[w:tc/w:p/w:r/w:t="u.b."][w:tc/w:p/w:r/w:t="Kabid SMP,"]')->length);
    }

    private function assertSrikandiOpeningIndent(string $path): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $xml = new \DOMDocument;
        $this->assertTrue($xml->loadXML($documentXml));
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $indent = $xpath->query('//w:p[w:r/w:t[starts-with(., "Berdasarkan Surat Keterangan Pindah")]]/w:pPr/w:ind')->item(0);
        $this->assertNotNull($indent);
        $this->assertSame('709', $xpath->evaluate('string(@w:firstLine)', $indent));
        $this->assertSame(0, $xpath->query('@w:hanging', $indent)->length);
    }

    private function assertSrikandiHeaderRule(string $path): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('<w:pBdr>', $documentXml);
        $this->assertMatchesRegularExpression('/<w:bottom[^>]*w:val="double"[^>]*w:color="000000"/', $documentXml);
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

    private function incomingStudent(): object
    {
        return (object) [
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
        ];
    }
}
