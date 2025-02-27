<?php

use Illuminate\Support\Facades\Route;
use app\Http\Middleware\Authenticate;

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


Route::middleware('Authenticate')->group(function () {
	Route::get('/', function () {
	    return view('welcome');
	});
});
