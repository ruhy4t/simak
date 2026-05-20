<?php

namespace Tests\Feature;

use App\Helpers\QrCodeHelper;
use Tests\TestCase;

class QrCodeHelperTest extends TestCase
{
    public function test_qr_code_helper_generates_image_markup(): void
    {
        $markup = QrCodeHelper::generateWithLogo('SIMAK|TEST|2026-05-20|Siswa Uji', 120);

        $this->assertStringContainsString('<img', $markup);
        $this->assertStringContainsString('alt="QR Code"', $markup);
        $this->assertStringContainsString('data:image/png;base64,', $markup);
        $this->assertStringContainsString('width: 120px', $markup);
    }

    public function test_surat_qr_data_is_compact_and_predictable(): void
    {
        $data = QrCodeHelper::generateSuratData('ABC-123', '2026-05-20', 'Nama Siswa Sangat Panjang Untuk Dipotong');

        $this->assertSame('SIMAK|ABC-123|2026-05-20|Nama Siswa Sangat Pa', $data);
    }
}
