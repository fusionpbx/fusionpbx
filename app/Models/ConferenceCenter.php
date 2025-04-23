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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ConferenceCenter extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_conference_centers';
	protected $primaryKey = 'conference_center_uuid';
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
        'dialplan_uuid',
        'conference_center_name',
        'conference_center_extension',
        'conference_center_pin_length',
        'conference_center_greeting',
        'conference_center_description',
        'conference_center_enabled',
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

	public function dialplan(): HasOne {
		return $this->HasOne(DialplanDetail::class, 'dialplan_uuid', 'dialplan_uuid');
	}

    public function conferencerooms(): HasMany {
		return $this->belongsTo(ConferenceRoom::class, 'conference_center_uuid', 'conference_center_uuid');
	}
}
