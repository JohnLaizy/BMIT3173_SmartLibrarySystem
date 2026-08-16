<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictInactiveUserActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Guests, active users and librarians continue normally.
        if (
            ! $user ||
            $user->isActive() ||
            $user->isLibrarian()
        ) {
            return $next($request);
        }

        $restrictedRoutes = [
            'borrowings.store',
            'book-reservations.store',
            'room-reservations.create',
            'room-reservations.store',
        ];

        if ($request->routeIs(...$restrictedRoutes)) {
            return redirect()
                ->back()
                ->with(
                    'inactive_action_error',
                    'Please return your overdue book and pay any outstanding fine before borrowing or making a new reservation.'
                );
        }

        return $next($request);
    }
}