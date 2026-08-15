<x-layouts::app :title="__('Edit User')">
    <div class="mx-auto max-w-2xl p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">
                Edit User
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                Update user information, role and account status.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('users.update', $user) }}"
            class="space-y-6"
        >
            @csrf
            @method('PATCH')

            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name', $user->name)"
                type="text"
                required
            />

            <flux:input
                name="email"
                :label="__('Email')"
                :value="old('email', $user->email)"
                type="email"
                required
            />

            <flux:input
                name="phone"
                :label="__('Phone number')"
                :value="old('phone', $user->phone)"
                type="tel"
                required
            />

            <div>
                <label class="mb-2 block text-sm font-medium">
                    Role
                </label>

                <select
                    name="role"
                    class="w-full rounded-lg border border-zinc-300
                           bg-white px-3 py-2
                           dark:border-zinc-600 dark:bg-zinc-800"
                    @disabled(auth()->id() === $user->id)
                >
                    <option
                        value="student"
                        @selected(old('role', $user->role) === 'student')
                    >
                        Student
                    </option>

                    <option
                        value="librarian"
                        @selected(old('role', $user->role) === 'librarian')
                    >
                        Librarian
                    </option>
                </select>

                @if (auth()->id() === $user->id)
                    <input
                        type="hidden"
                        name="role"
                        value="librarian"
                    >

                    <p class="mt-1 text-sm text-zinc-500">
                        You cannot change your own librarian role.
                    </p>
                @endif
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">
                    Account Status
                </label>

                <select
                    name="account_status"
                    class="w-full rounded-lg border border-zinc-300
                           bg-white px-3 py-2
                           dark:border-zinc-600 dark:bg-zinc-800"
                    @disabled(auth()->id() === $user->id)
                >
                    <option
                        value="active"
                        @selected(old('account_status', $user->account_status) === 'active')
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        @selected(old('account_status', $user->account_status) === 'inactive')
                    >
                        Inactive
                    </option>
                </select>

                @if (auth()->id() === $user->id)
                    <input
                        type="hidden"
                        name="account_status"
                        value="active"
                    >

                    <p class="mt-1 text-sm text-zinc-500">
                        You cannot deactivate your own account.
                    </p>
                @endif
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    class="rounded-lg bg-emerald-600 px-4 py-2
                           font-semibold text-white hover:bg-emerald-500"
                >
                    Save Changes
                </button>

                <a
                    href="{{ route('users.index') }}"
                    class="rounded-lg border border-zinc-300 px-4 py-2
                           font-semibold hover:bg-zinc-100
                           dark:border-zinc-600 dark:hover:bg-zinc-800"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-layouts::app>