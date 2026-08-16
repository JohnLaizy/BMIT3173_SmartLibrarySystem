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
                    @php
                        $availableBookOptions = $availableBooks
                            ->map(fn ($book) => [
                                'id' => $book->id,
                                'title' => $book->title,
                                'author' => $book->author,
                                'isbn' => $book->isbn,
                                'availableCopies' => $book->available_copies,
                            ])
                            ->values();

                        $selectedBook = $availableBooks->firstWhere(
                            'id',
                            (int) old('book_id')
                        );
                    @endphp

                    <form
                        method="POST"
                        action="{{ route('borrowings.store') }}"
                        data-borrow-form
                        class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-start"
                    >
                        @csrf

                        <div
                            data-book-combobox
                            class="relative min-w-0 flex-1"
                        >
                            <label
                                for="book_search"
                                class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white"
                            >
                                Search available books
                            </label>

                            <input
                                id="book_search"
                                data-book-search
                                type="search"
                                autocomplete="off"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-controls="available-book-results"
                                aria-expanded="false"
                                placeholder="Search by title, author or ISBN"
                                value="{{ $selectedBook ? $selectedBook->title.' — '.$selectedBook->author : '' }}"
                                class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition placeholder:text-zinc-500 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-400"
                            >

                            <input
                                data-book-id
                                type="hidden"
                                name="book_id"
                                value="{{ old('book_id') }}"
                            >

                            <div
                                id="available-book-results"
                                data-book-results
                                role="listbox"
                                hidden
                                class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                            ></div>

                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                Search results only include books with at least one available copy.
                            </p>

                            <script type="application/json" data-book-options>
                                @json($availableBookOptions)
                            </script>
                        </div>

                        <flux:button
                            type="submit"
                            variant="primary"
                            data-borrow-submit
                            :disabled="$selectedBook === null"
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
                <div class="flex min-h-28 flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <flux:heading size="lg">
                            {{ auth()->user()->isLibrarian()
                                ? 'All borrowing records'
                                : 'My borrowing records' }}
                        </flux:heading>

                        <flux:text class="mt-1">
                            {{ auth()->user()->isLibrarian()
                                ? 'Search, monitor and process current borrowing records.'
                                : 'Review your current and previous borrowing records.' }}
                        </flux:text>
                    </div>

                    @if (auth()->user()->isLibrarian())
                        <form
                            method="GET"
                            action="{{ route('borrowings.index') }}"
                            data-auto-search-form
                            class="flex w-full flex-col gap-2 sm:flex-row sm:items-center lg:w-[485px]"
                        >
                            <label class="sr-only" for="borrowing-search">
                                {{ __('Search borrowing records') }}
                            </label>

                            <div class="relative min-w-0 flex-1">
                                <svg
                                    class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <circle cx="11" cy="11" r="6" />
                                    <path d="m16 16 4 4" />
                                </svg>

                                <input
                                    id="borrowing-search"
                                    data-auto-search-input
                                    type="search"
                                    name="borrowing_search"
                                    value="{{ $borrowingSearch }}"
                                    placeholder="Search student, book or ISBN"
                                    autocomplete="off"
                                    class="min-h-11 w-full rounded-xl border border-zinc-700 bg-zinc-800 py-2 pe-3 ps-10 text-sm text-white shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-400/20"
                                >
                            </div>

                            <button
                                type="button"
                                data-borrowing-search-clear
                                @class([
                                    'hidden' => $borrowingSearch === '',
                                    'inline-flex min-h-11 items-center justify-center rounded-xl px-3 text-sm font-semibold text-zinc-300 transition hover:bg-zinc-800 hover:text-white',
                                ])
                            >
                                Clear
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- 第二层卡片使借阅记录在 Borrow & Return 页面保持清晰分组。 --}}
            <div class="p-5" data-live-borrowing-results>
                <div
                    class="overflow-hidden rounded-xl border border-zinc-800
                           bg-zinc-950 text-zinc-100"
                >
                    @if ($borrowings->isEmpty())
                        <div class="p-10 text-center text-sm text-zinc-500">
                            No borrowing records found.
                        </div>
                    @else
                        {{-- 清单固定保留五个 record row 的高度，footer 固定在卡片底部。 --}}
                        <div class="min-h-[33rem] overflow-x-auto">
                            <table class="min-w-[980px] w-full table-fixed text-left text-sm">
                        {{-- 记录表同样使用稳定栏宽，和上方 Book Copies table 的视觉节奏一致。 --}}
                        @if (auth()->user()->isLibrarian())
                            <colgroup>
                                <col class="w-[11%]">
                                <col class="w-[20%]">
                                <col class="w-[14%]">
                                <col class="w-[14%]">
                                <col class="w-[13%]">
                                <col class="w-[10%]">
                                <col class="w-[18%]">
                            </colgroup>
                        @else
                            <colgroup>
                                <col class="w-[25%]">
                                <col class="w-[16%]">
                                <col class="w-[16%]">
                                <col class="w-[16%]">
                                <col class="w-[11%]">
                                <col class="w-[16%]">
                            </colgroup>
                        @endif

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
                                            'border border-blue-400/25 bg-blue-500/15 text-blue-700 dark:text-blue-300',

                                        \App\Models\Borrowing::STATUS_OVERDUE,
                                        \App\Models\Borrowing::STATUS_FEE_UNPAID =>
                                            'border border-red-400/25 bg-red-500/15 text-red-700 dark:text-red-300',

                                        \App\Models\Borrowing::STATUS_PAYMENT_PENDING =>
                                            'border border-amber-400/25 bg-amber-500/15 text-amber-700 dark:text-amber-300',

                                        \App\Models\Borrowing::STATUS_COMPLETED =>
                                            'border border-emerald-400/25 bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',

                                        default =>
                                            'border border-zinc-400/25 bg-zinc-500/15 text-zinc-700 dark:text-zinc-300',
                                    };
                                @endphp

                                <tr class="h-24 transition-colors hover:bg-zinc-900/80">
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

                                                        <button
                                                            type="submit"
                                                            class="inline-flex min-h-10 items-center justify-center rounded-lg border border-violet-400/25
                                                                   bg-violet-500/15 px-4 text-sm font-semibold text-violet-700 transition
                                                                   hover:bg-violet-500/25 focus:outline-none focus:ring-2 focus:ring-violet-400/40
                                                                   dark:text-violet-200"
                                                        >
                                                            Return book
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif

                                            @if (
                                                $borrowing->status ===
                                                \App\Models\Borrowing::STATUS_FEE_UNPAID
                                            )
                                                @can('submitPayment', $borrowing)
                                                    <flux:button
                                                        :href="route('payments.show', $borrowing)"
                                                        size="sm"
                                                        variant="primary"
                                                        wire:navigate
                                                    >
                                                        Pay overdue fee
                                                    </flux:button>
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
                                                    <flux:button
                                                        :href="route('payments.show', $borrowing)"
                                                        size="sm"
                                                        variant="filled"
                                                        wire:navigate
                                                    >
                                                        View payment
                                                    </flux:button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                            </table>

                            @if ($borrowings->count() < 5)
                                <div class="border-t border-zinc-800"></div>
                            @endif
                        </div>

                        <x-listing-pagination
                            :paginator="$borrowings"
                            aria-label="Borrowing pagination"
                            :dark-card="true"
                        />
                    @endif
                </div>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const initializeBookSearch = (combobox) => {
                if (combobox.dataset.initialized === 'true') {
                    return;
                }

                combobox.dataset.initialized = 'true';

                const form = combobox.closest('[data-borrow-form]');
                const searchInput = combobox.querySelector('[data-book-search]');
                const bookIdInput = combobox.querySelector('[data-book-id]');
                const results = combobox.querySelector('[data-book-results]');
                const optionsElement = combobox.querySelector('[data-book-options]');
                const submitButton = form?.querySelector('[data-borrow-submit]');

                if (!form || !searchInput || !bookIdInput || !results || !optionsElement || !submitButton) {
                    return;
                }

                let books = [];
                let matchingBooks = [];

                try {
                    books = JSON.parse(optionsElement.textContent);
                } catch (error) {
                    console.error('Unable to load available book search options.', error);
                    return;
                }

                const hideResults = () => {
                    results.hidden = true;
                    searchInput.setAttribute('aria-expanded', 'false');
                };

                const chooseBook = (book) => {
                    searchInput.value = `${book.title} — ${book.author} (${book.availableCopies} available)`;
                    bookIdInput.value = String(book.id);
                    searchInput.setCustomValidity('');
                    submitButton.disabled = false;
                    hideResults();
                };

                const renderResults = () => {
                    results.replaceChildren();

                    if (matchingBooks.length === 0) {
                        const emptyMessage = document.createElement('p');
                        emptyMessage.className = 'px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400';
                        emptyMessage.textContent = 'No available books match this search.';
                        results.append(emptyMessage);
                    } else {
                        matchingBooks.forEach((book) => {
                            const option = document.createElement('button');
                            const title = document.createElement('span');
                            const detail = document.createElement('span');

                            option.type = 'button';
                            option.setAttribute('role', 'option');
                            option.className = 'flex w-full flex-col rounded-lg px-3 py-2 text-left transition hover:bg-violet-500/10 focus:bg-violet-500/10 focus:outline-none';

                            title.className = 'font-semibold text-zinc-900 dark:text-white';
                            title.textContent = book.title;

                            detail.className = 'mt-0.5 text-xs text-zinc-500 dark:text-zinc-400';
                            detail.textContent = `${book.author} · ISBN ${book.isbn} · ${book.availableCopies} available`;

                            option.append(title, detail);
                            option.addEventListener('click', () => chooseBook(book));
                            results.append(option);
                        });
                    }

                    results.hidden = false;
                    searchInput.setAttribute('aria-expanded', 'true');
                };

                const searchBooks = () => {
                    const searchTerm = searchInput.value.trim().toLocaleLowerCase();

                    matchingBooks = books
                        .filter((book) => {
                            if (searchTerm === '') {
                                return true;
                            }

                            return [book.title, book.author, book.isbn]
                                .join(' ')
                                .toLocaleLowerCase()
                                .includes(searchTerm);
                        })
                        .slice(0, 8);

                    renderResults();
                };

                searchInput.addEventListener('focus', searchBooks);

                searchInput.addEventListener('input', () => {
                    bookIdInput.value = '';
                    submitButton.disabled = true;
                    searchInput.setCustomValidity('Please select a book from the search results.');
                    searchBooks();
                });

                searchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        hideResults();
                        return;
                    }

                    if (event.key === 'Enter' && matchingBooks[0]) {
                        event.preventDefault();
                        chooseBook(matchingBooks[0]);
                    }
                });

                form.addEventListener('submit', (event) => {
                    if (bookIdInput.value !== '') {
                        return;
                    }

                    event.preventDefault();
                    searchInput.setCustomValidity('Please select a book from the search results.');
                    searchInput.reportValidity();
                    searchInput.focus();
                });

                document.addEventListener('click', (event) => {
                    if (!combobox.contains(event.target)) {
                        hideResults();
                    }
                });
            };

            const initializeAllBookSearches = () => {
                document
                    .querySelectorAll('[data-book-combobox]')
                    .forEach(initializeBookSearch);
            };

            const initializeAutoSearch = (form) => {
                if (form.dataset.initialized === 'true') {
                    return;
                }

                const input = form.querySelector('[data-auto-search-input]');

                if (!input) {
                    return;
                }

                form.dataset.initialized = 'true';

                let submitTimer;
                let activeRequest;

                const searchUrlFor = () => {
                    const url = new URL(form.action, window.location.origin);
                    const search = input.value.trim();

                    if (search !== '') {
                        url.searchParams.set('borrowing_search', search);
                    }

                    return url;
                };

                const updateClearButton = () => {
                    const clearButton = form.querySelector('[data-borrowing-search-clear]');

                    if (clearButton instanceof HTMLElement) {
                        clearButton.classList.toggle('hidden', input.value.trim() === '');
                    }
                };

                const updateBorrowingResults = async () => {
                    const url = searchUrlFor();
                    const currentResults = document.querySelector('[data-live-borrowing-results]');

                    if (!(currentResults instanceof HTMLElement)) {
                        window.location.assign(url);
                        return;
                    }

                    if (activeRequest) {
                        activeRequest.abort();
                    }

                    activeRequest = new AbortController();
                    currentResults.setAttribute('aria-busy', 'true');

                    try {
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            signal: activeRequest.signal,
                        });

                        if (!response.ok) {
                            throw new Error('Unable to update borrowing records.');
                        }

                        const documentResponse = new DOMParser().parseFromString(
                            await response.text(),
                            'text/html'
                        );
                        const nextResults = documentResponse.querySelector('[data-live-borrowing-results]');

                        if (!(nextResults instanceof HTMLElement)) {
                            throw new Error('Borrowing results were not found in the response.');
                        }

                        currentResults.replaceWith(nextResults);
                        window.history.replaceState({}, '', url);
                        updateClearButton();
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            window.location.assign(url);
                        }
                    } finally {
                        currentResults.removeAttribute('aria-busy');
                    }
                };

                input.addEventListener('input', () => {
                    window.clearTimeout(submitTimer);
                    submitTimer = window.setTimeout(updateBorrowingResults, 300);
                });

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    window.clearTimeout(submitTimer);
                    updateBorrowingResults();
                });

                form.querySelector('[data-borrowing-search-clear]')?.addEventListener('click', () => {
                    input.value = '';
                    updateBorrowingResults();
                    input.focus();
                });
            };

            const initializeAllAutoSearches = () => {
                document
                    .querySelectorAll('[data-auto-search-form]')
                    .forEach(initializeAutoSearch);
            };

            const initializeBorrowingPage = () => {
                initializeAllBookSearches();
                initializeAllAutoSearches();
            };

            if (!window.__smartLibraryBookSearchBound) {
                window.__smartLibraryBookSearchBound = true;
                document.addEventListener('livewire:navigated', initializeBorrowingPage);
            }

            initializeBorrowingPage();
        })();
    </script>
</x-layouts::app>
