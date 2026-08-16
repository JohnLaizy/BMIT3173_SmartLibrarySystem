<x-layouts::app :title="__('User Management')">
    <div class="p-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold">
                User Management
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                View and manage registered users.
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('users.index') }}"
            class="mb-6 flex flex-col gap-3 sm:flex-row"
        >
            <input
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Search by name, email or phone number"
                class="w-full rounded-lg border border-zinc-300
                       bg-white px-4 py-2
                       dark:border-zinc-600 dark:bg-zinc-800"
            >

            <button
                type="submit"
                class="rounded-lg bg-emerald-600 px-4 py-2
                       font-semibold text-white
                       hover:bg-emerald-500"
            >
                Search
            </button>

            @if ($search !== '')
                <a
                    href="{{ route('users.index') }}"
                    class="rounded-lg border border-zinc-300 px-4 py-2
                           text-center font-semibold
                           hover:bg-zinc-100
                           dark:border-zinc-600 dark:hover:bg-zinc-800"
                >
                    Clear
                </a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-t border-zinc-200 dark:border-zinc-700">
                            <td class="px-4 py-3">
                                {{ $user->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $user->email }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $user->phone ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ ucfirst($user->role) }}
                            </td>

                            <td class="px-4 py-3">
                                {{ ucfirst($user->account_status) }}
                            </td>

                            <td class="px-4 py-3">
                              <a
                              href="{{ route('users.edit', $user) }}"
                              class="inline-flex items-center rounded-lg bg-emerald-600
                              px-3 py-2 text-sm font-semibold text-white
                              hover:bg-emerald-500"
                              >
                              Edit
                             </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-zinc-500"
                            >
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $users->links() }}
        </div>
    </div>
</x-layouts::app>