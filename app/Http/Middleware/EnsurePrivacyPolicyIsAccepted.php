<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Asks patients to accept the personal data policy again whenever its version
 * changes, and before using an account that never accepted it.
 */
class EnsurePrivacyPolicyIsAccepted
{
    /**
     * Routes that stay reachable while the current version is not accepted.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTES = [
        'privacy',
        'privacy.accept',
        'privacy.accept.store',
        'logout',
        'verification.*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->must_change_password && $user->mustAcceptPrivacyPolicy() && ! $request->routeIs(self::ALLOWED_ROUTES)) {
            return to_route('privacy.accept', status: 303);
        }

        return $next($request);
    }
}
