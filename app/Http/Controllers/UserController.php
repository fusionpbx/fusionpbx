<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use DB;
use Auth;
use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\DomainSettingController;
use App\Http\Controllers\UserSettingController;
use Log;


class UserController extends Controller
{
    //

	private $username = null;
	private $domainname = null;
	private $user_uuid = null;

    public function login(){
        return view('auth.login');
    }

	public function Autheticate(string $username, string $domainname, string $password): bool{
		$result = false;

		$user_uuid = $this->getUuid($username, $domainname);
		if(!empty($user_uuid)){
			if (Auth::attempt(['user_uuid' => $user_uuid, 'password' => $password], true)){
				$result = true;
				$this->username = $username;
				$this->domainname = $domainname;
			}
		}

		return $result;
	}

	public function getUuid(?string $username = null, ?string $domainname = null): ?string{
		if (empty($this->user_uuid) && !empty($username) && !empty($domainname)){
			$this->user_uuid = DB::table('v_users')
				->join('v_domains', 'v_users.domain_uuid', '=', 'v_domains.domain_uuid')
				->whereRaw('(username = ?) or (username = ? and domain_name = ?)',[$username.'@'.$domainname, $username, $domainname])
				->value('v_users.user_uuid');
			\Log::debug('$user_uuid: ' .$this->user_uuid);
		}
		return $this->user_uuid;
	}

	public function logginbyUsernameDomain(string $username, string $domainname): bool{
		$user_uuid = $this->getUuid($username, $domainname);
		Auth::loginUsingId($user_uuid, true);
		return Auth::check();
	}

    public function default_setting(string $category, string $subcategory, ?string $name = null){
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
