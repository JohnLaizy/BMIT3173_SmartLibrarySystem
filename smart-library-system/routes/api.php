<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\RoomAvailabilityApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Public Query APIs
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/books',
        [BookController::class, 'index']
    );

    Route::get(
        '/books/{id}',
        [BookController::class, 'show']
    );

    /*
    |--------------------------------------------------------------------------
    | Room Availability Web Service
    |--------------------------------------------------------------------------
    |
    | Provides available room information to other systems/modules.
    |
    */

    Route::get(
        '/rooms/availability',
        [RoomAvailabilityApiController::class, 'index']
    )->name('api.rooms.availability');

    /*
    |--------------------------------------------------------------------------
    | Protected APIs
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post(
            '/books',
            [BookController::class, 'store']
        );
    });
});


// Default authenticated user API
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Room Booking Web Service
Route::get(
    '/bookings/availability',
    [BookingController::class, 'apiAvailability']
)->name('api.bookings.availability');
