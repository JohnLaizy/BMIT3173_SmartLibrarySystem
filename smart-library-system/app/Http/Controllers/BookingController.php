<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Services\Booking\StandardBookingStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    // View student's booking history
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->with('room')
            ->get();

        return view('bookings.index', compact('bookings'));
    }

    // Show booking form
    public function create()
    {
        $rooms = Room::where('status', 'available')->get();

        return view('bookings.create', compact('rooms'));
    }

    // Check available rooms based on date and time
    public function availability(Request $request)
    {
        $rooms = collect();

        if ($request->filled([
            'booking_date',
            'start_time',
            'end_time',
        ])) {
            $bookedRoomIds = Booking::where('booking_date', $request->booking_date)
                ->where('status', 'confirmed')
                ->where(function ($query) use ($request) {
                    $query->where('start_time', '<', $request->end_time)
                        ->where('end_time', '>', $request->start_time);
                })
                ->pluck('room_id');

            $rooms = Room::where('status', 'available')
                ->whereNotIn('id', $bookedRoomIds)
                ->get();
        }

        return view('bookings.availability', compact('rooms'));
    }

    // Provide Web Service: return available rooms as JSON
    public function apiAvailability(Request $request)
    {
        $request->validate([
            'booking_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'request_id' => 'required|string',
        ]);

        $bookedRoomIds = Booking::where('booking_date', $request->booking_date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->end_time)
                    ->where('end_time', '>', $request->start_time);
            })
            ->pluck('room_id');

        $rooms = Room::where('status', 'available')
            ->whereNotIn('id', $bookedRoomIds)
            ->get([
                'id',
                'room_number',
                'name',
                'status',
            ]);

        return response()->json([
            'status' => 'success',
            'timestamp' => now()->toISOString(),
            'request_id' => $request->request_id,
            'available_rooms' => $rooms,
        ]);
    }

    // Create booking
    public function store(Request $request)
    {
        $request->validate(
            [
                'room_id' => 'required',
                'booking_date' => 'required|date',
                'start_time' => 'required',
                'end_time' => 'required|after:start_time',
            ],
            [
                'end_time.after' => 'End time must be later than start time.',
            ]
        );

        if ($request->start_time >= $request->end_time) {
            return back()->with(
                'error',
                'End time must be later than start time.'
            );
        }

        // Strategy Pattern: check room availability
        $strategy = new StandardBookingStrategy();

        $isAvailable = $strategy->isRoomAvailable(
            (int) $request->room_id,
            $request->booking_date,
            $request->start_time,
            $request->end_time
        );

        if (!$isAvailable) {
            return redirect()
                ->route('bookings.create')
                ->with(
                    'error',
                    'This room is already booked during this time.'
                );
        }

        Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'booking_date' => $request->booking_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => 'confirmed',
        ]);

        return redirect('/bookings')
            ->with('success', 'Room booked successfully');
    }

    // Show edit booking page
    public function edit(Booking $booking)
    {
        // Only allow owner
        if ($booking->user_id != Auth::id()) {
            abort(403);
        }

        return view('bookings.edit', compact('booking'));
    }

    // Update booking
    public function update(Request $request, Booking $booking)
    {
        // Only allow owner
        if ($booking->user_id != Auth::id()) {
            abort(403);
        }

        $request->validate([
            'booking_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        $booking->update([
            'booking_date' => $request->booking_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect('/bookings')
            ->with('success', 'Booking updated successfully');
    }

    // Cancel booking
    public function cancel(Booking $booking)
    {
        // Only allow owner
        if ($booking->user_id != Auth::id()) {
            abort(403);
        }

        $booking->update([
            'status' => 'cancelled',
        ]);

        return back()
            ->with('success', 'Booking cancelled successfully');
    }
}