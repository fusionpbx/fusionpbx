<?php
/**
 * FusionPBX - Additional Supporting Models
 * 
 * This file contains Eloquent models for supporting tables
 * that are commonly used with main entities.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

// ============================================================================
// Extension Related Models
// ============================================================================

class ExtensionSetting extends BaseModel {
    protected $table = 'v_extension_settings';
    protected $primaryKey = 'extension_setting_uuid';
    protected $fillable = ['extension_setting_uuid', 'extension_uuid', 'domain_uuid', 
        'extension_setting_name', 'extension_setting_value', 'extension_setting_enabled', 
        'extension_setting_description'];
    
    public function extension() {
        return $this->belongsTo(Extension::class, 'extension_uuid', 'extension_uuid');
    }
}

class ExtensionUser extends BaseModel {
    protected $table = 'v_extension_users';
    protected $primaryKey = 'extension_user_uuid';
    protected $fillable = ['extension_user_uuid', 'domain_uuid', 'extension_uuid', 'user_uuid'];
}

// ============================================================================
// Voicemail Related Models
// ============================================================================

class VoicemailMessage extends BaseModel {
    protected $table = 'v_voicemail_messages';
    protected $primaryKey = 'voicemail_message_uuid';
    protected $fillable = ['voicemail_message_uuid', 'voicemail_uuid', 'domain_uuid', 
        'created_epoch', 'read_epoch', 'caller_id_name', 'caller_id_number', 
        'message_length', 'message_status', 'message_priority'];
    
    public function voicemail() {
        return $this->belongsTo(Voicemail::class, 'voicemail_uuid', 'voicemail_uuid');
    }
}

class VoicemailGreeting extends BaseModel {
    protected $table = 'v_voicemail_greetings';
    protected $primaryKey = 'voicemail_greeting_uuid';
    protected $fillable = ['voicemail_greeting_uuid', 'voicemail_id', 'domain_uuid', 
        'greeting_id', 'greeting_name', 'greeting_description', 'greeting_filename'];
    
    public function voicemail() {
        return $this->belongsTo(Voicemail::class, 'voicemail_id', 'voicemail_id');
    }
}

class VoicemailOption extends BaseModel {
    protected $table = 'v_voicemail_options';
    protected $primaryKey = 'voicemail_option_uuid';
    protected $fillable = ['voicemail_option_uuid', 'voicemail_uuid', 'domain_uuid', 
        'voicemail_option_digits', 'voicemail_option_action', 'voicemail_option_param', 
        'voicemail_option_order', 'voicemail_option_description'];
}

class VoicemailDestination extends BaseModel {
    protected $table = 'v_voicemail_destinations';
    protected $primaryKey = 'voicemail_destination_uuid';
    protected $fillable = ['voicemail_destination_uuid', 'voicemail_uuid', 'domain_uuid'];
}

// ============================================================================
// User Related Models
// ============================================================================
// Note: UserSetting is now a standalone model in UserSetting.php

class UserGroup extends BaseModel {
    protected $table = 'v_user_groups';
    protected $primaryKey = 'user_group_uuid';
    protected $fillable = ['user_group_uuid', 'domain_uuid', 'group_uuid', 'user_uuid'];
}

// ============================================================================
// Device Related Models
// ============================================================================

class DeviceLine extends BaseModel {
    protected $table = 'v_device_lines';
    protected $primaryKey = 'device_line_uuid';
    protected $fillable = ['device_line_uuid', 'device_uuid', 'domain_uuid', 
        'server_address', 'outbound_proxy', 'line_number', 'display_name', 
        'user_id', 'auth_id', 'password', 'sip_port', 'sip_transport', 
        'register_expires', 'enabled', 'extension_uuid'];
    
    public function device() {
        return $this->belongsTo(Device::class, 'device_uuid', 'device_uuid');
    }
    
    public function extension() {
        return $this->belongsTo(Extension::class, 'extension_uuid', 'extension_uuid');
    }
}

class DeviceKey extends BaseModel {
    protected $table = 'v_device_keys';
    protected $primaryKey = 'device_key_uuid';
    protected $fillable = ['device_key_uuid', 'device_uuid', 'domain_uuid', 
        'device_key_category', 'device_key_id', 'device_key_type', 
        'device_key_line', 'device_key_value', 'device_key_extension', 
        'device_key_label'];
    
    public function device() {
        return $this->belongsTo(Device::class, 'device_uuid', 'device_uuid');
    }
}

class DeviceSetting extends BaseModel {
    protected $table = 'v_device_settings';
    protected $primaryKey = 'device_setting_uuid';
    protected $fillable = ['device_setting_uuid', 'device_uuid', 'domain_uuid', 
        'device_setting_category', 'device_setting_subcategory', 
        'device_setting_name', 'device_setting_value', 'device_setting_enabled'];
    
    public function device() {
        return $this->belongsTo(Device::class, 'device_uuid', 'device_uuid');
    }
}

class DeviceProfile extends BaseModel {
    protected $table = 'v_device_profiles';
    protected $primaryKey = 'device_profile_uuid';
    protected $fillable = ['device_profile_uuid', 'domain_uuid', 'device_profile_name', 
        'device_profile_enabled', 'device_profile_description'];
    
    public function devices() {
        return $this->hasMany(Device::class, 'device_profile_uuid', 'device_profile_uuid');
    }
}

// ============================================================================
// Dialplan Related Models
// ============================================================================

class DialplanDetail extends BaseModel {
    protected $table = 'v_dialplan_details';
    protected $primaryKey = 'dialplan_detail_uuid';
    protected $fillable = ['dialplan_detail_uuid', 'dialplan_uuid', 'domain_uuid', 
        'dialplan_detail_tag', 'dialplan_detail_order', 'dialplan_detail_group', 
        'dialplan_detail_type', 'dialplan_detail_data', 'dialplan_detail_break', 
        'dialplan_detail_inline'];
    
    public function dialplan() {
        return $this->belongsTo(Dialplan::class, 'dialplan_uuid', 'dialplan_uuid');
    }
}

// ============================================================================
// Contact Related Models
// ============================================================================

class ContactPhone extends BaseModel {
    protected $table = 'v_contact_phones';
    protected $primaryKey = 'contact_phone_uuid';
    protected $fillable = ['contact_phone_uuid', 'contact_uuid', 'domain_uuid', 
        'phone_type_voice', 'phone_type_fax', 'phone_type_video', 
        'phone_type_text', 'phone_number', 'phone_extension', 
        'phone_label', 'phone_primary'];
    
    public function contact() {
        return $this->belongsTo(Contact::class, 'contact_uuid', 'contact_uuid');
    }
}

class ContactAddress extends BaseModel {
    protected $table = 'v_contact_addresses';
    protected $primaryKey = 'contact_address_uuid';
    protected $fillable = ['contact_address_uuid', 'contact_uuid', 'domain_uuid', 
        'address_type', 'address_label', 'address_street', 'address_extended', 
        'address_community', 'address_locality', 'address_region', 
        'address_postal_code', 'address_country', 'address_latitude', 
        'address_longitude', 'address_primary'];
    
    public function contact() {
        return $this->belongsTo(Contact::class, 'contact_uuid', 'contact_uuid');
    }
}

class ContactEmail extends BaseModel {
    protected $table = 'v_contact_emails';
    protected $primaryKey = 'contact_email_uuid';
    protected $fillable = ['contact_email_uuid', 'contact_uuid', 'domain_uuid', 
        'email_address', 'email_label', 'email_primary'];
    
    public function contact() {
        return $this->belongsTo(Contact::class, 'contact_uuid', 'contact_uuid');
    }
}

class ContactNote extends BaseModel {
    protected $table = 'v_contact_notes';
    protected $primaryKey = 'contact_note_uuid';
    protected $fillable = ['contact_note_uuid', 'contact_uuid', 'domain_uuid', 
        'contact_note', 'last_mod_date', 'last_mod_user'];
    
    public function contact() {
        return $this->belongsTo(Contact::class, 'contact_uuid', 'contact_uuid');
    }
}

class ContactAttachment extends BaseModel {
    protected $table = 'v_contact_attachments';
    protected $primaryKey = 'contact_attachment_uuid';
    protected $fillable = ['contact_attachment_uuid', 'contact_uuid', 'domain_uuid', 
        'attachment_name', 'attachment_description'];
    
    public function contact() {
        return $this->belongsTo(Contact::class, 'contact_uuid', 'contact_uuid');
    }
}

// ============================================================================
// Call Center Related Models
// ============================================================================

class CallCenterTier extends BaseModel {
    protected $table = 'v_call_center_tiers';
    protected $primaryKey = 'call_center_tier_uuid';
    protected $fillable = ['call_center_tier_uuid', 'domain_uuid', 
        'call_center_agent_uuid', 'call_center_queue_uuid', 
        'tier_level', 'tier_position'];
    
    public function agent() {
        return $this->belongsTo(CallCenterAgent::class, 'call_center_agent_uuid', 'call_center_agent_uuid');
    }
    
    public function queue() {
        return $this->belongsTo(CallCenterQueue::class, 'call_center_queue_uuid', 'call_center_queue_uuid');
    }
}

// ============================================================================
// Conference Related Models
// ============================================================================

class ConferenceUser extends BaseModel {
    protected $table = 'v_conference_users';
    protected $primaryKey = 'conference_user_uuid';
    protected $fillable = ['conference_user_uuid', 'domain_uuid', 'conference_uuid', 'user_uuid'];
    
    public function conference() {
        return $this->belongsTo(Conference::class, 'conference_uuid', 'conference_uuid');
    }
    
    public function user() {
        return $this->belongsTo(User::class, 'user_uuid', 'user_uuid');
    }
}

class ConferenceSession extends BaseModel {
    protected $table = 'v_conference_sessions';
    protected $primaryKey = 'conference_session_uuid';
    protected $fillable = ['conference_session_uuid', 'domain_uuid', 'conference_uuid', 
        'meeting_uuid', 'profile', 'recording', 'start_epoch', 'end_epoch'];
    
    public function conference() {
        return $this->belongsTo(Conference::class, 'conference_uuid', 'conference_uuid');
    }
}

// ============================================================================
// IVR Related Models
// ============================================================================

class IvrMenuOption extends BaseModel {
    protected $table = 'v_ivr_menu_options';
    protected $primaryKey = 'ivr_menu_option_uuid';
    protected $fillable = ['ivr_menu_option_uuid', 'ivr_menu_uuid', 'domain_uuid', 
        'ivr_menu_option_digits', 'ivr_menu_option_action', 'ivr_menu_option_param', 
        'ivr_menu_option_order', 'ivr_menu_option_description'];
    
    public function ivrMenu() {
        return $this->belongsTo(IvrMenu::class, 'ivr_menu_uuid', 'ivr_menu_uuid');
    }
}

// ============================================================================
// Ring Group Related Models
// ============================================================================

class RingGroupDestination extends BaseModel {
    protected $table = 'v_ring_group_destinations';
    protected $primaryKey = 'ring_group_destination_uuid';
    protected $fillable = ['ring_group_destination_uuid', 'domain_uuid', 
        'ring_group_uuid', 'destination_number', 'destination_delay', 
        'destination_timeout', 'destination_prompt'];
    
    public function ringGroup() {
        return $this->belongsTo(RingGroup::class, 'ring_group_uuid', 'ring_group_uuid');
    }
}

class RingGroupUser extends BaseModel {
    protected $table = 'v_ring_group_users';
    protected $primaryKey = 'ring_group_user_uuid';
    protected $fillable = ['ring_group_user_uuid', 'domain_uuid', 'ring_group_uuid', 'user_uuid'];
    
    public function ringGroup() {
        return $this->belongsTo(RingGroup::class, 'ring_group_uuid', 'ring_group_uuid');
    }
    
    public function user() {
        return $this->belongsTo(User::class, 'user_uuid', 'user_uuid');
    }
}

// ============================================================================
// Follow Me Related Models
// ============================================================================

class FollowMe extends BaseModel {
    protected $table = 'v_follow_me';
    protected $primaryKey = 'follow_me_uuid';
    protected $fillable = ['follow_me_uuid', 'domain_uuid', 'follow_me_enabled'];
    
    public function destinations() {
        return $this->hasMany(FollowMeDestination::class, 'follow_me_uuid', 'follow_me_uuid');
    }
}

class FollowMeDestination extends BaseModel {
    protected $table = 'v_follow_me_destinations';
    protected $primaryKey = 'follow_me_destination_uuid';
    protected $fillable = ['follow_me_destination_uuid', 'domain_uuid', 
        'follow_me_uuid', 'follow_me_destination', 'follow_me_timeout', 
        'follow_me_delay', 'follow_me_prompt', 'follow_me_order'];
    
    public function followMe() {
        return $this->belongsTo(FollowMe::class, 'follow_me_uuid', 'follow_me_uuid');
    }
}

// ============================================================================
// Fax Related Models
// ============================================================================

class FaxFile extends BaseModel {
    protected $table = 'v_fax_files';
    protected $primaryKey = 'fax_file_uuid';
    protected $fillable = ['fax_file_uuid', 'fax_uuid', 'domain_uuid', 
        'fax_mode', 'fax_file_type', 'fax_caller_id_name', 
        'fax_caller_id_number', 'fax_date', 'fax_epoch', 'fax_base64'];
    
    public function fax() {
        return $this->belongsTo(Fax::class, 'fax_uuid', 'fax_uuid');
    }
}

// ============================================================================
// Group Related Models
// ============================================================================
// Note: GroupPermission is now a standalone model in GroupPermission.php
