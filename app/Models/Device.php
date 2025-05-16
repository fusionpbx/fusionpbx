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

class Device extends Model
{
	use HasFactory, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_devices';
	protected $primaryKey = 'device_uuid';
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
        'device_profile_uuid',
        'device_mac_address',
        'device_label',
        'device_vendor',
        'device_location',
        'device_model',
        'device_firmware_version',
        'device_enabled',
        'device_enabled_date',
        'device_template',
        'device_user_uuid',
        'device_username',
        'device_password',
        'device_uuid_alternate',
        'device_description',
        'device_provisioned_date',
        'device_provisioned_method',
        'device_provisioned_ip',
        'device_provisioned_agent',
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

    public function user(): BelongsTo {
		return $this->belongsTo(User::class, 'user_uuid', 'device_user_uuid');
	}

	 public function settings(): HasMany {
        return $this->HasMany(DeviceSetting::class, 'device_uuid', 'device_uuid');
    }
}
