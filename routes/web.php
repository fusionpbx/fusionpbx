<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ModFormatCDRController;
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
    Route::view('/dashboard', 'dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    // DOMAIN
    Route::get('/domains', [DomainController::class, 'index'])->name('domain.index');
    Route::get('/domains/create', [DomainController::class, 'create'])->name('domain.create');
    Route::post('/domains', [DomainController::class, 'store'])->name('domain.store');
    Route::get('/domains/{domain_uuid}/edit', [DomainController::class, 'edit'])->name('domain.edit');
    Route::post('/domains/{domain_uuid}', [DomainController::class, 'update'])->name('domain.update');
    Route::get('/domains/{domain_uuid}/destroy', [DomainController::class, 'destroy'])->name('domain.destroy');
    Route::post('/domains/switch', [DomainController::class, 'switch'])->name('domain.switch');
    Route::get('/domains/switch', function () {
        return redirect('/dashboard');
    });
    Route::get('/domains/switch/{domain}', [DomainController::class, 'switch_by_uuid'])->name('domain.switchuuid');

    // GROUP
    Route::get('/groups', [GroupController::class, 'index'])->name('group.index');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('group.create');
    Route::post('/groups', [GroupController::class, 'store'])->name('group.store');
    Route::get('/groups/{group_uuid}/edit', [GroupController::class, 'edit'])->name('group.edit');
    Route::post('/groups/{group_uuid}', [GroupController::class, 'update'])->name('group.update');
    Route::get('/groups/{group_uuid}/destroy', [GroupController::class, 'destroy'])->name('group.destroy');

    // MENU
    Route::get('/menus', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menus/create', [MenuController::class, 'create'])->name('menu.create');
    Route::post('/menus', [MenuController::class, 'store'])->name('menu.store');
    Route::get('/menus/{menu_uuid}/edit', [MenuController::class, 'edit'])->name('menu.edit');
    Route::post('/menus/{menu_uuid}', [MenuController::class, 'update'])->name('menu.update');
    Route::get('/menus/{menu_uuid}/destroy', [MenuController::class, 'destroy'])->name('menu.destroy');

    // MENU ITEM
    Route::get('/menus/{menu_uuid}/items/create', [MenuItemController::class, 'create'])->name('menu_item.create');
    Route::post('/menus/{menu_uuid}/items/', [MenuItemController::class, 'store'])->name('menu_item.store');
    Route::get('/menus/items/{menu_item_uuid}/edit', [MenuItemController::class, 'edit'])->name('menu_item.edit');
    Route::post('/menus/items/{menu_item_uuid}', [MenuItemController::class, 'update'])->name('menu_item.update');
    Route::get('/menus/items/{menu_item_uuid}/destroy', [MenuItemController::class, 'destroy'])->name('menu_item.destroy');

    // USERS
    // Route::resource('/users', UserController::class)->name('users', 'users');
    Route::get('/users', [UserController::class, 'index'])->name('user.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('user.create');
    Route::post('/users', [UserController::class, 'store'])->name('user.store');
    Route::get('/users/{user_uuid}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::post('/users/{user_uuid}', [UserController::class, 'update'])->name('user.update');
    Route::get('/users/{user_uuid}/destroy', [UserController::class, 'destroy'])->name('user.destroy');
});

Route::post('/switch/xml_handler/{binding}', function (Request $request, string $binding){
    $xml = new ModXMLCURLController;
    $allowedMethods = ['configuration', 'directory', 'dialplan', 'languages'];

    if(!in_array($binding, $allowedMethods)){
        return response('Method not allowed', 403)->header('Content-Type', 'text/xml');
    }

    return response($xml->$binding($request), 200)->header('Content-Type', 'text/xml');
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post("/switch/format_cdr", [ModFormatCDRController::class, 'store'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
