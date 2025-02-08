<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUniqueIdentifier;

use Permission extends Model
{
	use HasFactory, HasUniqueIdentifier;
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
}
