<?php
/**
 * FusionPBX - Permission Model
 * 
 * Eloquent model for v_permissions table.
 * Represents system-wide permissions that can be assigned to groups.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Permission extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_permissions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'permission_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'permission_uuid',
        'application_uuid',
        'application_name',
        'permission_name',
        'permission_description',
        'insert_date',
        'insert_user',
        'update_date',
        'update_user'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the groups that have this permission.
     */
    public function groups()
    {
        return $this->belongsToMany(
            Group::class,
            'v_group_permissions',
            'permission_uuid',
            'group_uuid'
        )->withPivot('domain_uuid');
    }

    /**
     * Get the group permission assignments for this permission.
     */
    public function groupPermissions()
    {
        return $this->hasMany(GroupPermission::class, 'permission_uuid', 'permission_uuid');
    }

    /**
     * Scope a query to filter by application.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $applicationName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByApplication($query, $applicationName)
    {
        return $query->where('application_name', $applicationName);
    }

    /**
     * Scope a query to filter by permission name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $permissionName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName($query, $permissionName)
    {
        return $query->where('permission_name', $permissionName);
    }

    /**
     * Find a permission by name.
     *
     * @param  string  $permissionName
     * @return Permission|null
     */
    public static function findByName($permissionName)
    {
        return static::where('permission_name', $permissionName)->first();
    }

    /**
     * Check if this permission is assigned to any group in a domain.
     *
     * @param  string  $domainUuid
     * @return bool
     */
    public function isAssignedInDomain($domainUuid)
    {
        return $this->groupPermissions()
            ->where('domain_uuid', $domainUuid)
            ->exists();
    }
}
