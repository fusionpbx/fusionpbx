<?php
/**
 * FusionPBX - Group Model
 * 
 * Eloquent model for v_groups table.
 * Represents a permission group in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Group extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_groups';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'group_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_uuid',
        'domain_uuid',
        'group_name',
        'group_level',
        'group_description',
        'group_protected',
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
        'group_level' => 'integer',
        'group_protected' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the group belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the users that belong to the group.
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'v_user_groups',
            'group_uuid',
            'user_uuid'
        );
    }

    /**
     * Get the permissions for the group.
     */
    public function permissions()
    {
        return $this->hasMany(GroupPermission::class, 'group_uuid', 'group_uuid');
    }

    /**
     * Get the permission objects directly (many-to-many).
     */
    public function permissionsList()
    {
        return $this->belongsToMany(
            Permission::class,
            'v_group_permissions',
            'group_uuid',
            'permission_uuid'
        )->withPivot('domain_uuid');
    }

    /**
     * Check if the group has a specific permission.
     *
     * @param  string  $permissionName
     * @return bool
     */
    public function hasPermission($permissionName)
    {
        return $this->permissionsList()
            ->where('permission_name', $permissionName)
            ->exists();
    }

    /**
     * Grant a permission to this group.
     *
     * @param  string  $permissionUuid
     * @param  string  $domainUuid
     * @return GroupPermission
     */
    public function grantPermission($permissionUuid, $domainUuid = null)
    {
        $domainUuid = $domainUuid ?? $this->domain_uuid;
        return GroupPermission::grant($this->group_uuid, $permissionUuid, $domainUuid);
    }

    /**
     * Revoke a permission from this group.
     *
     * @param  string  $permissionUuid
     * @param  string  $domainUuid
     * @return bool
     */
    public function revokePermission($permissionUuid, $domainUuid = null)
    {
        $domainUuid = $domainUuid ?? $this->domain_uuid;
        return GroupPermission::revoke($this->group_uuid, $permissionUuid, $domainUuid);
    }

    /**
     * Sync permissions for this group (remove old, add new).
     *
     * @param  array  $permissionUuids
     * @param  string  $domainUuid
     * @return void
     */
    public function syncPermissions(array $permissionUuids, $domainUuid = null)
    {
        $domainUuid = $domainUuid ?? $this->domain_uuid;

        // Remove existing permissions
        GroupPermission::where('group_uuid', $this->group_uuid)
            ->where('domain_uuid', $domainUuid)
            ->delete();

        // Add new permissions
        foreach ($permissionUuids as $permissionUuid) {
            GroupPermission::grant($this->group_uuid, $permissionUuid, $domainUuid);
        }
    }

    /**
     * Get all permission names for this group.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPermissionNames()
    {
        return $this->permissionsList()->pluck('permission_name');
    }
}
