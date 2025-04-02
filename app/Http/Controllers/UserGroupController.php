<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{
    public function index(Group $group)
    {
        $members = $group->users;

        $users = User::whereDoesntHave('groups', function ($query) use ($group){
            $query->where(UserGroup::getTableName().'.group_uuid', $group->group_uuid);
            })
            ->get();

        return view('pages.groups.members', compact('group', 'members', 'users'));
    }

    public function update(Request $request, Group $group)
    {
        $this->syncUsers($request, $group);

        return redirect()->route("usergroup.index", [$group]);
    }

	private function syncUsers(Request $request, Group $group)
	{
		$users = array_values($request->input("members", []));

		$syncUsers = [];

		if(!empty($users))
		{
            $usersDB = User::whereIn("user_uuid", $users)->pluck("domain_uuid", "user_uuid");

			foreach($users as $user)
			{
				$syncUsers[$user] = [
					"domain_uuid" => $usersDB[$user], //NOTE: won't need in the future
					"group_name" => $group->group_name, //NOTE: won't need in the future
				];
			}
		}

		$group->users()->sync($syncUsers);
	}
}
