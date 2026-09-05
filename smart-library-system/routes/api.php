<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomAvailabilityApiController;
use App\Http\Controllers\RoomReservationApiController;
use App\Http\Controllers\BorrowingController;
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

    Route::get(
        '/books',
        [BookController::class, 'index']
    );

    Route::get(
        '/books/{book}',
        [BookController::class, 'show']
    );

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

    Route::middleware('auth:sanctum')
        ->group(function () {

           
            Route::middleware('admin')->group(function () {
                
    
                Route::post(
                    '/books',
                    [BookController::class, 'store']
                );

         
                Route::put(
                    '/books/{book}',
                    [BookController::class, 'update']
                );

             
                Route::delete(
                    '/books/{book}',
                    [BookController::class, 'destroy']
                );

            });

        });


});
