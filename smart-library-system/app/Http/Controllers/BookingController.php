<?php

namespace App\Http\Controllers;

use App\Models\Booking;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with([
            'user',
            'room'
        ])->get();

        return view('bookings.index', compact('bookings'));
    }

    public function store()
    {
        //
    }
}