<?php

namespace Tests\Unit;

use App\Models\MutasiKeluar;
use App\Models\MutasiMasuk;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SubmissionDateTest extends TestCase
{
    public function test_resubmission_date_is_primary_without_hiding_original_date(): void
    {
        $html = Blade::render(
            '<x-submission-date original="2026-07-01" latest="2026-07-16 09:30:00" :revision-count="2" />'
        );

        $this->assertStringContainsString('16 Juli 2026', $html);
        $this->assertStringContainsString('Kirim ulang ke-2', $html);
        $this->assertStringContainsString('Awal 1 Jul 2026', $html);
    }

    public function test_mutation_lists_sort_by_latest_submission(): void
    {
        $this->assertStringContainsString(
            'last_submitted_at',
            MutasiKeluar::query()->latestSubmissionFirst()->toSql()
        );
        $this->assertStringContainsString(
            'last_submitted_at',
            MutasiMasuk::query()->latestSubmissionFirst()->toSql()
        );
    }
}
