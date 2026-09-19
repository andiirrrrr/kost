<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $user?->tenant;

        abort_unless(
            $user?->hasRole('tenant') && $tenant?->canAccessPortal(),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
