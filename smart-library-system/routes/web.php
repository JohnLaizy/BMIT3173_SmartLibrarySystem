<?php

use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\LibrarySettingController;
use App\Http\Controllers\RoomAvailabilityController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomDashboardController;
use App\Http\Controllers\RoomMaintenanceController;
use App\Http\Controllers\RoomReservationController;
use App\Models\Room;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Welcome Page
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $rooms = Room::query()
        ->orderBy('room_number')
        ->limit(3)
        ->get();

    $availableRoomsCount = Room::query()
        ->where('status', 'available')
        ->count();

    $totalRoomsCount = Room::query()->count();

    return view('welcome', compact(
        'rooms',
        'availableRoomsCount',
        'totalRoomsCount',
    ));
})->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Room Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        'dashboard',
        RoomDashboardController::class
    )->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Room Availability
    |--------------------------------------------------------------------------
    */

    Route::get(
        'room-availability',
        RoomAvailabilityController::class
    )->name('room-availability.index');

    /*
    |--------------------------------------------------------------------------
    | Library Operating Hours
    |--------------------------------------------------------------------------
    */

    Route::patch(
        'library-settings/exam-period',
        LibrarySettingController::class
    )
        ->middleware('throttle:10,1')
        ->name('library-settings.exam-period.update');

    /*
    |--------------------------------------------------------------------------
    | Room Reservations
    |--------------------------------------------------------------------------
    */
    Route::get(
        'room-reservations',
        [
            RoomReservationController::class,
            'index',
        ]
    )->name('room-reservations.index');

    Route::get(
        'room-reservations/create',
        [
            RoomReservationController::class,
            'create',
        ]
    )->name('room-reservations.create');

    Route::post(
        'room-reservations',
        [
            RoomReservationController::class,
            'store',
        ]
    )->name('room-reservations.store');

    Route::patch(
        'room-reservations/{reservation}/cancel',
        [
            RoomReservationController::class,
            'cancel',
        ]
    )->name('room-reservations.cancel');

    /*
    |--------------------------------------------------------------------------
    | Room Management
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'rooms',
        RoomController::class
    );

    /*
    |--------------------------------------------------------------------------
    | Room Maintenance Management
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'maintenances',
        RoomMaintenanceController::class
    )->except('show');

    /*
    |--------------------------------------------------------------------------
    | Borrow & Return
    |--------------------------------------------------------------------------
    */

    Route::prefix('borrowings')
        ->name('borrowings.')
        ->group(function () {
            Route::get(
                '/',
                [
                    BorrowingController::class,
                    'index',
                ]
            )->name('index');

            Route::post(
                '/',
                [
                    BorrowingController::class,
                    'store',
                ]
            )
                ->middleware('throttle:20,1')
                ->name('store');

            Route::patch(
                '/{borrowing}/return',
                [
                    BorrowingController::class,
                    'returnCopy',
                ]
            )->name('return');

            Route::post(
                '/{borrowing}/payment',
                [
                    BorrowingController::class,
                    'submitPayment',
                ]
            )
                ->middleware('throttle:10,1')
                ->name('payment.submit');

            Route::patch(
                '/{borrowing}/payment/approve',
                [
                    BorrowingController::class,
                    'approvePayment',
                ]
            )->name('payment.approve');

            Route::patch(
                '/{borrowing}/payment/reject',
                [
                    BorrowingController::class,
                    'rejectPayment',
                ]
            )->name('payment.reject');
        });

        Route::patch(
            'books/{book}/copies',
            [
                BorrowingController::class,
                'updateCopyQuantity',
            ]
        )
            ->middleware('throttle:20,1')
            ->name('books.copies.update');
        });

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';
