<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\ComplexType\TblWidth as TblWidthComplex;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use ZipArchive;

class WordDocumentService
{
    private const A4_WIDTH = 11906;

    private const A4_HEIGHT = 16838;

    private const FONT = 'Arial';

    private const SRIKANDI_FONT = 'Times New Roman';

    public function mutasiKeluarFilename(object $mutasi): string
    {
        return sprintf(
            '%s_%s_%s.docx',
            $this->filenamePart($mutasi->id_mutasi ?? 'MK-ID'),
            $this->filenamePart($mutasi->nama_siswa ?? 'Murid'),
            $this->filenamePart($mutasi->sekolah_asal ?? 'Asal Sekolah')
        );
    }

    public function mutasiKeluarSrikandiFilename(object $mutasi): string
    {
        return pathinfo($this->mutasiKeluarFilename($mutasi), PATHINFO_FILENAME).'_SRIKANDI.docx';
    }

    public function mutasiMasukFilename(object $mutasi, int $studentCount): string
    {
        return sprintf(
            '%s_%s_%d Murid.docx',
            $this->filenamePart($mutasi->id_mutasi ?? 'MM-ID'),
            $this->filenamePart($mutasi->sekolah_tujuan ?? 'Sekolah Tujuan'),
            max(0, $studentCount)
        );
    }

    public function mutasiMasukSrikandiFilename(object $mutasi, int $studentCount): string
    {
        return pathinfo($this->mutasiMasukFilename($mutasi, $studentCount), PATHINFO_FILENAME).'_SRIKANDI.docx';
    }

    public function createMutasiKeluar(object $mutasi, ?object $penandatangan): string
    {
        return $this->createMutasiKeluarDocument($mutasi, $penandatangan, false);
    }

    public function createMutasiKeluarSrikandi(object $mutasi): string
    {
        return $this->createMutasiKeluarDocument($mutasi, null, true);
    }

    private function createMutasiKeluarDocument(object $mutasi, ?object $penandatangan, bool $srikandi): string
    {
        $word = $this->newDocument(
            'Surat Keterangan Mutasi Keluar'.($srikandi ? ' SRIKANDI' : ''),
            $srikandi ? self::SRIKANDI_FONT : self::FONT
        );
        $section = $word->addSection([
            'pageSizeW' => self::A4_WIDTH,
            'pageSizeH' => self::A4_HEIGHT,
            'marginTop' => (int) round(Converter::cmToTwip($srikandi ? 1 : 1.5)),
            'marginRight' => (int) round(Converter::cmToTwip($srikandi ? 2 : 2.5)),
            'marginBottom' => (int) round(Converter::cmToTwip(3)),
            'marginLeft' => (int) round(Converter::cmToTwip($srikandi ? 2 : 2.5)),
        ]);
        $contentWidth = self::A4_WIDTH - Converter::cmToTwip($srikandi ? 4 : 5);

        if ($srikandi) {
            $this->addSrikandiLetterhead($section, $contentWidth);
        } else {
            $this->addLetterhead($section, $contentWidth, false);
        }

        $section->addText('SURAT KETERANGAN', ['bold' => true, 'underline' => 'single'], [
            'alignment' => Jc::CENTER,
            'spaceBefore' => 0,
            'spaceAfter' => 0,
        ]);
        $section->addText('Nomor : '.($srikandi ? '${nomor_naskah}' : $this->text($mutasi->nomor_surat_izin ?? '______')), [], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 0,
        ]);

        $opening = sprintf(
            'Berdasarkan Surat Keterangan Pindah dari Kepala %s Nomor : %s tanggal %s, Kepala Dinas Pendidikan Kota Bogor menerangkan bahwa :',
            $this->text($mutasi->sekolah_asal ?? '-'),
            $this->text($mutasi->nomor_surat_keluar ?? '-'),
            $this->date($mutasi->tanggal_surat_keluar ?? null)
        );
        $section->addText($opening, [], $this->paragraph([
            'indentation' => $srikandi ? ['firstLine' => 709, 'hanging' => null] : ['firstLine' => 709],
        ]));

