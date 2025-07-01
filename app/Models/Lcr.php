<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HandlesStringBooleans;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Lcr extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HandlesStringBooleans, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_lcr';
	protected $primaryKey = 'lcr_uuid';
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
        'carrier_uuid',
        'digits',
        'origination_digits',
        'rate',
        'intrastate_rate',
        'intralata_rate',
        'lead_strip',
        'trail_strip',
        'prefix',
        'suffix',
        'lcr_profile',
        'date_start',
        'date_end',
        'quality',
        'reliability',
        'cid',
        'enabled',
        'description',
        'connect_increment',
        'talk_increment',
        'lcr_direction',
        'currency',
        'connect_rate',
	];

	protected static $stringBooleanFields = [
		'enabled'
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

	public function carrier(): BelongsTo {
		return $this->belongsTo(Carrier::class, 'carrier_uuid', 'carrier_uuid');
	}

}
