<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use DB;
use Auth;

class UserController extends Controller
{
    //

	private $username = null;
	private $domainname = null;
	private $user_uuid = null;
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
}
