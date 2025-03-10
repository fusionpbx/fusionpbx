<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
	use HasFactory;

	protected $table = "menu";

	protected $fillable = ["name", "url", "parent_id", "order"];

	public function parent()
	{
		return $this->belongsTo(Menu::class, "parent_id");
	}

	public function children()
	{
		return $this->hasMany(Menu::class, "parent_id")->orderBy("order");
	}

	public function childrenRecursive()
	{
		return $this->hasMany(Menu::class, "parent_id")->with("childrenRecursive")->orderBy("order");
	}
}
