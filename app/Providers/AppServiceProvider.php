<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\ReportingSchema;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReportingSchema::class);
    }

    public function boot(): void
    {
        Blade::precompiler(function (string $template): string {
            return preg_replace_callback(
                '/<script\b(?![^>]*\bnonce\s*=)/i',
                fn (array $match): string => $match[0].' nonce="{{ request()->attributes->get(\'csp_nonce\') }}"',
                $template
            ) ?? $template;
        });

        // Force HTTPS in production (required for Vercel)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Set locale Indonesia untuk format tanggal
        Carbon::setLocale('id');
        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'Indonesian');

        // Register User Policy
        Gate::policy(User::class, UserPolicy::class);

        DB::listen(function (QueryExecuted $query): void {
            if ($query->time >= (int) config('observability.slow_query_ms')) {
                Log::channel('metrics')->warning('database.slow_query', [
                    'connection' => $query->connectionName,
                    'duration_ms' => round($query->time, 2),
                    'sql' => $query->sql,
                ]);
            }
        });

        Queue::failing(function (JobFailed $event): void {
            Log::channel('metrics')->error('queue.failed', [
                'connection' => $event->connectionName,
                'job' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'attempts' => $event->job->attempts(),
                'exception_class' => $event->exception::class,
            ]);
        });
    }
}
