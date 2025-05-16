<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceKey extends Model
{
	use HasFactory, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_device_keys';
	protected $primaryKey = 'device_key_uuid';
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
        'device_uuid',
        'device_key_id',
        'device_key_category',
        'device_key_vendor',
        'device_key_type',
        'device_key_subtype',
        'device_key_line',
        'device_key_value',
        'device_key_extension',
        'device_key_protected',
        'device_key_label',
        'device_key_icon',
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function device(): BelongsTo {
		return $this->belongsTo(Device::class, 'device_uuid', 'device_uuid');
	}
}
