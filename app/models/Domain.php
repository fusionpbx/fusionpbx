<?php
/**
 * FusionPBX - Domain Model
 * 
 * Eloquent model for v_domains table.
 * Represents a domain/tenant in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Domain extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_domains';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'domain_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'domain_uuid',
        'domain_name',
        'domain_parent_uuid',
        'domain_enabled',
        'domain_description',
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
        'domain_enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the users for the domain.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the extensions for the domain.
     */
    public function extensions()
    {
        return $this->hasMany(Extension::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the devices for the domain.
     */
    public function devices()
    {
        return $this->hasMany(Device::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the gateways for the domain.
     */
    public function gateways()
    {
        return $this->hasMany(Gateway::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the parent domain.
     */
    public function parent()
    {
        return $this->belongsTo(Domain::class, 'domain_parent_uuid', 'domain_uuid');
    }

    /**
     * Get the child domains.
     */
    public function children()
    {
        return $this->hasMany(Domain::class, 'domain_parent_uuid', 'domain_uuid');
    }

    /**
     * Scope a query to only include enabled domains.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('domain_enabled', 'true');
    }
}
