<?php
/**
 * FusionPBX - Recording Model
 * 
 * Eloquent model for v_recordings table.
 * Represents audio recordings in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Recording extends BaseModel
{
    protected $table = 'v_recordings';
    protected $primaryKey = 'recording_uuid';

    protected $fillable = [
        'recording_uuid', 'domain_uuid', 'recording_filename',
        'recording_name', 'recording_description', 'recording_base64',
        'insert_date', 'insert_user', 'update_date', 'update_user'
    ];

    protected $casts = [
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }
}
