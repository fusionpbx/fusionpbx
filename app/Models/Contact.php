<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUniqueIdentifier;

class Contact extends Model
{
	use HasFactory, HasUniqueIdentifier;
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
}
