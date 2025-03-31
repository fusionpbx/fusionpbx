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
		$contacts = Contact::all();
		$domains = Domain::all();
		$groups = Group::all();
		$languages = Language::all();
		$timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

		return view("pages.users.form", compact("contacts", "domains", "groups", "languages", "timezones"));
	}

	public function store(UserRequest $request)
	{
		$user = User::create($request->validated());

		$this->syncGroups($request, $user);

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
		$validated = $request->validated();

		$this->handlePassword($validated);

		$user->update($validated);

		$this->syncGroups($request, $user);

		return redirect()->route("users.index");
	}

	public function destroy(User $user)
	{
		$user->delete();

		return redirect()->route("users.index");
	}

	private function handlePassword(&$request)
	{
		if(empty($request["password"]))
		{
			unset($request["password"]);
		}
	}

	private function syncGroups(UserRequest $request, User $user)
	{
		$groups = array_values($request->input("groups", []));

		$syncGroups = [];

		if(!empty($groups))
		{
    		$groupsDB = Group::whereIn("group_uuid", $groups)->pluck("group_name", "group_uuid");

			foreach($groups as $group)
			{
				$syncGroups[$group] = [
					"domain_uuid" => $user->domain->domain_uuid,
					"group_name" => $groupsDB[$group] ?? null,
				];
			}
		}

		$user->groups()->sync($syncGroups);
	}

    public function login(){
        return view('auth.login');
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
