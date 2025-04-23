<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Permission extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_permissions';
	protected $primaryKey = 'permission_uuid';
	public $incrementing = false;
	protected $keyType = 'string';
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
		'application_uuid',
		'application_name',
		'permission_name',
		'permission_description',
	];

    public function groups(): BelongsToMany {
        return $this->belongsToMany(
            Group::class,
            'v_group_permissions',
            'permission_name',
            'group_uuid',
            'permission_name'
        )->wherePivot('permission_assigned', 'true')
            ->withPivot(['permission_assigned', 'permission_protected']);
    }

    public function groupPermissions(): BelongsTo {
        return $this->belongsTo(GroupPermission::class, 'permission_name', 'permission_name');
    }

    public function groupPermissionByGroup($groupUuid = null): BelongsTo
    {
        return $this->belongsTo(GroupPermission::class, 'permission_name', 'permission_name')
            ->when($groupUuid, function($query, $groupUuid) {
                return $query->where('group_uuid', $groupUuid);
            });
    }
}
