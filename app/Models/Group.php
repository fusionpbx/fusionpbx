<?php

namespace App\Models;

use App\Models\MenuItem;
use App\Models\MenuItemGroup;
use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HandlesStringBooleans;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class Group extends Model
{
	use Notifiable, HasUniqueIdentifier, GetTableName, HandlesStringBooleans;

	protected $table = 'v_groups';
	protected $primaryKey = 'group_uuid';
	public $incrementing = false;
	protected $keyType = 'string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	protected static $stringBooleanFields = [
		'group_protected'
	];
	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
		'group_name',
		'group_protected',
		'group_level',
		'group_description',
		'domain_uuid'
	];

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'v_user_groups', 'group_uuid', 'user_uuid')
            ->using(UserGroup::class)
            ->withPivot(['user_group_uuid', 'domain_uuid'])
            ->withTimestamps([
                'created_at' => 'insert_date',
                'updated_at' => 'update_date'
            ]);
    }

	public function contacts(): BelongsToMany {
		return $this->belongsToMany(Contact::class, 'v_contact_groups', 'group_uuid', 'contact_uuid')->withTimestamps();
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

    public function permissions(): BelongsToMany {
        return $this->belongsToMany(
            Permission::class,
            'v_group_permissions',
            'group_uuid',
            'permission_name',
            null,  // Local key (defaults to primary key of Group model)
            'permission_name'  // Related key (permission_name in Permission model)
        )->wherePivot('permission_assigned', 'true')
            ->withPivot(['permission_assigned', 'permission_protected']);
    }

    public function groupPermissions(): HasMany
    {
        return $this->hasMany(GroupPermission::class, 'group_uuid', 'group_uuid');
    }


	public function getFullGroupNameAttribute(){

		if (array_key_exists('domain_uuid', $this->attributes) && (!is_null($this->attributes['domain_uuid']))){
			$myDomain = Domain::find($this->attributes['domain_uuid']);
			$suffix = '@'.$myDomain->domain_name;
		}
		else
			$suffix = '@Global';

		return $this->attributes['group_name'].$suffix;
	}


	public static function findGlobals() {
		$groups = DB::table('v_groups')->select('*')->whereNull('domain_uuid')->get();
		return $groups;
	}

   	public function menuitems(): BelongsToMany {
		return $this->belongsToMany(MenuItem::class, 'v_menu_item_groups', 'group_uuid', 'menu_item_uuid')->withTimestamps();
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}


}
