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
}
