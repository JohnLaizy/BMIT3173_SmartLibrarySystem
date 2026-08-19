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

        $borrowingSearch = trim((string) $request->query('borrowing_search', ''));

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
        } elseif ($borrowingSearch !== '') {
            $borrowingsQuery->where(function ($query) use ($borrowingSearch): void {
                $query
                    ->whereHas('student', function ($studentQuery) use ($borrowingSearch): void {
                        $studentQuery
                            ->where('name', 'like', "%{$borrowingSearch}%")
                            ->orWhere('email', 'like', "%{$borrowingSearch}%");
                    })
                    ->orWhereHas('book', function ($bookQuery) use ($borrowingSearch): void {
                        $bookQuery
                            ->where('title', 'like', "%{$borrowingSearch}%")
                            ->orWhere('author', 'like', "%{$borrowingSearch}%")
                            ->orWhere('isbn', 'like', "%{$borrowingSearch}%");
                    });
            });
        }

        // 每页固定五笔借阅记录，和其他资料列表保持一致。
        $borrowings = $borrowingsQuery
            ->paginate(5)
            ->withQueryString();

        $availableBooks = $user->isStudent()
            ? Book::query()
                ->borrowable()
                ->orderBy('title')
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
            'hasUnresolvedOverdue' => $hasUnresolvedOverdue,
            'borrowingSearch' => $borrowingSearch,
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
            throw new AuthorizationException;
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
                    'user_id' => $request->user()
                        ?->getAuthIdentifier(),

                    'reason' => $exception->getMessage(),
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

                    'user_id' => $request->user()
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

    public function updateCopyQuantity(
        Request $request,
        Book $book,
        BorrowingService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        if (! $user->isLibrarian()) {
            throw new AuthorizationException(
                'Only librarians may manage book quantities.'
            );
        }

        $validated = $request->validate([
            'total_copies' => [
                'required',
                'integer',
                'min:0',
                'max:10000',
            ],
        ]);

        $newTotal = (int) $validated['total_copies'];

        return $this->performAction(
            $request,
            fn () => $service->updateCopyQuantity(
                $user,
                $book,
                $newTotal
            ),
            'Book copy quantity updated successfully.'
        );
    }
}
