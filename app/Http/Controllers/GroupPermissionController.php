<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupPermission;
use App\Models\Permission;
use App\Models\User;
use App\Http\Requests\GroupPermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GroupPermissionController extends Controller
{
    //
	protected ?int $telegram_id = null;

	public function setTelegramUser(?int $telegram_id){
		$this->telegram_id = $telegram_id;
	}

	public function allowed(string $permissionName): bool {
		$result = false;

		if (!empty($this->telegram_id)){
			$session_id = md5($this->telegram_id);
			Log::debug('$session_id: '.$session_id);
			\OKayInc\StatelessSession::start($session_id);
			$coolpbx_user = \OKayInc\StatelessSession::get('coolpbx_user');
			$coolpbx_domain = \OKayInc\StatelessSession::get('coolpbx_domain');
			$user_uuid = \OKayInc\StatelessSession::get('user_uuid');
			Auth::loginUsingId($user_uuid, true);
		}

		if (Auth::check()){
			Log::debug('Authenticated');
			$user = User::find($user_uuid);
			if (count($user->groups) > 0){
				foreach ($user->groups as $group){
					Log::debug('$group->group_name: ' .$group->group_name);
					$g = Group::find($group->group_uuid);
					$permissions = $g->permissions()->where('v_permissions.permission_name', $permissionName)->get();
					if (count($permissions) > 0)
						$result = true;

				}
			}
		}

		return $result;
	}

	    public function index(GroupPermissionRequest $request, $groupUuid = null)
    {
        $search = $request->input('search', '');
        $groupUuid = $groupUuid ?? $request->input('group_uuid');
        $filter = $request->input('filter', 'all');

        $group = $groupUuid ? Group::findOrFail($groupUuid) : null;

        $query = Permission::query();

        if ($groupUuid) {
            $query->when($filter === 'assigned', function ($q) use ($groupUuid) {
                $q->whereHas('groupPermissions', function ($subQ) use ($groupUuid) {
                    $subQ->where('group_uuid', $groupUuid);
                });
            })
            ->when($filter === 'not_assigned', function ($q) use ($groupUuid) {
                $q->whereDoesntHave('groupPermissions', function ($subQ) use ($groupUuid) {
                    $subQ->where('group_uuid', $groupUuid);
                });
            })
            ->when($filter === 'protected', function ($q) use ($groupUuid) {
                $q->whereHas('groupPermissions', function ($subQ) use ($groupUuid) {
                    $subQ->where('group_uuid', $groupUuid)
                        ->where('permission_protected', true);
                });
            });
        }

        if (!empty($search)) {
            $query->where('permission_name', 'like', '%' . $search . '%');
        }

        $permissions = $query
            ->with(['groupPermissionByGroup' => function ($q) use ($groupUuid) {
                $q->where('group_uuid', $groupUuid);
            }])
            ->orderBy('application_name')
            ->orderBy('permission_name')
            ->get();

        $permissionsByApp = $permissions->pluck('application_name')->unique()->toArray();

        return view('pages.permission.index', compact(
            'search',
            'permissionsByApp',
            'groupUuid',
            'group',
            'filter',
            'permissions'
        ));
    }

    public function update(Request $request, $groupUuid)
    {
        $group = Group::findOrFail($groupUuid);
        $permissions = $request->input('permissions', []);
        $protectedPermissions = $request->input('permissions_protected', []);

        DB::beginTransaction();

        try {
            GroupPermission::where('group_uuid', $groupUuid)
                ->whereNotIn('permission_name', $permissions)
                ->delete();

            foreach ($permissions as $permissionName) {
                $isProtected = in_array($permissionName, $protectedPermissions) ? 'true' : 'false';


                GroupPermission::updateOrCreate(
                    [
                        'group_uuid' => $groupUuid,
                        'permission_name' => $permissionName
                    ],
                    [
                        'permission_assigned' => 'true',
                        'permission_protected' => $isProtected,
                        'domain_uuid' => $group->domain_uuid,
                        'group_name' => $group->group_name
                    ]
                );
            }

            DB::commit();
            return redirect()->route('groups.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
