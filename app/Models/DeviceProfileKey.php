<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUniqueIdentifier;

class DeviceProfileKey extends Model
{
	use HasFactory, HasUniqueIdentifier;
	protected $table = 'v_device_profile_keys';
	protected $primaryKey = 'device_profile_key_uuid';
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
        'profile_key_id',
        'profile_key_category',
        'profile_key_vendor',
        'profile_key_type',
        'profile_key_subtype',
        'profile_key_line',
        'profile_key_value',
        'profile_key_extension',
        'profile_key_protected',
        'profile_key_label',
        'profile_key_icon',
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function deviceprofile(): BelongsTo {
		return $this->belongsTo(DeviceProfile::class, 'device_profile_key_uuid', 'device_profile_key_uuid');
	}
}
