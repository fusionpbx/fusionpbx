<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
	use HasFactory, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_contacts';
	protected $primaryKey = 'contact_uuid';
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
		'contact_parent_uuid',
		'contact_type',
		'contact_organization',
		'contact_name_prefix',
        'contact_name_given',
        'contact_name_middle',
        'contact_name_family',
        'contact_name_suffix',
        'contact_nickname',
        'contact_title',
        'contact_role',
        'contact_category',
        'contact_url',
        'contact_time_zone',
        'contact_note',
	];

    public function users(): HasMany {
		return $this->hasMany(User::class, 'contact_uuid', 'contact_uuid');
	}

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function addresses(): HasMany {
		return $this->hasMany(ContactAddress::class, 'contact_address_uuid', 'contact_address_uuid');
	}

	public function attachments(): HasMany {
		return $this->hasMany(ContactAttachment::class, 'contact_attachment_uuid', 'contact_attachment_uuid');
	}

	public function emails(): HasMany {
		return $this->hasMany(ContactEmail::class, 'contact_email_uuid', 'contact_email_uuid');
	}

	public function groups(): BelongsToMany {
		return $this->belongsToMany(Group::class, 'v_contact_groups', 'contact_uuid', 'group_uuid');
//		$this->belongsToMany(Group::class)->using(UserGroup::class);
	}

	public function notes(): HasMany {
		return $this->hasMany(ContactNote::class, 'contact_note_uuid', 'contact_note_uuid');
	}

	public function phones(): HasMany {
		return $this->hasMany(ContactPhone::class, 'contact_phone_uuid', 'contact_phone_uuid');
	}

	// TODO: Review
	public function relations(): HasMany {
		return $this->hasMany(ContactRelation::class, 'contact_relation_uuid', 'contact_relation_uuid');
	}

	public function settings(): HasMany {
		return $this->hasMany(ContactSetting::class, 'contact_setting_uuid', 'contact_setting_uuid');
	}

	// TODO: Check if User::class needs this method as well
	public function times(): HasMany {
		return $this->hasMany(ContactTimes::class, 'contact_setting_uuid', 'contact_setting_uuid');
	}

	public function urls(): HasMany {
		return $this->hasMany(ContactUrl::class, 'contact_url_uuid', 'contact_url_uuid');
	}

	public function contactusers(): BelongsToMany {
		return $this->belongsToMany(User::class, 'v_contact_users', 'contact_uuid', 'user_uuid')->withTimestamps();
//		$this->belongsToMany(Group::class)->using(UserGroup::class);
	}
}
