<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict route access to admin users only
 *
 * @example
 * Route::middleware(['auth', 'admin'])->group(fn () => ...);
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Admin access required.');
        }

        return $next($request);
    }
}
