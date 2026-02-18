<?php
/**
 * FusionPBX - User Model
 * 
 * Eloquent model for v_users table.
 * Represents a user account in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class User extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_users';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_uuid',
        'domain_uuid',
        'user_language',
        'user_time_zone',
        'username',
        'password',
        'salt',
        'api_key',
        'user_enabled',
        'contact_uuid',
        'user_status',
        'add_user',
        'add_date',
        'user_edit_own_extension',
        'user_edit_own_device',
        'user_sip_profile',
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
        'password',
        'salt',
        'api_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_enabled' => 'boolean',
        'user_edit_own_extension' => 'boolean',
        'user_edit_own_device' => 'boolean',
        'add_date' => 'datetime',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the user belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the contact associated with the user.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_uuid', 'contact_uuid');
    }

    /**
     * Get the user settings.
     */
    public function settings()
    {
        return $this->hasMany(UserSetting::class, 'user_uuid', 'user_uuid');
    }

    /**
     * Get the groups that the user belongs to.
     */
    public function groups()
    {
        return $this->belongsToMany(
            Group::class,
            'v_user_groups',
            'user_uuid',
            'group_uuid'
        );
    }

    /**
     * Get the extensions associated with the user.
     */
    public function extensions()
    {
        return $this->belongsToMany(
            Extension::class,
            'v_extension_users',
            'user_uuid',
            'extension_uuid'
        );
    }

    /**
     * Scope a query to only include enabled users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('user_enabled', 'true');
    }
}
