<?php

use App\Http\Middleware\RequestId;
use App\Http\Middleware\RequestPerformance;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RequestId::class);
        $middleware->append(RequestPerformance::class);
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (AuthorizationException $exception): void {
            Log::channel('operations')->warning('authorization.denied', [
                'user_id' => auth()->id(),
                'route' => app()->runningInConsole() ? null : request()->route()?->getName(),
                'request_id' => app()->runningInConsole() ? null : request()->attributes->get('request_id'),
                'ability' => method_exists($exception, 'ability') ? $exception->ability() : null,
            ]);
        });

        // Filament/Livewire form submissions may return 419 after a session
        // expires. Browser navigation goes back to the correct panel login;
        // JSON/XHR clients keep the original 419 response semantics.
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()->route('filament.admin.auth.login')
                    ->with('message', 'Sesi telah berakhir, silakan login kembali.');
            }

            return redirect()->route('filament.peserta.auth.login')
                ->with('message', 'Sesi telah berakhir, silakan login kembali.');
        });
    })->create();
