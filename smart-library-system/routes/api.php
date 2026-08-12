<?php

use App\Http\Controllers\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Default authenticated user API
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Room Booking Web Service
Route::get(
    '/bookings/availability',
    [BookingController::class, 'apiAvailability']
)->name('api.bookings.availability');