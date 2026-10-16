<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,user')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(...$roles)) {
            abort(403, 'Танд энэ хуудсанд хандах эрх байхгүй байна.');
        }

        return $next($request);
    }
}
