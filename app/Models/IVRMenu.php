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

class IVRMenu extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_ivr_menus';
	protected $primaryKey = 'ivr_menu_uuid';
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
        'ivr_menu_name',
        'ivr_menu_extension',
        'ivr_menu_parent_uuid',
        'ivr_menu_language',
        'ivr_menu_dialect',
        'ivr_menu_voice',
        'ivr_menu_greet_long',
        'ivr_menu_greet_short',
        'ivr_menu_invalid_sound',
        'ivr_menu_exit_sound',
        'ivr_menu_pin_number',
        'ivr_menu_confirm_macro',
        'ivr_menu_confirm_key',
        'ivr_menu_tts_engine',
        'ivr_menu_tts_voice',
        'ivr_menu_confirm_attempts',
        'ivr_menu_timeout',
        'ivr_menu_exit_app',
        'ivr_menu_exit_data',
        'ivr_menu_inter_digit_timeout',
        'ivr_menu_max_failures',
        'ivr_menu_max_timeouts',
        'ivr_menu_digit_len',
        'ivr_menu_direct_dial',
        'ivr_menu_ringback',
        'ivr_menu_cid_prefix',
        'ivr_menu_context',
        'ivr_menu_enabled',
        'ivr_menu_description',
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

	public function options(): HasMany {
		return $this->hasMany(IVRMenuOption::class, 'ivr_menu_uuid', 'ivr_menu_uuid');
	}
}
