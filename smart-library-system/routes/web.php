<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookReservationController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\LibrarySettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RoomAvailabilityController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomDashboardController;
use App\Http\Controllers\RoomMaintenanceController;
use App\Http\Controllers\RoomReservationController;
use App\Http\Controllers\UserManagementController;
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
    | Dashboard
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

    Route::get(
        'room-reservations/{reservation}/edit',
        [
            RoomReservationController::class,
            'edit',
        ]
    )->name('room-reservations.edit');

    Route::patch(
        'room-reservations/{reservation}',
        [
            RoomReservationController::class,
            'update',
        ]
    )->name('room-reservations.update');

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
    | Room Maintenance
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'maintenances',
        RoomMaintenanceController::class
    )->except('show');

    /*
    |--------------------------------------------------------------------------
    | Simulated Payment Management
    |--------------------------------------------------------------------------
    */

    Route::get('payments', [
        PaymentController::class,
        'index',
    ])->name('payments.index');

    Route::get('payments/{borrowing}', [
        PaymentController::class,
        'show',
    ])->name('payments.show');

    Route::get('payments/{borrowing}/receipt', [
        PaymentController::class,
        'receipt',
    ])->name('payments.receipt');

    Route::post('payments/{borrowing}/start', [
        PaymentController::class,
        'start',
    ])
        ->middleware('throttle:10,1')
        ->name('payments.start');

    Route::post('payments/{borrowing}/complete', [
        PaymentController::class,
        'complete',
    ])
        ->middleware('throttle:10,1')
        ->name('payments.complete');

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

            Route::post(
                '/{borrowing}/renewal',
                [
                    BorrowingController::class,
                    'requestRenewal',
                ]
            )
                ->middleware('throttle:10,1')
                ->name('renewal.request');

            Route::patch(
                '/{borrowing}/renewal/approve',
                [
                    BorrowingController::class,
                    'approveRenewal',
                ]
            )->name('renewal.approve');

            Route::patch(
                '/{borrowing}/renewal/reject',
                [
                    BorrowingController::class,
                    'rejectRenewal',
                ]
            )->name('renewal.reject');
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

    /*
    |--------------------------------------------------------------------------
    | Book Reservations
    |--------------------------------------------------------------------------
    */

    Route::prefix('book-reservations')
        ->name('book-reservations.')
        ->group(function () {

            Route::get(
                '/',
                [
                    BookReservationController::class,
                    'index',
                ]
            )->name('index');

            Route::post(
                '/',
                [
                    BookReservationController::class,
                    'store',
                ]
            )
                ->middleware('throttle:10,1')
                ->name('store');

            Route::patch(
                '/{reservation}/collect',
                [
                    BookReservationController::class,
                    'collect',
                ]
            )->name('collect');

            Route::patch(
                '/{reservation}/approve',
                [
                    BookReservationController::class,
                    'approve',
                ]
            )->name('approve');

            Route::patch(
                '/{reservation}/reject',
                [
                    BookReservationController::class,
                    'reject',
                ]
            )->name('reject');

            Route::patch(
                '/{reservation}/cancel',
                [
                    BookReservationController::class,
                    'cancel',
                ]
            )->name('cancel');
        });

    /*
    |--------------------------------------------------------------------------
    | Book Management
    |--------------------------------------------------------------------------
    */
// All logged-in users, including students, can browse books.
Route::resource('books', BookController::class)
    ->only(['index', 'show']);

// Only non-students can create, edit, update, or delete books.
Route::middleware('manage-books')->group(function () {
    Route::resource('books', BookController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy']);
});

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */

    Route::get(
        'users',
        [
            UserManagementController::class,
            'index',
        ]
    )->name('users.index');

    Route::get(
        'users/{user}/edit',
        [
            UserManagementController::class,
            'edit',
        ]
    )->name('users.edit');

    Route::patch(
        'users/{user}',
        [
            UserManagementController::class,
            'update',
        ]
    )->name('users.update');
});

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/settings.php';
