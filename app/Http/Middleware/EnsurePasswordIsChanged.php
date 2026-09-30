<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps users with a temporary password on the security page until they
 * choose their own.
 */
class EnsurePasswordIsChanged
{
    /**
     * Routes that stay reachable while the password is temporary.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTES = [
        'security.edit',
        'user-password.update',
        'logout',
        'password.confirm',
        'password.confirmation',
        'password.confirm.store',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs(self::ALLOWED_ROUTES)) {
            return to_route('security.edit', status: 303)
                ->with('error', 'Tu contraseña es temporal. Cámbiala para continuar.');
        }

        return $next($request);
    }
}
