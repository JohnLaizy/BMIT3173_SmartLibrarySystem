<x-layouts::app :title="__('Add New Book')">

    <div
        data-page-transition
        class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-2 sm:px-4"
    >

        {{-- Page Header --}}
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div
                    class="mb-3 inline-flex items-center gap-2 rounded-full
                           border border-blue-500/20 bg-blue-500/10
                           px-3 py-1 text-xs font-bold uppercase
                           tracking-wider text-blue-700 dark:text-blue-300"
                >
                    <span
                        class="size-2 rounded-full bg-blue-500"
                        aria-hidden="true"
                    ></span>
                    Book Collection
                </div>

                <flux:heading size="xl" level="1">
                    Add New Book
                </flux:heading>

                <flux:text class="mt-2 max-w-2xl">
                    Add a new physical or digital book to the library collection.
                </flux:text>
            </div>

            <div>
                <flux:button
                    :href="route('books.index')"
                    variant="ghost"
                    wire:navigate
                >
                    ← Back to Books
                </flux:button>
            </div>
        </header>

        {{-- Form Card --}}
        <section
            class="overflow-hidden rounded-2xl border border-zinc-200
                   bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >

            <div
                class="border-b border-zinc-200 px-6 py-5 dark:border-zinc-700"
            >
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                    Book Information
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Enter the details of the book below.
                </p>
            </div>

            {{-- 注意：必须添加 enctype="multipart/form-data" 才能支持安全文件上传 --}}
            <form
                method="POST"
                action="{{ route('books.store') }}"
                enctype="multipart/form-data"
                class="space-y-6 p-6"
            >
                @csrf

                {{-- ISBN --}}
                <div>
                    <label
                        for="isbn"
                        class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white"
                    >
                        ISBN
                    </label>
                    <input
                        id="isbn"
                        type="text"
                        name="isbn"
                        value="{{ old('isbn') }}"
                        placeholder="e.g. 9780132350884"
                        required
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    >
                    @error('isbn')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Title --}}
                <div>
                    <label
                        for="title"
                        class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white"
                    >
                        Title
                    </label>
                    <input
                        id="title"
                        type="text"
                        name="title"
                        value="{{ old('title') }}"
                        placeholder="Enter book title"
                        required
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    >
                    @error('title')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Author --}}
                <div>
                    <label
                        for="author"
                        class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white"
                    >
                        Author
                    </label>
                    <input
                        id="author"
                        type="text"
                        name="author"
                        value="{{ old('author') }}"
                        placeholder="Enter author name"
                        required
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    >
                    @error('author')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label
                        for="category"
                        class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white"
                    >
                        Category
                    </label>
                    <input
                        id="category"
                        type="text"
                        name="category"
                        value="{{ old('category') }}"
                        placeholder="e.g. Programming, Database, Networking"
                        required
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    >
                    @error('category')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Type --}}
                <div>
                    <label
                        for="type"
                        class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white"
                    >
                        Book Type
                    </label>
                    <select
                        id="type"
                        name="type"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    >
                        <option value="physical" {{ old('type', 'physical') === 'physical' ? 'selected' : '' }}>
                            Physical Book
                        </option>
                        <option value="ebook" {{ old('type') === 'ebook' ? 'selected' : '' }}>
                            E-Book (Digital)
                        </option>
                    </select>
                    @error('type')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Copies (Only for Physical) --}}
                <div>
                    <label
                        for="total_copies"
                        class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white"
                    >
                        Total Copies
                    </label>
                    <input
                        id="total_copies"
                        type="number"
                        name="total_copies"
                        min="1"
                        max="500"
                        value="{{ old('total_copies', 1) }}"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    >
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Applicable for physical copies (Ignored if E-Book is selected).</p>
                    @error('total_copies')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- File Management: Cover Image & E-Book Document --}}
                <div class="grid gap-6 sm:grid-cols-2">
                    {{-- Cover Image --}}
                    <div>
                        <label
                            for="cover_image"
                            class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white"
                        >
                            Book Cover Image (Optional)
                        </label>
                        <input
                            id="cover_image"
                            type="file"
                            name="cover_image"
                            accept="image/jpeg,image/png,image/webp"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-zinc-700 hover:file:bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700 dark:file:text-zinc-200"
                        >
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Allowed formats: JPG, PNG, WEBP (Max 2MB).</p>
                        @error('cover_image')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- E-Book File --}}
                    <div>
                        <label
                            for="ebook_file"
                            class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white"
                        >
                            E-Book File (Required if E-Book)
                        </label>
                        <input
                            id="ebook_file"
                            type="file"
                            name="ebook_file"
                            accept=".pdf,.epub"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-zinc-700 hover:file:bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:file:bg-zinc-700 dark:file:text-zinc-200"
                        >
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Allowed formats: PDF, EPUB (Max 20MB).</p>
                        @error('ebook_file')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div
                    class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-6 sm:flex-row sm:justify-end dark:border-zinc-700"
                >
                    <flux:button
                        :href="route('books.index')"
                        variant="ghost"
                        wire:navigate
                    >
                        Cancel
                    </flux:button>

                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="plus"
                    >
                        Add Book
                    </flux:button>
                </div>

            </form>

        </section>

    </div>

</x-layouts::app>