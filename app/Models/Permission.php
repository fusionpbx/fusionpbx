<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Permission extends Model
{
	use HasFactory, HasUuids;
	protected $table = 'v_permissions';
	protected $primaryKey = 'permission_name';	// FusionPBX has a wrong DB structure, it should be permission_uuid
	public $incrementing = false;
	protected $keyType = 'string';
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	public function groups(): BelongsToMany {
		return $this->belongsToMany(Group::class, 'v_group_permissions', 'permission_name', 'group_uuid');
// 		$this->belongsToMany(Group::class)->using(UserGroup::class);
        }

}
