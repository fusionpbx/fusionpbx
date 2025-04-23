<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CallCenterQueue extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_call_center_queues';
	protected $primaryKey = 'call_center_queue_uuid';
	public $incrementing = false;
	protected $keyType = 'string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
        'domain_uuid',
        'dialplan_uuid',
        'queue_name',
        'queue_extension',
        'queue_greeting',
        'queue_strategy',
        'queue_moh_sound',
        'queue_record_template',
        'queue_time_base_score',
        'queue_time_base_score_sec',
        'queue_max_wait_time',
        'queue_max_wait_time_with_no_agent',
        'queue_max_wait_time_with_no_agent_time_reached',
        'queue_tier_rules_apply',
        'queue_tier_rule_wait_second',
        'queue_tier_rule_no_agent_no_wait',
        'queue_timeout_action',
        'queue_discard_abandoned_after',
        'queue_abandoned_resume_allowed',
        'queue_tier_rule_wait_multiply_level',
        'queue_cid_prefix',
        'queue_outbound_caller_id_name',
        'queue_outbound_caller_id_number',
        'queue_announce_position',
        'queue_announce_sound',
        'queue_announce_frequency',
        'queue_cc_exit_keys',
        'queue_email_address',
        'queue_description',
	];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
	protected $hidden = [
	];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
	protected $casts = [
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function dialplan(): HasOne {
		return $this->HasOne(DialplanDetail::class, 'dialplan_uuid', 'dialplan_uuid');
	}

    public function callcenteragents(): BelongsToMany {
		return $this->belongsToMany(CallCenterAgent::class, 'v_call_center_tiers', 'call_center_queue_uuid', 'call_center_agent_uuid');
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}
}
