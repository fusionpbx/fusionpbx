<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class RingGroup extends Model
{
	use HasFactory, HasUniqueIdentifier;
	protected $table = 'v_ring_groups';
	protected $primaryKey = 'ring_group_uuid';
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
        'ring_group_name',
        'ring_group_extension',
        'ring_group_greeting',
        'ring_group_context',
        'ring_group_call_timeout',
        'ring_group_forward_destination',
        'ring_group_forward_enabled',
        'ring_group_caller_id_name',
        'ring_group_caller_id_number',
        'ring_group_cid_name_prefix',
        'ring_group_cid_number_prefix',
        'ring_group_strategy',
        'ring_group_timeout_app',
        'ring_group_timeout_data',
        'ring_group_distinctive_ring',
        'ring_group_ringback',
        'ring_group_call_forward_enabled',
        'ring_group_follow_me_enabled',
        'ring_group_missed_call_app',
        'ring_group_missed_call_data',
        'ring_group_enabled',
        'ring_group_description',
        'dialplan_uuid',
        'ring_group_forward_toll_allow',
	];

	public function users(): BelongsToMany {
		return $this->belongsToMany(User::class, 'v_ring_group_users', 'ring_group_uuid', 'user_uuid')->withTimestamps();
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

    public function destinations(): HasMany {
		return $this->hasMany(RingGroupDestination::class, 'ring_group_uuid', 'ring_group_uuid');
	}

	public function dialplan(): HasOne {
		return $this->HasOne(Dialplan::class, 'dialplan_uuid', 'dialplan_uuid');
	}
}
