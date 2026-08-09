<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <!-- Header -->
        <div>
            <p
                class="text-sm font-bold uppercase
                       tracking-[0.16em] text-emerald-400"
            >
                Welcome Back
            </p>

            <h1
                class="mt-2 text-3xl font-bold tracking-tight
                       text-white"
            >
                Log in to your account
            </h1>

            <p class="mt-2 leading-6 text-zinc-400">
                Enter your email address and password to continue.
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status
            class="rounded-xl border border-emerald-500/30
                   bg-emerald-500/10 px-4 py-3 text-center
                   text-sm text-emerald-300"
            :status="session('status')"
        />

        <form
            method="POST"
            action="{{ route('login.store') }}"
            class="flex flex-col gap-5"
        >
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Enter your password')"
                    viewable
                />
            </div>

            <!-- Options -->
            <div
                class="flex flex-col gap-3 sm:flex-row
                       sm:items-center sm:justify-between"
            >
                <flux:checkbox
                    name="remember"
                    :label="__('Remember me')"
                    :checked="old('remember')"
                />

                @if (Route::has('password.request'))
                    <flux:link
                        :href="route('password.request')"
                        class="text-sm font-semibold text-emerald-400
                               hover:text-emerald-300"
                        wire:navigate
                    >
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Submit button -->
            <button
                type="submit"
                data-test="login-button"
                class="inline-flex min-h-12 w-full items-center
                       justify-center rounded-xl bg-emerald-600
                       px-5 font-semibold text-white shadow-lg
                       shadow-emerald-950/30 transition
                       hover:bg-emerald-500
                       focus:outline-none focus:ring-2
                       focus:ring-emerald-400
                       focus:ring-offset-2
                       focus:ring-offset-zinc-900"
            >
                {{ __('Log in') }}
            </button>
        </form>

        @if (Route::has('register'))
            <div class="flex items-center gap-4">
                <div class="h-px flex-1 bg-zinc-700"></div>

                <span class="text-xs uppercase tracking-wider text-zinc-500">
                    New User
                </span>

                <div class="h-px flex-1 bg-zinc-700"></div>
            </div>

            <div class="text-center text-sm text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>

                <flux:link
                    :href="route('register')"
                    class="ms-1 font-semibold text-emerald-400
                           hover:text-emerald-300"
                    wire:navigate
                >
                    {{ __('Create an account') }}
                </flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>