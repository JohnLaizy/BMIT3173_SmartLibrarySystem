<?php

use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view(
        'dashboard',
        'dashboard'
    )->name('dashboard');

    Route::resource(
        'rooms',
        RoomController::class
    );

    Route::prefix('borrowings')
        ->name('borrowings.')
        ->group(function () {
            Route::get(
                '/',
                [BorrowingController::class, 'index']
            )->name('index');

            Route::post(
                '/',
                [BorrowingController::class, 'store']
            )
                ->middleware('throttle:20,1')
                ->name('store');

            Route::patch(
                '/{borrowing}/return',
                [BorrowingController::class, 'returnCopy']
            )->name('return');

            Route::post(
                '/{borrowing}/payment',
                [BorrowingController::class, 'submitPayment']
            )
                ->middleware('throttle:10,1')
                ->name('payment.submit');

            Route::patch(
                '/{borrowing}/payment/approve',
                [BorrowingController::class, 'approvePayment']
            )->name('payment.approve');

            Route::patch(
                '/{borrowing}/payment/reject',
                [BorrowingController::class, 'rejectPayment']
            )->name('payment.reject');
        });
});

require __DIR__.'/settings.php';