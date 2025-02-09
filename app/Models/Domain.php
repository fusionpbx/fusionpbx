<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUniqueIdentifier;

class Domain extends Model
{
	use HasFactory, HasUniqueIdentifier;
	protected $table = 'v_domains';
	protected $primaryKey = 'domain_uuid';
	public $incrementing = false;
	protected $keyType='string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	public function extensions(): HasMany {
		return $this->hasMany(Extension::class, 'domain_uuid', 'domain_uuid');
	}
	
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

	public function domainsettings(): HasMany {
		return $this->hasMany(DomainSetting::class, 'domain_uuid', 'domain_uuid');
	}

	public function dialplans(): HasMany {
		return $this->hasMany(Dialplan::class, 'domain_uuid', 'domain_uuid');
	}

	public function gateways(): HasMany {
		return $this->hasMany(Gateway::class, 'domain_uuid', 'domain_uuid');
	}

}
