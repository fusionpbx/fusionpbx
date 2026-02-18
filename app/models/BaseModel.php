<?php
/**
 * FusionPBX - Base Eloquent Model
 * 
 * Base model class for all FusionPBX Eloquent models.
 * Provides common functionality and configuration.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     * FusionPBX uses UUIDs as primary keys.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     * FusionPBX uses UUIDs (strings) as primary keys.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The connection name for the model.
     * Uses the default connection configured in eloquent_bootstrap.php
     *
     * @var string|null
     */
    protected $connection = null;

    /**
     * Indicates if the model should be timestamped.
     * Most FusionPBX tables use insert_date/insert_user/update_date/update_user
     * instead of created_at/updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * Get the table associated with the model.
     * FusionPBX tables use 'v_' prefix by default.
     *
     * @return string
     */
    public function getTable()
    {
        if (!isset($this->table)) {
            // Get the class name without namespace
            $className = class_basename($this);
            
            // Convert from PascalCase to snake_case
            $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
            
            // Add v_ prefix if not present
            $this->table = 'v_' . $snakeCase;
        }

        return $this->table;
    }

    /**
     * Scope a query to only include records for a specific domain.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $domainUuid
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDomain($query, $domainUuid)
    {
        return $query->where('domain_uuid', $domainUuid);
    }

    /**
     * Scope a query to only include enabled records.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', 'true');
    }

    /**
     * Scope a query to only include disabled records.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisabled($query)
    {
        return $query->where('enabled', 'false');
    }
}
