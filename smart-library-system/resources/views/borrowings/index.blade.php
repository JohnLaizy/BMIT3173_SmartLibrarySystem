<x-layouts::app :title="__('Borrow & Return')">
    <div
        data-page-transition
        class="mx-auto flex w-full max-w-7xl flex-col gap-7 px-2 sm:px-4"
    >
        <header>
            <div
                class="mb-3 inline-flex items-center gap-2 rounded-full
                       border border-violet-500/20 bg-violet-500/10 px-3 py-1
                       text-xs font-bold uppercase tracking-wider
                       text-violet-700 dark:text-violet-300"
            >
                <span class="size-2 rounded-full bg-violet-500" aria-hidden="true"></span>
                Circulation desk
            </div>

            <flux:heading size="xl" level="1">
                Borrow & Return
            </flux:heading>

            <flux:text class="mt-2 max-w-2xl">
                Borrow books, return copies and manage overdue payments.
            </flux:text>
        </header>

        @if (session('success'))
            <div
                role="status"
                class="rounded-lg border border-green-200 bg-green-50
                       px-4 py-3 text-sm text-green-800
                       dark:border-green-800 dark:bg-green-950
                       dark:text-green-200"
            >
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div
                role="alert"
                class="rounded-lg border border-red-200 bg-red-50
                       px-4 py-3 text-sm text-red-800
                       dark:border-red-800 dark:bg-red-950
                       dark:text-red-200"
            >
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                role="alert"
                class="rounded-lg border border-red-200 bg-red-50
                       px-4 py-3 text-sm text-red-800"
            >
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (auth()->user()->isLibrarian() && $managedBooks)
            <section
                class="mt-1 rounded-2xl border border-zinc-200 bg-white p-5
                       shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
            >
                <div class="mb-4">
                    <flux:heading size="lg">
                        {{ __('Manage Book Copies') }}
                    </flux:heading>

                    <flux:text class="mt-1">
                        {{ __('Change the total number of copies available in the library.') }}
                    </flux:text>
                </div>

                @if (session('success'))
                    <flux:callout
                        variant="success"
                        icon="check-circle"
                        class="mb-4"
                    >
                        {{ session('success') }}
                    </flux:callout>
                @endif

                @if (session('error'))
                    <flux:callout
                        variant="danger"
                        icon="x-circle"
                        class="mb-4"
                    >
                        {{ session('error') }}
                    </flux:callout>
                @endif

                @error('total_copies')
                    <flux:callout
                        variant="danger"
                        icon="x-circle"
                        class="mb-4"
                    >
                        {{ $message }}
                    </flux:callout>
                @enderror

                <div
                    class="overflow-hidden rounded-xl border border-zinc-200
                           bg-zinc-950 text-zinc-100 dark:border-zinc-700"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-zinc-900 text-zinc-200">
                                <tr>
                                    <th class="px-4 py-3">
                                        {{ __('Book') }}
                                    </th>

                                    <th class="px-4 py-3">
                                        {{ __('ISBN') }}
                                    </th>

                                    <th class="px-4 py-3 text-center">
                                        {{ __('Borrowed') }}
                                    </th>

                                    <th class="px-4 py-3 text-center">
                                        {{ __('Available') }}
                                    </th>

                                    <th class="px-4 py-3">
                                        {{ __('Total Copies') }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-zinc-800 bg-zinc-950">
                            @forelse ($managedBooks as $book)
                                    <tr class="transition-colors hover:bg-zinc-900/80">
                                        <td class="px-4 py-3">
                                            <div class="font-medium">
                                                {{ $book->title }}
                                            </div>

                                            <div class="text-xs text-zinc-500">
                                                {{ $book->author }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3">
                                            {{ $book->isbn }}
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            {{ $book->active_borrowings_count }}
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            {{ $book->available_copies }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'books.copies.update',
                                                    $book
                                                ) }}"
                                                class="flex items-end gap-2"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <div class="w-28">
                                                    <label
                                                        for="total-copies-{{ $book->id }}"
                                                        class="sr-only"
                                                    >
                                                        {{ __('Total copies') }}
                                                    </label>

                                                    <input
                                                        id="total-copies-{{ $book->id }}"
                                                        name="total_copies"
                                                        type="number"
                                                        min="{{ $book->active_borrowings_count }}"
                                                        max="10000"
                                                        value="{{ $book->total_copies }}"
                                                        required
                                                        class="w-full rounded-lg border border-zinc-600 bg-zinc-800 px-3 py-2 text-sm text-white"
                                                    >
                                                </div>

                                                <flux:button
                                                    type="submit"
                                                    variant="primary"
                                                    size="sm"
                                                >
                                                    {{ __('Update') }}
                                                </flux:button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="5"
                                            class="px-4 py-8 text-center
                                                text-zinc-500"
                                        >
                                            {{ __('No books found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    {{ $managedBooks->withQueryString()->links() }}
                </div>
            </section>
        @endif

        @if (auth()->user()->isStudent())
            @php
                $borrowLimit = (int) config(
                    'library.borrow_limit',
                    5
                );

                $canBorrow =
                    ! $hasUnresolvedOverdue
                    && $activeCopyCount < $borrowLimit;
            @endphp

            <section
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm
                   dark:border-zinc-700 dark:bg-slate-900"
            >
                <div class="flex flex-col justify-between gap-4 sm:flex-row">
                    <div>
                        <flux:heading>Borrow a book</flux:heading>

                        <flux:text class="mt-1">
                            Active copies:
                            {{ $activeCopyCount }}/{{ $borrowLimit }}
                        </flux:text>
                    </div>

                    @if ($hasUnresolvedOverdue)
                        <span
                            class="rounded-lg bg-red-100 px-3 py-2
                                   text-sm font-medium text-red-800"
                        >
                            Borrowing blocked: resolve overdue fees first
                        </span>
                    @endif
                </div>

                @if ($canBorrow)
                    <form
                        method="POST"
                        action="{{ route('borrowings.store') }}"
                        class="mt-5 flex flex-col gap-3 sm:flex-row"
                    >
                        @csrf

                        <label class="sr-only" for="book_id">
                            Select a book
                        </label>

                        <select
                            id="book_id"
                            name="book_id"
                            required
                            class="min-h-10 flex-1 rounded-lg border
                                   border-zinc-300 bg-white px-3 py-2
                                   text-sm dark:border-zinc-600
                                   dark:bg-zinc-800"
                        >
                            <option value="">Select a book</option>

                            @foreach ($availableBooks as $book)
                                <option
                                    value="{{ $book->id }}"
                                    @selected(
                                        old('book_id') == $book->id
                                    )
                                >
                                    {{ $book->title }}
                                    — {{ $book->author }}
                                    ({{ $book->available_copies }} available)
                                </option>
                            @endforeach
                        </select>

                        <flux:button
                            type="submit"
                            variant="primary"
                        >
                            Borrow for 7 days
                        </flux:button>
                    </form>

                    @if ($availableBooks->isEmpty())
                        <p class="mt-4 text-sm text-zinc-500">
                            No book copies are currently available.
                        </p>
                    @endif
                @elseif (! $hasUnresolvedOverdue)
                    <p class="mt-4 text-sm text-amber-700">
                        You have reached the five-copy borrowing limit.
                    </p>
                @endif
            </section>
        @endif

        <section
            class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950 text-zinc-100 shadow-sm"
        >
            <div class="border-b border-zinc-800 bg-zinc-900 p-5">
                <flux:heading>
                    {{ auth()->user()->isLibrarian()
                        ? 'All borrowing records'
                        : 'My borrowing records' }}
                </flux:heading>
            </div>

            @if ($borrowings->isEmpty())
                <div class="p-10 text-center text-sm text-zinc-500">
                    No borrowing records found.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="border-b border-zinc-800 bg-zinc-900 text-zinc-200"
                        >
                            <tr>
                                @if (auth()->user()->isLibrarian())
                                    <th class="px-4 py-3">Student</th>
                                @endif

                                <th class="px-4 py-3">Book</th>
                                <th class="px-4 py-3">Borrowed</th>
                                <th class="px-4 py-3">Due</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Fee</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-800 bg-zinc-950">
                            @foreach ($borrowings as $borrowing)
                                @php
                                    $statusLabel = match (
                                        $borrowing->status
                                    ) {
                                        \App\Models\Borrowing::STATUS_BORROWED =>
                                            'Borrowed',

                                        \App\Models\Borrowing::STATUS_OVERDUE =>
                                            'Overdue',

                                        \App\Models\Borrowing::STATUS_FEE_UNPAID =>
                                            'Fee Unpaid',

                                        \App\Models\Borrowing::STATUS_PAYMENT_PENDING =>
                                            'Payment Pending',

                                        \App\Models\Borrowing::STATUS_COMPLETED =>
                                            'Completed',

                                        default => 'Unknown',
                                    };

                                    $statusClasses = match (
                                        $borrowing->status
                                    ) {
                                        \App\Models\Borrowing::STATUS_BORROWED =>
                                            'bg-blue-100 text-blue-800',

                                        \App\Models\Borrowing::STATUS_OVERDUE,
                                        \App\Models\Borrowing::STATUS_FEE_UNPAID =>
                                            'bg-red-100 text-red-800',

                                        \App\Models\Borrowing::STATUS_PAYMENT_PENDING =>
                                            'bg-amber-100 text-amber-800',

                                        \App\Models\Borrowing::STATUS_COMPLETED =>
                                            'bg-green-100 text-green-800',

                                        default =>
                                            'bg-zinc-100 text-zinc-800',
                                    };
                                @endphp

                                <tr class="transition-colors hover:bg-zinc-900/80">
                                    @if (auth()->user()->isLibrarian())
                                        <td class="px-4 py-4">
                                            {{ $borrowing->student->name }}
                                        </td>
                                    @endif

                                    <td class="px-4 py-4">
                                        <div class="font-medium">
                                            {{ $borrowing->book->title }}
                                        </div>

                                        <div class="text-xs text-zinc-500">
                                            {{ $borrowing->book->author }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4">
                                        {{ $borrowing->borrowed_at?->format(
                                            'd M Y'
                                        ) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4">
                                        {{ $borrowing->due_at?->format(
                                            'd M Y'
                                        ) }}
                                    </td>

                                    <td class="px-4 py-4">
                                        <span
                                            class="whitespace-nowrap rounded-full
                                                   px-2.5 py-1 text-xs font-medium
                                                   {{ $statusClasses }}"
                                        >
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4">
                                        RM {{ number_format(
                                            $borrowing->overdue_fee_cents / 100,
                                            2
                                        ) }}
                                    </td>

                                    <td class="min-w-56 px-4 py-4">
                                        <div class="flex flex-col gap-2">
                                            @if ($borrowing->returned_at === null)
                                                @can('returnCopy', $borrowing)
                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'borrowings.return',
                                                            $borrowing
                                                        ) }}"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <flux:button
                                                            type="submit"
                                                            size="sm"
                                                        >
                                                            Return book
                                                        </flux:button>
                                                    </form>
                                                @endcan
                                            @endif

                                            @if (
                                                $borrowing->status ===
                                                \App\Models\Borrowing::STATUS_FEE_UNPAID
                                            )
                                                @can('submitPayment', $borrowing)
                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'borrowings.payment.submit',
                                                            $borrowing
                                                        ) }}"
                                                        class="flex flex-col gap-2"
                                                    >
                                                        @csrf

                                                        <input
                                                            type="text"
                                                            name="payment_reference"
                                                            required
                                                            minlength="6"
                                                            maxlength="100"
                                                            pattern="[A-Za-z0-9][A-Za-z0-9 _-]*"
                                                            placeholder="Payment reference"
                                                            class="rounded-lg border
                                                                   border-zinc-300
                                                                   px-3 py-2 text-sm"
                                                        >

                                                        <flux:button
                                                            type="submit"
                                                            size="sm"
                                                            variant="primary"
                                                        >
                                                            Submit payment
                                                        </flux:button>
                                                    </form>
                                                @endcan
                                            @endif

                                            @if (
                                                $borrowing->status ===
                                                \App\Models\Borrowing::STATUS_PAYMENT_PENDING
                                            )
                                                @can('approvePayment', $borrowing)
                                                    <div class="flex gap-2">
                                                        <form
                                                            method="POST"
                                                            action="{{ route(
                                                                'borrowings.payment.approve',
                                                                $borrowing
                                                            ) }}"
                                                        >
                                                            @csrf
                                                            @method('PATCH')

                                                            <flux:button
                                                                type="submit"
                                                                size="sm"
                                                                variant="primary"
                                                            >
                                                                Approve
                                                            </flux:button>
                                                        </form>

                                                        <form
                                                            method="POST"
                                                            action="{{ route(
                                                                'borrowings.payment.reject',
                                                                $borrowing
                                                            ) }}"
                                                        >
                                                            @csrf
                                                            @method('PATCH')

                                                            <flux:button
                                                                type="submit"
                                                                size="sm"
                                                                variant="danger"
                                                            >
                                                                Reject
                                                            </flux:button>
                                                        </form>
                                                    </div>

                                                    <p class="text-xs text-zinc-500">
                                                        Reference:
                                                        {{ $borrowing->payment_reference }}
                                                    </p>
                                                @endcan

                                                @can('submitPayment', $borrowing)
                                                    <span class="text-xs text-amber-700">
                                                        Awaiting librarian approval
                                                    </span>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                    {{ $borrowings->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts::app>
