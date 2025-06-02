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

class DeviceProfile extends Model
{
	use HasFactory, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_device_profiles';
	protected $primaryKey = 'device_profile_uuid';
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
        'device_profile_name',
        'device_profile_enabled',
        'device_profile_description',
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function keys(): HasMany {
        return $this->HasMany(DeviceProfileKey::class, 'device_profile_key_uuid', 'device_profile_key_uuid');
    }

    public function settings(): HasMany {
        return $this->HasMany(DeviceProfileSetting::class, 'device_profile_uuid', 'device_profile_uuid');
    }

    public function devices(): HasMany {
        return $this->HasMany(Device::class, 'device_profile_uuid', 'device_profile_uuid');
    }
}
