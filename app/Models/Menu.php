<?php

namespace App\Models;

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
	protected $table = 'menu';
	protected $primaryKey = 'id';
	public $incrementing = false;
	protected $keyType = 'int';
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
		'name',
		'url',
		'parent_id',
		'order',
	];

	public function parent(): BelongsTo {
		return $this->belongsTo(Menu::class, 'parent_id');
	}

	public function children(): HasMany	{
		return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
	}

	public function childrenRecursive()
	{
		return $this->hasMany(Menu::class, 'parent_id')->with('childrenRecursive')->orderBy('order');
	}
}
