<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DialplanController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupPermissionController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\ModFormatCDRController;
use App\Http\Controllers\ModXMLCURLController;
use App\Http\Controllers\XmlCDRController;
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

    // DIALPLAN
    Route::resource('/dialplans', DialplanController::class)->name('dialplans', 'dialplans');

    // DOMAIN
    Route::resource('/domains', DomainController::class)->name('domains', 'domains');
    Route::post('/domains/switch', [DomainController::class, 'switch'])->name('domain.switch');
    Route::get('/domains/switch', function () {
        return redirect('/dashboard');
    });
    Route::get('/domains/switch/{domain}', [DomainController::class, 'switchByUuid'])->name('domain.switchuuid');

    // GROUP
    Route::resource('/groups', GroupController::class)->name('groups', 'groups');
    Route::get('/groups/{group}/copy', [GroupController::class, 'copy'])->name('groups.copy');

    // PERMISSION
    //Route::resource('/permissions', PermissionController::class)->name('permissions', 'permissions');
    #Route::get('/permissions', [GroupPermissionController::class, 'index'])->name('permissions.index');
    Route::get('/groups/{groupUuid}/permissions', [GroupPermissionController::class, 'index'])->name('permissions.index');
    //Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    //Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
    Route::put('/permissions/{groupUuid}', [GroupPermissionController::class, 'update'])->name('permissions.update');
    Route::patch('/permissions/{groupUuid}', [GroupPermissionController::class, 'update'])->name('permissions.update');
    //Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->name('permissions.show');
    #Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    //Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    //Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');

    // MENU
    Route::resource('/menus', MenuController::class)->name('menus', 'menus');

    // MENU ITEM
//    Route::resource('/menuitems', MenuItemController::class)->name('menuitems', 'menuitems');
    Route::get('/menu/{menu}/menuitem/{menuitem}/edit', [MenuItemController::class, 'edit'])->name('menuitems.edit');
    Route::get('/menu/{menu}/menuitems', [MenuItemController::class, 'index'])->name('menuitems.index');
    Route::put('/menu/{menu}/menuitem/{menuitem}', [MenuItemController::class, 'update'])->name('menuitems.update');
    Route::patch('/menu/{menu}/menuitem/{menuitem}', [MenuItemController::class, 'update'])->name('menuitems.update');
    Route::delete('/menu/{menu}/menuitem/{menuitem}', [MenuItemController::class, 'destroy'])->name('menuitems.destroy');
    Route::get('/menu/{menu}/menuitems/create', [MenuItemController::class, 'create'])->name('menuitems.create');
    Route::post('/menu/{menu}/menuitems', [MenuItemController::class, 'store'])->name('menuitems.store');

    // USERS
    Route::resource('/users', UserController::class)->name('users', 'users');

    // USER GROUP
    Route::get('/groups/{group}/members', [UserGroupController::class, 'index'])->name('usergroup.index');
    Route::put('/groups/{group}/members', [UserGroupController::class, 'update'])->name('usergroup.update');

    // XML CDR
    Route::get('/xmlcdr', [XmlCDRController::class, 'index'])->name('xmlcdr.index');
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
