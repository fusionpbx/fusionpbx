<?php
/**
 * FusionPBX - Conference Model
 * 
 * Eloquent model for v_conferences table.
 * Represents a conference room in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Conference extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_conferences';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'conference_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'conference_uuid',
        'domain_uuid',
        'conference_name',
        'conference_extension',
        'conference_pin_number',
        'conference_profile',
        'conference_flags',
        'conference_order',
        'conference_enabled',
        'conference_description',
        'dialplan_uuid',
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
        'conference_pin_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'conference_order' => 'integer',
        'conference_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the conference belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the conference users.
     */
    public function users()
    {
        return $this->hasMany(ConferenceUser::class, 'conference_uuid', 'conference_uuid');
    }

    /**
     * Get the conference sessions.
     */
    public function sessions()
    {
        return $this->hasMany(ConferenceSession::class, 'conference_uuid', 'conference_uuid');
    }

    /**
     * Get the dialplan associated with the conference.
     */
    public function dialplan()
    {
        return $this->belongsTo(Dialplan::class, 'dialplan_uuid', 'dialplan_uuid');
    }
}
