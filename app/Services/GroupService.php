<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;

class GroupService
{
    private string $group_name = '';
    private string $group_uuid = '';
    private bool $group_protected = false;


    public function findFromDomainUuid(string $domain_uuid)
    {
        return Domain::find($domain_uuid)?->groups() ?? collect();
    }

    public function findGlobals()
    {
        return Group::findGlobals() ?? collect();
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

	public function isAllowed(string $permission_name): bool{
        $answer = false;

        $record = DB::table('v_permissions')
                    ->join('v_group_permissions', 'permission_name', '=', 'permission_name')
                    ->where('permission_assigned', 'true')
                    ->where('group_uuid', $this->group_uuid)
                    ->where('permission_name', $permission_name)
                    ->first();

        return !is_null($record);
    }
}
