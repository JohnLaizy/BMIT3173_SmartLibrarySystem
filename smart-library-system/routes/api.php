<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\RoomAvailabilityApiController;
use App\Http\Controllers\RoomReservationApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentInformationApiController;
use App\Http\Controllers\UserStatusApiController;

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
     | Payment Information API
     |--------------------------------------------------------------------------
     */

     Route::get(
       '/payments/users/{user}/status',
       [PaymentInformationApiController::class, 'show']
    )->name('api.payments.users.status');


     /*
     |--------------------------------------------------------------------------
     | User Management APIs
     |--------------------------------------------------------------------------
     */

    Route::get(
      '/users/{user}/account-status',
       [UserStatusApiController::class, 'show']
    )->name('api.users.account-status');



    /*
    |--------------------------------------------------------------------------
    | Public Book APIs
    |--------------------------------------------------------------------------
    */

    Route::get('/books', [BookController::class, 'index'])
        ->name('api.books.index');

    Route::get('/books/{book}', [BookController::class, 'show'])
        ->name('api.books.show');

    /*
    |--------------------------------------------------------------------------
    | Public Borrow & Return APIs
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/borrowings/active-counts',
        [BorrowingController::class, 'getActiveCounts']
    )->name('api.borrowings.active-counts');

    // No token: reads the Book Management JSON API.
    Route::get(
        '/borrowings/books',
        [BorrowingController::class, 'apiBookCatalog']
    )->name('api.borrowings.books');

    /*
    |--------------------------------------------------------------------------
    | Protected Borrow & Return APIs
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')
        ->prefix('borrowings')
        ->group(function () {
            Route::get('/', [BorrowingController::class, 'apiIndex'])
                ->name('api.borrowings.index');

            Route::post('/', [BorrowingController::class, 'apiStore'])
                ->name('api.borrowings.store');

            Route::patch(
                '/{borrowing}/return',
                [BorrowingController::class, 'apiReturn']
            )->name('api.borrowings.return');
        });

    /*
    |--------------------------------------------------------------------------
    | Room APIs
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/rooms/availability',
        [RoomAvailabilityApiController::class, 'index']
    )->name('api.rooms.availability');

    Route::get(
        '/room-reservations',
        [RoomReservationApiController::class, 'index']
    )->name('api.room-reservations.index');

    /*
    |--------------------------------------------------------------------------
    | Protected Book Management APIs
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:sanctum', 'manage-books'])->group(function () {
        Route::post('/books', [BookController::class, 'store'])
            ->name('api.books.store');

        Route::match(['put', 'patch'], '/books/{book}', [BookController::class, 'update'])
            ->name('api.books.update');

        Route::delete('/books/{book}', [BookController::class, 'destroy'])
            ->name('api.books.destroy');
    });
});