<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUniqueIdentifier;

use DB;

class Destination extends Model
{
	use HasFactory, HasUniqueIdentifier;
	protected $table = 'v_destinations';
	protected $primaryKey = 'destination_uuid';
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
        'fax_uuid',
        'user_uuid',
        'group_uuid',
        'destination_type',
        'destination_number',
        'destination_trunk_prefix',
        'destination_area_code',
        'destination_condition_field',
        'destination_number_regex',
        'destination_caller_id_name',
        'destination_caller_id_number',
        'destination_cid_name_prefix',
        'destination_context',
        'destination_record',
        'destination_hold_music',
        'destination_distinctive_ring',
        'destination_accountcode',
        'destination_type_voice',
        'destination_type_fax',
        'destination_type_emergency',
        'destination_type_text',
        'destination_conditions',
        'destination_actions',
        'destination_app',
        'destination_data',
        'destination_alternate_app',
        'destination_alternate_data',
        'destination_order',
        'destination_enabled',
        'destination_description',
        'currency',
        'destination_sell',
        'destination_buy',
        'destination_carrier',
        'currency_buy',
	];

	public function user(): HasOne {
		return $this->HasOne(User::class, 'user_uuid', 'user_uuid');
	}

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function dialplan(): HasOne {
		return $this->HasOne(Dialplan::class, 'dialplan_uuid', 'dialplan_uuid');
	}

    public function fax(): HasOne {
		return $this->HasOne(Fax::class, 'fax_uuid', 'fax_uuid');
	}

    public function group(): HasOne {
		return $this->HasOne(Group::class, 'group_uuid', 'group_uuid');
	}
}
