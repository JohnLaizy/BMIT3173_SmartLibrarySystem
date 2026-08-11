<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
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

    public function store(Request $request)
{
$request->validate([
    'room_id' => 'required',
    'booking_date' => 'required|date',
    'start_time' => 'required',
    'end_time' => 'required|after:start_time',
], [
    'end_time.after' => 'End time must be later than start time.'
]);

    if ($request->start_time >= $request->end_time) {

    return back()->with(
        'error',
        'End time must be later than start time.'
    );

}


// Prevent double booking
$existingBooking = Booking::where('room_id', $request->room_id)
    ->where('booking_date', $request->booking_date)
    ->where('status', 'confirmed')
    ->where(function($query) use ($request) {

        $query->where('start_time', '<', $request->end_time)
              ->where('end_time', '>', $request->start_time);

    })
    ->exists();


if ($existingBooking) {

    return redirect()
        ->route('bookings.create')
        ->with('error',
        'This room is already booked during this time.');

}

    Booking::create([

        'user_id' => Auth::id(),

        'room_id' => $request->room_id,

        'booking_date' => $request->booking_date,

        'start_time' => $request->start_time,

        'end_time' => $request->end_time,

        'status' => 'confirmed'

    ]);


    return redirect('/bookings')
        ->with('success','Room booked successfully');

    }

    public function cancel(Booking $booking)
{
    // Only allow owner to cancel
    if ($booking->user_id != Auth::id()) {
        abort(403);
    }


    $booking->update([
        'status' => 'cancelled'
    ]);


    return back()
        ->with('success','Booking cancelled successfully');
}

}