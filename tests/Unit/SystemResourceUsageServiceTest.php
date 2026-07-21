<?php

namespace Tests\Unit;

use App\Services\SystemResourceUsageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemResourceUsageServiceTest extends TestCase
{
    public function test_database_usage_uses_live_schema_size_against_one_gibibyte_limit(): void
    {
        config()->set('system_status.database_limit_bytes', 1024 * 1024 * 1024);
        config()->set('database.connections.mysql.database', 'simak');

        DB::shouldReceive('select')
            ->once()
            ->withArgs(fn (string $query, array $bindings) => str_contains($query, 'information_schema.INNODB_TABLESPACES')
                && $bindings === ['simak'])
            ->andReturn([(object) ['bytes' => 256 * 1024 * 1024]]);

        $stats = app(SystemResourceUsageService::class)->database();

        $this->assertSame(256 * 1024 * 1024, $stats['size']);
        $this->assertSame('256.00 MB', $stats['formatted_size']);
        $this->assertSame('1.00 GB', $stats['formatted_limit']);
        $this->assertSame(25.0, $stats['percentage']);
        $this->assertSame('768.00 MB', $stats['formatted_remaining']);
        $this->assertNull($stats['error']);
    }

    public function test_backblaze_usage_sums_object_metadata_without_a_file_count_cutoff(): void
    {
        Storage::fake('b2');
        config()->set('filesystems.disks.b2.key', 'read-only-key');
        config()->set('filesystems.disks.b2.bucket', 'simak-documents');
        config()->set('system_status.b2_limit_bytes', 10 * 1024 * 1024 * 1024);

        foreach (range(1, 1001) as $number) {
            Storage::disk('b2')->put("dokumen/{$number}.pdf", 'a');
        }

        $stats = app(SystemResourceUsageService::class)->backblaze();

        $this->assertSame(1001, $stats['count']);
        $this->assertSame(1001, $stats['size']);
        $this->assertSame('1,001 B', $stats['formatted_size']);
        $this->assertSame('10.00 GB', $stats['formatted_limit']);
        $this->assertNull($stats['error']);
    }
}
