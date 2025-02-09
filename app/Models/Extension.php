<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUniqueIdentifier;

class Extension extends Authenticatable
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier;
	protected $table = 'v_extensions';
	protected $primaryKey = 'extension_uuid';
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
		'extension',
		'number_alias',
		'password',
		'accountcode',
		'effective_caller_id_name',
		'effective_caller_id_number',
		'outbound_caller_id_name',
		'outbound_caller_id_number',
		'emergency_caller_id_name',
		'emergency_caller_id_number',
		'directory_first_name',
		'directory_last_name',
		'directory_visible',
		'max_registrations',
		'limit_max',
		'limit_destination',
		'missed_call_app',
		'missed_call_data',
		'user_context',
		'toll_allow',
		'call_timeout',
		'call_group',
		'call_screen_enabled',
		'user_record',
		'hold_music',
		'auth_acl',
		'cidr',
		'sip_force_contact',
		'nibble_account',
		'sip_force_expires',
		'mwi_account',
		'sip_bypass_media',
		'unique_id',
		'dial_string',
		'dial_user',
		'dial_domain',
		'do_not_disturb',
		'forward_all_destination',
		'forward_all_enabled',
		'forward_busy_destination',
		'forward_busy_enabled',
		'forward_no_answer_destination',
		'forward_no_answer_enabled',
		'forward_user_not_registered_destination',
		'forward_user_not_registered_enabled',
		'follow_me_uuid',
		'follow_me_enabled',
		'follow_me_destinations',
		'enabled',
		'description',
		'absolute_codec_string',
		'force_ping',
	];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
	protected $hidden = [
		'password',
		'remember_token',
	];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
	protected $casts = [
		'email_verified_at' => 'datetime',
		'password' => 'hashed',
	];

	public function groups(): BelongsToMany {
		return $this->belongsToMany(Group::class, 'v_extension_users', 'extension_uuid', 'user_uuid');
//		$this->belongsToMany(Group::class)->using(UserGroup::class);
	}

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}
}
