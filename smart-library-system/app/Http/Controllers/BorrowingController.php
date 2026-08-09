<?php

namespace App\Http\Controllers;

use App\Exceptions\BorrowingRuleViolation;
use App\Http\Requests\BorrowBookRequest;
use App\Http\Requests\SubmitOverduePaymentRequest;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Services\BorrowingService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class BorrowingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'viewAny',
            Borrowing::class
        );

        $borrowingsQuery = Borrowing::query()
            ->with([
                'book',
                'student',
                'paymentApprover',
            ])
            ->latest('borrowed_at');

        if ($user->isStudent()) {
            $borrowingsQuery->where(
                'user_id',
                $user->id
            );
        }

        $borrowings = $borrowingsQuery
            ->paginate(10);

        $availableBooks = $user->isStudent()
            ? Book::query()
                ->where('available_copies', '>', 0)
                ->orderBy('title')
                ->limit(50)
                ->get()
            : collect();

        $activeCopyCount = $user->isStudent()
            ? Borrowing::query()
                ->where('user_id', $user->id)
                ->activeCopies()
                ->count()
            : null;

        $hasUnresolvedOverdue = $user->isStudent()
            ? Borrowing::query()
                ->where('user_id', $user->id)
                ->unresolvedOverdue()
                ->exists()
            : false;

        return view('borrowings.index', [
            'borrowings' => $borrowings,
            'availableBooks' => $availableBooks,
            'activeCopyCount' => $activeCopyCount,
            'hasUnresolvedOverdue' =>
                $hasUnresolvedOverdue,
        ]);
    }

    public function store(
        BorrowBookRequest $request,
        BorrowingService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'create',
            Borrowing::class
        );

        $validated = $request->validated();

        $book = Book::query()->findOrFail(
            (int) $validated['book_id']
        );

        return $this->performAction(
            $request,
            fn () => $service->borrow(
                $user,
                $book
            ),
            'Book borrowed successfully.'
        );
    }

    public function returnCopy(
        Request $request,
        Borrowing $borrowing,
        BorrowingService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'returnCopy',
            $borrowing
        );

        return $this->performAction(
            $request,
            fn () => $service->returnCopy(
                $user,
                $borrowing
            ),
            'Book returned successfully.'
        );
    }

    public function submitPayment(
        SubmitOverduePaymentRequest $request,
        Borrowing $borrowing,
        BorrowingService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'submitPayment',
            $borrowing
        );

        $validated = $request->validated();

        return $this->performAction(
            $request,
            fn () => $service->submitPayment(
                $user,
                $borrowing,
                $validated['payment_reference']
            ),
            'Payment submitted for librarian approval.'
        );
    }

    public function approvePayment(
        Request $request,
        Borrowing $borrowing,
        BorrowingService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'approvePayment',
            $borrowing
        );

        return $this->performAction(
            $request,
            fn () => $service->approvePayment(
                $user,
                $borrowing
            ),
            'Overdue payment approved.'
        );
    }

    public function rejectPayment(
        Request $request,
        Borrowing $borrowing,
        BorrowingService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'rejectPayment',
            $borrowing
        );

        return $this->performAction(
            $request,
            fn () => $service->rejectPayment(
                $user,
                $borrowing
            ),
            'Overdue payment rejected.'
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
        Request $request,
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
            Log::warning(
                'Borrowing request rejected by a business rule.',
                [
                    'user_id' =>
                        $request->user()
                            ?->getAuthIdentifier(),

                    'reason' =>
                        $exception->getMessage(),
                ]
            );

            return back()->with(
                'error',
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            $reference = (string) Str::uuid();

            Log::error(
                'Unexpected borrowing operation failure.',
                [
                    'reference' => $reference,

                    'user_id' =>
                        $request->user()
                            ?->getAuthIdentifier(),

                    'exception' => $exception,
                ]
            );

            return back()->with(
                'error',
                "Unable to process the request. Reference: {$reference}"
            );
        }
    }
}