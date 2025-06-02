<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DialplanController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\GateWayController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupPermissionController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\BridgeController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\ModFormatCDRController;
use App\Http\Controllers\ModXMLCURLController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationsController;
use App\Http\Controllers\MusicOnHoldController;
use App\Http\Controllers\XmlCDRController;
use App\Http\Controllers\SipProfileController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\UserActivationController;
use App\Http\Middleware\Authenticate;
use App\Models\AccessControl;
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

    // BRIDGE
    Route::resource('/bridges', BridgeController::class)->name('bridges', 'bridges');

    // DIALPLAN
    Route::resource('/dialplans', DialplanController::class)->name('dialplans', 'dialplans');
    Route::get('/dialplans/inbound/create', [DialplanController::class, 'createInbound'])->name('dialplans.inbound.create');
    Route::post('/dialplans/inbound/store', [DialplanController::class, 'storeInbound'])->name('dialplans.inbound.store');
    Route::get('/dialplans/outbound/create', [DialplanController::class, 'createOutbound'])->name('dialplans.outbound.create');
    Route::post('/dialplans/outbound/store', [DialplanController::class, 'storeOutbound'])->name('dialplans.outbound.store');

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

    // GETEWAY
    Route::resource('/gateways', GateWayController::class)->name('gateways', 'gateways');
    Route::get('/gateways/{gateway}/copy', [GateWayController::class, 'copy'])->name('gateways.copy');

    // CARRIERS
    Route::resource('/carriers', CarrierController::class)->name('carriers', 'carriers');

    // SIP PROFILE
    Route::resource('/sipprofiles', SipProfileController::class)->name('sipprofiles', 'sipprofiles');
    Route::get('/sipprofiles/{sipprofile}/copy', [SipProfileController::class, 'copy'])->name('sipprofiles.copy');


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

    // MUSIC ON HOLD
    Route::resource('/musiconhold', MusicOnHoldController::class)->name('musiconhold', 'musiconhold');
    Route::get('/musiconhold/{musiconhold}/{file}/play', [MusicOnHoldController::class, 'play'])->name('musiconhold.play');
    Route::get('/musiconhold/{musiconhold}/{file}/download', [MusicOnHoldController::class, 'download'])->name('musiconhold.download');
    Route::post('/musiconhold/upload', [MusicOnHoldController::class, 'upload'])->name('musiconhold.upload');

    // STREAMS
    Route::resource('/streams', StreamController::class)->name('streams', 'streams');

    // USERS
    Route::resource('/users', UserController::class)->name('users', 'users');

    // USER GROUP
    Route::get('/groups/{group}/members', [UserGroupController::class, 'index'])->name('usergroup.index');
    Route::put('/groups/{group}/members', [UserGroupController::class, 'update'])->name('usergroup.update');

    // ACCESS CONTROL
    Route::resource('/accesscontrol', AccessControlController::class)->name('accesscontrol', 'accesscontrol');
    Route::get('/accesscontrol/{accesscontrol}/copy', [AccessControlController::class, 'copy'])->name('accesscontrol.copy');

    // XML CDR
    Route::get('/xmlcdr', [XmlCDRController::class, 'index'])->name('xmlcdr.index');
    Route::get('/xmlcdr/{xmlcdr}/play', [XmlCDRController::class, 'play'])->name('xmlcdr.play');
    Route::get('/xmlcdr/{xmlcdr}/download', [XmlCDRController::class, 'download'])->name('xmlcdr.download');

    Route::resource('registrations', RegistrationsController::class)->name('registrations', 'registrations');

    Route::resource('/contacts', ContactController::class);
    Route::get('/contacts/{uuid}/vcard', [ContactController::class, 'exportVCard'])->name('contacts.vcard');

    Route::resource('/extensions', ExtensionController::class)->except('show');
    Route::get('extensions/import', [ExtensionController::class, 'import'])->name('extensions.import');
    Route::get('extensions/export', [ExtensionController::class, 'export'])->name('extensions.export');
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
