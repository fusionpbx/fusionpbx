<?php
/**
 * FusionPBX - CallCenterAgent Model
 * 
 * Eloquent model for v_call_center_agents table.
 * Represents a call center agent in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class CallCenterAgent extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_call_center_agents';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'call_center_agent_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'call_center_agent_uuid',
        'domain_uuid',
        'agent_name',
        'agent_type',
        'agent_call_timeout',
        'agent_contact',
        'agent_status',
        'agent_no_answer_delay_time',
        'agent_max_no_answer',
        'agent_wrap_up_time',
        'agent_reject_delay_time',
        'agent_busy_delay_time',
        'agent_logout_on_reject',
        'user_uuid',
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
        'agent_call_timeout' => 'integer',
        'agent_no_answer_delay_time' => 'integer',
        'agent_max_no_answer' => 'integer',
        'agent_wrap_up_time' => 'integer',
        'agent_reject_delay_time' => 'integer',
        'agent_busy_delay_time' => 'integer',
        'agent_logout_on_reject' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the agent belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the user associated with the agent.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'user_uuid');
    }

    /**
     * Get the agent tiers (queue assignments).
     */
    public function tiers()
    {
        return $this->hasMany(CallCenterTier::class, 'call_center_agent_uuid', 'call_center_agent_uuid');
    }
}
