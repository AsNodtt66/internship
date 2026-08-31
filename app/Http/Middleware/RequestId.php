<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    private const HEADER = 'X-Request-ID';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get(self::HEADER);
        $requestId = is_string($incoming) && $this->isSafe($incoming)
            ? $incoming
            : (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);
        Log::withContext(['request_id' => $requestId]);

        try {
            /** @var Response $response */
            $response = $next($request);
            $response->headers->set(self::HEADER, $requestId);

            return $response;
        } finally {
            // Prevent context leakage in Octane / long-lived processes.
            Log::withoutContext(['request_id']);
        }
    }

    private function isSafe(string $value): bool
    {
        return strlen($value) >= 8
            && strlen($value) <= 100
            && preg_match('/^[A-Za-z0-9._:-]+$/', $value) === 1;
    }
}
