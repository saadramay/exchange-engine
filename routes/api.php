<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TradeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/my/orders', [OrderController::class, 'myOrders']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{order}/match', [OrderController::class, 'match']);

    Route::get('/trades', [TradeController::class, 'index']);
    Route::get('/my/trades', [TradeController::class, 'myTrades']);

});
