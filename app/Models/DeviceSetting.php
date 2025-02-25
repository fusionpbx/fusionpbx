<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUniqueIdentifier;

class DeviceSetting extends Model
{
	use HasFactory, HasUniqueIdentifier;
	protected $table = 'v_device_settings';
	protected $primaryKey = 'device_setting_uuid';
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
        'device_setting_category',
        'device_setting_subcategory',
        'device_setting_name',
        'device_setting_value',
        'device_setting_enabled',
        'device_setting_description',
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function device(): BelongsTo {
		return $this->belongsTo(DeviceProfile::class, 'device_profile_uuid', 'device_profile_uuid');
	}
}
