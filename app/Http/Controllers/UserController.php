<?php

namespace App\Http\Controllers;

use App\Facades\DefaultSetting;
use App\Http\Controllers\DomainSettingController;
use App\Http\Controllers\UserSettingController;
use App\Http\Requests\UserRequest;
use App\Models\Contact;
use App\Models\Domain;
use App\Models\Group;
use App\Models\Language;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;


class UserController extends Controller
{
    protected $userRepository;
    protected $settingService;

    public function __construct(UserRepository $userRepository, SettingService $settingService)
    {
        $this->settingService = $settingService;
        $this->userRepository = $userRepository;
    }


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
        if (App::hasDebugModeEnabled()) {
            Log::debug('[' . __FILE__ . ':' . __LINE__ . '][' . __CLASS__ . '][' . __METHOD__ . '] $request: ' . print_r($request->toArray(), true));
        }
        
        $canSelectDomain = auth()->user()->hasPermission('domain_select');
        if (!$canSelectDomain) {
            $request['domain_uuid'] = Session::get('domain_uuid');
        }
        
        $validatedUser = $request->validated();
        
        if (App::hasDebugModeEnabled()) {
            Log::debug('[' . __FILE__ . ':' . __LINE__ . '][' . __CLASS__ . '][' . __METHOD__ . '] $validatedUser: ' . print_r($validatedUser, true));
        }
        
        $user = $this->userRepository->create($validatedUser);

        $this->userRepository->syncGroups($user, array_values($request->input("groups", [])));

        $this->userRepository->syncSettings($user, [
            "language" => $request->input("language"),
            "time_zone" => $request->input("timezone"),
        ]);

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
        if (!$canSelectDomain) {
            $request['domain_uuid'] = Session::get('domain_uuid');
        }

        $validated = $request->validated();

        $this->userRepository->handlePassword($validated);

        $this->userRepository->update($user, $validated);

        $this->userRepository->syncGroups($user, array_values($request->input("groups", [])));

        $this->userRepository->syncSettings($user, [
            "language" => $request->input("language"),
            "time_zone" => $request->input("timezone"),
        ]);

        return redirect()->route("users.index");
    }

    public function destroy(User $user)
    {
        $this->userRepository->delete($user);

        return redirect()->route("users.index");
    }

    public function login()
    {
        return view('auth.login');
    }

    public function get(string $category, string $subcategory, ?string $name = null)
    {
        return $this->settingService->getSetting($category, $subcategory, $name);
    }
}