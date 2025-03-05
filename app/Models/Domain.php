<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUniqueIdentifier;

class Domain extends Model
{
	use HasFactory, HasUniqueIdentifier;
	protected $table = 'v_domains';
	protected $primaryKey = 'domain_uuid';
	public $incrementing = false;
	protected $keyType='string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';
 /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
	    'domain_parent_uuid',
	    'domain_name',
	    'domain_enabled',
	    'domain_description',
	];

	public function parent(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_parent_uuid', 'domain_uuid');
	}

	public function children(): HasMany {
		return $this->hasMany(Domain::class, 'domain_parent_uuid', 'domain_uuid');
	}

	public function extensions(): HasMany {
		return $this->hasMany(Extension::class, 'domain_uuid', 'domain_uuid');
	}

	public function users(): HasMany {
		return $this->hasMany(User::class, 'domain_uuid', 'domain_uuid');
	}

	public function groups(): HasMany {
		return $this->hasMany(Group::class, 'domain_uuid', 'domain_uuid');
	}

	public function usergroups(): HasMany {
		return $this->hasMany(UserGroup::class, 'domain_uuid', 'domain_uuid');
	}

	public function grouppermissions(): HasMany {
		return $this->hasMany(GroupPermission::class, 'domain_uuid', 'domain_uuid');
	}

	public function domainsettings(): HasMany {
		return $this->hasMany(DomainSetting::class, 'domain_uuid', 'domain_uuid');
	}

	public function dialplans(): HasMany {
		return $this->hasMany(Dialplan::class, 'domain_uuid', 'domain_uuid');
	}

	public function gateways(): HasMany {
		return $this->hasMany(Gateway::class, 'domain_uuid', 'domain_uuid');
	}

	public function voicemails(): HasMany {
		return $this->hasMany(Voicemail::class, 'domain_uuid', 'domain_uuid');
	}

	public function voicemailgreetins(): HasMany {
		return $this->hasMany(VoicemailGreeting::class, 'domain_uuid', 'domain_uuid');
	}

	public function xmlcdr(): HasMany {
		return $this->hasMany(XmlCdr::class, 'domain_uuid', 'domain_uuid');
	}

	public function musiconhold(): HasMany {
		return $this->hasMany(MusicOnHold::class, 'domain_uuid', 'domain_uuid');
	}

	public function billingprofiless(): HasMany {
		return $this->hasMany(BillingProfile::class, 'domain_uuid', 'domain_uuid');
	}

	public function billingauthorizedpaymentsources(): HasMany {
		return $this->hasMany(BillingAuthorizedPaymentSource::class, 'domain_uuid', 'domain_uuid');
	}

	public function billingdeals(): HasMany {
		return $this->hasMany(BillingDeal::class, 'domain_uuid', 'domain_uuid');
	}

	public function billinginvoices(): HasMany {
		return $this->hasMany(BillingInvoice::class, 'domain_uuid', 'domain_uuid');
	}

	public function bridges(): HasMany {
		return $this->hasMany(Bridge::class, 'domain_uuid', 'domain_uuid');
	}

	public function callblocks(): HasMany {
		return $this->hasMany(CallBlock::class, 'domain_uuid', 'domain_uuid');
	}

	public function callbroadcasts(): HasMany {
		return $this->hasMany(CallBroadcast::class, 'domain_uuid', 'domain_uuid');
	}

	public function callcenterqueues(): HasMany {
		return $this->hasMany(CallCenterQueue::class, 'domain_uuid', 'domain_uuid');
	}

	public function callcenteragent(): HasMany {
		return $this->hasMany(CallCenterAgent::class, 'domain_uuid', 'domain_uuid');
	}

	public function callflows(): HasMany {
		return $this->hasMany(CallFlow::class, 'domain_uuid', 'domain_uuid');
	}

	public function conferencecenters(): HasMany {
		return $this->hasMany(ConferenceCenter::class, 'domain_uuid', 'domain_uuid');
	}

	public function conferencerooms(): HasMany {
		return $this->hasMany(ConferenceRoom::class, 'domain_uuid', 'domain_uuid');
	}

	public function recordings(): HasMany {
		return $this->hasMany(Recording::class, 'domain_uuid', 'domain_uuid');
	}

	public function devices(): HasMany {
		return $this->hasMany(Device::class, 'domain_uuid', 'domain_uuid');
	}

	public function ivr_menus(): HasMany {
		return $this->hasMany(IVRMenu::class, 'domain_uuid', 'domain_uuid');
	}
}
