<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/exchange/order', fn() => Inertia::render('LimitOrder'))->name('exchange.order');
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
});


require __DIR__ . '/settings.php';
