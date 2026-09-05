<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageBooks
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isLibrarian()) {
            abort(403, 'Only librarians are allowed to manage books.');
        }

        return $next($request);
    }
}
