<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="dark scroll-smooth"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta
            name="description"
            content="Discover library resources and check study-room availability."
        >

        <title>Smart Library System</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="min-h-screen bg-zinc-800 font-sans text-white antialiased">

        <!-- Navigation -->
            <header
                class="sticky top-0 z-40 border-b border-zinc-700
                    bg-zinc-900/95 backdrop-blur"
                >
            <div
                class="mx-auto flex h-18 max-w-7xl items-center
                       justify-between px-5 sm:px-8 lg:px-10"
            >
                <a
                    href="{{ route('home') }}"
                    class="flex items-center gap-3"
                >
                    <span
                        class="grid size-10 place-items-center rounded-xl
                               bg-emerald-700 text-white shadow-sm"
                    >
                        <svg
                            class="size-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h3.25A2.25
                                   2.25 0 0 1 13 5.25V20a3.5 3.5 0 0 0-3.5-3.5
                                   H4.75V5.75ZM19.25 5.75A2.75 2.75 0 0 0
                                   16.5 3h-1.25A2.25 2.25 0 0 0 13 5.25V20
                                   a3.5 3.5 0 0 1 3.5-3.5h2.75V5.75Z"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>

                    <span>
                        <span class="block font-bold leading-tight">
                            Smart Library
                        </span>

                        <span class="block text-xs text-zinc-400">
                            Learn. Discover. Connect.
                        </span>
                    </span>
                </a>

                <nav
                    class="hidden items-center gap-8 text-sm font-medium
                           text-zinc-300 md:flex"
                >
                    <a
                        href="#features"
                        class="transition hover:text-emerald-700"
                    >
                        Features
                    </a>

                    <a
                        href="#rooms"
                        class="transition hover:text-emerald-700"
                    >
                        Rooms
                    </a>

                    <a
                        href="#roles"
                        class="transition hover:text-emerald-700"
                    >
                        For Everyone
                    </a>
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-flex min-h-11 items-center
                                   justify-center rounded-xl border
                                   border-zinc-600 bg-zinc-900 px-4 text-sm
                                   font-semibold text-zinc-200 shadow-sm
                                   transition hover:bg-zinc-900"
                        >
                            Dashboard
                        </a>

                        <a
                            href="{{ route('rooms.index') }}"
                            class="hidden min-h-11 items-center justify-center
                                   rounded-xl bg-emerald-700 px-4 text-sm
                                   font-semibold text-white shadow-sm
                                   transition hover:bg-emerald-800 sm:inline-flex"
                        >
                            View Rooms
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex min-h-11 items-center
                                       justify-center rounded-xl px-4 text-sm
                                       font-semibold text-zinc-200
                                       transition hover:bg-slate-100"
                            >
                                Log In
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex min-h-11 items-center
                                       justify-center rounded-xl bg-emerald-700
                                       px-4 text-sm font-semibold text-white
                                       shadow-sm transition
                                       hover:bg-emerald-800"
                            >
                                Join Library
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <!-- Hero -->
            <section class="relative overflow-hidden border-b border-zinc-700">

                <!-- Decorative circles -->
                <div
                    class="pointer-events-none absolute -end-48 -top-56
                           size-[34rem] rounded-full border-[72px]
                           border-emerald-100/70"
                    aria-hidden="true"
                ></div>

                <div
                    class="pointer-events-none absolute -bottom-56 -start-40
                           size-[30rem] rounded-full border-[64px]
                           border-amber-100/80"
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
                                   font-semibold text-emerald-800"
                        >
                            <span
                                class="size-2 rounded-full bg-emerald-500"
                            ></span>

                            Your library, made simpler
                        </div>

                        <h1
                            class="text-4xl font-bold leading-[1.08]
                                   tracking-tight text-white
                                   sm:text-5xl lg:text-6xl"
                        >
                            A smarter way to
                            <span class="text-emerald-700">
                                learn and connect.
                            </span>
                        </h1>

                        <p
                            class="mt-6 max-w-xl text-lg leading-8
                                   text-zinc-300"
                        >
                            Discover library resources, check study-room
                            availability, and manage every visit from one
                            clear and secure workspace.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a
                                    href="{{ route('rooms.index') }}"
                                    class="inline-flex min-h-12 items-center
                                           justify-center gap-2 rounded-xl
                                           bg-emerald-700 px-6 text-base
                                           font-semibold text-white shadow-lg
                                           transition hover:-translate-y-0.5
                                           hover:bg-emerald-800"
                                >
                                    Explore Available Rooms
                                    <span aria-hidden="true">→</span>
                                </a>
                            @else
                                <a
                                    href="{{ Route::has('login')
                                        ? route('login')
                                        : '#features' }}"
                                    class="inline-flex min-h-12 items-center
                                           justify-center gap-2 rounded-xl
                                           bg-emerald-700 px-6 text-base
                                           font-semibold text-white shadow-lg
                                           transition hover:-translate-y-0.5
                                           hover:bg-emerald-800"
                                >
                                    Get Started
                                    <span aria-hidden="true">→</span>
                                </a>
                            @endauth

                            <a
                                href="#features"
                                class="inline-flex min-h-12 items-center
                                       justify-center rounded-xl border
                                       border-zinc-600 bg-zinc-900 px-6
                                       text-base font-semibold text-zinc-200
                                       shadow-sm transition hover:bg-zinc-900"
                            >
                                See How It Works
                            </a>
                        </div>

                        <!-- Statistics -->
                        <dl
                            class="mt-10 grid max-w-xl grid-cols-3 gap-4
                                   border-t border-zinc-700 pt-6"
                        >
                            <div>
                                <dt
                                    class="text-xs font-medium uppercase
                                           tracking-wider text-zinc-400"
                                >
                                    Access
                                </dt>

                                <dd class="mt-1 text-lg font-bold">
                                    24 / 7
                                </dd>
                            </div>

                            <div>
                                <dt
                                    class="text-xs font-medium uppercase
                                           tracking-wider text-zinc-400"
                                >
                                    Rooms
                                </dt>

                                <dd class="mt-1 text-lg font-bold">
                                    Live Status
                                </dd>
                            </div>

                            <div>
                                <dt
                                    class="text-xs font-medium uppercase
                                           tracking-wider text-zinc-400"
                                >
                                    Accounts
                                </dt>

                                <dd class="mt-1 text-lg font-bold">
                                    Secured
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Room preview -->
                    <div
                        id="rooms"
                        class="relative mx-auto w-full max-w-xl
                               scroll-mt-28 lg:mx-0 lg:ms-auto"
                    >
                        <div
                            class="absolute -inset-4 -z-10 rotate-2
                                   rounded-[2rem] bg-emerald-100/70"
                        ></div>

                        <div
                            class="overflow-hidden rounded-[1.75rem]
                                   border border-slate-700 bg-slate-950
                                   shadow-2xl shadow-slate-950/20"
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

                                    <p class="mt-0.5 text-xs text-zinc-400">
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
                                           bg-zinc-900/[0.06] p-4"
                                >
                                    <p
                                        class="text-xs font-semibold
                                               text-emerald-300"
                                    >
                                        AVAILABLE ROOMS
                                    </p>

                                    <p
                                        class="mt-3 text-3xl font-bold text-white"
                                    >
                                        {{ $availableRoomsCount }}
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-400">
                                        Out of {{ $totalRoomsCount }} rooms
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-400">
                                        Across three floors
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-white/10
                                           bg-zinc-900/[0.06] p-4"
                                >
                                    <p
                                        class="text-xs font-semibold
                                               text-amber-200"
                                    >
                                        CATALOGUE
                                    </p>

                                    <p
                                        class="mt-3 text-3xl font-bold
                                               text-white"
                                    >
                                        1,240
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-400">
                                        Organized titles
                                    </p>
                                </div>
                            </div>

                            <!-- Room listing preview -->
                            <div class="px-5 pb-5 sm:px-6 sm:pb-6">
                                <div class="rounded-2xl border border-zinc-700 bg-zinc-900 p-4 shadow-sm">
                                    <div
                                        class="mb-3 flex items-center
                                               justify-between"
                                    >
                                        <p class="text-sm font-bold">
                                            Room Availability
                                        </p>

                                        @auth
                                            <a
                                                href="{{ route('rooms.index') }}"
                                                class="text-xs font-semibold
                                                       text-emerald-700"
                                            >
                                                View All
                                            </a>
                                        @else
                                            <span class="text-xs text-zinc-400">
                                                Updated now
                                            </span>
                                        @endauth
                                    </div>

                                  <div class="space-y-2.5">
                                     @forelse ($rooms as $room)
                                        <div
                                            class="flex items-center justify-between gap-4
                                                rounded-xl bg-zinc-800 px-3.5 py-3"
                                        >
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold">
                                                {{ $room->room_number }} · {{ $room->name }}
                                            </p>

                                            <p class="mt-0.5 text-xs text-zinc-400">
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
                                        class="rounded-xl bg-zinc-900 px-4 py-6
                                            text-center text-sm text-zinc-400"
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
                    </div>
                </div>
            </section>

            <!-- Features -->
            <section
                   id="features"
                   class="scroll-mt-24 bg-zinc-800 py-20 sm:py-24"
                >
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div class="max-w-2xl">
                        <p
                            class="text-sm font-bold uppercase
                                   tracking-[0.18em] text-emerald-700"
                        >
                            Everything In One Place
                        </p>

                        <h2
                            class="mt-3 text-3xl font-bold tracking-tight
                                   sm:text-4xl"
                        >
                            Designed around real library needs.
                        </h2>

                        <p class="mt-4 text-lg leading-8 text-zinc-300">
                            Find what you need faster while librarians keep
                            resources accurate, secure and easy to manage.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-5 md:grid-cols-3">
                        <article
                            class="rounded-2xl border border-zinc-700
                                   bg-zinc-900 p-6 transition
                                   hover:-translate-y-1
                                   hover:border-emerald-500
                                   hover:shadow-lg"
                        >
                            <span class="text-sm font-bold text-emerald-700">
                                01
                            </span>

                            <h3 class="mt-8 text-xl font-bold">
                                Live Room Status
                            </h3>

                            <p class="mt-3 leading-7 text-zinc-300">
                                Check room capacity, location, facilities and
                                availability before visiting the library.
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-zinc-700
                                   bg-zinc-900 p-6 transition
                                   hover:-translate-y-1
                                   hover:border-emerald-500
                                   hover:shadow-lg"
                        >
                            <span class="text-sm font-bold text-amber-700">
                                02
                            </span>

                            <h3 class="mt-8 text-xl font-bold">
                                Organized Resources
                            </h3>

                            <p class="mt-3 leading-7 text-zinc-300">
                                Keep titles, copy counts and library spaces
                                organized in one consistent system.
                            </p>
                        </article>

                        <article
                            class="rounded-2xl border border-zinc-700
                                   bg-zinc-900 p-6 transition
                                   hover:-translate-y-1
                                   hover:border-emerald-200
                                   hover:shadow-lg"
                        >
                            <span class="text-sm font-bold text-sky-700">
                                03
                            </span>

                            <h3 class="mt-8 text-xl font-bold">
                                Secure By Role
                            </h3>

                            <p class="mt-3 leading-7 text-zinc-300">
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
                class="scroll-mt-24 border-y border-zinc-700
                       bg-emerald-50/60 py-20 sm:py-24"
            >
                <div
                    class="mx-auto grid max-w-7xl gap-10 px-5
                           sm:px-8 lg:grid-cols-[0.8fr_1.2fr]
                           lg:items-center lg:px-10"
                >
                    <div>
                        <p
                            class="text-sm font-bold uppercase
                                   tracking-[0.18em] text-emerald-700"
                        >
                            Built For Everyone
                        </p>

                        <h2
                            class="mt-3 text-3xl font-bold tracking-tight
                                   sm:text-4xl"
                        >
                            The right tools for every library role.
                        </h2>

                        <p class="mt-4 text-lg leading-8 text-zinc-300">
                            A focused experience keeps common tasks simple
                            without exposing controls to the wrong users.
                        </p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <!-- Student -->
                        <article
                            class="rounded-2xl border border-emerald-200
                                   bg-zinc-900 p-6 shadow-sm"
                        >
                            <span
                                class="inline-flex rounded-full bg-emerald-100
                                       px-3 py-1 text-sm font-bold
                                       text-emerald-800"
                            >
                                Students
                            </span>

                            <h3 class="mt-5 text-xl font-bold">
                                Find a place to focus.
                            </h3>

                            <ul class="mt-5 space-y-3 text-sm text-zinc-300">
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

                        <!-- Librarian -->
                        <article
                            class="rounded-2xl border border-slate-800
                                   bg-slate-950 p-6 text-white shadow-lg"
                        >
                            <span
                                class="inline-flex rounded-full bg-zinc-900/10
                                       px-3 py-1 text-sm font-bold
                                       text-emerald-300"
                            >
                                Librarians
                            </span>

                            <h3 class="mt-5 text-xl font-bold">
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
            <section class="bg-zinc-900 py-20 sm:py-24">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div
                        class="rounded-[2rem] bg-slate-950 px-6 py-12
                               text-center shadow-xl sm:px-12 sm:py-16"
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

                            <p
                                class="mt-4 text-lg leading-8
                                       text-slate-300"
                            >
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
                                        class="inline-flex min-h-12
                                               items-center justify-center
                                               rounded-xl bg-emerald-500
                                               px-6 font-semibold
                                               text-white transition
                                               hover:bg-emerald-400"
                                    >
                                        Browse Rooms
                                    </a>
                                @else
                                    @if (Route::has('login'))
                                        <a
                                            href="{{ route('login') }}"
                                            class="inline-flex min-h-12
                                                   items-center justify-center
                                                   rounded-xl bg-emerald-500
                                                   px-6 font-semibold
                                                   text-white transition
                                                   hover:bg-emerald-400"
                                        >
                                            Log In To Continue
                                        </a>
                                    @endif

                                    @if (Route::has('register'))
                                        <a
                                            href="{{ route('register') }}"
                                            class="inline-flex min-h-12
                                                   items-center justify-center
                                                   rounded-xl border
                                                   border-white/20
                                                   bg-zinc-900/5 px-6
                                                   font-semibold text-white
                                                   transition
                                                   hover:bg-zinc-900/10"
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
       <footer class="border-t border-zinc-700 bg-zinc-900">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-3 px-5
                       py-8 text-sm text-zinc-400 sm:flex-row
                       sm:items-center sm:justify-between
                       sm:px-8 lg:px-10"
            >
                <p class="font-semibold text-zinc-200">
                    Smart Library System
                </p>

                <p>
                    &copy; {{ now()->year }} Smart Library.
                    Built for better learning.
                </p>
            </div>
        </footer>
    </body>
</html>