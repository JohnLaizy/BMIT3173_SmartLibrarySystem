<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('home') }}" wire:navigate.hover />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

   <flux:sidebar.nav>
    {{-- 所有已登录用户都可以使用 --}}
    <flux:sidebar.group
        :heading="__('Platform')"
        class="grid"
    >
        <flux:sidebar.item
            icon="home"
            :href="route('dashboard')"
            :current="request()->routeIs('dashboard')"
            wire:navigate
        >
            {{ __('Dashboard') }}
        </flux:sidebar.item>

        <flux:sidebar.item
            icon="calendar-days"
            :href="route('room-availability.index')"
            :current="request()->routeIs('room-availability.*')"
            wire:navigate
        >
            {{ __('Room Availability') }}
        </flux:sidebar.item>

        <flux:sidebar.item
            icon="bookmark-square"
            :href="route('room-reservations.index')"
            :current="request()->routeIs('room-reservations.*')"
            wire:navigate
        >
            @if (auth()->user()->isLibrarian())
                {{ __('Reservations') }}
            @else
                {{ __('My Reservations') }}
            @endif
        </flux:sidebar.item>
    </flux:sidebar.group>

    {{-- 系统管理功能 --}}
    <flux:sidebar.group
        :heading="__('Management')"
        class="grid"
    >
        {{-- 只有 Librarian 可以管理房间和维修 --}}
        @if (auth()->user()->isLibrarian())
            <flux:sidebar.item
                icon="building-office-2"
                :href="route('rooms.index')"
                :current="request()->routeIs('rooms.*')"
                wire:navigate
            >
                {{ __('Room Management') }}
            </flux:sidebar.item>

            <flux:sidebar.item
                icon="wrench-screwdriver"
                :href="route('maintenances.index')"
                :current="request()->routeIs('maintenances.*')"
                wire:navigate
            >
                {{ __('Maintenance Management') }}
            </flux:sidebar.item>
        @endif

        {{-- Book Management 保留给队员的模块 --}}
        @if (Route::has('books.index'))
            <flux:sidebar.item
                icon="book-open-text"
                :href="route('books.index')"
                :current="request()->routeIs('books.*')"
                wire:navigate
            >
                {{ __('Book Management') }}
            </flux:sidebar.item>
        @else
            <flux:sidebar.item
                icon="book-open-text"
                badge="Pending"
                disabled
            >
                {{ __('Book Management') }}
            </flux:sidebar.item>
        @endif

        {{-- Borrow & Return --}}
        @if (Route::has('borrowings.index'))
            <flux:sidebar.item
                icon="arrow-path"
                :href="route('borrowings.index')"
                :current="request()->routeIs('borrowings.*')"
                wire:navigate
            >
                {{ __('Borrow & Return') }}
            </flux:sidebar.item>
        @endif

        {{-- 只有 Librarian 可以管理用户 --}}
        @if (auth()->user()->isLibrarian())
            @if (Route::has('users.index'))
                <flux:sidebar.item
                    icon="users"
                    :href="route('users.index')"
                    :current="request()->routeIs('users.*')"
                    wire:navigate
                >
                    {{ __('User Management') }}
                </flux:sidebar.item>
            @else
                <flux:sidebar.item
                    icon="users"
                    badge="Pending"
                    disabled
                >
                    {{ __('User Management') }}
                </flux:sidebar.item>
            @endif
        @endif
    </flux:sidebar.group>
</flux:sidebar.nav>
            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
