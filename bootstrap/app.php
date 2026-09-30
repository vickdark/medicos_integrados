<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureDeviceId;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'device_id']);

        $middleware->web(append: [
            HandleAppearance::class,
            EnsureDeviceId::class,
            HandleInertiaRequests::class,
            EnsureAccountIsActive::class,
            EnsurePasswordIsChanged::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            $isFriendlyStatus = in_array($status, [403, 404, 419, 429], true)
                || (in_array($status, [500, 503], true) && ! config('app.debug'));

            if ($request->expectsJson() && ! $request->header('X-Inertia') || ! $isFriendlyStatus) {
                return $response;
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
