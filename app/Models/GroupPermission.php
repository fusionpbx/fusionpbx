<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUniqueIdentifier;


class GroupPermission extends Pivot
{
	use HasFactory, HasUniqueIdentifier;
	protected $table = 'v_group_permissions';
	protected $primaryKey = 'group_permission_uuid';
	public $incrementing = false;
	protected $keyType = 'string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
		'permission_name',
		'permission_protected',
		'permission_assigned',
		'group_name',			// TODO: get rid of this later
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function group(): BelongsTo {
		return $this->belongsTo(Group::class, 'group_uuid', 'group_uuid');
	}

}
