<?php

namespace App\Http\Controllers;

use App\Services\DailyTaskService;

/**
 * HTTP bridge for schedulers on serverless/shared hosting.
 */
class CronController extends Controller
{
    /**
     * Run revision reminders and expiry cancellation.
     *
     * URL: /cron/reminder with Authorization: Bearer <CRON_SECRET>
     */
    public function revisionReminder(DailyTaskService $dailyTasks)
    {
        return response()->json($dailyTasks->run());
    }
}
