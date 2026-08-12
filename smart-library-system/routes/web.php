<?php

use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;
use App\Models\Room;


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
        'totalRoomsCount'
    ));
})->name('home');






Route::middleware(['auth', 'verified'])->group(function () {


    Route::get('dashboard', function () {

        $statusCounts = Room::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');


        $roomStats = [
            'total' => $statusCounts->sum(),
            'available' => $statusCounts->get('available', 0),
            'unavailable' => $statusCounts->get('unavailable', 0),
            'maintenance' => $statusCounts->get('maintenance', 0),
        ];


        $recentRooms = Room::query()
            ->with('creator')
            ->latest('updated_at')
            ->limit(5)
            ->get();


        return view('dashboard', compact(
            'roomStats',
            'recentRooms'
        ));
    })->name('dashboard');






    // Room Management
    Route::resource(
        'rooms',
        RoomController::class
    );








    // Room Booking
    Route::prefix('bookings')
        ->name('bookings.')
        ->group(function () {


            // View booking history
            Route::get(
                '/',
                [BookingController::class, 'index']
            )->name('index');




            // Show booking form
            Route::get(
                '/create',
                [BookingController::class, 'create']
            )->name('create');




            // Check room availability
            Route::get(
                '/availability',
                [BookingController::class, 'availability']
            )->name('availability');




            // Create booking
            Route::post(
                '/',
                [BookingController::class, 'store']
            )->name('store');




            // Edit booking
            Route::get(
                '/{booking}/edit',
                [BookingController::class, 'edit']
            )->name('edit');




            // Update booking
            Route::patch(
                '/{booking}',
                [BookingController::class, 'update']
            )->name('update');




            // Cancel booking
            Route::patch(
                '/{booking}/cancel',
                [BookingController::class, 'cancel']
            )->name('cancel');
        });









    // Borrowing Management
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






    require __DIR__ . '/settings.php';
});
