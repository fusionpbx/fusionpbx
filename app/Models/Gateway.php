<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUniqueIdentifier;

class Gateway extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier;
	protected $table = 'v_gateways';
	protected $primaryKey = 'gateway_uuid';
	public $incrementing = false;
	protected $keyType = 'string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
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
    'contact_params',
    'retry_seconds',
    'extension',
    'ping',
    'ping_min',
    'ping_max',
    'contact_in_ping',
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
	];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
	protected $hidden = [
	];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
	protected $casts = [
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}
}
