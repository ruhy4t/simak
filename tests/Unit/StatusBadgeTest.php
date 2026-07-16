<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class StatusBadgeTest extends TestCase
{
    public function test_status_badges_use_consistent_semantic_styles(): void
    {
        $completed = Blade::render('<x-status-badge status="Selesai" />');
        $waiting = Blade::render('<x-status-badge status="Menunggu Verifikasi Dinas" />');
        $revision = Blade::render('<x-status-badge status="Perlu Perbaikan" />');

        $this->assertStringContainsString('bg-emerald-50', $completed);
        $this->assertStringContainsString('bg-amber-50', $waiting);
        $this->assertStringContainsString('Menunggu Verifikasi', $waiting);
        $this->assertStringContainsString('bg-rose-50', $revision);
    }
}
