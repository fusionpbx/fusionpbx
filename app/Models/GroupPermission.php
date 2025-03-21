<?php

namespace App\Models;

use App\Traits\HasUniqueIdentifier;
use App\Traits\GetTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class GroupPermission extends Pivot
{
	use HasFactory, HasUniqueIdentifier, GetTableName;
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
        'domain_uuid',
        'group_uuid',
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
    public function permission(): BelongsTo {
        return $this->belongsTo(Permission::class, 'permission_name', 'permission_name');
    }
}
