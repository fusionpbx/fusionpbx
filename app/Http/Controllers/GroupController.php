<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Domain;
use App\Http\Requests\GroupRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
	private $group_name = NULL;
	private $group_uuid = NULL;
	private bool $group_protected = false;

	public function index()
	{
		$groups = Group::all();

		return view("group/index", compact("domains"));
	}

	public function create()
	{
		$group = new Group();

		$domains = Domain::all();

		return view("group/form", compact("group", "domains"));
	}

	public function store(GroupRequest $request)
	{
		$validated = $request->validated();

		Group::create($validated);

		return redirect()->route("group.index")->with("success", "Group created successfully!");
	}

	public function edit($group_uuid)
	{
		$group = Group::findOrFail($group_uuid);

		$domains = Domain::all();

		return view("group/form", compact("group", "domains"));
	}

	public function update(GroupRequest $request, $group_uuid)
	{
		$group = Group::findOrFail($group_uuid);

		$validated = $request->validated();

		$group->update($validated);

		return redirect()->route("group.edit", $group_uuid)->with("success", "Group updated successfully!");
	}

	public function destroy($group_uuid)
	{
		$group = Group::findOrFail($group_uuid);

		$group->delete();

		return redirect()->route("group.index")->with("success", "Group deleted successfully!");
	}

	public function findFromDomainUuid(string $domain_uuid){
		$groups = Domain::find($domain_uuid)->groups();
		return $groups;
	}

	public function findGlobals(){
		$global_groups = Group::findGlobals();
		return $global_groups;
	}

	public function switch(string $name){
		$domain_uuid = null;
		if ($domain_name = strstr($name, '@')){
			// We have a domain name
			list($group_name, $domain_name) = explode('@', $name, 2);
			$domain = Domain::where('domain_name', $domain_name)
					->where('domain_enabled', 'true')
					->first();

			if (is_null($domain))
				return;

			$domain_uuid = $domain->domain_uuid;
		}
		if (is_null($domain_uuid)){
			$group = Group::where('group_name', $name)
					->whereNull('domain_uuid')
					->orderBy('group_level')
					->first();
		}
		else{
			$group = Group::where('group_name', $name)
					->orderBy('group_level')
					->first();
		}

		if ($group){
			$this->group_name = $group->group_name;
			$this->group_uuid = $group->group_uuid;
			$this->group_protected = $group->group_protected;
		}
	}

	public function isAllowed(string $permission_name){
		$answer = false;

		$record = DB::table('v_permissions')
					->join('v_group_permissions', 'permission_name', '=', 'permission_name')
					->where('permission_assigned', 'true')
					->where('group_uuid', $this->group_uuid)
					->where('permission_name', $permission_name)
					->first();

		// return !is_null($record);
	}
}
