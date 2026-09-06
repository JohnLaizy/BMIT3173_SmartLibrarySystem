<?php

namespace App\Http\Controllers;

use App\Exceptions\BookManagementApiException;
use App\Exceptions\BorrowingRuleViolation;
use App\Http\Requests\BorrowBookRequest;
use App\Http\Requests\RejectBorrowingRenewalRequest;
use App\Http\Requests\SubmitOverduePaymentRequest;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Services\BookManagementApiClient;
use App\Services\BorrowingService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
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
                'renewalReviewer',
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

    public function requestRenewal(
        Request $request,
        Borrowing $borrowing,
        BorrowingService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'requestRenewal',
            $borrowing
        );

        return $this->performAction(
            $request,
            fn () => $service->requestRenewal(
                $user,
                $borrowing
            ),
            'Extension request submitted.'
        );
    }

    public function approveRenewal(
        Request $request,
        Borrowing $borrowing,
        BorrowingService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'approveRenewal',
            $borrowing
        );

        return $this->performAction(
            $request,
            fn () => $service->approveRenewal(
                $user,
                $borrowing
            ),
            'Extension request approved.'
        );
    }

    public function rejectRenewal(
        RejectBorrowingRenewalRequest $request,
        Borrowing $borrowing,
        BorrowingService $service
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'rejectRenewal',
            $borrowing
        );

        $validated = $request->validated();

        return $this->performAction(
            $request,
            fn () => $service->rejectRenewal(
                $user,
                $borrowing,
                $validated['renewal_rejection_reason']
            ),
            'Extension request rejected.'
        );
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'viewAny',
            Borrowing::class
        );

        $perPage = min(
            max($request->integer('per_page', 15), 1),
            50
        );

        $query = Borrowing::query()
            ->with('book')
            ->latest('borrowed_at');

        if ($user->isStudent()) {
            $query->where('user_id', $user->id);
        }

        $borrowings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $borrowings
                ->getCollection()
                ->map(
                    fn (Borrowing $borrowing): array => $this->borrowingPayload($borrowing)
                )
                ->values(),
            'meta' => [
                'current_page' => $borrowings->currentPage(),
                'last_page' => $borrowings->lastPage(),
                'per_page' => $borrowings->perPage(),
                'total' => $borrowings->total(),
            ],
        ]);
    }

    public function apiStore(
        BorrowBookRequest $request,
        BorrowingService $service
    ): JsonResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'create',
            Borrowing::class
        );

        $validated = $request->validated();

        $book = Book::query()->findOrFail(
            (int) $validated['book_id']
        );

        try {
            $borrowing = $service->borrow($user, $book);

            return response()->json([
                'success' => true,
                'message' => 'Book borrowed successfully.',
                'data' => $this->borrowingPayload(
                    $borrowing->load('book')
                ),
            ], 201);
        } catch (BorrowingRuleViolation $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function apiReturn(
        Request $request,
        Borrowing $borrowing,
        BorrowingService $service
    ): JsonResponse {
        $user = $this->authenticatedUser($request);

        Gate::authorize(
            'returnCopy',
            $borrowing
        );

        try {
            $returnedBorrowing = $service->returnCopy(
                $user,
                $borrowing
            );

            return response()->json([
                'success' => true,
                'message' => 'Book returned successfully.',
                'data' => $this->borrowingPayload(
                    $returnedBorrowing->load('book')
                ),
            ]);
        } catch (BorrowingRuleViolation $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function apiBookCatalog(
        Request $request,
        BookManagementApiClient $bookManagement
    ): JsonResponse {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $bookManagement->searchBooks(
                    trim(
                        (string) ($validated['search'] ?? '')
                    )
                ),
            ]);
        } catch (BookManagementApiException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->status);
        }
    }

    private function borrowingPayload(
        Borrowing $borrowing
    ): array {
        return [
            'id' => $borrowing->id,
            'book_id' => $borrowing->book_id,
            'status' => $borrowing->status,
            'borrowed_at' => $borrowing->borrowed_at
                ?->toIso8601String(),
            'due_at' => $borrowing->due_at
                ?->toIso8601String(),
            'returned_at' => $borrowing->returned_at
                ?->toIso8601String(),
            'overdue_fee_cents' => $borrowing->overdue_fee_cents,
            'book' => [
                'id' => $borrowing->book?->id,
                'title' => $borrowing->book?->title,
                'author' => $borrowing->book?->author,
                'isbn' => $borrowing->book?->isbn,
            ],
        ];
    }

    public function getActiveCounts(Request $request)
    {
        $bookIdsWereRequested = $request->has('book_ids');

        $bookIds = collect(explode(',', (string) $request->query('book_ids', '')))
            ->filter(
                fn (string $id): bool => ctype_digit($id)
                    && (int) $id > 0
            )
            ->map(fn (string $id): int => (int) $id)
            ->unique()
            ->values();

        $counts = Borrowing::whereNull('returned_at')
            ->when(
                $bookIdsWereRequested,
                fn ($query) => $query->whereIn('book_id', $bookIds->all())
            )
            ->selectRaw('book_id, count(*) as active_count')
            ->groupBy('book_id')
            ->pluck('active_count', 'book_id');

        return response()->json([
            'success' => true,
            'data' => $counts,
        ]);
    }
}
