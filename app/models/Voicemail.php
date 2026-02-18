<?php
/**
 * FusionPBX - Voicemail Model
 * 
 * Eloquent model for v_voicemails table.
 * Represents a voicemail box in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Voicemail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_voicemails';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'voicemail_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voicemail_uuid',
        'domain_uuid',
        'voicemail_id',
        'voicemail_password',
        'greeting_id',
        'voicemail_alternate_greet_id',
        'voicemail_mail_to',
        'voicemail_attach_file',
        'voicemail_local_after_email',
        'voicemail_enabled',
        'voicemail_description',
        'voicemail_tutorial',
        'voicemail_file',
        'voicemail_keep_local_after_email',
        'voicemail_transcription_enabled',
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
        'voicemail_password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'voicemail_enabled' => 'boolean',
        'voicemail_attach_file' => 'boolean',
        'voicemail_local_after_email' => 'boolean',
        'voicemail_keep_local_after_email' => 'boolean',
        'voicemail_tutorial' => 'boolean',
        'voicemail_transcription_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the voicemail belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the voicemail messages.
     */
    public function messages()
    {
        return $this->hasMany(VoicemailMessage::class, 'voicemail_uuid', 'voicemail_uuid');
    }

    /**
     * Get the voicemail greetings.
     */
    public function greetings()
    {
        return $this->hasMany(VoicemailGreeting::class, 'voicemail_uuid', 'voicemail_uuid');
    }

    /**
     * Get the voicemail options.
     */
    public function options()
    {
        return $this->hasMany(VoicemailOption::class, 'voicemail_uuid', 'voicemail_uuid');
    }

    /**
     * Get the voicemail destinations.
     */
    public function destinations()
    {
        return $this->hasMany(VoicemailDestination::class, 'voicemail_uuid', 'voicemail_uuid');
    }
}
