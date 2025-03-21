<?php

namespace App\Models;

use App\Models\MenuItem;
use App\Traits\HasUniqueIdentifier;
use App\Traits\GetTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Menu extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_menus';
	protected $primaryKey = 'menu_uuid';
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
		'menu_name',
		'menu_language',
		'menu_description',
	];

	public function items(): HasMany {
		return $this->hasMany(MenuItem::class, 'menu_uuid', 'menu_uuid')
			->orderBy('menu_item_order')
			->whereNull('menu_item_parent_uuid');  // only root items in first level
	}

	public function children(): HasMany {
		return $this->hasMany(MenuItem::class, 'menu_uuid', 'menu_uuid')->orderBy('menu_item_title');
	}
}
