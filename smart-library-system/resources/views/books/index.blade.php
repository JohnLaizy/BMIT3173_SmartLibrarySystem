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
    </header>


    {{-- 搜索 --}}
    <section
        class="overflow-hidden rounded-2xl
               border border-zinc-200 bg-white shadow-sm
               dark:border-zinc-700 dark:bg-zinc-900"
    >

        <div class="p-5">

            <form
                method="GET"
                action="{{ route('books.index') }}"
                class="flex flex-col gap-3 sm:flex-row"
            >

                <div class="flex-1">

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by ISBN, title, author, or category..."
                        class="w-full rounded-xl border border-zinc-300
                               bg-white px-4 py-2.5 text-sm
                               text-zinc-900 outline-none
                               transition focus:border-blue-500
                               focus:ring-2 focus:ring-blue-500/20
                               dark:border-zinc-700
                               dark:bg-zinc-800
                               dark:text-zinc-100
                               dark:placeholder-zinc-500"
                    >

                </div>

                <flux:button
                    type="submit"
                    variant="primary"
                >
                    Search
                </flux:button>

                @if(request('search'))

                    <flux:button
                        :href="route('books.index')"
                        variant="ghost"
                        wire:navigate
                    >
                        Clear
                    </flux:button>

                @endif

            </form>

        </div>

    </section>


    {{-- Book List --}}
    <section
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
                    All registered physical and digital books.
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

                <flux:button
                    :href="route('books.create')"
                    variant="primary"
                    class="mt-5"
                    wire:navigate
                >
                    Add New Book
                </flux:button>

            </div>

        @else

            {{-- Book Table --}}
            <div class="overflow-x-auto">

                <table class="w-full min-w-[900px] text-sm">

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

                        </tr>

                    </thead>


                    <tbody
                        class="divide-y divide-zinc-200
                               dark:divide-zinc-800"
                    >

                        @foreach ($books as $book)

                            <tr
                                class="transition-colors
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

                                        @if ($book->available_copies > 0)

                                            <span
                                                class="font-semibold
                                                       text-emerald-700
                                                       dark:text-emerald-300"
                                            >
                                                {{ $book->available_copies }}
                                            </span>

                                            <span
                                                class="text-zinc-500
                                                       dark:text-zinc-400"
                                            >
                                                / {{ $book->total_copies }}
                                            </span>

                                        @else

                                            <span
                                                class="font-semibold
                                                       text-red-700
                                                       dark:text-red-300"
                                            >
                                                0
                                            </span>

                                            <span
                                                class="text-zinc-500
                                                       dark:text-zinc-400"
                                            >
                                                / {{ $book->total_copies }}
                                            </span>

                                        @endif

                                    @else

                                        <span
                                            class="font-semibold
                                                   text-emerald-700
                                                   dark:text-emerald-300"
                                        >
                                            Digital Access
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            @if ($books->hasPages())

                <div
                    class="border-t border-zinc-200
                           px-6 py-4 dark:border-zinc-700"
                >
                    {{ $books->withQueryString()->links() }}
                </div>

            @endif

        @endif

    </section>

</div>

</x-layouts::app>