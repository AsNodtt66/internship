<?php

namespace App\Providers;

use App\Models\ApprovalWorkflow;
use App\Models\Evaluasi;
use App\Models\Pengajuan;
use App\Models\Penilaian;
use App\Models\PenugasanPembimbing;
use App\Models\Perpanjangan;
use App\Models\SuratBalasan;
use App\Models\SuratKeterangan;
use App\Observers\DomainAuditObserver;
use Filament\Forms\Components\FileUpload;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FileUpload::configureUsing(function (FileUpload $component): void {
            $component->preventFilePathTampering();
        });

        foreach ([
            Pengajuan::class,
            ApprovalWorkflow::class,
            PenugasanPembimbing::class,
            Evaluasi::class,
            Perpanjangan::class,
            Penilaian::class,
            SuratBalasan::class,
            SuratKeterangan::class,
        ] as $model) {
            $model::observe(DomainAuditObserver::class);
        }

        $this->configureRateLimiters();
        $this->configureSecurityEventLogging();
        $this->configureQueueLogging();
        $this->configurePerformanceMonitoring();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('private-documents', function (Request $request): Limit {
            return Limit::perMinute((int) config('security.rate_limits.documents_per_minute', 60))
                ->by('documents:'.($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('generated-reports', function (Request $request): Limit {
            return Limit::perMinute((int) config('security.rate_limits.reports_per_minute', 30))
                ->by('reports:'.($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('health', function (Request $request): Limit {
            return Limit::perMinute((int) config('security.rate_limits.health_per_minute', 120))
                ->by('health:'.$request->ip());
        });
    }

    private function configureSecurityEventLogging(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            Log::channel('operations')->info('auth.login_succeeded', [
                'guard' => $event->guard,
                'user_id' => $event->user->getAuthIdentifier(),
                'request_id' => app()->runningInConsole() ? null : request()->attributes->get('request_id'),
            ]);
        });

        Event::listen(Failed::class, function (Failed $event): void {
            Log::channel('operations')->warning('auth.login_failed', [
                'guard' => $event->guard,
                'user_id' => $event->user->getAuthIdentifier(),
                'credential_type' => array_key_exists('nip', $event->credentials) ? 'nip' : 'email',
                'request_id' => app()->runningInConsole() ? null : request()->attributes->get('request_id'),
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            Log::channel('operations')->info('auth.logout', [
                'guard' => $event->guard,
                'user_id' => $event->user->getAuthIdentifier(),
                'request_id' => app()->runningInConsole() ? null : request()->attributes->get('request_id'),
            ]);
        });
    }

    private function configureQueueLogging(): void
    {
        Queue::failing(function (JobFailed $event): void {
            Log::channel('operations')->error('queue.job_failed', [
                'connection' => $event->connectionName,
                'job_id' => $event->job->getJobId(),
                'job' => $event->job->resolveName(),
                'exception' => $event->exception::class,
            ]);
        });

        Event::listen(QueueBusy::class, function (QueueBusy $event): void {
            // Laravel 13 renamed QueueBusy::$connection to $connectionName.
            // Keep this listener source-compatible with Laravel 12 during the staged upgrade.
            $connectionName = property_exists($event, 'connectionName')
                ? $event->connectionName
                : $event->connection;

            Log::channel('operations')->warning('queue.busy', [
                'connection' => $connectionName,
                'queue' => $event->queue,
                'size' => $event->size,
            ]);
        });
    }

    private function configurePerformanceMonitoring(): void
    {
        if ((bool) config('performance.prevent_lazy_loading', false)) {
            Model::preventLazyLoading();
        }

        $thresholdMs = max(1, (int) config('performance.database_warn_ms', 500));

        DB::whenQueryingForLongerThan(
            $thresholdMs,
            function (Connection $connection, QueryExecuted $event): void {
                Log::channel('operations')->warning('performance.slow_database_request', [
                    'connection' => $connection->getName(),
                    'query_time_ms' => $event->time,
                    'total_query_time_ms' => round($connection->totalQueryDuration(), 2),
                    // Do not log bindings: they may contain participant PII.
                    'sql' => $event->sql,
                    'route' => app()->runningInConsole() ? null : request()->route()?->getName(),
                    'request_id' => app()->runningInConsole() ? null : request()->attributes->get('request_id'),
                ]);
            }
        );
    }
}
