<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Domain;

class GroupController extends Controller
{
    //
	public function findFromDomainUuid(string $domain_uuid){
		$groups = Domain::find($domain_uuid)->groups();
		return $groups;
	}

	public function findGlobals(){
		$global_groups = Group::findGlobals();
		return $global_groups;
	}

}
