<?php

namespace App\Models;

use App\Traits\HasUniqueIdentifier;
use App\Traits\GetTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CallCenterAgent extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_call_center_agents';
	protected $primaryKey = 'call_center_agent_uuid';
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
        'user_uuid',
        'agent_name',
        'agent_type',
        'agent_call_timeout',
        'agent_id',
        'agent_password',
        'agent_contact',
        'agent_status',
        'agent_logout',
        'agent_max_no_answer',
        'agent_wrap_up_time',
        'agent_reject_delay_time',
        'agent_busy_delay_time',
        'agent_no_answer_delay_time',
        'agent_record',
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

	public function user(): HasOne {
		return $this->HasOne(User::class, 'user_uuid', 'user_uuid');
	}

	public function callcenterqueues(): BelongsToMany {
		return $this->belongsToMany(CallCenterQueue::class, 'v_call_center_tiers', 'call_center_agent_uuid', 'call_center_queue_uuid');
//		$this->belongsToMany(Group::class)->using(UserGroup::class);
	}
}
