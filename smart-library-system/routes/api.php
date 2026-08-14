<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomAvailabilityApiController;
use Illuminate\Http\Request;
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

    Route::get(
        '/rooms/availability',
        [
            RoomAvailabilityApiController::class,
            'index',
        ]
    )->name('api.rooms.availability');

    /*
    |--------------------------------------------------------------------------
    | Protected APIs
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')
        ->group(function () {
            Route::post(
                '/books',
                [BookController::class, 'store']
            );
        });
});

/*
|--------------------------------------------------------------------------
| Authenticated User API
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Room Booking Availability API
|--------------------------------------------------------------------------
*/

Route::get(
    '/bookings/availability',
    [
        BookingController::class,
        'apiAvailability',
    ]
)->name('api.bookings.availability');