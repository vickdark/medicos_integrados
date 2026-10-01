<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes the session of the clinic staff after a period without activity.
 * Patients are never affected. Requests the page makes by itself in the
 * background (marked with the X-Background header) do not count as activity.
 */
class LogoutInactiveStaff
{
    private const SESSION_KEY = 'last_activity_at';

    /**
     * Extra minute over the limit of the browser, which warns and closes first.
     */
    private const GRACE_MINUTES = 1;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $minutes = (int) config('auth.staff_idle_minutes');

        if (! $user || ! $user->isStaff() || $minutes <= 0) {
            return $next($request);
        }

        $lastActivity = $request->session()->get(self::SESSION_KEY);

        if ($lastActivity !== null && now()->timestamp - $lastActivity > ($minutes + self::GRACE_MINUTES) * 60) {
            Auth::guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson() && ! $request->header('X-Inertia')
                ? response()->json(['message' => 'Tu sesión se cerró por inactividad.'], 401)
                : to_route('login', ['expired' => 1]);
        }

        if (! $request->hasHeader('X-Background') || $lastActivity === null) {
            $request->session()->put(self::SESSION_KEY, now()->timestamp);
        }

        return $next($request);
    }
}
