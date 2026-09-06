<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\RoomAvailabilityApiController;
use App\Http\Controllers\RoomReservationApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

/*
|--------------------------------------------------------------------------
| Version 1 APIs
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Book APIs
    |--------------------------------------------------------------------------
    */

    Route::get('/books', [BookController::class, 'index'])->name('api.books.index');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('api.books.show');

    Route::get(
        '/borrowings/active-counts',
        [BorrowingController::class, 'getActiveCounts']
    )->name('api.borrowings.active-counts');

    /*
    |--------------------------------------------------------------------------
    | Room Availability API
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/rooms/availability',
        [
            RoomAvailabilityApiController::class,
            'index',
        ]
    )->name('api.rooms.availability');

    /*
    |--------------------------------------------------------------------------
    | Room Reservation Information API
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/room-reservations',
        [
            RoomReservationApiController::class,
            'index',
        ]
    )->name('api.room-reservations.index');

    /*
     |--------------------------------------------------------------------------
     | Protected APIs
     |--------------------------------------------------------------------------
     */

    Route::middleware(['auth:sanctum', 'manage-books'])->group(function () {
        Route::post('/books', [BookController::class, 'store'])->name('api.books.store');
        Route::match(['put', 'patch'], '/books/{book}', [BookController::class, 'update'])->name('api.books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('api.books.destroy');
    });

});
