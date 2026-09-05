<x-layouts::app :title="__('Edit Book')">
    <div data-page-transition class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 px-2 sm:px-4">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl" level="1">Edit Book</flux:heading>
                <flux:text class="mt-2">Update the library record for {{ $book->title }}.</flux:text>
            </div>

            <flux:button :href="route('books.index')" variant="ghost" wire:navigate>
                ← Back to Books
            </flux:button>
        </header>

        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <form method="POST" action="{{ route('books.update', $book) }}" class="space-y-6 p-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="title" class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $book->title) }}" required class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('title')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="author" class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white">Author</label>
                    <input id="author" name="author" type="text" value="{{ old('author', $book->author) }}" required class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('author')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="category" class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white">Category</label>
                    <input id="category" name="category" type="text" value="{{ old('category', $book->category) }}" required class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('category')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                @if ($book->isPhysical())
                    <div>
                        <label for="total_copies" class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white">Total Copies</label>
                        <input id="total_copies" name="total_copies" type="number" min="0" max="10000" value="{{ old('total_copies', $book->total_copies) }}" required class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('total_copies')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div class="flex justify-end gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:button :href="route('books.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                </div>
            </form>
        </section>
    </div>
</x-layouts::app>
