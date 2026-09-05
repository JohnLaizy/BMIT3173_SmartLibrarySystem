<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageBooks
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canManageBooks()) {
            abort(403, 'Students are not allowed to manage books.');
        }

        return $next($request);
    }
}