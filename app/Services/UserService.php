<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    private $username = null;
	private $domainName = null;
	private $userUuid = null;

    public function authenticate(string $username, string $domainName, string $password): bool{
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
}
