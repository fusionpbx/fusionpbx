<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use DB;

class Group extends Model
{
	use HasFactory, HasUuids;
	protected $table = 'v_groups';
	protected $primaryKey = 'group_uuid';
	public $incrementing = false;
	protected $keyType = 'string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	public function users(): BelongsToMany {
		return $this->belongsToMany(User::class, 'v_user_groups', 'group_uuid', 'user_uuid');
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function permissions(): BelongsToMany {
		return $this->belongsToMany(Permission::class, 'v_group_permissions', 'group_uuid', 'permission_name');
	}

	public static function findGlobals() {
		$groups = DB::table('v_groups')->select('*')->whereNull('domain_uuid')->get();
		return $groups;
	}
}
