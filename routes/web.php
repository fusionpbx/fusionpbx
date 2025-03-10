<?php

use App\Http\Controllers\DomainController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ModXMLCURLController;
use App\Http\Middleware\Authenticate;
use Illuminate\Http\Request;
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

    Route::post('/domains/switch', [DomainController::class, 'switch'])->name('switchDomain');
    Route::get('/domains/switch', function () {
        return redirect('/dashboard');
    });
    Route::get('/domains/switch/{domain}', [DomainController::class, 'switch_by_uuid'])->name('switchDomainFusionPBX');
});


Route::middleware('guest')->group(function () {
    Route::get('/login', [UserController::class, 'login']);
    Route::post('/login', [UserController::class, 'authenticate'])->name('login');
});


Route::post('/curl/xml_handler/configuration', function (Request $request){
    $xml = new ModXMLCURLController;
    return response($xml->configuration($request), 200)->header('Content-Type','text/xml');
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);


Route::get('/menu', [MenuController::class, 'index']);
Route::post('/menus', [MenuController::class, 'store']);
