<?php

namespace App\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReleaseCandidateCheck extends Command
{
    protected $signature = 'release:check {--strict : Enforce staging/production release requirements}';

    protected $description = 'Validate P7 release-candidate runtime, security, storage, database and dependency readiness';

    /** @var array<int, array{label:string, ok:bool, detail:string, strict_only?:bool}> */
    private array $checks = [];

    public function handle(): int
    {
        $strict = (bool) $this->option('strict');

        $this->info('P7 Release Candidate Check'.($strict ? ' (strict)' : ''));

        $this->check('PHP runtime', version_compare(PHP_VERSION, (string) config('release.required_php_major_minor'), '>='), PHP_VERSION);
        $this->check('APP_KEY configured', filled(config('app.key')), filled(config('app.key')) ? 'configured' : 'missing');
        $this->check('APP_DEBUG disabled', ! config('app.debug'), config('app.debug') ? 'true' : 'false', true);

        $appUrl = (string) config('app.url');
        $httpsOk = ! config('release.require_https') || str_starts_with(strtolower($appUrl), 'https://');
        $this->check('HTTPS application URL', $httpsOk, $appUrl !== '' ? $appUrl : '(empty)', true);

        $secureCookie = (bool) config('session.secure');
        $this->check('Secure session cookie', ! config('release.require_secure_cookie') || $secureCookie, $secureCookie ? 'enabled' : 'disabled', true);
        $this->check('HTTP-only session cookie', (bool) config('session.http_only'), config('session.http_only') ? 'enabled' : 'disabled');
        $this->check('Session SameSite configured', in_array(config('session.same_site'), ['lax', 'strict', 'none'], true), (string) config('session.same_site'));

        $queue = (string) config('queue.default');
        $asyncOk = ! config('release.require_queue_async') || ! in_array($queue, ['sync', 'null'], true);
        $this->check('Asynchronous queue', $asyncOk, $queue, true);

        $privateDiskName = (string) config('filesystems.private_documents_disk', 'documents');
        $privateDisk = config("filesystems.disks.{$privateDiskName}", []);
        $privateRoot = (string) ($privateDisk['root'] ?? '');
        $privateVisibility = (string) ($privateDisk['visibility'] ?? 'private');
        $privateOk = $privateDiskName !== 'public'
            && $privateVisibility !== 'public'
            && $privateRoot !== ''
            && ! str_starts_with(realpath($privateRoot) ?: $privateRoot, public_path());
        $this->check('Private document storage', $privateOk, $privateDiskName.' / '.$privateVisibility);

        $csp = trim((string) config('security.headers.csp', ''));
        $cspReportOnly = trim((string) config('security.headers.csp_report_only', ''));
        $cspOk = ! config('release.require_csp') || $csp !== '' || $cspReportOnly !== '';
        $this->check('CSP configured', $cspOk, $csp !== '' ? 'enforced' : ($cspReportOnly !== '' ? 'report-only' : 'not configured'), true);

        $this->checkDependency('Laravel', 'laravel/framework', (int) config('release.required_laravel_major'), $strict);
        $this->checkDependency('Filament', 'filament/filament', (int) config('release.required_filament_major'), $strict);
        $this->checkPackageJsonVite((int) config('release.required_vite_major'), $strict);

        $this->checkDatabase($strict);

        foreach ($this->checks as $check) {
            if (($check['strict_only'] ?? false) && ! $strict) {
                $symbol = $check['ok'] ? 'INFO' : 'DEFER';
                $this->line(sprintf('[%s] %s — %s', $symbol, $check['label'], $check['detail']));
                continue;
            }

            $this->line(sprintf('[%s] %s — %s', $check['ok'] ? 'PASS' : 'FAIL', $check['label'], $check['detail']));
        }

        $failed = array_filter($this->checks, fn (array $check): bool => ! $check['ok'] && ($strict || ! ($check['strict_only'] ?? false)));

        if ($failed !== []) {
            $this->newLine();
            $this->error(count($failed).' release-candidate check(s) failed.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info($strict ? 'Strict release-candidate checks passed.' : 'Baseline release-readiness checks passed. Run --strict on staging before promotion.');

        return self::SUCCESS;
    }

    private function check(string $label, bool $ok, string $detail, bool $strictOnly = false): void
    {
        $this->checks[] = compact('label', 'ok', 'detail') + ['strict_only' => $strictOnly];
    }

    private function checkDependency(string $label, string $package, int $requiredMajor, bool $strict): void
    {
        try {
            $version = InstalledVersions::isInstalled($package) ? InstalledVersions::getPrettyVersion($package) : null;
        } catch (Throwable) {
            $version = null;
        }

        $major = $version !== null && preg_match('/(\d+)/', $version, $m) === 1 ? (int) $m[1] : 0;
        $this->check("{$label} major", $major >= $requiredMajor, $version ?? 'not installed', true);
    }

    private function checkPackageJsonVite(int $requiredMajor, bool $strict): void
    {
        $packageFile = base_path('package.json');
        $version = null;
        if (is_file($packageFile)) {
            $json = json_decode((string) file_get_contents($packageFile), true);
            $version = $json['devDependencies']['vite'] ?? null;
        }
        $major = is_string($version) && preg_match('/(\d+)/', $version, $m) === 1 ? (int) $m[1] : 0;
        $this->check('Vite major', $major >= $requiredMajor, $version ?? 'missing', true);
    }

    private function checkDatabase(bool $strict): void
    {
        try {
            DB::connection()->getPdo();
            $this->check('Database connectivity', true, DB::connection()->getDriverName());
        } catch (Throwable $e) {
            $this->check('Database connectivity', false, $e->getMessage());
            return;
        }

        foreach (['users', 'pengajuans', 'approval_workflows', 'jobs', 'failed_jobs'] as $table) {
            $this->check("Database table {$table}", Schema::hasTable($table), Schema::hasTable($table) ? 'present' : 'missing');
        }

        if ($strict) {
            try {
                Artisan::call('migrate:status', ['--no-interaction' => true]);
                $output = Artisan::output();
                $pending = preg_match_all('/\bPending\b/i', $output);
                $this->check('Pending migrations', $pending <= (int) config('release.max_pending_migrations'), (string) $pending, true);
            } catch (Throwable $e) {
                $this->check('Pending migrations', false, $e->getMessage(), true);
            }
        }
    }
}
