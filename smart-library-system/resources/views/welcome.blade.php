<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="scroll-smooth"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta
            name="description"
            content="Discover library resources and check study-room availability."
        >

        <title>Smart Library System</title>

        <link
            rel="icon"
            href="{{ asset('smart-library-icon.svg') }}"
            type="image/svg+xml"
        >

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>

    <body
        class="min-h-screen bg-zinc-50 text-zinc-900 antialiased
               transition-colors duration-300
               dark:bg-zinc-900 dark:text-white"
    >
        <!-- Navigation -->
        <header
            class="sticky top-0 z-50 border-b border-zinc-200
                   bg-white/90 backdrop-blur
                   dark:border-zinc-700 dark:bg-zinc-950/90"
        >
            <div
                class="mx-auto flex h-18 max-w-7xl items-center
                       justify-between px-5 sm:px-8 lg:px-10"
            >
                <a
                    href="{{ route('home') }}"
                    class="flex items-center gap-3"
                    wire:navigate.hover
                >
                  <x-app-logo-icon
                         class="size-10 shrink-0 rounded-xl shadow-sm"
                    />
                    <span>
                        <span
                            class="block font-bold leading-tight
                                   text-zinc-900 dark:text-white"
                        >
                            Smart Library
                        </span>

                        <span
                            class="block text-xs text-zinc-500
                                   dark:text-zinc-400"
                        >
                            Learn. Discover. Connect.
                        </span>
                    </span>
                </a>

                <nav
                    class="hidden items-center gap-8 text-sm font-medium
                           md:flex"
                >
                    <a
                        href="#features"
                        class="text-zinc-600 transition-colors
                               hover:text-emerald-700
                               dark:text-zinc-300 dark:hover:text-emerald-400"
                    >
                        Features
                    </a>

                    <a
                        href="#rooms"
                        class="text-zinc-600 transition-colors
                               hover:text-emerald-700
                               dark:text-zinc-300 dark:hover:text-emerald-400"
                    >
                        Rooms
                    </a>

                    <a
                        href="#roles"
                        class="text-zinc-600 transition-colors
                               hover:text-emerald-700
                               dark:text-zinc-300 dark:hover:text-emerald-400"
                    >
                        For Everyone
                    </a>
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            wire:navigate.hover
                            class="inline-flex min-h-11 items-center
                                   justify-center rounded-xl border
                                   border-zinc-300 bg-white px-4 text-sm
                                   font-semibold text-zinc-800 shadow-sm
                                   transition hover:bg-zinc-100
                                   dark:border-zinc-600 dark:bg-transparent
                                   dark:text-zinc-100 dark:hover:bg-zinc-800"
                        >
                            Dashboard
                        </a>

                        <a
                            href="{{ route('rooms.index') }}"
                            wire:navigate.hover
                            class="hidden min-h-11 items-center justify-center
                                   rounded-xl bg-emerald-600 px-4 text-sm
                                   font-semibold text-white shadow-sm transition
                                   hover:bg-emerald-700 sm:inline-flex
                                   dark:bg-emerald-500 dark:hover:bg-emerald-400"
                        >
                            View Rooms
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a
                                href="{{ route('login') }}"
                                wire:navigate.hover
                                class="inline-flex min-h-11 items-center
                                       justify-center rounded-xl border
                                       border-zinc-300 bg-white px-4 text-sm
                                       font-semibold text-zinc-800 transition
                                       hover:bg-zinc-100
                                       focus-visible:outline-none
                                       focus-visible:ring-2
                                       focus-visible:ring-emerald-500
                                       dark:border-zinc-600 dark:bg-transparent
                                       dark:text-zinc-100 dark:hover:bg-zinc-800"
                            >
                                Log In
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                wire:navigate.hover
                                class="inline-flex min-h-11 items-center
                                       justify-center rounded-xl bg-emerald-600
                                       px-4 text-sm font-semibold text-white
                                       shadow-sm transition hover:bg-emerald-700
                                       dark:bg-emerald-500
                                       dark:hover:bg-emerald-400"
                            >
                                Join Library
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main data-page-transition>
            <!-- Hero -->
            <section
                class="relative overflow-hidden border-b border-zinc-200
                       dark:border-zinc-700"
            >
                <!-- Decorative circles -->
                <div
                    class="pointer-events-none absolute -end-48 -top-56
                           size-[34rem] rounded-full border-[72px]
                           border-emerald-200/60
                           dark:border-emerald-100/60"
                    aria-hidden="true"
                ></div>

                <div
                    class="pointer-events-none absolute -bottom-56 -start-40
                           size-[30rem] rounded-full border-[64px]
                           border-amber-200/70 dark:border-amber-100/70"
                    aria-hidden="true"
                ></div>

                <div
                    class="relative mx-auto grid max-w-7xl items-center
                           gap-14 px-5 py-16 sm:px-8 sm:py-20
                           lg:grid-cols-[1.02fr_0.98fr]
                           lg:px-10 lg:py-24"
                >
                    <!-- Hero text -->
                    <div class="max-w-2xl">
                        <div
                            class="mb-6 inline-flex items-center gap-2
                                   rounded-full border border-emerald-200
                                   bg-emerald-50 px-3 py-1.5 text-sm
                                   font-semibold text-emerald-700
                                   dark:border-emerald-500/20
                                   dark:bg-emerald-500/10
                                   dark:text-emerald-300"
                        >
                            <span
                                class="size-2 rounded-full bg-emerald-500
                                       dark:bg-emerald-400"
                            ></span>

                            Your library, made simpler
                        </div>

                        <h1
                            class="text-4xl font-bold leading-[1.08]
                                   tracking-tight text-zinc-900
                                   dark:text-white sm:text-5xl lg:text-6xl"
                        >
                            A smarter way to
                            <span
                                class="block text-emerald-600
                                       dark:text-emerald-400"
                            >
                                learn and connect.
                            </span>
                        </h1>

                        <p
                            class="mt-6 max-w-xl text-lg leading-8
                                   text-zinc-600 dark:text-zinc-300"
                        >
                            Discover library resources, check study-room
                            availability, and manage every visit from one
                            clear and secure workspace.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a
                                    href="{{ route('rooms.index') }}"
                                    wire:navigate.hover
                                    class="inline-flex min-h-12 items-center
                                           justify-center gap-2 rounded-xl
                                           bg-emerald-600 px-6 text-base
                                           font-semibold text-white shadow-lg
                                           transition hover:-translate-y-0.5
                                           hover:bg-emerald-700
                                           dark:bg-emerald-500
                                           dark:hover:bg-emerald-400"
                                >
                                    Explore Available Rooms
                                    <span aria-hidden="true">→</span>
                                </a>
                            @else
                                <a
                                    href="{{ Route::has('login')
                                        ? route('login')
                                        : '#features' }}"
                                    wire:navigate.hover
                                    class="inline-flex min-h-12 items-center
                                           justify-center gap-2 rounded-xl
                                           bg-emerald-600 px-6 text-base
                                           font-semibold text-white shadow-lg
                                           transition hover:-translate-y-0.5
                                           hover:bg-emerald-700
                                           dark:bg-emerald-500
                                           dark:hover:bg-emerald-400"
                                >
                                    Get Started
                                    <span aria-hidden="true">→</span>
                                </a>
                            @endauth

                            <a
                                href="#features"
                                class="inline-flex min-h-12 items-center
                                       justify-center rounded-xl border
                                       border-zinc-300 bg-white px-6 text-base
                                       font-semibold text-zinc-800 shadow-sm
                                       transition hover:bg-zinc-100
                                       dark:border-zinc-600 dark:bg-transparent
                                       dark:text-zinc-100 dark:hover:bg-zinc-800"
                            >
                                See How It Works
                            </a>
                        </div>

                        <!-- Statistics -->
                        <dl
                            class="mt-10 grid max-w-xl grid-cols-3 gap-4
                                   border-t border-zinc-200 pt-6
                                   dark:border-zinc-700"
                        >
                            <div>
                                <dt
                                    class="text-xs font-medium uppercase
                                           tracking-wider text-zinc-500
                                           dark:text-zinc-400"
                                >
                                    Access
                                </dt>

                                <dd
                                    class="mt-1 text-lg font-bold
                                           text-zinc-900 dark:text-white"
                                >
                                    24 / 7
                                </dd>
                            </div>

                            <div>
                                <dt
                                    class="text-xs font-medium uppercase
                                           tracking-wider text-zinc-500
                                           dark:text-zinc-400"
                                >
                                    Rooms
                                </dt>

                                <dd
                                    class="mt-1 text-lg font-bold
                                           text-zinc-900 dark:text-white"
                                >
                                    Live Status
                                </dd>
                            </div>

                            <div>
                                <dt
                                    class="text-xs font-medium uppercase
                                           tracking-wider text-zinc-500
                                           dark:text-zinc-400"
                                >
                                    Accounts
                                </dt>

                                <dd
                                    class="mt-1 text-lg font-bold
                                           text-zinc-900 dark:text-white"
                                >
                                    Secured
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Room preview: intentionally dark in both themes -->
                    <div
                        id="rooms"
                        class="relative mx-auto w-full max-w-xl
                               scroll-mt-28 lg:mx-0 lg:ms-auto"
                    >
                        <div
                            class="absolute -inset-4 -z-10 rotate-2
                                   rounded-[2rem] bg-emerald-200/70
                                   dark:bg-emerald-100/70"
                        ></div>

                        <div
                            class="overflow-hidden rounded-[1.75rem]
                                   border border-slate-700 bg-slate-950
                                   text-white shadow-2xl
                                   shadow-slate-950/20"
                        >
                            <div
                                class="flex items-center justify-between
                                       border-b border-white/10
                                       px-5 py-4 sm:px-6"
                            >
                                <div>
                                    <p class="text-sm font-semibold text-white">
                                        Library Today
                                    </p>

                                    <p class="mt-0.5 text-xs text-slate-400">
                                        Study spaces at a glance
                                    </p>
                                </div>

                                <span
                                    class="inline-flex items-center gap-2
                                           rounded-full bg-emerald-400/10
                                           px-3 py-1.5 text-xs font-semibold
                                           text-emerald-300"
                                >
                                    <span
                                        class="size-1.5 rounded-full
                                               bg-emerald-400"
                                    ></span>

                                    Live
                                </span>
                            </div>

                            <!-- Summary cards -->
                            <div class="grid grid-cols-2 gap-3 p-5 sm:p-6">
                                <div
                                    class="rounded-2xl border border-white/10
                                           bg-white/[0.04] p-4"
                                >
                                    <p
                                        class="text-xs font-semibold
                                               text-emerald-300"
                                    >
                                        AVAILABLE ROOMS
                                    </p>

                                    <p class="mt-3 text-3xl font-bold text-white">
                                        {{ $availableRoomsCount }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Out of {{ $totalRoomsCount }} rooms
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Live room information
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-white/10
                                           bg-white/[0.04] p-4"
                                >
                                    <p
                                        class="text-xs font-semibold
                                               text-amber-200"
                                    >
                                        CATALOGUE
                                    </p>

                                    <p class="mt-3 text-xl font-bold text-white">
                                        Coming Soon
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Book management module
                                    </p>
                                </div>
                            </div>

                            <!-- Room listing preview -->
                            <div class="px-5 pb-5 sm:px-6 sm:pb-6">
                                <div
                                    class="rounded-2xl border border-white/10
                                           bg-white/[0.04] p-4 shadow-sm"
                                >
                                    <div
                                        class="mb-3 flex items-center
                                               justify-between"
                                    >
                                        <p class="text-sm font-bold text-white">
                                            Room Availability
                                        </p>

                                        @auth
                                            <a
                                                href="{{ route('rooms.index') }}"
                                                wire:navigate.hover
                                                class="text-xs font-semibold
                                                       text-emerald-300
                                                       transition
                                                       hover:text-emerald-200"
                                            >
                                                View All
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400">
                                                Updated now
                                            </span>
                                        @endauth
                                    </div>

                                    <div class="space-y-2.5">
                                        @forelse ($rooms as $room)
                                            <div
                                                class="flex items-center
                                                       justify-between gap-4
                                                       rounded-xl
                                                       bg-white/[0.06]
                                                       px-3.5 py-3"
                                            >
                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-sm
                                                               font-semibold
                                                               text-white"
                                                    >
                                                        {{ $room->room_number }} ·
                                                        {{ $room->name }}
                                                    </p>

                                                    <p
                                                        class="mt-0.5 text-xs
                                                               text-slate-400"
                                                    >
                                                        {{ $room->location }} ·
                                                        {{ $room->capacity }} people
                                                    </p>
                                                </div>

                                                <span
                                                    @class([
                                                        'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                                                        'bg-emerald-100 text-emerald-800' =>
                                                            $room->status === 'available',
                                                        'bg-red-100 text-red-800' =>
                                                            $room->status === 'unavailable',
                                                        'bg-amber-100 text-amber-800' =>
                                                            $room->status === 'maintenance',
                                                    ])
                                                >
                                                    {{ str($room->status)->headline() }}
                                                </span>
                                            </div>
                                        @empty
                                            <div
                                                class="rounded-xl
                                                       bg-white/[0.06]
                                                       px-4 py-6 text-center
                                                       text-sm text-slate-400"
                                            >
                                                No rooms have been added yet.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features -->
            <section
                id="features"
                class="scroll-mt-24 bg-zinc-100 py-20
                       dark:bg-zinc-800 sm:py-24"
            >
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div class="max-w-2xl">
                        <p
                            class="text-sm font-bold uppercase
                                   tracking-[0.18em] text-emerald-700
                                   dark:text-emerald-400"
                        >
                            Everything In One Place
                        </p>

                        <h2
                            class="mt-3 text-3xl font-bold tracking-tight
                                   text-zinc-900 dark:text-white sm:text-4xl"
                        >
                            Designed around real library needs.
                        </h2>

                        <p
                            class="mt-4 text-lg leading-8 text-zinc-600
                                   dark:text-zinc-300"
                        >
                            Find what you need faster while librarians keep
                            resources accurate, secure and easy to manage.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-5 md:grid-cols-3">
                        <article
                            class="rounded-2xl border border-zinc-200
                                   bg-white p-6 shadow-sm transition
                                   hover:-translate-y-1
                                   hover:border-emerald-400 hover:shadow-lg
                                   dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <span
                                class="text-sm font-bold text-emerald-700
                                       dark:text-emerald-400"
                            >
                                01
                            </span>

                            <h3
                                class="mt-8 text-xl font-bold
                                       text-zinc-900 dark:text-white"
                            >
                                Live Room Status
                            </h3>

                            <p
                                class="mt-3 leading-7 text-zinc-600
                                       dark:text-zinc-300"
                            >
                                Check room capacity, location, facilities and
                                availability before visiting the library.
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-zinc-200
                                   bg-white p-6 shadow-sm transition
                                   hover:-translate-y-1
                                   hover:border-amber-400 hover:shadow-lg
                                   dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <span
                                class="text-sm font-bold text-amber-700
                                       dark:text-amber-400"
                            >
                                02
                            </span>

                            <h3
                                class="mt-8 text-xl font-bold
                                       text-zinc-900 dark:text-white"
                            >
                                Organized Resources
                            </h3>

                            <p
                                class="mt-3 leading-7 text-zinc-600
                                       dark:text-zinc-300"
                            >
                                Keep titles, copy counts and library spaces
                                organized in one consistent system.
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-zinc-200
                                   bg-white p-6 shadow-sm transition
                                   hover:-translate-y-1
                                   hover:border-sky-400 hover:shadow-lg
                                   dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <span
                                class="text-sm font-bold text-sky-700
                                       dark:text-sky-400"
                            >
                                03
                            </span>

                            <h3
                                class="mt-8 text-xl font-bold
                                       text-zinc-900 dark:text-white"
                            >
                                Secure By Role
                            </h3>

                            <p
                                class="mt-3 leading-7 text-zinc-600
                                       dark:text-zinc-300"
                            >
                                Students view resources while librarians
                                securely control every management action.
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Roles -->
            <section
                id="roles"
                class="scroll-mt-24 border-y border-emerald-200
                       bg-emerald-50/60 py-20
                       dark:border-zinc-700 dark:bg-emerald-950/10
                       sm:py-24"
            >
                <div
                    class="mx-auto grid max-w-7xl gap-10 px-5
                           sm:px-8 lg:grid-cols-[0.8fr_1.2fr]
                           lg:items-center lg:px-10"
                >
                    <div>
                        <p
                            class="text-sm font-bold uppercase
                                   tracking-[0.18em] text-emerald-700
                                   dark:text-emerald-400"
                        >
                            Built For Everyone
                        </p>

                        <h2
                            class="mt-3 text-3xl font-bold tracking-tight
                                   text-zinc-900 dark:text-white sm:text-4xl"
                        >
                            The right tools for every library role.
                        </h2>

                        <p
                            class="mt-4 text-lg leading-8 text-zinc-600
                                   dark:text-zinc-300"
                        >
                            A focused experience keeps common tasks simple
                            without exposing controls to the wrong users.
                        </p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <!-- Student -->
                        <article
                            class="rounded-2xl border border-emerald-200
                                   bg-white p-6 shadow-sm
                                   dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <span
                                class="inline-flex rounded-full bg-emerald-100
                                       px-3 py-1 text-sm font-bold
                                       text-emerald-800
                                       dark:bg-emerald-500/10
                                       dark:text-emerald-300"
                            >
                                Students
                            </span>

                            <h3
                                class="mt-5 text-xl font-bold
                                       text-zinc-900 dark:text-white"
                            >
                                Find a place to focus.
                            </h3>

                            <ul
                                class="mt-5 space-y-3 text-sm text-zinc-600
                                       dark:text-zinc-300"
                            >
                                <li class="flex gap-3">
                                    <span class="text-emerald-600">✓</span>
                                    View room details and availability
                                </li>

                                <li class="flex gap-3">
                                    <span class="text-emerald-600">✓</span>
                                    Check capacity, floor and facilities
                                </li>

                                <li class="flex gap-3">
                                    <span class="text-emerald-600">✓</span>
                                    Access a secure personal account
                                </li>
                            </ul>
                        </article>

                        <!-- Librarian: intentionally dark in both themes -->
                        <article
                            class="rounded-2xl border border-slate-800
                                   bg-slate-950 p-6 text-white shadow-lg"
                        >
                            <span
                                class="inline-flex rounded-full
                                       bg-emerald-400/10 px-3 py-1
                                       text-sm font-bold text-emerald-300"
                            >
                                Librarians
                            </span>

                            <h3 class="mt-5 text-xl font-bold text-white">
                                Manage with confidence.
                            </h3>

                            <ul class="mt-5 space-y-3 text-sm text-slate-300">
                                <li class="flex gap-3">
                                    <span class="text-emerald-400">✓</span>
                                    Add and update room information
                                </li>

                                <li class="flex gap-3">
                                    <span class="text-emerald-400">✓</span>
                                    Control availability and maintenance
                                </li>

                                <li class="flex gap-3">
                                    <span class="text-emerald-400">✓</span>
                                    Work with validated and audited records
                                </li>
                            </ul>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Call to action -->
            <section class="bg-zinc-100 py-20 dark:bg-zinc-900 sm:py-24">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div
                        class="rounded-[2rem] bg-slate-950 px-6 py-12
                               text-center text-white shadow-xl
                               sm:px-12 sm:py-16"
                    >
                        <div class="mx-auto max-w-2xl">
                            <p
                                class="text-sm font-bold uppercase
                                       tracking-[0.18em] text-emerald-300"
                            >
                                Ready When You Are
                            </p>

                            <h2
                                class="mt-3 text-3xl font-bold tracking-tight
                                       text-white sm:text-4xl"
                            >
                                Make your next library visit effortless.
                            </h2>

                            <p class="mt-4 text-lg leading-8 text-slate-300">
                                Sign in to explore available rooms and access
                                your Smart Library workspace.
                            </p>

                            <div
                                class="mt-8 flex flex-col justify-center
                                       gap-3 sm:flex-row"
                            >
                                @auth
                                    <a
                                        href="{{ route('rooms.index') }}"
                                        wire:navigate.hover
                                        class="inline-flex min-h-12
                                               items-center justify-center
                                               rounded-xl bg-emerald-500
                                               px-6 font-semibold text-white
                                               transition hover:bg-emerald-400"
                                    >
                                        Browse Rooms
                                    </a>
                                @else
                                    @if (Route::has('login'))
                                        <a
                                            href="{{ route('login') }}"
                                            wire:navigate.hover
                                            class="inline-flex min-h-12
                                                   items-center justify-center
                                                   rounded-xl bg-emerald-500
                                                   px-6 font-semibold text-white
                                                   transition
                                                   hover:bg-emerald-400"
                                        >
                                            Log In To Continue
                                        </a>
                                    @endif

                                    @if (Route::has('register'))
                                        <a
                                            href="{{ route('register') }}"
                                            wire:navigate.hover
                                            class="inline-flex min-h-12
                                                   items-center justify-center
                                                   rounded-xl border
                                                   border-white/20
                                                   bg-white/5 px-6
                                                   font-semibold text-white
                                                   transition hover:bg-white/10"
                                        >
                                            Create Account
                                        </a>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer
            class="border-t border-zinc-200 bg-white
                   dark:border-zinc-700 dark:bg-zinc-950"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col gap-3 px-5
                       py-8 text-sm text-zinc-500
                       dark:text-zinc-400 sm:flex-row
                       sm:items-center sm:justify-between
                       sm:px-8 lg:px-10"
            >
                <p
                    class="font-semibold text-zinc-800
                           dark:text-zinc-200"
                >
                    Smart Library System
                </p>

                <p>
                    &copy; {{ now()->year }} Smart Library.
                    Built for better learning.
                </p>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>