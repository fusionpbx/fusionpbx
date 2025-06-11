<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HandlesStringBooleans;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceProfileSetting extends Model
{
	use HasFactory, HasUniqueIdentifier, GetTableName, HandlesStringBooleans;
	protected $table = 'v_device_profile_settings';
	protected $primaryKey = 'device_profile_setting_uuid';
	public $incrementing = false;
	protected $keyType = 'string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	protected static $stringBooleanFields = [
		'profile_setting_enabled'
	];

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
        'domain_uuid',
        'device_profile_uuid',
        'profile_setting_name',
        'profile_setting_value',
        'profile_setting_enabled',
        'profile_setting_description',
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function deviceprofile(): BelongsTo {
		return $this->belongsTo(DeviceProfile::class, 'device_profile_uuid', 'device_profile_uuid');
	}
}
