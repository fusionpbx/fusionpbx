<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUniqueIdentifier;

use DB;

class FollowMe extends Model
{
	use HasFactory, HasUniqueIdentifier;
	protected $table = 'v_follow_me';
	protected $primaryKey = 'follow_me_uuid';
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
        'cid_name_prefix',
        'cid_number_prefix',
        'dial_string',
        'follow_me_enabled',
        'follow_me_ignore_busy',
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

    public function destinations(): HasMany {
		return $this->hasMany(FolowMeDestination::class, 'follow_me_uuid', 'follow_me_uuid');
	}
}
