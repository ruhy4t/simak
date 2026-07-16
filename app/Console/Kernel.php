<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('simak:daily-tasks')
            ->dailyAt('06:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();

        $schedule->command('simak:prune-runtime')
            ->dailyAt('02:30')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();

        $schedule->command('audit:prune --days=90')
            ->weeklyOn(1, '03:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