        $section->addText('DATA MURID :', ['bold' => true], ['indentation' => ['left' => 709], 'spaceAfter' => 0]);
        $this->addKeyValueTable($section, [
            ['Nama', $mutasi->nama_siswa ?? '-', true],
            ['Tempat, Tanggal Lahir', $this->text($mutasi->tempat_lahir ?? '-').', '.$this->date($mutasi->tanggal_lahir ?? null)],
            ['Jenis Kelamin', match ($mutasi->jenis_kelamin ?? null) {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => '-',
            }],
            ['Kelas', $mutasi->kelas ?? '-'],
            ['NIS/NISN', $this->text($mutasi->nis ?? '-').'/'.$this->text($mutasi->nisn ?? '-')],
            ['Asal Sekolah', $mutasi->sekolah_asal ?? '-'],
            ['Alamat Sekolah', $mutasi->alamat_sekolah_asal ?? '-'],
        ], $contentWidth - 709, 709);

        $section->addText('Bermaksud pindah dan melanjutkan sekolah di ;', [], ['spaceBefore' => $srikandi ? 260 : 0, 'spaceAfter' => 0]);
        $section->addText('DATA SEKOLAH TUJUAN :', ['bold' => true], ['indentation' => ['left' => 709], 'spaceAfter' => 0]);
        $this->addKeyValueTable($section, [
            ['NPSN Sekolah Tujuan', $mutasi->npsn_tujuan ?? '-'],
            ['Nama Sekolah Tujuan', $mutasi->sekolah_tujuan ?? '-'],
            ['Kota/Kabupaten', str_replace('Kabupaten', 'Kab.', $this->text($mutasi->kota_tujuan ?? '-'))],
            ['Provinsi', $mutasi->provinsi_tujuan ?? '-'],
            ['Alasan mutasi/pindah', $mutasi->alasan_pindah ?? '-'],
        ], $contentWidth - 709, 709);

        $section->addText(
            'Demikian Surat Keterangan ini kami buat untuk dipergunakan sebagaimana mestinya.',
            [],
            $srikandi
                ? $this->paragraph(['spaceBefore' => 280])
                : $this->paragraph(['indentation' => ['firstLine' => 709]])
        );
        if ($srikandi) {
            $this->addSrikandiSignature($section, $contentWidth, true, true);
        } else {
            $this->addSignature($section, $penandatangan, 'Bogor, '.$this->date($mutasi->tanggal_surat_izin ?? null), $contentWidth);
        }

