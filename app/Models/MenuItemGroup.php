<?php

namespace App\Models;

use App\Models\MenuItem;
use App\Traits\HasUniqueIdentifier;
use App\Traits\GetTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class MenuItemGroup extends Pivot
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_menu_item_groups';
	protected $primaryKey = 'menu_item_group_uuid';
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
        'menu_item_uuid',
        'group_name',           // TODO: Review if this can be drop in the future
        'group_uuid',
	];


}
