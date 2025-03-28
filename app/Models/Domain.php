<?php

namespace App\Models;

use App\Traits\GetTableName;
use App\Traits\HandlesStringBooleans;
use App\Traits\HasUniqueIdentifier;
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
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName, HandlesStringBooleans;
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

	protected static $stringBooleanFields = [
		'domain_enabled'
	];

    protected function domainParentUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function domainDescription(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

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

	public function userGroups(): HasMany {
		return $this->hasMany(UserGroup::class, 'domain_uuid', 'domain_uuid');
	}

	public function groupPermissions(): HasMany {
		return $this->hasMany(GroupPermission::class, 'domain_uuid', 'domain_uuid');
	}

	public function settings(?bool $domain_setting_enabled = null): HasMany {
		return $this->hasMany(DomainSetting::class, 'domain_uuid', 'domain_uuid')
                ->when(is_bool($domain_setting_enabled), function($query) use($domain_setting_enabled){
                    return $query->where('domain_setting_enabled', $domain_setting_enabled ? 'true' : 'false');
                });
	}

	public function gateways(?bool $enabled = null): HasMany {
        return $this->hasMany(Gateway::class, 'domain_uuid', 'domain_uuid')
            ->when(is_bool($enabled), function($query) use($enabled){
                return $query->where('enabled', $enabled ? 'true' : 'false');
            });
	}

	public function voicemails(): HasMany {
		return $this->hasMany(Voicemail::class, 'domain_uuid', 'domain_uuid');
	}

	public function voicemailGreetins(): HasMany {
		return $this->hasMany(VoicemailGreeting::class, 'domain_uuid', 'domain_uuid');
	}

	public function xmlCDR(): HasMany {
		return $this->hasMany(XmlCDR::class, 'domain_uuid', 'domain_uuid');
	}

	public function musiconHold(): HasMany {
		return $this->hasMany(MusicOnHold::class, 'domain_uuid', 'domain_uuid');
	}

	public function billingProfiless(): HasMany {
		return $this->hasMany(BillingProfile::class, 'domain_uuid', 'domain_uuid');
	}

	public function billingAuthorizedPaymentSources(): HasMany {
		return $this->hasMany(BillingAuthorizedPaymentSource::class, 'domain_uuid', 'domain_uuid');
	}

	public function billingDeals(): HasMany {
		return $this->hasMany(BillingDeal::class, 'domain_uuid', 'domain_uuid');
	}

	public function billingInvoices(): HasMany {
		return $this->hasMany(BillingInvoice::class, 'domain_uuid', 'domain_uuid');
	}

	public function bridges(?bool $bridge_enabled = null): HasMany {
		return $this->hasMany(Bridge::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($bridge_enabled), function ($query) use ($bridge_enabled) {
				return $query->where('bridge_enabled', $bridge_enabled ? 'true' : 'false');
			});
	}

	public function callBlocks(): HasMany {
		return $this->hasMany(CallBlock::class, 'domain_uuid', 'domain_uuid');
	}

	public function callBroadcasts(): HasMany {
		return $this->hasMany(CallBroadcast::class, 'domain_uuid', 'domain_uuid');
	}

	public function callCenterQueues(): HasMany {
		return $this->hasMany(CallCenterQueue::class, 'domain_uuid', 'domain_uuid');
	}

	public function callCenterAgents(): HasMany {
		return $this->hasMany(CallCenterAgent::class, 'domain_uuid', 'domain_uuid');
	}

	public function callFlows(): HasMany {
		return $this->hasMany(CallFlow::class, 'domain_uuid', 'domain_uuid');
	}

	public function conferences(?bool $conference_enabled = null): HasMany {
		return $this->hasMany(Conference::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($conference_enabled), function ($query) use ($conference_enabled) {
				return $query->where('conference_enabled', $conference_enabled ? 'true' : 'false');
			});
	}

	public function conferenceCenters(?bool $conference_center_enabled = null): HasMany {
		return $this->hasMany(ConferenceCenter::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($conference_center_enabled), function ($query) use ($conference_center_enabled) {
				return $query->where('conference_center_enabled', $conference_center_enabled ? 'true' : 'false');
			});
	}

	public function conferenceRooms(): HasMany {
		return $this->hasMany(ConferenceRoom::class, 'domain_uuid', 'domain_uuid');
	}

	public function contactSettings(?bool $contact_setting_enabled = null): HasMany {
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

	public function deviceProfiles(?bool $device_profile_enabled = null): HasMany {
		return $this->hasMany(DeviceProfile::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($device_profile_enabled), function ($query) use ($device_profile_enabled) {
				return $query->where('device_profile_enabled', $device_profile_enabled ? 'true' : 'false');
			});
	}

	public function deviceProfileSettings(?bool $profile_setting_enabled = null): HasMany {
		return $this->hasMany(DeviceProfileSetting::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($profile_setting_enabled), function ($query) use ($profile_setting_enabled) {
				return $query->where('profile_setting_enabled', $profile_setting_enabled ? 'true' : 'false');
			});
	}

	public function deviceSettings(?bool $device_setting_enabled = null): HasMany {
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

	public function dialplanDetails(?bool $dialplan_detail_enabled = null): HasMany {
		return $this->hasMany(DialplanDetail::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($dialplan_detail_enabled), function ($query) use ($dialplan_detail_enabled) {
				return $query->where('dialplan_detail_enabled', $dialplan_detail_enabled ? 'true' : 'false');
			});
	}

	public function extensions(): HasMany {
		return $this->hasMany(Extension::class, 'domain_uuid', 'domain_uuid');
	}

	public function extensionSettings(?bool $extension_setting_enabled = null): HasMany {
		return $this->hasMany(ExtensionSetting::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($extension_setting_enabled), function ($query) use ($extension_setting_enabled) {
				return $query->where('extension_setting_enabled', $extension_setting_enabled ? 'true' : 'false');
			});
	}

	public function followMe(?bool $follow_me_enabled = null): HasMany {
		return $this->hasMany(FollowMe::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($follow_me_enabled), function ($query) use ($follow_me_enabled) {
				return $query->where('follow_me_enabled', $follow_me_enabled ? 'true' : 'false');
			});
	}

	public function ivrMenus(?bool $ivr_menu_enabled = null): HasMany {
		return $this->hasMany(IVRMenu::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($ivr_menu_enabled), function ($query) use ($ivr_menu_enabled) {
				return $query->where('ivr_menu_enabled', $ivr_menu_enabled ? 'true' : 'false');
			});
	}

	public function ivrMenuOptions(?bool $ivr_menu_option_enabled = null): HasMany {
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

	public function ringGroupForward(?bool $ring_group_forward_enabled = null): HasMany {
		return $this->hasMany(RingGroup::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($ring_group_forward_enabled), function ($query) use ($ring_group_forward_enabled) {
				return $query->where('ring_group_forward_enabled', $ring_group_forward_enabled ? 'true' : 'false');
			});
	}

	public function ringGroupCallForward(?bool $ring_group_call_forward_enabled = null): HasMany {
		return $this->hasMany(RingGroup::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($ring_group_call_forward_enabled), function ($query) use ($ring_group_call_forward_enabled) {
				return $query->where('ring_group_call_forward_enabled', $ring_group_call_forward_enabled ? 'true' : 'false');
			});
	}

	public function ringGroups(?bool $ring_group_enabled = null): HasMany {
		return $this->hasMany(RingGroup::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($ring_group_enabled), function ($query) use ($ring_group_enabled) {
				return $query->where('ring_group_enabled', $ring_group_enabled ? 'true' : 'false');
			});
	}

	public function ringGroupDestinations(?bool $destination_enabled = null): HasMany {
		return $this->hasMany(RingGroupDestination::class, 'domain_uuid', 'domain_uuid')
			->when(is_bool($destination_enabled), function ($query) use ($destination_enabled) {
				return $query->where('destination_enabled', $destination_enabled ? 'true' : 'false');
			});
	}

	public function recordings(): HasMany {
		return $this->hasMany(Recording::class, 'domain_uuid', 'domain_uuid');
	}


}
