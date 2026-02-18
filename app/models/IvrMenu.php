<?php
/**
 * FusionPBX - IvrMenu Model
 * 
 * Eloquent model for v_ivr_menus table.
 * Represents an IVR menu in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class IvrMenu extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_ivr_menus';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ivr_menu_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ivr_menu_uuid',
        'domain_uuid',
        'dialplan_uuid',
        'ivr_menu_name',
        'ivr_menu_extension',
        'ivr_menu_greet_long',
        'ivr_menu_greet_short',
        'ivr_menu_invalid_sound',
        'ivr_menu_exit_sound',
        'ivr_menu_pin_number',
        'ivr_menu_confirm_macro',
        'ivr_menu_confirm_key',
        'ivr_menu_tts_engine',
        'ivr_menu_tts_voice',
        'ivr_menu_confirm_attempts',
        'ivr_menu_timeout',
        'ivr_menu_exit_app',
        'ivr_menu_exit_data',
        'ivr_menu_inter_digit_timeout',
        'ivr_menu_max_failures',
        'ivr_menu_max_timeouts',
        'ivr_menu_digit_len',
        'ivr_menu_direct_dial',
        'ivr_menu_ringback',
        'ivr_menu_cid_prefix',
        'ivr_menu_enabled',
        'ivr_menu_description',
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
        'ivr_menu_pin_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'ivr_menu_confirm_attempts' => 'integer',
        'ivr_menu_timeout' => 'integer',
        'ivr_menu_inter_digit_timeout' => 'integer',
        'ivr_menu_max_failures' => 'integer',
        'ivr_menu_max_timeouts' => 'integer',
        'ivr_menu_digit_len' => 'integer',
        'ivr_menu_direct_dial' => 'boolean',
        'ivr_menu_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the IVR menu belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the IVR menu options.
     */
    public function options()
    {
        return $this->hasMany(IvrMenuOption::class, 'ivr_menu_uuid', 'ivr_menu_uuid');
    }

    /**
     * Get the dialplan for the IVR menu.
     */
    public function dialplan()
    {
        return $this->belongsTo(Dialplan::class, 'dialplan_uuid', 'dialplan_uuid');
    }
}
