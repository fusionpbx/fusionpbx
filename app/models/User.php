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
     * Get only enabled user settings.
     */
    public function enabledSettings()
    {
        return $this->hasMany(UserSetting::class, 'user_uuid', 'user_uuid')
            ->where('user_setting_enabled', 'true');
    }

    /**
     * Get user settings by category.
     *
     * @param  string  $category
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function settingsByCategory($category)
    {
        return $this->hasMany(UserSetting::class, 'user_uuid', 'user_uuid')
            ->where('user_setting_category', $category)
            ->where('user_setting_enabled', 'true');
    }

    /**
     * Get a specific user setting value.
     *
     * @param  string  $category
     * @param  string  $subcategory
     * @param  string  $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getSetting($category, $subcategory, $name, $default = null)
    {
        return UserSetting::getValue($this->user_uuid, $category, $subcategory, $name, $default);
    }

    /**
     * Set a specific user setting value.
     *
     * @param  string  $category
     * @param  string  $subcategory
     * @param  string  $name
     * @param  mixed  $value
     * @return UserSetting
     */
    public function setSetting($category, $subcategory, $name, $value)
    {
        return UserSetting::setValue($this->user_uuid, $this->domain_uuid, $category, $subcategory, $name, $value);
    }

    /**
     * Get dashboards accessible to this user (via their domain).
     */
    public function dashboards()
    {
        return $this->hasMany(Dashboard::class, 'domain_uuid', 'domain_uuid')
            ->where('dashboard_enabled', 'true');
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

    /**
     * Check if the user has a specific permission (via their groups).
     *
     * @param  string  $permissionName
     * @return bool
     */
    public function hasPermission($permissionName)
    {
        return $this->groups()
            ->whereHas('permissionsList', function($query) use ($permissionName) {
                $query->where('permission_name', $permissionName);
            })
            ->exists();
    }

    /**
     * Check if the user has any of the given permissions.
     *
     * @param  array  $permissionNames
     * @return bool
     */
    public function hasAnyPermission(array $permissionNames)
    {
        return $this->groups()
            ->whereHas('permissionsList', function($query) use ($permissionNames) {
                $query->whereIn('permission_name', $permissionNames);
            })
            ->exists();
    }

    /**
     * Check if the user has all of the given permissions.
     *
     * @param  array  $permissionNames
     * @return bool
     */
    public function hasAllPermissions(array $permissionNames)
    {
        foreach ($permissionNames as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all permissions for this user (via their groups).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions()
    {
        return Permission::whereHas('groups.users', function($query) {
            $query->where('user_uuid', $this->user_uuid);
        })->get();
    }

    /**
     * Get all permission names for this user.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPermissionNames()
    {
        return $this->getAllPermissions()->pluck('permission_name')->unique();
    }

    /**
     * Check if the user belongs to a specific group.
     *
     * @param  string  $groupName
     * @return bool
     */
    public function hasGroup($groupName)
    {
        return $this->groups()
            ->where('group_name', $groupName)
            ->exists();
    }

    /**
     * Check if the user belongs to any of the given groups.
     *
     * @param  array  $groupNames
     * @return bool
     */
    public function hasAnyGroup(array $groupNames)
    {
        return $this->groups()
            ->whereIn('group_name', $groupNames)
            ->exists();
    }

    /**
     * Check if the user is a superadmin.
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->hasGroup('superadmin');
    }

    /**
     * Check if the user is an admin (superadmin or admin).
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasAnyGroup(['superadmin', 'admin']);
    }
}
