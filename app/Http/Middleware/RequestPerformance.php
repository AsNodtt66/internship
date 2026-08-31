<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $thresholdMs = max(1, (int) config('performance.request_warn_ms', 1500));

        if ($durationMs >= $thresholdMs) {
            Log::channel('operations')->warning('performance.slow_request', [
                'duration_ms' => round($durationMs, 2),
                'method' => $request->method(),
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'request_id' => $request->attributes->get('request_id'),
            ]);
        }

        if ((bool) config('performance.server_timing', false)) {
            $response->headers->set('Server-Timing', 'app;dur='.number_format($durationMs, 2, '.', ''));
        }

        return $response;
    }
}
