<x-layouts::app :title="__('Book Room')">

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-8 px-4 py-8">


    {{-- Page Header --}}
    <div>

        <flux:heading size="xl">
            Book Room
        </flux:heading>


        <flux:text class="mt-2">
            Reserve a library room for your study session.
        </flux:text>

    </div>




{{-- Error Message --}}
@if(session('error') || $errors->any())

<div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-700">

    <div class="flex items-center gap-3">

        <span class="text-xl">
            ❌
        </span>

        <div>

            <p class="font-semibold">
                Booking Failed
            </p>


            @if(session('error'))

                <p class="text-sm">
                    {{ session('error') }}
                </p>

            @endif


            @foreach($errors->all() as $error)

                <p class="text-sm">
                    {{ $error }}
                </p>

            @endforeach


        </div>

    </div>

</div>

@endif




    {{-- Booking Form Card --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">


        <form method="POST" action="/bookings">

            @csrf



            {{-- Select Room --}}
            <div class="mb-5">


                <label class="mb-2 block text-sm font-medium text-zinc-700">
                    Select Room
                </label>



                <select
                    name="room_id"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2"
                    required
                >


                    @foreach($rooms as $room)

                        <option value="{{ $room->id }}">

                            {{ $room->name }}

                        </option>


                    @endforeach


                </select>


            </div>





            {{-- Booking Date --}}
            <div class="mb-5">


                <label class="mb-2 block text-sm font-medium text-zinc-700">
                    Booking Date
                </label>



                <input
                    type="date"
                    name="booking_date"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2"
                    required
                >


            </div>







            {{-- Time --}}
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">


                {{-- Start Time --}}
                <div>


                    <label class="mb-2 block text-sm font-medium text-zinc-700">
                        Start Time
                    </label>



                    <input
                        type="time"
                        name="start_time"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2"
                        required
                    >


                </div>





                {{-- End Time --}}
                <div>


                    <label class="mb-2 block text-sm font-medium text-zinc-700">
                        End Time
                    </label>



                    <input
                        type="time"
                        name="end_time"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2"
                        required
                    >


                </div>


            </div>







                     <button
                type="submit"
                style="
                    margin-top:20px;
                    background:black;
                    color:white;
                    padding:12px 30px;
                    border-radius:8px;
                "
            >
                Book Room
            </button>


        </form>


    </div>



</div>


</x-layouts::app>