<?php

namespace App\Http\Controllers;

use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\DomainSettingController;
use App\Http\Controllers\UserSettingController;
use App\Http\Requests\UserRequest;
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
		$users = User::all();

		return view("user/index", compact("domains"));
	}

	public function create()
	{
		$user = new User();

		$domains = Domain::all();
		$groups = Group::all();
		$languages = Language::all();

		//timezones
        //NOTE: Timezones are taken from the Linux filesystem

		return view("user/form", compact("user", "domains", "groups", "languages"));
	}

	public function store(UserRequest $request)
	{
		$validated = $request->validated();
		User::create($validated);

		return redirect()->route("user.index")->with("success", "User created successfully!");
	}

	public function edit($userUuid)
	{
		$user = User::findOrFail($userUuid);
		$domains = Domain::all();
		$groups = Group::all();
		$languages = Language::all();

		//timezones

		return view("user/form", compact("user", "domains", "groups", "languages"));
	}

	public function update(UserRequest $request, $userUuid)
	{
		$user = User::findOrFail($userUuid);
		$validated = $request->validated();
		$user->update($validated);

		return redirect()->route("user.edit", $userUuid)->with("success", "User updated successfully!");
	}

	public function destroy($userUuid)
	{
		$user = User::findOrFail($userUuid);
		$user->delete();

		return redirect()->route("user.index")->with("success", "User deleted successfully!");
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
