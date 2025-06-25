<?php

namespace App\Http\Controllers;

use App\Facades\DefaultSetting;
use App\Http\Resources\UserResource;
use App\Models\Domain;
use App\Models\Group;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Socialite;

class AuthController extends Controller
{
    // use AuthenticatesUsers;

    public function index()
    {
        $userUnique = DefaultSetting::get('users', 'unique', 'text');
        $defaultDomainUuid = DefaultSetting::get('openid', 'default_domain_uuid', 'uuid');
        $defaultGroupUuid = DefaultSetting::get('openid', 'default_group_uuid', 'uuid');

        // Environmental Variables have preference
        $openidClientId = env('OKTA_CLIENT_ID');
        $openidSecreitId = env('OKTA_CLIENT_SECRET');
        $loginDestination = env('OKTA_REDIRECT_URI');
        $openidBaseUrl = env('OKTA_BASE_URL') ?? env('APP_URL');

        if(App::hasDebugModeEnabled()){
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $openidClientId = '.$openidClientId);
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $openidSecreitId = '.$openidSecreitId);
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $loginDestination = '.$loginDestination);
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $openidBaseUrl = '.$openidBaseUrl);
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $userUnique = '.$userUnique);
        }

        if (isset($userUnique) && ($userUnique == 'global') &&
            isset($openidSecreitId) &&
            isset($openidClientId) &&
            isset($loginDestination) &&
            isset($openidBaseUrl) &&
            isset($defaultDomainUuid) &&
            isset($defaultGroupUuid)
        ){
            $oktaEnabled = true;
        }
        else{
            $oktaEnabled = false;
        }

        if(App::hasDebugModeEnabled()){
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $oktaEnabled = '.(int)$oktaEnabled);
        }

        return view('auth.login')->with('okta_enabled', $oktaEnabled);
    }

    public function apiLogin(Request $request)
    {
        $credentials = [
            'user_email' => $request->user_email,
            'password' => $request->password,
        ];

        $validator = Validator::make($credentials, [
            'user_email' => 'required|string|email:strict,spoof,dns',
            'password' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'error' => $validator->messages(),
            ],  422);
        }

        if (Auth::attemptWhen($credentials, function (User $user){ return ($user->user_enabled == 'true');}, $request->filled('remember'))) {
            $user = Auth::user();
            $extension = $user->extensions()->first();
            return response()->json([
                'data' => $user->toResource(),
                'available_extensions' => [
                    'api_key' => $user->api_key,
                    'extension_uuid' => $extension->extension_uuid,
                    'domain_uuid' => $extension->domain_uuid,
                    'extension' => $extension->extension,
                    'password' => $extension->password,
                ],
            ]);
        }

        return response()->json([
                'error' => 'Authentication failed.',
            ],  401);
    }

    //TODO: add support for the non-global loging style from Fusion
    public function login(Request $request)
    {
        $request->validate([
            'user_email' => 'required|string|email:strict,spoof,dns',
            'password' => 'required|string',
        ]);

        $credentials = [
            'user_email' => $request->user_email,
            'password' => $request->password,
        ];

        if (Auth::attemptWhen($credentials, function (User $user){ return ($user->user_enabled == 'true');}, $request->filled('remember'))) {
            $request->session()->regenerate();
            $domain = Auth::user()->domain;
            Session::put('domain_uuid', $domain->domain_uuid);
            Session::put('domain_name', $domain->domain_name);
            Session::put('domain_description', !empty($domain->domain_description) ? $domain->domain_description : $domain->domain_name);
            return redirect()->intended('/dashboard');
        }

        return back()
            ->withInput($request->only('user_email', 'remember'))
            ->with('error', __('Wrong Username or Password.'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function redirectToProvider()
    {
        return Socialite::driver('okta')->redirect();
    }

    public function handleProviderCallback(Request $request)
    {
        $user = Socialite::driver('okta')->user();
        $defaultDomainUuid = DefaultSetting::get('openid', 'default_domain_uuid', 'uuid');
        $defaultGroupUuid = DefaultSetting::get('openid', 'default_group_uuid', 'uuid');

        if(App::hasDebugModeEnabled()){
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $defaultDomainUuid = '. $defaultDomainUuid);
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $defaultGroupUuid = '. $defaultGroupUuid);
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $user = '.print_r($user, true));
        }

        $localGroup = Group::where('group_uuid', $defaultGroupUuid)->first();
        $localDomain = Domain::where('domain_uuid', $defaultDomainUuid)->first();
        if (!$localGroup || !$localDomain){
            if(App::hasDebugModeEnabled()){
                Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] Default Group or Domain does not exist, contact your system admin.');
            }
            return back()
            ->with('error', __('Default Group or Domain does not exist, contact your system admin.'));
        }
        else{
            $localUser = User::where('username', $user->email)->first();
            // create a local user with the email and token from Okta
            if (!$localUser) {
                Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] User NOT in the DB');

                $localUser = User::create([
                    'username' => $user->user['preferred_username'],
                    'user_email' => $user->email,
                    'user_enabled'  => 'true',
                    'token' => $user->token,
                    'domain_uuid' => $defaultDomainUuid,
                ]);

	        if(App::hasDebugModeEnabled()){
	            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $localUser = '.print_r($localUser, true));
	        }

                $localUserGroup = UserGroup::where('user_uuid', $localUser->user_uuid)
                                ->where('group_uuid', $defaultGroupUuid)
                                ->where('domain_uuid', $defaultDomainUuid)
                                ->first();

	        if(App::hasDebugModeEnabled()){
	            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $localUserGroup = '.print_r($localUserGroup, true));
	        }

                if (!$localUserGroup){
                    $localUserGroup = UserGroup::create([
                        'domain_uuid' => $defaultDomainUuid,
                        'group_name' => $localGroup->group_name,        // TODO: Get rid of this in the future
                        'group_uuid' => $defaultGroupUuid,
                        'user_uuid' => $localUser->user_uuid,
                    ]);
                }
            }
            else {
                Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] User already in the DB');
                // if the user already exists, just update the token:
                $localUser->token = $user->token;
                $localUser->save();
            }

            try {
                Auth::login($localUser);
            } catch (\Throwable $e) {
                return redirect('/login-okta');
            }
        }

        return redirect()->intended('/dashboard');
    }
}
