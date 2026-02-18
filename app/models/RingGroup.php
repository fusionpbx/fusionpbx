<?php
/**
 * FusionPBX - RingGroup Model
 * 
 * Eloquent model for v_ring_groups table.
 * Represents a ring group in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class RingGroup extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_ring_groups';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ring_group_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ring_group_uuid',
        'domain_uuid',
        'dialplan_uuid',
        'ring_group_name',
        'ring_group_extension',
        'ring_group_strategy',
        'ring_group_timeout_sec',
        'ring_group_timeout_app',
        'ring_group_timeout_data',
        'ring_group_cid_name_prefix',
        'ring_group_cid_number_prefix',
        'ring_group_caller_id_name',
        'ring_group_caller_id_number',
        'ring_group_distinctive_ring',
        'ring_group_ringback',
        'ring_group_forward_enabled',
        'ring_group_forward_destination',
        'ring_group_enabled',
        'ring_group_description',
        'dialplan_description',
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
        'ring_group_timeout_sec' => 'integer',
        'ring_group_forward_enabled' => 'boolean',
        'ring_group_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the ring group belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the ring group destinations.
     */
    public function destinations()
    {
        return $this->hasMany(RingGroupDestination::class, 'ring_group_uuid', 'ring_group_uuid');
    }

    /**
     * Get the ring group users.
     */
    public function users()
    {
        return $this->hasMany(RingGroupUser::class, 'ring_group_uuid', 'ring_group_uuid');
    }

    /**
     * Get the dialplan for the ring group.
     */
    public function dialplan()
    {
        return $this->belongsTo(Dialplan::class, 'dialplan_uuid', 'dialplan_uuid');
    }
}
