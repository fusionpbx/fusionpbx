<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class App extends Model
{
	use Notifiable, GetTableName;

	protected $table = 'v_apps';
	protected $primaryKey = 'app_uuid';
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
		'app_uuid',
		'app_description',
		'app_controller',
	];
}
