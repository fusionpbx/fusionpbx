<?php
/**
 * FusionPBX - Extension Model
 * 
 * Eloquent model for v_extensions table.
 * Represents a SIP extension in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Extension extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_extensions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'extension_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'extension_uuid',
        'domain_uuid',
        'extension',
        'number_alias',
        'password',
        'accountcode',
        'effective_caller_id_name',
        'effective_caller_id_number',
        'outbound_caller_id_name',
        'outbound_caller_id_number',
        'emergency_caller_id_name',
        'emergency_caller_id_number',
        'directory_first_name',
        'directory_last_name',
        'directory_visible',
        'directory_exten_visible',
        'limit_max',
        'limit_destination',
        'missed_call_app',
        'missed_call_data',
        'user_context',
        'toll_allow',
        'call_timeout',
        'call_group',
        'call_screen_enabled',
        'record_name',
        'record_description',
        'auth_acl',
        'cidr',
        'sip_force_contact',
        'nibble_account',
        'sip_force_expires',
        'mwi_account',
        'sip_bypass_media',
        'absolute_codec_string',
        'force_ping',
        'dial_string',
        'dial_user',
        'dial_domain',
        'do_not_disturb',
        'forward_all_destination',
        'forward_all_enabled',
        'forward_busy_destination',
        'forward_busy_enabled',
        'forward_no_answer_destination',
        'forward_no_answer_enabled',
        'forward_user_not_registered_destination',
        'forward_user_not_registered_enabled',
        'follow_me_uuid',
        'enabled',
        'description',
        'insert_date',
        'insert_user',
        'update_date',
        'update_user'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'directory_visible' => 'boolean',
        'directory_exten_visible' => 'boolean',
        'call_screen_enabled' => 'boolean',
        'do_not_disturb' => 'boolean',
        'forward_all_enabled' => 'boolean',
        'forward_busy_enabled' => 'boolean',
        'forward_no_answer_enabled' => 'boolean',
        'forward_user_not_registered_enabled' => 'boolean',
        'enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the extension belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the users associated with the extension.
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'v_extension_users',
            'extension_uuid',
            'user_uuid'
        );
    }

    /**
     * Get the voicemail associated with the extension.
     */
    public function voicemail()
    {
        return $this->hasOne(Voicemail::class, 'extension_uuid', 'extension_uuid');
    }

    /**
     * Get the extension settings.
     */
    public function settings()
    {
        return $this->hasMany(ExtensionSetting::class, 'extension_uuid', 'extension_uuid');
    }

    /**
     * Get the devices associated with the extension.
     */
    public function deviceLines()
    {
        return $this->hasMany(DeviceLine::class, 'extension_uuid', 'extension_uuid');
    }

    /**
     * Get the follow me settings.
     */
    public function followMe()
    {
        return $this->belongsTo(FollowMe::class, 'follow_me_uuid', 'follow_me_uuid');
    }
}
