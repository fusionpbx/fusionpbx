<?php
/**
 * FusionPBX - Gateway Model
 * 
 * Eloquent model for v_gateways table.
 * Represents a SIP gateway/trunk in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class Gateway extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_gateways';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'gateway_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'gateway_uuid',
        'domain_uuid',
        'gateway',
        'username',
        'password',
        'distinct_to',
        'auth_username',
        'realm',
        'from_user',
        'from_domain',
        'proxy',
        'register_proxy',
        'outbound_proxy',
        'expire_seconds',
        'register',
        'register_transport',
        'retry_seconds',
        'extension',
        'ping',
        'ping_min',
        'ping_max',
        'caller_id_in_from',
        'supress_cng',
        'sip_cid_type',
        'codec_prefs',
        'channels',
        'extension_in_contact',
        'context',
        'profile',
        'hostname',
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
        'expire_seconds' => 'integer',
        'retry_seconds' => 'integer',
        'ping_min' => 'integer',
        'ping_max' => 'integer',
        'channels' => 'integer',
        'register' => 'boolean',
        'ping' => 'boolean',
        'caller_id_in_from' => 'boolean',
        'supress_cng' => 'boolean',
        'extension_in_contact' => 'boolean',
        'enabled' => 'boolean',
        'insert_date' => 'datetime',
        'update_date' => 'datetime',
    ];

    /**
     * Get the domain that the gateway belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }
}
