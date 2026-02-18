<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Live CDR
Route::get('/cdr/live', \App\Livewire\LiveCDRTable::class)
    ->middleware(['auth'])->name('cdr.live');

// WebRTC Dialer
Route::get('/dialer', \App\Livewire\WebRTCDialer::class)
    ->middleware(['auth'])->name('dialer');
