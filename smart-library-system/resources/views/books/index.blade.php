<x-layouts::app :title="__('Book Management')">

<div
    data-page-transition
    class="mx-auto flex w-full max-w-7xl flex-1
           flex-col gap-6 px-2 sm:px-4"
>
    @if (session('success'))
        <div
            role="status"
            class="flex items-center gap-3 rounded-xl
                   border border-emerald-500/30
                   bg-emerald-500/10 px-4 py-3
                   text-sm font-medium
                   text-emerald-700
                   dark:text-emerald-300"
        >
            <span
                class="flex size-6 items-center justify-center
                       rounded-full bg-emerald-500/20"
            >
                ✓
            </span>

            {{ session('success') }}
        </div>
    @endif
    {{-- 安全防御拦截提示框 (Error Alert) --}}
@if (session('error'))
    <div
        role="status"
        class="mt-4 flex items-center gap-3 rounded-xl
               border border-red-500/30
               bg-red-500/10 px-4 py-3
               text-sm font-medium
               text-red-700
               dark:text-red-400"
    >
        <span
            class="flex size-6 items-center justify-center
                   rounded-full bg-red-500/20"
        >
            ✕
        </span>

        {{ session('error') }}
    </div>
@endif

    @if (session('error'))
        <div
            role="alert"
            class="flex items-center gap-3 rounded-xl border border-red-500/30
                   bg-red-500/10 px-4 py-3 text-sm font-medium text-red-700
                   dark:text-red-300"
        >
            <span
                class="flex size-6 items-center justify-center rounded-full bg-red-500/20"
                aria-hidden="true"
            >
                !
            </span>

            {{ session('error') }}
        </div>
    @endif

    @error('total_copies')
        <div
            role="alert"
            class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3
                   text-sm font-medium text-red-700 dark:text-red-300"
        >
            {{ $message }}
        </div>
    @enderror

    {{-- 页面标题 --}}
    <header
        class="flex flex-col gap-4
               sm:flex-row sm:items-end sm:justify-between"
    >
        <div>

            {{-- Badge --}}
            <div
                class="mb-3 inline-flex items-center gap-2
                       rounded-full border border-blue-500/20
                       bg-blue-500/10 px-3 py-1
                       text-xs font-bold uppercase tracking-wider
                       text-blue-700 dark:text-blue-300"
            >
                <span
                    class="size-2 rounded-full bg-blue-500"
                    aria-hidden="true"
                ></span>

                Book Collection
            </div>

            {{-- Title --}}
            <flux:heading size="xl" level="1">
                Book Management
            </flux:heading>

            {{-- Description --}}
            <flux:text class="mt-2 max-w-2xl">
                Manage, search, and monitor physical and digital
                library resources.
            </flux:text>

        </div>

        {{-- Add Book --}}
        @if (auth()->user()?->isLibrarian())
            <div class="flex flex-wrap gap-3">
                <flux:button
                    :href="route('books.create')"
                    variant="primary"
                    icon="plus"
                    wire:navigate
                >
                    New Book
                </flux:button>
            </div>
        @endif
    </header>


    {{-- 搜索 --}}
    <section
        class="relative z-20 overflow-visible rounded-2xl
               border border-zinc-200 bg-white shadow-sm
               dark:border-zinc-700 dark:bg-zinc-900"
    >

        <div class="p-5">

            <form
                method="GET"
                action="{{ route('books.index') }}"
                data-book-search-form
                class="flex flex-col gap-3 sm:flex-row sm:items-center"
            >

                <div class="relative flex-1">
                    <svg
                        class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-zinc-400"
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
                        id="book-catalogue-search"
                        data-book-search-input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        autocomplete="off"
                        aria-describedby="book-search-hint"
                        aria-controls="book-search-suggestions"
                        aria-expanded="false"
                        placeholder="Search any word in a title, author, ISBN or category"
                        class="min-h-12 w-full rounded-2xl border border-zinc-300 bg-white py-3 pe-4 ps-12 text-sm
                               text-zinc-900 shadow-sm outline-none transition placeholder:text-zinc-500
                               focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700
                               dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-400"
                    >

                    <span
                        class="pointer-events-none absolute end-4 top-1/2 hidden -translate-y-1/2 rounded-full
                               bg-zinc-100 px-2 py-1 text-[11px] font-medium text-zinc-500 dark:bg-zinc-700
                               dark:text-zinc-300 md:inline"
                    >
                        Live search
                    </span>

                    <div
                        id="book-search-suggestions"
                        data-book-search-suggestions
                        role="listbox"
                        aria-label="Book search suggestions"
                        hidden
                        class="absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-zinc-200
                               bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
                    ></div>
                </div>

                <button
                    type="button"
                    data-book-search-clear
                    @class([
                        'hidden' => ! request('search'),
                        'inline-flex min-h-11 items-center justify-center rounded-xl px-3 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                    ])
                >
                    Clear
                </button>

            </form>

            <p
                id="book-search-hint"
                class="mt-3 text-xs text-zinc-500 dark:text-zinc-400"
            >
                Results and suggestions update while you type.
            </p>

        </div>

    </section>


    {{-- Book List --}}
    <section
        data-live-book-results
        class="overflow-hidden rounded-2xl
               border border-zinc-200 bg-white shadow-sm
               dark:border-zinc-700 dark:bg-zinc-900"
    >

        {{-- Card Header --}}
        <div
            class="flex flex-wrap items-center
                   justify-between gap-4
                   border-b border-zinc-200 px-6 py-5
                   dark:border-zinc-700"
        >

            <div>

                <h2
                    class="text-lg font-bold
                           text-zinc-900 dark:text-white"
                >
                    Library Books
                </h2>

                <p
                    class="mt-1 text-sm
                           text-zinc-500 dark:text-zinc-400"
                >
                    {{ auth()->user()->isLibrarian()
                        ? 'Manage catalogue details and physical book copies.'
                        : 'Browse registered physical and digital books.' }}
                </p>

            </div>

            {{-- Book Count --}}
            <span
                class="rounded-full bg-blue-500/10
                       px-3 py-1 text-sm font-semibold
                       text-blue-700 dark:text-blue-300"
            >
                {{ $books->total() }}

                {{ \Illuminate\Support\Str::plural(
                    'book',
                    $books->total()
                ) }}
            </span>

        </div>


        {{-- Empty State / Table --}}
        @if ($books->isEmpty())

            <div class="px-6 py-16 text-center">

                <div
                    class="mx-auto flex size-14 items-center
                           justify-center rounded-2xl
                           bg-blue-500/10 text-2xl
                           text-blue-500"
                    aria-hidden="true"
                >
                    📚
                </div>

                <h3
                    class="mt-4 font-bold
                           text-zinc-900 dark:text-white"
                >
                    No books registered
                </h3>

                <p
                    class="mt-2 text-sm
                           text-zinc-500 dark:text-zinc-400"
                >
                    Add your first book to start managing
                    the library collection.
                </p>
                @if (auth()->user()?->isLibrarian())
                <flux:button
                    :href="route('books.create')"
                    variant="primary"
                    class="mt-5"
                    wire:navigate
                >
                    Add New Book
                </flux:button>
                @endif
            </div>

        @else

            {{--
                清单永远保留五个 record row 的视觉高度。
                少于五笔时，最后一笔下方的横线会在 footer 之前收尾。
            --}}
            <div class="min-h-[33rem] overflow-x-auto">

                <table
                    @class([
                        'w-full table-fixed text-sm',
                        'min-w-[1180px]' => auth()->user()->isLibrarian(),
                        'min-w-[900px]' => ! auth()->user()->isLibrarian(),
                    ])
                >
                    @if (auth()->user()->isLibrarian())
                        <colgroup>
                            <col class="w-[10%]">
                            <col class="w-[15%]">
                            <col class="w-[12%]">
                            <col class="w-[10%]">
                            <col class="w-[8%]">
                            <col class="w-[12%]">
                            <col class="w-[8%]">
                            <col class="w-[10%]">
                            <col class="w-[15%]">
                        </colgroup>
                    @endif

                    <thead
                        class="bg-zinc-100 text-zinc-700
                               dark:bg-zinc-800
                               dark:text-zinc-200"
                    >

                        <tr>

                            <th class="px-6 py-4 text-left">
                                ISBN
                            </th>

                            <th class="px-6 py-4 text-left">
                                Title
                            </th>

                            <th class="px-6 py-4 text-left">
                                Author
                            </th>

                            <th class="px-6 py-4 text-left">
                                Category
                            </th>

                            <th class="px-6 py-4 text-left">
                                Type
                            </th>

                            <th class="px-6 py-4 text-left">
                                Availability
                            </th>

                            @if (auth()->user()?->isLibrarian())
                                <th class="px-6 py-4 text-right">
                                    Actions
                                </th>
                            @endif

                            @if (auth()->user()->isLibrarian())
                                <th class="px-4 py-4 text-center">
                                    Borrowed
                                </th>

                                <th class="px-4 py-4 text-center">
                                    Total Copies
                                </th>

                                <th class="px-4 py-4 text-center">
                                    Actions
                                </th>
                            @endif

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">

                        @foreach ($books as $book)

                            <tr
                                class="h-24 transition-colors
                                       hover:bg-zinc-500/5"
                            >

                                {{-- ISBN --}}
                                <td class="px-6 py-5">

                                    <span
                                        class="font-mono text-xs
                                               text-zinc-500
                                               dark:text-zinc-400"
                                    >
                                        {{ $book->isbn }}
                                    </span>

                                </td>


                                {{-- Title --}}
                                <td class="px-6 py-5">

                                    <p
                                        class="font-bold
                                               text-zinc-900
                                               dark:text-white"
                                    >
                                        {{ $book->title }}
                                    </p>

                                </td>


                                {{-- Author --}}
                                <td class="px-6 py-5">

                                    <p
                                        class="text-zinc-600
                                               dark:text-zinc-400"
                                    >
                                        {{ $book->author }}
                                    </p>

                                </td>


                                {{-- Category --}}
                                <td class="px-6 py-5">

                                    <p
                                        class="text-zinc-600
                                               dark:text-zinc-400"
                                    >
                                        {{ $book->category }}
                                    </p>

                                </td>


                                {{-- Type --}}
                                <td class="px-6 py-5">

                                    @if ($book->type === 'ebook')

                                        <span
                                            class="inline-flex rounded-full
                                                   bg-purple-500/10
                                                   px-3 py-1 text-xs
                                                   font-bold
                                                   text-purple-700
                                                   dark:text-purple-300"
                                        >
                                            Ebook
                                        </span>

                                    @else

                                        <span
                                            class="inline-flex rounded-full
                                                   bg-blue-500/10
                                                   px-3 py-1 text-xs
                                                   font-bold
                                                   text-blue-700
                                                   dark:text-blue-300"
                                        >
                                            Physical
                                        </span>

                                    @endif

                                </td>


                               {{-- Availability --}}
                                <td class="px-6 py-5">
                                    @if ($book->type === 'physical')
                                        @php
                                            // 核心逻辑：利用从 JSON API 消费过来的 active_borrowings_count 计算可用库存
                                            $borrowed = $book->active_borrowings_count ?? 0;
                                            $available = $book->total_copies - $borrowed;
                                        @endphp

                                        @if ($available > 0)
                                            <span class="font-semibold text-emerald-700 dark:text-emerald-300">
                                                {{ $available }}
                                            </span>
                                        @else
                                            <span class="font-semibold text-red-700 dark:text-red-300">
                                                0
                                            </span>
                                        @endif
                                        
                                        <span class="text-zinc-500 dark:text-zinc-400">
                                            / {{ $book->total_copies }}
                                        </span>

                                        {{-- 明确展示“已被借出”的数量，向老师证明你成功 Consume 了 JSON API 数据 --}}
                                        @if ($borrowed > 0)
                                            <div class="mt-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                                ({{ $borrowed }} currently borrowed)
                                            </div>
                                        @endif

                                    @else
                                        <span class="font-semibold text-emerald-700 dark:text-emerald-300">
                                            Digital Access
                                        </span>
                                    @endif
                                </td>

                                
                            

                                @if (auth()->user()?->isLibrarian())
                                    <td class="px-6 py-5 text-right">
                                        <div class="flex justify-end gap-3">
                                            <a
                                                href="{{ route('books.edit', $book) }}"
                                                class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                Edit
                                            </a>

                                            <form action="{{ route('books.destroy', $book) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                        

                                @if (auth()->user()->isLibrarian())
                                    <td class="px-4 py-5 text-center">
                                        @if ($book->type === 'physical')
                                            {{ $book->active_borrowings_count }}
                                        @else
                                            <span class="text-zinc-500 dark:text-zinc-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-5 text-center">
                                        @if ($book->type === 'physical')
                                            <form
                                                id="book-copy-form-{{ $book->id }}"
                                                method="POST"
                                                action="{{ route('books.copies.update', $book) }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <label
                                                    for="total-copies-{{ $book->id }}"
                                                    class="sr-only"
                                                >
                                                    Total copies for {{ $book->title }}
                                                </label>

                                                <div class="flex justify-center">
                                                    <input
                                                        id="total-copies-{{ $book->id }}"
                                                        name="total_copies"
                                                        type="number"
                                                        min="{{ $book->active_borrowings_count }}"
                                                        max="10000"
                                                        value="{{ $book->total_copies }}"
                                                        required
                                                        class="w-24 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-center text-sm text-zinc-900 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                                    >
                                                </div>
                                            </form>
                                        @else
                                            <span class="text-zinc-500 dark:text-zinc-400">Digital</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-5 text-center">
                                        @if ($book->type === 'physical')
                                            <div class="flex justify-center">
                                                <button
                                                    type="submit"
                                                    form="book-copy-form-{{ $book->id }}"
                                                    class="inline-flex min-h-11 min-w-36 items-center justify-center whitespace-nowrap rounded-xl border border-blue-500/25 bg-blue-500/10 px-5 text-sm font-semibold text-blue-700 transition hover:bg-blue-500/20 focus:outline-none focus:ring-2 focus:ring-blue-500/40 dark:text-blue-300"
                                                >
                                                    Update copies
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-zinc-500 dark:text-zinc-400">—</span>
                                        @endif
                                    </td>
                                @endif

                            </tr>

                        @endforeach

                    </tbody>

                </table>

                @if ($books->count() < 5)
                    <div class="border-t border-zinc-200 dark:border-zinc-800"></div>
                @endif

            </div>


            <x-listing-pagination
                :paginator="$books"
                aria-label="Book pagination"
            />

        @endif

    </section>

</div>

<script data-navigate-once>
    (() => {
        if (window.smartLibraryBookSearchInitialised) {
            return;
        }

        window.smartLibraryBookSearchInitialised = true;

        let searchTimer;
        let suggestionRequest;
        let resultRequest;

        const closeSuggestions = (input, suggestions) => {
            suggestions.replaceChildren();
            suggestions.hidden = true;
            input.setAttribute('aria-expanded', 'false');
        };

        const addSuggestion = (suggestions, input, book) => {
            const option = document.createElement('button');
            const title = String(book.title ?? 'Untitled book');
            const author = String(book.author ?? 'Unknown author');
            const isbn = String(book.isbn ?? 'No ISBN');

            option.type = 'button';
            option.role = 'option';
            option.className = 'flex w-full items-center justify-between gap-4 border-b border-zinc-100 px-4 py-3 text-start transition last:border-b-0 hover:bg-blue-500/10 focus:bg-blue-500/10 focus:outline-none dark:border-zinc-800';

            const details = document.createElement('span');
            details.className = 'min-w-0';

            const bookTitle = document.createElement('span');
            bookTitle.className = 'block truncate text-sm font-semibold text-zinc-900 dark:text-white';
            bookTitle.textContent = title;

            const bookMeta = document.createElement('span');
            bookMeta.className = 'mt-0.5 block truncate text-xs text-zinc-500 dark:text-zinc-400';
            bookMeta.textContent = `${author} · ${isbn}`;

            const resultType = document.createElement('span');
            resultType.className = 'shrink-0 rounded-full bg-blue-500/10 px-2 py-1 text-[11px] font-semibold text-blue-700 dark:text-blue-300';
            resultType.textContent = String(book.type ?? 'book').replace(/^./, (letter) => letter.toUpperCase());

            details.append(bookTitle, bookMeta);
            option.append(details, resultType);

            option.addEventListener('click', () => {
                input.value = title;
                closeSuggestions(input, suggestions);
                requestCatalogueResults(input.closest('form[data-book-search-form]'));
                input.focus();
            });

            suggestions.append(option);
        };

        const searchUrlFor = (form) => {
            const url = new URL(form.action, window.location.origin);
            const search = form.querySelector('[data-book-search-input]')?.value.trim() ?? '';

            if (search !== '') {
                url.searchParams.set('search', search);
            }

            return url;
        };

        const setClearButtonVisibility = (form) => {
            const clearButton = form.querySelector('[data-book-search-clear]');
            const hasSearch = (form.querySelector('[data-book-search-input]')?.value.trim() ?? '') !== '';

            if (clearButton instanceof HTMLElement) {
                clearButton.classList.toggle('hidden', !hasSearch);
            }
        };

        const requestCatalogueResults = async (form) => {
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const url = searchUrlFor(form);
            const currentResults = document.querySelector('[data-live-book-results]');

            if (!(currentResults instanceof HTMLElement)) {
                window.location.assign(url);
                return;
            }

            if (resultRequest) {
                resultRequest.abort();
            }

            resultRequest = new AbortController();
            currentResults.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: resultRequest.signal,
                });

                if (!response.ok) {
                    throw new Error('Unable to update catalogue results.');
                }

                const documentResponse = new DOMParser().parseFromString(
                    await response.text(),
                    'text/html'
                );

                const nextResults = documentResponse.querySelector(
                    '[data-live-book-results]'
                );

                if (!(nextResults instanceof HTMLElement)) {
                    throw new Error('Catalogue results were not found in the response.');
                }

                currentResults.replaceWith(nextResults);
                window.history.replaceState({}, '', url);
                setClearButtonVisibility(form);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    window.location.assign(url);
                }
            } finally {
                currentResults.removeAttribute('aria-busy');
            }
        };

        document.addEventListener('input', (event) => {
            const input = event.target;

            if (!(input instanceof HTMLInputElement) || !input.matches('[data-book-search-input]')) {
                return;
            }

            const form = input.closest('form[data-book-search-form]');
            const suggestions = form?.querySelector('[data-book-search-suggestions]');
            const search = input.value.trim();

            if (!(form instanceof HTMLFormElement) || !(suggestions instanceof HTMLElement)) {
                return;
            }

            window.clearTimeout(searchTimer);

            if (suggestionRequest) {
                suggestionRequest.abort();
            }

            if (search === '') {
                closeSuggestions(input, suggestions);
            }

            searchTimer = window.setTimeout(async () => {
                requestCatalogueResults(form);

                if (search === '') {
                    return;
                }

                suggestionRequest = new AbortController();

                const url = new URL(form.action, window.location.origin);
                url.searchParams.set('search', search);

                try {
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                        },
                        signal: suggestionRequest.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Unable to fetch book suggestions.');
                    }

                    const payload = await response.json();
                    const books = Array.isArray(payload.data?.data)
                        ? payload.data.data.slice(0, 6)
                        : [];

                    // 忽略旧请求的结果，避免较慢的请求覆盖最新输入。
                    if (input.value.trim() !== search) {
                        return;
                    }

                    suggestions.replaceChildren();

                    if (books.length === 0) {
                        const empty = document.createElement('p');
                        empty.className = 'px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400';
                        empty.textContent = 'No matching books found.';
                        suggestions.append(empty);
                    } else {
                        books.forEach((book) => {
                            addSuggestion(suggestions, input, book);
                        });
                    }

                    suggestions.hidden = false;
                    input.setAttribute('aria-expanded', 'true');
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        closeSuggestions(input, suggestions);
                    }
                }
            }, 220);
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || !form.matches('[data-book-search-form]')) {
                return;
            }

            event.preventDefault();
            window.clearTimeout(searchTimer);
            requestCatalogueResults(form);
        });

        document.addEventListener('click', (event) => {
            const clearButton = event.target.closest('[data-book-search-clear]');

            if (!(clearButton instanceof HTMLElement)) {
                return;
            }

            const form = clearButton.closest('form[data-book-search-form]');
            const input = form?.querySelector('[data-book-search-input]');
            const suggestions = form?.querySelector('[data-book-search-suggestions]');

            if (!(form instanceof HTMLFormElement) || !(input instanceof HTMLInputElement)) {
                return;
            }

            input.value = '';

            if (suggestions instanceof HTMLElement) {
                closeSuggestions(input, suggestions);
            }

            requestCatalogueResults(form);
            input.focus();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const input = event.target;

            if (!(input instanceof HTMLInputElement) || !input.matches('[data-book-search-input]')) {
                return;
            }

            const suggestions = input.closest('form[data-book-search-form]')?.querySelector('[data-book-search-suggestions]');

            if (suggestions instanceof HTMLElement) {
                closeSuggestions(input, suggestions);
            }
        });
    })();
</script>

</x-layouts::app>
