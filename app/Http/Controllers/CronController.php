<?php

namespace App\Http\Controllers;

use App\Services\DailyTaskService;
use App\Services\RuntimePruner;

/**
 * HTTP bridge for schedulers on serverless/shared hosting.
 */
class CronController extends Controller
{
    /**
     * Run expiry cancellation and runtime maintenance.
     *
     * URL: /cron/reminder with Authorization: Bearer <CRON_SECRET>
     */
    public function revisionReminder(DailyTaskService $dailyTasks, RuntimePruner $pruner)
    {
        $result = $dailyTasks->run();
        $result['maintenance'] = $pruner->prune();

        return response()->json($result);
    }
}
