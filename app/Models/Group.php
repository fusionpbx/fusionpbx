<?php

namespace App\Models;

use App\Models\MenuItem;
use App\Models\MenuItemGroup;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Group extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_groups';
	protected $primaryKey = 'group_uuid';
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
		'group_name',
		'group_protected',
		'group_level',
		'group_description',
	];

	public function users(): BelongsToMany {
		return $this->belongsToMany(User::class, 'v_user_groups', 'group_uuid', 'user_uuid')->withTimestamps();
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}

	public function contacts(): BelongsToMany {
		return $this->belongsToMany(Contact::class, 'v_contact_groups', 'group_uuid', 'contact_uuid')->withTimestamps();
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

   	public function menuitems(): BelongsToMany {
		return $this->belongsToMany(MenuItem::class, 'v_menu_item_groups', 'group_uuid', 'menu_item_uuid')->withTimestamps();
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}

}
