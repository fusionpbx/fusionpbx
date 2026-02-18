<?php
/**
 * FusionPBX - Dialplan Model
 * 
 * Eloquent model for v_dialplans table.
 * Represents a dialplan in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Dialplan extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_dialplans';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'dialplan_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dialplan_uuid',
        'domain_uuid',
        'app_uuid',
        'hostname',
        'dialplan_context',
        'dialplan_name',
        'dialplan_number',
        'dialplan_continue',
        'dialplan_xml',
        'dialplan_order',
        'dialplan_enabled',
        'dialplan_description',
        'insert_date',
        'insert_user',
        'update_date',
        'update_user'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'dialplan_order' => 'integer',
        'dialplan_enabled' => 'boolean',
        'dialplan_continue' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the dialplan belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the dialplan details.
     */
    public function details()
    {
        return $this->hasMany(DialplanDetail::class, 'dialplan_uuid', 'dialplan_uuid');
    }

    /**
     * Scope a query to only include enabled dialplans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('dialplan_enabled', 'true');
    }
}
