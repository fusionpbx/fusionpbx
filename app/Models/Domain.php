<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
	use HasFactory, HasUuids;
	protected $table = 'v_domains';
	protected $primaryKey = 'domain_uuid';
	public $incrementing = false;
	protected $keyType='string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	public function users(): HasMany {
		return $this->hasMany(User::class, 'domain_uuid', 'domain_uuid');
	}

	public function groups(): HasMany {
		return $this->hasMany(Group::class, 'domain_uuid', 'domain_uuid');
	}

	public function usergroups(): HasMany {
		return $this->hasMany(UserGroup::class, 'domain_uuid', 'domain_uuid');
	}

	public function grouppermissions(): HasMany {
		return $this->hasMany(GroupPermission::class, 'domain_uuid', 'domain_uuid');
	}
}
