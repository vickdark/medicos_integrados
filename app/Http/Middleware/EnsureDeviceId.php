<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifies the browser/device with a long-lived random cookie. It is not a
 * credential: it only lets the app remember what was already shown on a device.
 */
class EnsureDeviceId
{
    public const COOKIE = 'device_id';

    private const LIFETIME_MINUTES = 60 * 24 * 400;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = (string) $request->cookie(self::COOKIE);

        if (! Str::isUuid($deviceId)) {
            $deviceId = (string) Str::uuid();

            Cookie::queue(Cookie::make(self::COOKIE, $deviceId, self::LIFETIME_MINUTES, httpOnly: true, sameSite: 'lax'));
        }

        $request->attributes->set(self::COOKIE, $deviceId);

        return $next($request);
    }

    /**
     * The identifier of the device that made the request.
     */
    public static function from(Request $request): string
    {
        return (string) $request->attributes->get(self::COOKIE, '');
    }
}
