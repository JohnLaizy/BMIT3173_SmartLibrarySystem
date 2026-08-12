<?php

use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 公开查询接口
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{id}', [BookController::class, 'show']);

    // 需要认证与权限的写入接口
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/books', [BookController::class, 'store']);
    });
});