<flux:header class="relative lg:hidden">
    <details class="relative">
    <summary
        class="flex min-h-11 min-w-11 cursor-pointer
               list-none items-center justify-center
               rounded-lg text-zinc-700 transition
               hover:bg-zinc-100
               focus:outline-none focus-visible:ring-2
               focus-visible:ring-blue-500
               dark:text-zinc-200 dark:hover:bg-zinc-800
               [&::-webkit-details-marker]:hidden"
        aria-label="Open navigation menu"
    >
        {{-- 三条杠 icon --}}
        <svg
            class="size-6"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.8"
            stroke="currentColor"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M4 6h16M4 12h16M4 18h16"
            />
        </svg>

        <span class="sr-only">
            Open navigation menu
        </span>
    </summary>

    {{-- 手机端菜单面板 --}}
    <nav
        class="absolute start-0 top-[calc(100%+0.75rem)]
               z-50 w-[min(21rem,calc(100vw-1.5rem))]
               overflow-hidden rounded-2xl border
               border-zinc-200 bg-white p-3 shadow-2xl
               dark:border-zinc-700 dark:bg-zinc-900"
        aria-label="Mobile navigation"
    >
        {{-- Platform --}}
        <p
            class="px-3 pb-2 pt-1 text-xs font-bold
                   uppercase tracking-wider
                   text-zinc-500 dark:text-zinc-400"
        >
            Platform
        </p>

        <a
            href="{{ route('dashboard') }}"
            class="flex min-h-11 items-center gap-3 rounded-xl
                   px-3 text-sm font-semibold transition
                   {{ request()->routeIs('dashboard')
                       ? 'bg-zinc-100 text-zinc-950 dark:bg-zinc-800 dark:text-white'
                       : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
            wire:navigate
        >
            <span aria-hidden="true">⌂</span>
            Dashboard
        </a>

        <a
            href="{{ route('room-availability.index') }}"
            class="mt-1 flex min-h-11 items-center gap-3 rounded-xl
                   px-3 text-sm font-semibold transition
                   {{ request()->routeIs('room-availability.*')
                       ? 'bg-zinc-100 text-zinc-950 dark:bg-zinc-800 dark:text-white'
                       : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
            wire:navigate
        >
            <span aria-hidden="true">▣</span>
            Room Availability
        </a>

        <a
            href="{{ route('room-reservations.index') }}"
            class="mt-1 flex min-h-11 items-center gap-3 rounded-xl
                   px-3 text-sm font-semibold transition
                   {{ request()->routeIs('room-reservations.*')
                       ? 'bg-zinc-100 text-zinc-950 dark:bg-zinc-800 dark:text-white'
                       : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
            wire:navigate
        >
            <span aria-hidden="true">▤</span>

            @if (auth()->user()->isLibrarian())
                Reservations
            @else
                My Reservations
            @endif
        </a>

        {{-- Librarian-only management links --}}
        @if (auth()->user()->isLibrarian())
            <div class="my-3 border-t border-zinc-200 dark:border-zinc-700"></div>

            <p
                class="px-3 pb-2 text-xs font-bold uppercase
                       tracking-wider text-zinc-500
                       dark:text-zinc-400"
            >
                Management
            </p>

            <a
                href="{{ route('rooms.index') }}"
                class="flex min-h-11 items-center gap-3 rounded-xl
                       px-3 text-sm font-semibold transition
                       {{ request()->routeIs('rooms.*')
                           ? 'bg-zinc-100 text-zinc-950 dark:bg-zinc-800 dark:text-white'
                           : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                wire:navigate
            >
                <span aria-hidden="true">▥</span>
                Room Management
            </a>

            <a
                href="{{ route('maintenances.index') }}"
                class="mt-1 flex min-h-11 items-center gap-3 rounded-xl
                       px-3 text-sm font-semibold transition
                       {{ request()->routeIs('maintenances.*')
                           ? 'bg-zinc-100 text-zinc-950 dark:bg-zinc-800 dark:text-white'
                           : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                wire:navigate
            >
                <span aria-hidden="true">⚒</span>
                Maintenance Management
            </a>
        @endif

        {{-- Book Module：若队友已完成 route 才可进入 --}}
        <div class="my-3 border-t border-zinc-200 dark:border-zinc-700"></div>

        @if (Route::has('books.index'))
            <a
                href="{{ route('books.index') }}"
                class="flex min-h-11 items-center gap-3 rounded-xl
                       px-3 text-sm font-semibold
                       text-zinc-700 transition hover:bg-zinc-100
                       dark:text-zinc-200 dark:hover:bg-zinc-800"
                wire:navigate
            >
                <span aria-hidden="true">▧</span>
                Book Management
            </a>
        @else
            <div
                class="flex min-h-11 items-center justify-between
                       rounded-xl px-3 text-sm font-semibold
                       text-zinc-400 dark:text-zinc-500"
            >
                <span class="flex items-center gap-3">
                    <span aria-hidden="true">▧</span>
                    Book Management
                </span>

                <span
                    class="rounded-md bg-zinc-200 px-2 py-0.5
                           text-xs dark:bg-zinc-700"
                >
                    Pending
                </span>
            </div>
        @endif

        {{-- Settings --}}
        <div class="my-3 border-t border-zinc-200 dark:border-zinc-700"></div>

        <a
            href="{{ route('profile.edit') }}"
            class="flex min-h-11 items-center gap-3 rounded-xl
                   px-3 text-sm font-semibold
                   text-zinc-700 transition hover:bg-zinc-100
                   dark:text-zinc-200 dark:hover:bg-zinc-800"
            wire:navigate
        >
            <span aria-hidden="true">⚙</span>
            Settings
        </a>
    </nav>
</details>
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                    {{-- Settings 含有 Livewire form state，不使用 hover prefetch --}}
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        
       {{ $slot }}
     

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
