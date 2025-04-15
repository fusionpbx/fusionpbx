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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;


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
		$api_key = Str::uuid();

		$contacts = Contact::all();
		$currentDomain = Domain::find(Session::get('domain_uuid'));
		$groups = $currentDomain->groups;
		$domains = Domain::all();
		$languages = Language::all();
		$timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        $canSelectDomain = auth()->user()->hasPermission('domain_select');

		return view("pages.users.form", compact("contacts", "domains", "groups", "languages", "timezones", "api_key", 'currentDomain', 'canSelectDomain'));
	}

	public function store(UserRequest $request)
	{
        if(App::hasDebugModeEnabled()){
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $request: '.print_r($request->toArray(), true));
        }
        $canSelectDomain = auth()->user()->hasPermission('domain_select');
        if (!$canSelectDomain){
            $request['domain_uuid'] = Session::get('domain_uuid');
        }
        $validatedUser = $request->validated();
        if(App::hasDebugModeEnabled()){
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $validatedUser: '.print_r($validatedUser, true));
        }
		$user = User::create($validatedUser);

		$this->syncGroups($request, $user);

		$this->syncSettings($request, $user);

		return redirect()->route("users.index");
	}

	public function show(User $user)
	{
		//
	}

	public function edit(User $user)
	{
		$contacts = Contact::all();
		$domains = Domain::all();
		$currentDomain = Domain::find(Session::get('domain_uuid'));
		$groups = $currentDomain->groups;
		$languages = Language::all();
		$timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        $canSelectDomain = auth()->user()->hasPermission('domain_select');

		$selectedLanguage = $user->userSettings->where('user_setting_subcategory', 'language')->first()->user_setting_value ?? null;
		$selectedTimezone = $user->userSettings->where('user_setting_subcategory', 'time_zone')->first()->user_setting_value ?? null;

		return view("pages.users.form", compact("user", "contacts", "domains", "groups", "languages", "timezones", "selectedLanguage", "selectedTimezone", 'currentDomain', 'canSelectDomain'));
	}

	public function update(UserRequest $request, User $user)
	{
        $canSelectDomain = auth()->user()->hasPermission('domain_select');
        if (!$canSelectDomain){
            $request['domain_uuid'] = Session::get('domain_uuid');
        }

		$validated = $request->validated();

		$this->handlePassword($validated);

		$user->update($validated);

		$this->syncGroups($request, $user);

		$this->syncSettings($request, $user);

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
					"domain_uuid" => $user->domain_uuid, //NOTE: won't need in the future
					"group_name" => $groupsDB[$group] ?? null, //NOTE: won't need in the future
				];
			}
		}

		$user->groups()->sync($syncGroups);
	}

	private function syncSettings(UserRequest $request, User $user)
	{
		$settings = [
			"language" => $request->input("language"),
			"time_zone" => $request->input("timezone"),
		];

		foreach($settings as $setting_subcategory => $setting_value)
		{
			if($setting_value)
			{
				$setting_name = match ($setting_subcategory)
				{
					"language" => "code",
					"time_zone" => "name",
					default => "",
				};

				$user->userSettings()->updateOrCreate(
					[
						"user_uuid" => $user->user_uuid,
						"user_setting_subcategory" => $setting_subcategory,
					],
					[
						"domain_uuid" => $user->domain_uuid,
						"user_setting_category" => "domain",
						"user_setting_name" => $setting_name,
						"user_setting_value" => $setting_value,
					]
				);
			}
		}
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
