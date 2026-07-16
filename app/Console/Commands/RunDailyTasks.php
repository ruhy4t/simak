<?php

namespace App\Console\Commands;

use App\Services\DailyTaskService;
use Illuminate\Console\Command;

class RunDailyTasks extends Command
{
    protected $signature = 'simak:daily-tasks';

    protected $description = 'Send revision reminders and auto-cancel expired revisions';

    public function handle(DailyTaskService $dailyTasks): int
    {
        $result = $dailyTasks->run();
        $this->line(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
