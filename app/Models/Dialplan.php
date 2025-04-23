<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Dialplan extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_dialplans';
	protected $primaryKey = 'dialplan_uuid';
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
	        'app_uuid',
	        'hostname',
	        'dialplan_context',
	        'dialplan_name',
	        'dialplan_number',
	        'dialplan_destination',
	        'dialplan_continue',
	        'dialplan_xml',
	        'dialplan_order',
	        'dialplan_enabled',
	        'dialplan_description',
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

	public function dialplanDetails(): HasMany {
		return $this->hasMany(DialplanDetail::class, 'dialplan_uuid', 'dialplan_uuid')
			->orderBy('dialplan_detail_group', 'asc')
			->orderByRaw("case when dialplan_detail_tag='condition' then 0 when dialplan_detail_tag='regex' then 1 when dialplan_detail_tag='action' then 2 when dialplan_detail_tag='anti-action' then 4 end ASC")
			->orderBy('dialplan_detail_order', 'asc');
	}

	public function callcenterqueue(): BelongsTo {
		return $this->belongsTo(CallCenterQueue::class, 'dialplan_uuid', 'dialplan_uuid');
	}

	public function callflow(): BelongsTo {
		return $this->belongsTo(CallFlow::class, 'dialplan_uuid', 'dialplan_uuid');
	}

    public function conferencecenter(): BelongsTo {
		return $this->belongsTo(ConferenceCenter::class, 'dialplan_uuid', 'dialplan_uuid');
	}

    public function ringgroup(): BelongsTo {
		return $this->belongsTo(RingGroup::class, 'dialplan_uuid', 'dialplan_uuid');
	}

	 public function fax(): BelongsTo {
		return $this->belongsTo(Fax::class, 'fax_uuid', 'fax_uuid');
	}

	 public function ivr_menu(): BelongsTo {
		return $this->belongsTo(IVRMenu::class, 'ivr_menu_uuid', 'ivr_menu_uuid');
	}
}
