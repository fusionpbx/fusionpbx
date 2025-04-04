<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\GateWayController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\ModFormatCDRController;
use App\Http\Controllers\ModXMLCURLController;
use App\Http\Controllers\PermissionController;
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
    Route::get('/login/okta', [AuthController::class, 'redirectToProvider'])->name('login-okta');
    Route::get('/login/okta/callback', [AuthController::class, 'handleProviderCallback']);
});

Route::middleware(['auth','permission'])->group(function () {
    Route::view('/dashboard', 'dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    // DOMAIN
    Route::resource('/domains', DomainController::class)->name('domains', 'domains');
    Route::post('/domains/switch', [DomainController::class, 'switch'])->name('domain.switch');
    Route::get('/domains/switch', function () {
        return redirect('/dashboard');
    });
    Route::get('/domains/switch/{domain}', [DomainController::class, 'switch_by_uuid'])->name('domain.switchuuid');

    // GROUP
    Route::resource('/groups', GroupController::class)->name('groups', 'groups');
    Route::get('/groups/{group}/copy', [GroupController::class, 'copy'])->name('groups.copy');

    // PERMISSION
    Route::resource('/permissions', PermissionController::class)->name('permissions', 'permissions');
    Route::put('/permissions/{groupUuid}', [PermissionController::class, 'update'])->name('permissions.update');

    // GETEWAY
    Route::resource('/gateways', GateWayController::class)->name('gateways', 'gateways');
    Route::get('/gateways/{gateway}/copy', [GateWayController::class, 'copy'])->name('gateways.copy');
    

    // MENU
    Route::resource('/menus', MenuController::class)->name('menus', 'menus');

    // MENU ITEM
    Route::resource('/menuitems', MenuItemController::class)->name('menuitems', 'menuitems');
    Route::get('/menus/{menu}/menuitems/create', [MenuItemController::class, 'create'])->name('menuitems.create');

    // USERS
    Route::resource('/users', UserController::class)->name('users', 'users');

    // USER GROUP
    Route::get('/groups/{group}/members', [UserGroupController::class, 'index'])->name('usergroup.index');
    Route::put('/groups/{group}/members', [UserGroupController::class, 'update'])->name('usergroup.update');
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
