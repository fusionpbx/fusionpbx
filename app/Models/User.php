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

class User extends Authenticatable
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier;
	protected $table = 'v_users';
	protected $primaryKey = 'user_uuid';
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
		'username',
		'password',
		'salt',
		'user_email',
		'user_status',
		'api_key',
		'user_totp_secret',
		'user_enabled',
	];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
	protected $hidden = [
		'password',
		'salt',
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

	public function contacts(): HasMany {
		return $this->hasMany(Contact::class, 'contact_uuid', 'contact_uuid');
	}

	public function groups(): BelongsToMany {
		return $this->belongsToMany(Group::class, 'v_user_groups', 'user_uuid', 'group_uuid');
//		$this->belongsToMany(Group::class)->using(UserGroup::class);
	}

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function usersettings(): HasMany {
		return $this->hasMany(UserSetting::class, 'user_setting_uuid', 'user_setting_uuid');
	}

}
