<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\MenuItemGroup;
use App\Traits\HasUniqueIdentifier;
use App\Traits\GetTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class MenuItem extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_menu_items';
	protected $primaryKey = 'menu_item_uuid';
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
        'menu_uuid',
        'menu_item_parent_uuid',
        'uuid',
        'menu_item_title',
        'menu_item_link',
        'menu_item_icon',
        'menu_item_category',
        'menu_item_protected',
        'menu_item_order',
        'menu_item_description',
        'menu_item_add_user',       // TODO: Review is these are useful
        'menu_item_add_date',
        'menu_item_mod_user',
        'menu_item_mod_date',
	];


	public function menu(): BelongsTo {
		return $this->belongsTo(Menu::class, 'menu_uuid', 'menu_uuid');
	}

	public function parent(): BelongsTo {
		return $this->belongsTo(MenuItem::class, 'menu_item_parent_uuid', 'menu_item_uuid');
	}

	public function children(): HasMany	{
		return $this->hasMany(MenuItem::class, 'menu_item_parent_uuid')->orderBy('menu_item_order');
	}

	public function items(): HasMany {
		return $this->hasMany(MenuItem::class, 'menu_item_parent_uuid', 'menu_item_uuid')->with('items')->orderBy('menu_item_order');
	}

	public function groups(): BelongsToMany {
        return $this->belongsToMany(Group::class, 'v_menu_item_groups', 'menu_item_uuid', 'group_uuid')->orderBy('group_name')->withTimestamps();
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}
}
