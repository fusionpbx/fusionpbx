<?php
/**
 * FusionPBX - CallCenterQueue Model
 * 
 * Eloquent model for v_call_center_queues table.
 * Represents a call center queue in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class CallCenterQueue extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_call_center_queues';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'call_center_queue_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'call_center_queue_uuid',
        'domain_uuid',
        'dialplan_uuid',
        'queue_name',
        'queue_extension',
        'queue_greeting',
        'queue_strategy',
        'queue_moh_sound',
        'queue_record_template',
        'queue_time_base_score',
        'queue_max_wait_time',
        'queue_max_wait_time_with_no_agent',
        'queue_max_wait_time_with_no_agent_time_reached',
        'queue_tier_rules_apply',
        'queue_tier_rule_wait_second',
        'queue_tier_rule_wait_multiply_level',
        'queue_tier_rule_no_agent_no_wait',
        'queue_timeout_action',
        'queue_discard_abandoned_after',
        'queue_abandoned_resume_allowed',
        'queue_tier_rule_apply',
        'queue_agent_no_answer_status',
        'queue_max_member_delay',
        'queue_announce_sound',
        'queue_announce_frequency',
        'queue_cc_exit_keys',
        'queue_cid_prefix',
        'queue_description',
        'dialplan_enabled',
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
        'queue_max_wait_time' => 'integer',
        'queue_max_wait_time_with_no_agent' => 'integer',
        'queue_tier_rule_wait_second' => 'integer',
        'queue_discard_abandoned_after' => 'integer',
        'queue_max_member_delay' => 'integer',
        'queue_announce_frequency' => 'integer',
        'queue_tier_rules_apply' => 'boolean',
        'queue_tier_rule_wait_multiply_level' => 'boolean',
        'queue_tier_rule_no_agent_no_wait' => 'boolean',
        'queue_abandoned_resume_allowed' => 'boolean',
        'queue_tier_rule_apply' => 'boolean',
        'dialplan_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the queue belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the queue agents (tiers).
     */
    public function tiers()
    {
        return $this->hasMany(CallCenterTier::class, 'call_center_queue_uuid', 'call_center_queue_uuid');
    }

    /**
     * Get the dialplan for the queue.
     */
    public function dialplan()
    {
        return $this->belongsTo(Dialplan::class, 'dialplan_uuid', 'dialplan_uuid');
    }
}
