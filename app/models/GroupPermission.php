<?php
/**
 * FusionPBX - GroupPermission Model
 * 
 * Eloquent model for v_group_permissions table.
 * Links permissions to groups within a domain for multi-tenant access control.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class GroupPermission extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_group_permissions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'group_permission_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_permission_uuid',
        'domain_uuid',
        'permission_uuid',
        'group_uuid',
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
     * Get the group that this permission assignment belongs to.
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_uuid', 'group_uuid');
    }

    /**
     * Get the permission associated with this assignment.
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_uuid', 'permission_uuid');
    }

    /**
     * Get the domain that this permission assignment belongs to.
     * Essential for multi-tenant support.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Grant a permission to a group within a domain.
     *
     * @param  string  $groupUuid
     * @param  string  $permissionUuid
     * @param  string  $domainUuid
     * @return GroupPermission
     */
    public static function grant($groupUuid, $permissionUuid, $domainUuid)
    {
        return static::firstOrCreate([
            'group_uuid' => $groupUuid,
            'permission_uuid' => $permissionUuid,
            'domain_uuid' => $domainUuid,
        ]);
    }

    /**
     * Revoke a permission from a group within a domain.
     *
     * @param  string  $groupUuid
     * @param  string  $permissionUuid
     * @param  string  $domainUuid
     * @return bool
     */
    public static function revoke($groupUuid, $permissionUuid, $domainUuid)
    {
        return static::where('group_uuid', $groupUuid)
            ->where('permission_uuid', $permissionUuid)
            ->where('domain_uuid', $domainUuid)
            ->delete();
    }
}
