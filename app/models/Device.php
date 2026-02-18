<?php
/**
 * FusionPBX - Device Model
 * 
 * Eloquent model for v_devices table.
 * Represents a SIP device in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Device extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_devices';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'device_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'device_uuid',
        'domain_uuid',
        'device_mac_address',
        'device_label',
        'device_vendor',
        'device_model',
        'device_firmware_version',
        'device_enabled',
        'device_template',
        'device_profile_uuid',
        'device_address',
        'device_username',
        'device_password',
        'device_provisioned_date',
        'device_provisioned_method',
        'device_provisioned_ip',
        'device_user_agent',
        'device_description',
        'insert_date',
        'insert_user',
        'update_date',
        'update_user'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'device_password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'device_enabled' => 'boolean',
        'device_provisioned_date' => 'datetime',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the device belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the device lines.
     */
    public function lines()
    {
        return $this->hasMany(DeviceLine::class, 'device_uuid', 'device_uuid');
    }

    /**
     * Get the device keys.
     */
    public function keys()
    {
        return $this->hasMany(DeviceKey::class, 'device_uuid', 'device_uuid');
    }

    /**
     * Get the device settings.
     */
    public function settings()
    {
        return $this->hasMany(DeviceSetting::class, 'device_uuid', 'device_uuid');
    }

    /**
     * Get the device profile.
     */
    public function profile()
    {
        return $this->belongsTo(DeviceProfile::class, 'device_profile_uuid', 'device_profile_uuid');
    }
}
