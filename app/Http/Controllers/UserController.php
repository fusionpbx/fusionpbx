<?php

namespace App\Http\Controllers;

use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\DomainSettingController;
use App\Http\Controllers\UserSettingController;
use App\Http\Requests\UserRequest;
use App\Models\Contact;
use App\Models\Domain;
use App\Models\Group;
use App\Models\Language;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class UserController extends Controller
{
	private $username = null;
	private $domainName = null;
	private $userUuid = null;

	public function index()
	{
		return view("pages.users.index");
	}

	public function create()
	{
		$domains = Domain::all();
		$groups = Group::all();
		$languages = Language::all();
		$timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

		return view("pages.users.form", compact("domains", "groups", "languages", "timezones"));
	}

	public function store(UserRequest $request)
	{
		User::create($request->validated());

		return redirect()->route("pages.users.index");
	}

	public function show(User $user)
	{
		//
	}

	public function edit(User $user)
	{
		$contacts = Contact::all();
		$domains = Domain::all();
		$groups = Group::all();
		$languages = Language::all();
		$timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

		$selectedLanguage = $user->usersettings->where('user_setting_subcategory', 'language')->first()->user_setting_value ?? null;
		$selectedTimezone = $user->usersettings->where('user_setting_subcategory', 'time_zone')->first()->user_setting_value ?? null;

		return view("pages.users.form", compact("user", "contacts", "domains", "groups", "languages", "timezones", "selectedLanguage", "selectedTimezone"));
	}

	public function update(UserRequest $request, User $user)
	{
		$user->update($request->validated());

		return redirect()->route("users.index");
	}

	public function destroy(User $user)
	{
		$user->delete();

		return redirect()->route("users.index");
	}

    public function login(){
        return view('auth.login');
    }

	public function autheticate(string $username, string $domainName, string $password): bool{
		$result = false;

		$userUuid = $this->getUuid($username, $domainName);
		if(!empty($userUuid)){
			if (Auth::attempt(['user_uuid' => $userUuid, 'password' => $password], true)){
				$result = true;
				$this->username = $username;
				$this->domainname = $domainName;
			}
		}

		return $result;
	}

	public function getUuid(?string $username = null, ?string $domainName = null): ?string{
		if (empty($this->userUuid) && !empty($username) && !empty($domainName)){
			$this->userUuid = User::join(Domain::getTableName(), User::getTableName().'.domain_uuid', '=', Domain::getTableName().'.domain_uuid')
                ->where('username', $username.'@'.$domainName)
                ->orWhere(function (Builder $query) use($username, $domainName){
                                    $query->where('username', $username)
                                        ->Where('domain_name', $domainName);
                                })
				->value('v_users.user_uuid');
			Log::debug('$userUuid: ' .$this->userUuid);
		}
		return $this->user_uuid;
	}

	public function logginbyUsernameDomain(string $username, string $domainName): bool{
		$userUuid = $this->getUuid($username, $domainName);
		Auth::loginUsingId($userUuid, true);
		return Auth::check();
	}

    public function defaultSetting(string $category, string $subcategory, ?string $name = null){
        $uds = new UserSettingController;
        $setting = $uds->get($category, $subcategory, $name);
        if (!isset($setting)){                  // TODO: Verify if it is easier to use DomainController instead
            $dds = new DomainSettingController;
            $setting = $dds->get($category, $subcategory, $name);
            if (!isset($setting)){
                $ds = new DefaultSettingController;
                $setting = $ds->get($category, $subcategory, $name);
            }
        }

        return $ds ?? null;
    }
}
