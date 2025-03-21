<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuItemController;
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


Route::redirect('/', '/login');

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware(['auth','permission'])->group(function () {
    Route::post('/domains/switch', [DomainController::class, 'switch'])->name('switchDomain');
    Route::get('/domains/switch', function () {
        return redirect('/dashboard');
    });
    Route::get('/domains/switch/{domain}', [DomainController::class, 'switch_by_uuid'])->name('switchDomainFusionPBX');
    Route::view('/dashboard', 'dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    // MENU
    Route::get('/menus', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menus/create', [MenuController::class, 'create'])->name('menu.create');
    Route::post('/menus/', [MenuController::class, 'store'])->name('menu.store');
    Route::get('/menus/{menu_uuid}/edit', [MenuController::class, 'edit'])->name('menu.edit');
    Route::post('/menus/{menu_uuid}', [MenuController::class, 'update'])->name('menu.update');

    // MENU ITEM
    Route::get('/menus/items/{menu_item_uuid}/edit', [MenuItemController::class, 'edit'])->name('menu_item.edit');
    Route::post('/menus/items/{menu_item_uuid}', [MenuItemController::class, 'update'])->name('menu_item.update');
});

Route::post('/curl/xml_handler/configuration', function (Request $request){
    $xml = new ModXMLCURLController;
    return response($xml->configuration($request), 200)->header('Content-Type','text/xml');
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);


Route::post('/curl/xml_handler/directory', function (Request $request){
    $xml = new ModXMLCURLController;
    return response($xml->directory($request), 200)->header('Content-Type','text/xml');
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/curl/xml_handler/dialplan', function (Request $request){
    $xml = new ModXMLCURLController;
    return response($xml->dialplan($request), 200)->header('Content-Type','text/xml');
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/curl/xml_handler/languages', function (Request $request){
    $xml = new ModXMLCURLController;
    return response($xml->languages($request), 200)->header('Content-Type','text/xml');
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
