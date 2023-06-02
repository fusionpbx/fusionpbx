<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupPermission;
use App\Models\User;

class GroupPermissionController extends Controller
{
    //
	protected ?int $telegram_id = null;

	public function setTelegramUser(?int $telegram_id){
		$this->telegram_id = $telegram_id;
	}
	public function allowed(string $permission_name): bool {
		$result = false;

		if (!empty($this->telegram_id)){
			$session_id = md5($this->telegram_id);
			\Log::debug('$session_id: '.$session_id);
			\OKayInc\StatelessSession::start($session_id);
			$coolpbx_user = \OKayInc\StatelessSession::get('coolpbx_user');
			$coolpbx_domain = \OKayInc\StatelessSession::get('coolpbx_domain');
			$user_uuid = \OKayInc\StatelessSession::get('user_uuid');
			Auth::loginUsingId($user_uuid, true);
		}

		if (Auth::check()){
			\Log::debug('Authenticated');
			$user = User::find($user_uuid);
			if (count($user->groups) > 0){
				foreach ($user->groups as $group){
					\Log::debug('$group->group_name: ' .$group->group_name);
					$g = Group::find($group->group_uuid);
					$permissions = $g->permissions()->where('v_permissions.permission_name', $permission_name)->get();
					if (count($permissions) > 0)
						$result = true;

				}
			}
		}

		return $result;
	}
}
