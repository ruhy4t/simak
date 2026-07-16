<?php

namespace App\Console\Commands;

use App\Services\RuntimePruner;
use Illuminate\Console\Command;

class PruneRuntimeData extends Command
{
    protected $signature = 'simak:prune-runtime';

    protected $description = 'Prune expired database cache entries and abandoned temporary uploads';

    public function handle(RuntimePruner $pruner): int
    {
        $this->line(json_encode($pruner->prune(), JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
