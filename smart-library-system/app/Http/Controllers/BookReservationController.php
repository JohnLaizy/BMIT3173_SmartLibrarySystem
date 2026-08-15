<?php

namespace App\Http\Controllers;

use App\Exceptions\BorrowingRuleViolation;
use App\Models\Book;
use App\Models\BookReservation;
use App\Models\User;
use App\Services\BookReservationService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class BookReservationController extends Controller
{
    public function index(
        Request $request,
        BookReservationService $service
    ): View {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'viewAny',
            BookReservation::class
        );

        $service->expireApprovedReservations();

        $reservationsQuery =
            BookReservation::query()
                ->with([
                    'book',
                    'student',
                    'reviewer',
                ])
                ->latest('requested_at');

        if ($user->isStudent()) {
            $reservationsQuery->where(
                'user_id',
                $user->id
            );
        }

        $reservations = $reservationsQuery
            ->paginate(10);

        $books = $user->isStudent()
            ? Book::query()
                ->where('type', Book::TYPE_PHYSICAL)
                ->orderBy('title')
                ->limit(100)
                ->get()
            : collect();

        $activeReservationBookIds =
            $user->isStudent()
                ? BookReservation::query()
                    ->where('user_id', $user->id)
                    ->active()
                    ->pluck('book_id')
                : collect();

        return view(
            'book-reservations.index',
            [
                'reservations' => $reservations,
                'books' => $books,
                'activeReservationBookIds' =>
                    $activeReservationBookIds,
            ]
        );
    }
    public function store(
        Request $request,
        BookReservationService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'create',
            BookReservation::class
        );

        $validated = $request->validate([
            'book_id' => [
                'required',
                'integer',
                Rule::exists('books', 'id')
                    ->where(
                        fn (Builder $query) =>
                            $query->where(
                                'type',
                                Book::TYPE_PHYSICAL
                            )
                    ),
            ],
        ]);

        $book = Book::query()->findOrFail(
            (int) $validated['book_id']
        );

        return $this->performAction(
            fn () => $service->request($user, $book),
            'Reservation request submitted.'
        );
    }

    public function approve(
        Request $request,
        BookReservation $reservation,
        BookReservationService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize('approve', $reservation);

        return $this->performAction(
            fn () => $service->approve(
                $user,
                $reservation
            ),
            'Reservation approved.'
        );
    }

    public function reject(
        Request $request,
        BookReservation $reservation,
        BookReservationService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize('reject', $reservation);

        $validated = $request->validate([
            'rejection_reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        return $this->performAction(
            fn () => $service->reject(
                $user,
                $reservation,
                $validated['rejection_reason'] ?? null
            ),
            'Reservation rejected.'
        );
    }

    public function cancel(
        Request $request,
        BookReservation $reservation,
        BookReservationService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize('cancel', $reservation);

        return $this->performAction(
            fn () => $service->cancel(
                $user,
                $reservation
            ),
            'Reservation cancelled.'
        );
    }

    private function authenticatedUser(
        Request $request
    ): User {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthorizationException();
        }

        return $user;
    }

    private function performAction(
        Closure $operation,
        string $successMessage
    ): RedirectResponse {
        try {
            $operation();

            return back()->with(
                'success',
                $successMessage
            );
        } catch (
            BorrowingRuleViolation $exception
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $exception->getMessage()
                );
        }
    }

    public function collect(
        Request $request,
        BookReservation $reservation,
        BookReservationService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize('collect', $reservation);

        return $this->performAction(
            fn () => $service->collect(
                $user,
                $reservation
            ),
            'Reservation collected and borrowing created.'
        );
    }
}