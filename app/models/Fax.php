<?php
/**
 * FusionPBX - Fax Model
 * 
 * Eloquent model for v_fax table.
 * Represents a fax extension in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Fax extends BaseModel
{
    protected $table = 'v_fax';
    protected $primaryKey = 'fax_uuid';

    protected $fillable = [
        'fax_uuid', 'domain_uuid', 'dialplan_uuid', 'fax_extension',
        'fax_destination_number', 'fax_name', 'fax_email', 'fax_pin_number',
        'fax_caller_id_name', 'fax_caller_id_number', 'fax_forward_number',
        'fax_description', 'fax_enabled', 'insert_date', 'insert_user',
        'update_date', 'update_user'
    ];

    protected $hidden = ['fax_pin_number'];

    protected $casts = [
        'fax_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    public function files()
    {
        return $this->hasMany(FaxFile::class, 'fax_uuid', 'fax_uuid');
    }
}
