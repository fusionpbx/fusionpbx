<?php

namespace App\Models;

use App\Traits\HasUniqueIdentifier;
use App\Traits\GetTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Setting extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_settings';
	protected $primaryKey = 'setting_uuid';
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
        'numbering_plan',
        'event_socket_ip_address',
        'event_socket_port',
        'event_socket_password',
        'event_socket_acl',
        'xml_rpc_http_port',
        'xml_rpc_auth_realm',
        'xml_rpc_auth_user',
        'xml_rpc_auth_pass',
        'admin_pin',
        'mod_shout_decoder',
        'mod_shout_volume',
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

	public function sipprofile(): BelongsTo {
		return $this->belongsTo(SipProfile::class, 'sip_profile_uuid', 'sip_profile_uuid');
	}
}
