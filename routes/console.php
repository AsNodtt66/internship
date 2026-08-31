<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pkl:ingatkan-keputusan-perpanjangan')
    ->dailyAt('08:00')
    ->withoutOverlapping(30)
    ->onSuccess(fn () => Log::channel('operations')->info('scheduler.extension_reminder.succeeded'))
    ->onFailure(fn () => Log::channel('operations')->error('scheduler.extension_reminder.failed'));

$queueTarget = (string) config('operations.queue_monitor.target');
$queueMax = (int) config('operations.queue_monitor.max');

Schedule::command("queue:monitor {$queueTarget} --max={$queueMax}")
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onFailure(fn () => Log::channel('operations')->error('scheduler.queue_monitor.failed'));

$failedRetention = (int) config('operations.failed_jobs_retention_hours');

Schedule::command("queue:prune-failed --hours={$failedRetention}")
    ->dailyAt('02:30')
    ->withoutOverlapping(30)
    ->onFailure(fn () => Log::channel('operations')->error('scheduler.queue_prune_failed.failed'));
