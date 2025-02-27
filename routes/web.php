<?php

use App\Http\Middleware\Authenticate;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('auth')->group(function () {
	Route::get('/', function () {
	    return view('welcome');
	});
});


Route::middleware('guest')->group(function () {
    Route::get('/login', [UserController::class, 'login']);
});