        return $this->save($word, $srikandi ? 'surat-mutasi-keluar-srikandi' : 'surat-mutasi-keluar');
    }

    public function createMutasiMasuk(object $mutasi, Collection|array $siswaList, ?object $penandatangan): string
    {
        return $this->createMutasiMasukDocument($mutasi, $siswaList, $penandatangan, false);
    }

    public function createMutasiMasukSrikandi(object $mutasi, Collection|array $siswaList): string
    {
        return $this->createMutasiMasukDocument($mutasi, $siswaList, null, true);
    }

    private function createMutasiMasukDocument(
        object $mutasi,
        Collection|array $siswaList,
        ?object $penandatangan,
        bool $srikandi
    ): string {
        $word = $this->newDocument(
            'Surat Izin Mutasi Masuk'.($srikandi ? ' SRIKANDI' : ''),
            $srikandi ? self::SRIKANDI_FONT : self::FONT
        );
        $portraitWidth = self::A4_WIDTH - Converter::cmToTwip(5);
        $section = $word->addSection([
            'pageSizeW' => self::A4_WIDTH,
            'pageSizeH' => self::A4_HEIGHT,
            'marginTop' => (int) round(Converter::cmToTwip(1.5)),
            'marginRight' => (int) round(Converter::cmToTwip(2.5)),
            'marginBottom' => (int) round(Converter::cmToTwip(3)),
            'marginLeft' => (int) round(Converter::cmToTwip(2.5)),
        ]);

        if ($srikandi) {
            $this->addIncomingSrikandiLetterhead($section, $portraitWidth);
        } else {
            $this->addLetterhead($section, $portraitWidth, true);
        }
        $this->addIncomingLetterInfo($section, $mutasi, $portraitWidth, $srikandi);

        if ($srikandi) {
            $section->addText('Yth. ${jabatan_tujuan}', [], ['spaceAfter' => 0]);
        } else {
            $recipient = $section->addTable(['width' => $portraitWidth, 'unit' => TblWidth::TWIP, 'borderSize' => 0]);
            $row = $recipient->addRow();
            $row->addCell(500)->addText('Yth.', [], ['spaceAfter' => 0]);
            $recipientCell = $row->addCell((int) ($portraitWidth * 0.48));
            $recipientCell->addText('Kepala '.$this->text($mutasi->sekolah_tujuan ?? '-'), [], ['spaceAfter' => 0]);
            $recipientCell->addText($this->text($mutasi->alamat_sekolah_tujuan ?? '-'), [], ['spaceAfter' => 0]);
            $row->addCell((int) ($portraitWidth * 0.52) - 500);
        }
        $section->addText('di Bogor', [], ['indentation' => ['left' => 500], 'spaceBefore' => 0, 'spaceAfter' => 0]);

        $body = sprintf(
            'Berdasarkan surat dari Kepala %s nomor : %s tanggal %s perihal Permohonan Izin Mutasi Masuk, dengan ini memberikan izin untuk menerima murid mutasi masuk sebagaimana terlampir.',
            $this->text($mutasi->sekolah_tujuan ?? '-'),
            $this->text($mutasi->nomor_surat_permohonan ?? '-'),
            $this->date($mutasi->tanggal_surat_permohonan ?? null)
        );
        $section->addText($body, [], $this->paragraph(['indentation' => ['firstLine' => 567]]));
        $section->addText('Dengan catatan :', ['bold' => true], ['spaceAfter' => 0]);
        foreach ([
            'proses perpindahan murid berpedoman kepada ketentuan yang berlaku;',
            'Kuota murid per rombel tidak melebihi ketentuan yang sudah ditetapkan;',
            'Satuan Pendidikan wajib memperbaharui data di Dapodik.',
        ] as $note) {
            $section->addListItem($note, 0, [], 'numberedList', ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1]);
        }
        $section->addText(
            'Demikian surat ini kami buat untuk dipergunakan sebagaimana mestinya.',
            [],
            $this->paragraph(['indentation' => ['firstLine' => 567]])
        );
        if ($srikandi) {
            $this->addSrikandiSignature($section, $portraitWidth, false, true);
        } else {
            $this->addSignature($section, $penandatangan, null, $portraitWidth);
        }

        $students = collect($siswaList)->map(fn ($student) => (array) $student)->values();
        if ($students->isNotEmpty()) {
            $chunks = $students->chunk(6)->values();
            $counter = 1;

            foreach ($chunks as $pageIndex => $chunk) {
                $landscapeWidth = self::A4_HEIGHT - Converter::cmToTwip(5);
                $landscape = $word->addSection([
                    'orientation' => 'landscape',
                    'pageSizeW' => self::A4_HEIGHT,
                    'pageSizeH' => self::A4_WIDTH,
                    'marginTop' => (int) round(Converter::cmToTwip(1.5)),
                    'marginRight' => (int) round(Converter::cmToTwip(2.5)),
                    'marginBottom' => (int) round(Converter::cmToTwip(3)),
                    'marginLeft' => (int) round(Converter::cmToTwip(2.5)),
                ]);

                if ($pageIndex === 0) {
                    $landscape->addText('LAMPIRAN', ['bold' => true], ['spaceAfter' => 0]);
                    $this->addKeyValueTable($landscape, [
                        ['Nomor', $srikandi ? '${nomor_naskah}' : ($mutasi->nomor_surat_izin ?? '-')],
                        ['Tanggal', $srikandi ? '${tanggal_naskah}' : $this->date($mutasi->tanggal_surat_izin ?? null)],
                    ], 4500, 0, 1000);
                    $landscape->addText('DAFTAR MURID MUTASI MASUK', ['bold' => true], [
                        'alignment' => Jc::CENTER,
                        'spaceBefore' => 0,
                        'spaceAfter' => 0,
                    ]);
                    $landscape->addText('KE '.$this->text($mutasi->sekolah_tujuan ?? '-'), ['bold' => true], [
                        'alignment' => Jc::CENTER,
                        'spaceAfter' => 0,
                    ]);
                }

                $this->addStudentTable($landscape, $chunk->all(), $counter, $landscapeWidth, $srikandi);

                if ($pageIndex === $chunks->count() - 1) {
                    if ($srikandi) {
                        $this->addSrikandiSignature($landscape, $landscapeWidth, false, true);
                    } else {
                        $this->addSignature($landscape, $penandatangan, null, $landscapeWidth);
                    }
                }
            }
        }

        return $this->save(
            $word,
            $srikandi ? 'surat-mutasi-masuk-srikandi' : 'surat-mutasi-masuk',
            $srikandi
        );
    }

    private function newDocument(string $title, string $font = self::FONT): PhpWord
    {
        $word = new PhpWord;
        $word->setDefaultFontName($font);
        $word->setDefaultFontSize(12);
        $word->addParagraphStyle('Normal', [
            'spaceBefore' => 0,
            'spaceAfter' => 0,
            'lineHeight' => 1,
        ]);
        $word->getDocInfo()->setTitle($title)->setSubject($title)->setCompany('Dinas Pendidikan Kota Bogor');
        $word->addNumberingStyle('numberedList', [
            'type' => 'multilevel',
            'levels' => [[
                'format' => 'decimal',
                'text' => '%1.',
                'left' => 500,
                'hanging' => 250,
                'tabPos' => 500,
            ]],
        ]);

        return $word;
    }

    private function addLetterhead(Section $section, int $width, bool $compact): void
    {
        $table = $section->addTable([
            'width' => $width,
            'unit' => TblWidth::TWIP,
            'borderSize' => 0,
            'cellMargin' => 40,
        ]);
        $row = $table->addRow();
        $logoCell = $row->addCell(1200, ['valign' => 'center']);
        $logo = public_path('images/logo_Kota_Bogor.png');
        if (is_file($logo)) {
            $logoCell->addImage($logo, ['width' => $compact ? 60 : 65, 'height' => $compact ? 75 : 80, 'alignment' => Jc::LEFT]);
        }
        $textCell = $row->addCell($width - 1200, ['valign' => 'center']);
        $textCell->addText('PEMERINTAH KOTA BOGOR', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $textCell->addText('DINAS PENDIDIKAN', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $textCell->addText('Jl. Padjajaran No. 125, Bantarjati, Bogor Utara, Kota Bogor, Jawa Barat 16153', ['size' => $compact ? 9 : 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $textCell->addText('Telp. 0251-8341101 Faksimile 0251-8341101', ['size' => $compact ? 9 : 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $textCell->addText('www.disdik.kotabogor.go.id', ['size' => $compact ? 9 : 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
    }

    private function addSrikandiLetterhead(Section $section, int $width): void
    {
        $table = $section->addTable([
            'width' => $width,
            'unit' => TblWidth::TWIP,
            'borderSize' => 0,
            'cellMargin' => 40,
        ]);
        $row = $table->addRow();
        $logoCell = $row->addCell(1200, ['valign' => 'center']);
        $logo = public_path('images/logo_Kota_Bogor.png');
        if (is_file($logo)) {
            $logoCell->addTextRun(['indentation' => ['left' => 140]])
                ->addImage($logo, ['width' => 48, 'height' => 62]);
        }
        $textCell = $row->addCell($width - 1200, ['valign' => 'center']);
        $textCell->addText('PEMERINTAH KOTA BOGOR', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $textCell->addText('DINAS PENDIDIKAN', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $textCell->addText(
            'Jl. Pajajaran No. 125 Kecamatan Bogor Utara, Kota Bogor',
            ['size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $textCell->addText(
            'Telepon (0251) 8341101 Faksimile (0251) 8341101',
            ['size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $textCell->addText('Situs web disdik.kotabogor.go.id', ['size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText('', ['size' => 1], [
            'borderBottomSize' => 8,
            'borderBottomStyle' => 'double',
            'borderBottomColor' => '000000',
            'lineHeight' => 0.1,
            'spaceBefore' => 0,
            'spaceAfter' => 380,
        ]);
    }

    private function addIncomingSrikandiLetterhead(Section $section, int $width): void
    {
        $this->addLetterhead($section, $width, true);
        $section->addText('', [], [
            'borderBottomSize' => 18,
            'borderBottomColor' => '000000',
            'spaceBefore' => 0,
            'spaceAfter' => 0,
        ]);
    }

    private function addIncomingLetterInfo(Section $section, object $mutasi, int $width, bool $srikandi): void
    {
        $table = $section->addTable(['width' => $width, 'unit' => TblWidth::TWIP, 'borderSize' => 0]);
        $row = $table->addRow();
        $left = $row->addCell((int) ($width * 0.58));
        $this->addKeyValueRows($left->addTable(['width' => (int) ($width * 0.58), 'unit' => TblWidth::TWIP, 'borderSize' => 0]), [
            ['Nomor', $srikandi ? '${nomor_naskah}' : ($mutasi->nomor_surat_izin ?? '-')],
            ['Sifat', $srikandi ? '${sifat}' : 'Biasa'],
            ['Lampiran', '1 (satu)'],
            ['Hal', $srikandi ? '${hal}' : 'Izin Mutasi Murid', true],
        ], (int) ($width * 0.58), 1000);
        $row->addCell((int) ($width * 0.42))->addText(
            'Bogor, '.($srikandi ? '${tanggal_naskah}' : $this->date($mutasi->tanggal_surat_izin ?? null)),
            [],
            ['alignment' => Jc::RIGHT, 'spaceAfter' => 0]
        );
    }

    private function addKeyValueTable(Section $section, array $rows, int $width, int $leftIndent = 0, int $labelWidth = 2300): void
    {
        $table = $section->addTable([
            'width' => $width,
            'unit' => TblWidth::TWIP,
            'indent' => new TblWidthComplex($leftIndent, TblWidth::TWIP),
            'borderSize' => 0,
            'cellMargin' => 20,
        ]);
        $this->addKeyValueRows($table, $rows, $width, $labelWidth);
    }

    private function addKeyValueRows($table, array $rows, int $width, int $labelWidth): void
    {
        foreach ($rows as $data) {
            $row = $table->addRow();
            $row->addCell($labelWidth)->addText($this->text($data[0]), [], ['spaceAfter' => 0]);
            $row->addCell(240)->addText(':', [], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $row->addCell($width - $labelWidth - 240)->addText(
                $this->text($data[1]),
                ['bold' => (bool) ($data[2] ?? false)],
                ['spaceAfter' => 0]
            );
        }
    }

    private function addStudentTable(
        Section $section,
        array $students,
        int &$counter,
        int $width,
        bool $visibleBorders = false
    ): void {
        $widths = [500, 2700, 1600, 600, 2500, 650, 3900, $width - 11850];
        $table = $section->addTable([
            'width' => $width,
            'unit' => TblWidth::TWIP,
            'borderSize' => $visibleBorders ? 1 : 0,
            'borderColor' => 'B7B7B7',
            'cellMargin' => 70,
            'layout' => 'fixed',
        ]);
        $header = $table->addRow(null, ['tblHeader' => true]);
        foreach (['No', 'Nama Murid', 'NISN', 'L/P', 'Tempat, Tanggal Lahir', 'Kelas', 'Sekolah Asal', 'Tgl Diterima'] as $index => $heading) {
            $header->addCell($widths[$index], ['valign' => 'center'])->addText($heading, ['bold' => true, 'size' => 10], [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 0,
            ]);
        }

        foreach ($students as $student) {
            $row = $table->addRow();
            $origin = 'NPSN: '.$this->text($student['npsn_asal'] ?? $student['npsn_sekolah_asal'] ?? '-')."\n"
                .$this->text($student['sekolah_asal'] ?? $student['nama_sekolah_asal'] ?? '-')."\n"
                .str_replace('Kabupaten', 'Kab.', $this->text($student['kabupaten_kota_asal'] ?? ''))
                .(! empty($student['kabupaten_kota_asal']) && ! empty($student['provinsi_asal']) ? ' - ' : '')
                .$this->text($student['provinsi_asal'] ?? '');
            $birth = $this->text($student['tempat_lahir'] ?? '-').",\n".$this->date($student['tanggal_lahir'] ?? null);
            $values = [
                $counter++,
                $student['nama_siswa'] ?? $student['nama_murid'] ?? '-',
                $student['nisn'] ?? '-',
                $student['jenis_kelamin'] ?? '-',
                $birth,
                $student['kelas'] ?? '-',
                $origin,
                $this->date($student['tanggal_diterima'] ?? null),
            ];
            foreach ($values as $index => $value) {
                $cell = $row->addCell($widths[$index], ['valign' => 'center']);
                $cell->addText($this->text($value), [
                    'bold' => $index === 1,
                    'size' => $index === 6 ? 9 : 10,
                ], [
                    'alignment' => in_array($index, [0, 2, 3, 5, 7], true) ? Jc::CENTER : Jc::LEFT,
                    'spaceAfter' => 0,
                ]);
            }
        }
    }

    private function addSignature(Section $section, ?object $signer, ?string $dateLine, int $width): void
    {
        $outer = $section->addTable(['width' => $width, 'unit' => TblWidth::TWIP, 'borderSize' => 0]);
        $row = $outer->addRow();
        $row->addCell((int) ($width * 0.53));
        $cell = $row->addCell((int) ($width * 0.47));
        if ($dateLine) {
            $cell->addText($dateLine, [], ['spaceAfter' => 0]);
        }
        $signature = $cell->addTable(['width' => (int) ($width * 0.47), 'unit' => TblWidth::TWIP, 'borderSize' => 0]);
        foreach ([
            ['a.n.', 'Kepala,'],
            ['', 'Sekretaris'],
            ['u.b.', 'Kepala Bidang SMP'],
        ] as $line) {
            $signatureRow = $signature->addRow();
            $signatureRow->addCell(500)->addText($line[0], [], ['spaceAfter' => 0]);
            $signatureRow->addCell((int) ($width * 0.47) - 500)->addText($line[1], [], ['spaceAfter' => 0]);
        }
        $signature->addRow(800)->addCell((int) ($width * 0.47), ['gridSpan' => 2]);
        foreach ([
            [$this->text($signer->nama ?? 'Nama Pejabat'), true],
            [$this->text($signer->pangkat_golongan ?? 'Pangkat'), false],
            ['NIP. '.$this->text($signer->nip ?? '-'), false],
        ] as $line) {
            $signatureRow = $signature->addRow();
            $signatureRow->addCell(500);
            $signatureRow->addCell((int) ($width * 0.47) - 500)->addText($line[0], ['bold' => $line[1]], ['spaceAfter' => 0]);
        }
    }

    private function addSrikandiSignature(
        Section $section,
        int $width,
        bool $includeDate = true,
        bool $spacious = false
    ): void {
        $outer = $section->addTable(['width' => $width, 'unit' => TblWidth::TWIP, 'borderSize' => 0]);
        $row = $outer->addRow();
        $row->addCell((int) ($width * 0.53));
        $cell = $row->addCell((int) ($width * 0.47));
        if ($spacious && $includeDate) {
            $cell->addText('Bogor, ${tanggal_naskah}', [], ['spaceBefore' => 560, 'spaceAfter' => 0]);
            $signature = $cell->addTable([
                'width' => (int) ($width * 0.47),
                'unit' => TblWidth::TWIP,
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            foreach ([['a.n.', 'Kepala Dinas,'], ['', 'Sekretaris,'], ['u.b.', 'Kabid SMP,']] as $line) {
                $signatureRow = $signature->addRow();
                $signatureRow->addCell(560)->addText($line[0], [], ['spaceAfter' => 0]);
                $signatureRow->addCell((int) ($width * 0.47) - 560)->addText($line[1], [], ['spaceAfter' => 0]);
            }
            $cell->addText('${ttd_pengirim}', [], [
                'indentation' => ['left' => 1080],
                'spaceBefore' => 580,
                'spaceAfter' => 0,
            ]);
            foreach (['${nama_pengirim}', '${pangkat_pengirim}', 'NIP. ${nip_pengirim}'] as $index => $line) {
                $cell->addText($line, [], [
                    'indentation' => ['left' => 560],
                    'spaceBefore' => $index === 0 ? 640 : 0,
                    'spaceAfter' => 0,
                ]);
            }

            return;
        }
        $lines = [
            'a.n. Kepala Dinas,',
            'Sekretaris,',
            'u.b. Kabid SMP,',
            '${ttd_pengirim}',
            '${nama_pengirim}',
            '${pangkat_pengirim}',
            'NIP. ${nip_pengirim}',
        ];
        if ($includeDate) {
            array_unshift($lines, 'Bogor, ${tanggal_naskah}');
        }
        foreach ($lines as $line) {
            $cell->addText($line, ['bold' => $line === '${nama_pengirim}'], [
                'spaceBefore' => 0,
                'spaceAfter' => 0,
            ]);
        }
    }

    private function save(PhpWord $word, string $prefix, bool $preserveTableBorders = false): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix.'-');
        if ($path === false) {
            throw new \RuntimeException('Tidak dapat membuat file DOCX sementara.');
        }
        $docxPath = $path.'.docx';
        @unlink($path);
        $word->save($docxPath, 'Word2007');
        $this->removeVisibleTableBorders($docxPath, $preserveTableBorders);

        return $docxPath;
    }

    private function removeVisibleTableBorders(string $docxPath, bool $preserveStudentTable = false): void
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('Tidak dapat membuka DOCX untuk menghapus border tabel.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            throw new \RuntimeException('Struktur dokumen Word tidak ditemukan.');
        }

        $noBorders = '<w:tblBorders>'
            .'<w:top w:val="nil"/><w:left w:val="nil"/>'
            .'<w:bottom w:val="nil"/><w:right w:val="nil"/>'
            .'<w:insideH w:val="nil"/><w:insideV w:val="nil"/>'
            .'</w:tblBorders>';
        $updatedXml = preg_replace_callback(
            '/<w:tblBorders\b[^>]*>.*?<\/w:tblBorders>/s',
            static function (array $matches) use ($noBorders, $preserveStudentTable): string {
                if ($preserveStudentTable && str_contains($matches[0], 'w:color="B7B7B7"')) {
                    return $matches[0];
                }

                return $noBorders;
            },
            $documentXml
        );

        if ($updatedXml === null || ! $zip->addFromString('word/document.xml', $updatedXml)) {
            $zip->close();
            throw new \RuntimeException('Tidak dapat menyimpan pengaturan tanpa border tabel.');
        }

        $zip->close();
    }

    private function paragraph(array $overrides = []): array
    {
        return array_replace_recursive([
            'alignment' => Jc::BOTH,
            'lineHeight' => 1,
            'spaceBefore' => 0,
            'spaceAfter' => 0,
        ], $overrides);
    }

    private function date(mixed $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->locale('id')->translatedFormat('j F Y');
        } catch (\Throwable) {
            return '-';
        }
    }

    private function filenamePart(mixed $value): string
    {
        $part = $this->text($value);
        $part = preg_replace('/[\\\\\/:*?"<>|]+/u', '-', $part) ?? '';
        $part = preg_replace('/\s+/u', ' ', $part) ?? '';
        $part = trim($part, " .-_\t\n\r\0\x0B");

        return $part !== '' ? $part : '-';
    }

    private function text(mixed $value): string
    {
        $text = html_entity_decode((string) ($value ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';

        return trim($text) !== '' ? trim($text) : '-';
    }
}
