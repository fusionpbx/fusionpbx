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

class Domain extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
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

	public function users(?bool $user_enabled = null): HasMany {
        return $this->hasMany(User::class, 'domain_uuid', 'domain_uuid')
                ->when(is_bool($user_enabled), function($query) use($user_enabled){
                    return $query->where('user_enabled', $user_enabled ? 'true' : 'false');
                });
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

	public function settings(?bool $domain_setting_enabled = null): HasMany {
		return $this->hasMany(DomainSetting::class, 'domain_uuid', 'domain_uuid')
                ->when(is_bool($domain_setting_enabled), function($query) use($domain_setting_enabled){
                    return $query->where('domain_setting_enabled', $domain_setting_enabled ? 'true' : 'false');
                });
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

	public function bridges(?bool $bridge_enabled = null): HasMany {
		return $this->hasMany(Bridge::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($bridge_enabled), function ($query) use ($bridge_enabled) {
				return $query->where('bridge_enabled', $bridge_enabled ? 'true' : 'false');
			});
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

	public function conferences(?bool $conference_enabled = null): HasMany {
		return $this->hasMany(Conference::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($conference_enabled), function ($query) use ($conference_enabled) {
				return $query->where('conference_enabled', $conference_enabled ? 'true' : 'false');
			});
	}

	public function conferencecenters(?bool $conference_center_enabled = null): HasMany {
		return $this->hasMany(ConferenceCenter::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($conference_center_enabled), function ($query) use ($conference_center_enabled) {
				return $query->where('conference_center_enabled', $conference_center_enabled ? 'true' : 'false');
			});
	}

	public function conferencerooms(): HasMany {
		return $this->hasMany(ConferenceRoom::class, 'domain_uuid', 'domain_uuid');
	}

	public function contactsettings(?bool $contact_setting_enabled = null): HasMany {
		return $this->hasMany(ContactSetting::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($contact_setting_enabled), function ($query) use ($contact_setting_enabled) {
				return $query->where('contact_setting_enabled', $contact_setting_enabled ? 'true' : 'false');
			});
	}

	public function destinations(?bool $destination_enabled = null): HasMany {
		return $this->hasMany(Destination::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($destination_enabled), function ($query) use ($destination_enabled) {
				return $query->where('destination_enabled', $destination_enabled ? 'true' : 'false');
			});
	}

	public function devices(?bool $device_enabled = null): HasMany {
		return $this->hasMany(Device::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($device_enabled), function ($query) use ($device_enabled) {
				return $query->where('device_enabled', $device_enabled ? 'true' : 'false');
			});
	}

	public function deviceprofiles(?bool $device_profile_enabled = null): HasMany {
		return $this->hasMany(DeviceProfile::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($device_profile_enabled), function ($query) use ($device_profile_enabled) {
				return $query->where('device_profile_enabled', $device_profile_enabled ? 'true' : 'false');
			});
	}

	public function deviceprofilesettings(?bool $profile_setting_enabled = null): HasMany {
		return $this->hasMany(DeviceProfileSetting::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($profile_setting_enabled), function ($query) use ($profile_setting_enabled) {
				return $query->where('profile_setting_enabled', $profile_setting_enabled ? 'true' : 'false');
			});
	}

	public function devicesettings(?bool $device_setting_enabled = null): HasMany {
		return $this->hasMany(DeviceSetting::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($device_setting_enabled), function ($query) use ($device_setting_enabled) {
				return $query->where('device_setting_enabled', $device_setting_enabled ? 'true' : 'false');
			});
	}

	public function dialplans(?bool $dialplan_enabled = null): HasMany {
		return $this->hasMany(Dialplan::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($dialplan_enabled), function ($query) use ($dialplan_enabled) {
				return $query->where('dialplan_enabled', $dialplan_enabled ? 'true' : 'false');
			});
	}

	public function dialplandetails(?bool $dialplan_detail_enabled = null): HasMany {
		return $this->hasMany(DialplanDetail::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($dialplan_detail_enabled), function ($query) use ($dialplan_detail_enabled) {
				return $query->where('dialplan_detail_enabled', $dialplan_detail_enabled ? 'true' : 'false');
			});
	}

	public function extensions(): HasMany {
		return $this->hasMany(Extension::class, 'domain_uuid', 'domain_uuid');
	}

	public function extensionsettings(?bool $extension_setting_enabled = null): HasMany {
		return $this->hasMany(ExtensionSetting::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($extension_setting_enabled), function ($query) use ($extension_setting_enabled) {
				return $query->where('extension_setting_enabled', $extension_setting_enabled ? 'true' : 'false');
			});
	}

	public function followme(?bool $follow_me_enabled = null): HasMany {
		return $this->hasMany(FollowMe::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($follow_me_enabled), function ($query) use ($follow_me_enabled) {
				return $query->where('follow_me_enabled', $follow_me_enabled ? 'true' : 'false');
			});
	}

	public function ivr_menus(?bool $ivr_menu_enabled = null): HasMany {
		return $this->hasMany(IVRMenu::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($ivr_menu_enabled), function ($query) use ($ivr_menu_enabled) {
				return $query->where('ivr_menu_enabled', $ivr_menu_enabled ? 'true' : 'false');
			});
	}

	public function ivr_menu_options(?bool $ivr_menu_option_enabled = null): HasMany {
		return $this->hasMany(IVRMenuOption::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($ivr_menu_option_enabled), function ($query) use ($ivr_menu_option_enabled) {
				return $query->where('ivr_menu_option_enabled', $ivr_menu_option_enabled ? 'true' : 'false');
			});
	}

	public function phrases(?bool $phrase_enabled = null): HasMany {
		return $this->hasMany(Phrase::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($phrase_enabled), function ($query) use ($phrase_enabled) {
				return $query->where('phrase_enabled', $phrase_enabled ? 'true' : 'false');
			});
	}

	public function ringroupforward(?bool $ring_group_forward_enabled = null): HasMany {
		return $this->hasMany(RingGroup::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($ring_group_forward_enabled), function ($query) use ($ring_group_forward_enabled) {
				return $query->where('ring_group_forward_enabled', $ring_group_forward_enabled ? 'true' : 'false');
			});
	}

	public function ringroupcallforward(?bool $ring_group_call_forward_enabled = null): HasMany {
		return $this->hasMany(RingGroup::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($ring_group_call_forward_enabled), function ($query) use ($ring_group_call_forward_enabled) {
				return $query->where('ring_group_call_forward_enabled', $ring_group_call_forward_enabled ? 'true' : 'false');
			});
	}

	public function ringroups(?bool $ring_group_enabled = null): HasMany {
		return $this->hasMany(RingGroup::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($ring_group_enabled), function ($query) use ($ring_group_enabled) {
				return $query->where('ring_group_enabled', $ring_group_enabled ? 'true' : 'false');
			});
	}

	public function ringroupdestinations(?bool $destination_enabled = null): HasMany {
		return $this->hasMany(RingGroupDestination::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($destination_enabled), function ($query) use ($destination_enabled) {
				return $query->where('destination_enabled', $destination_enabled ? 'true' : 'false');
			});
	}

	public function recordings(): HasMany {
		return $this->hasMany(Recording::class, 'domain_uuid', 'domain_uuid');
	}


}
