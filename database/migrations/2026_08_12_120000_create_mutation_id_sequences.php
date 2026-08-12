<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutation_id_sequences', function (Blueprint $table): void {
            $table->string('type', 10)->primary();
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('mutation_id_sequences')->insert([
            [
                'type' => 'keluar',
                'last_value' => $this->highestExistingSequence('mutasi_keluar', 'MK'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'masuk',
                'last_value' => $this->highestExistingSequence('mutasi_masuk', 'MM'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mutation_id_sequences');
    }

    private function highestExistingSequence(string $table, string $prefix): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->pluck('id_mutasi')
            ->reduce(function (int $highest, string $id) use ($prefix): int {
                if (preg_match('/^'.preg_quote($prefix, '/').'\d{8}(\d+)$/', $id, $matches) !== 1) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);
    }
};
