<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomAvailabilityApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Existing API Routes
|--------------------------------------------------------------------------
*/

// Default authenticated user API
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Existing Room Booking Web Service
Route::get(
    '/bookings/availability',
    [BookingController::class, 'apiAvailability']
)->name('api.bookings.availability');


/*
|--------------------------------------------------------------------------
| Version 1 APIs
|--------------------------------------------------------------------------
*/

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