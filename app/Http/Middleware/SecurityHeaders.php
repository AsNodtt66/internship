<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', (string) config('security.headers.frame_options', 'SAMEORIGIN'));
        $response->headers->set('Referrer-Policy', (string) config('security.headers.referrer_policy', 'strict-origin-when-cross-origin'));
        $response->headers->set('Permissions-Policy', (string) config('security.headers.permissions_policy', 'camera=(), microphone=(), geolocation=()'));

        $cspReportOnly = config('security.headers.csp_report_only');
        if (is_string($cspReportOnly) && trim($cspReportOnly) !== '') {
            $response->headers->set('Content-Security-Policy-Report-Only', trim($cspReportOnly));
        }

        $csp = config('security.headers.csp');
        if (is_string($csp) && trim($csp) !== '') {
            $response->headers->set('Content-Security-Policy', trim($csp));
        }

        if ($request->isSecure() && config('security.headers.hsts.enabled', false)) {
            $hsts = 'max-age='.(int) config('security.headers.hsts.max_age', 31536000);

            if (config('security.headers.hsts.include_subdomains', true)) {
                $hsts .= '; includeSubDomains';
            }

            if (config('security.headers.hsts.preload', false)) {
                $hsts .= '; preload';
            }

            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        return $response;
    }
}
