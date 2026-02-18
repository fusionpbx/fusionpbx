<?php
/**
 * FusionPBX - MusicOnHold Model
 * 
 * Eloquent model for v_music_on_hold table.
 * Represents music on hold categories in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class MusicOnHold extends BaseModel
{
    protected $table = 'v_music_on_hold';
    protected $primaryKey = 'music_on_hold_uuid';

    protected $fillable = [
        'music_on_hold_uuid', 'domain_uuid', 'music_on_hold_name',
        'music_on_hold_path', 'music_on_hold_rate', 'music_on_hold_shuffle',
        'music_on_hold_channels', 'music_on_hold_interval', 'music_on_hold_timer_name',
        'music_on_hold_chime_list', 'music_on_hold_chime_freq', 'music_on_hold_chime_max',
        'insert_date', 'insert_user', 'update_date', 'update_user'
    ];

    protected $casts = [
        'music_on_hold_rate' => 'integer',
        'music_on_hold_channels' => 'integer',
        'music_on_hold_interval' => 'integer',
        'music_on_hold_chime_freq' => 'integer',
        'music_on_hold_chime_max' => 'integer',
        'music_on_hold_shuffle' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }
}
