<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUniqueIdentifier;

class Billing extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier;
	protected $table = 'v_billings';
	protected $primaryKey = 'billing_uuid';
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
		'parent_billing_uuid',
		'type',
		'type_value',
		'credit_type',
		'credit',
		'billing_cycle',
		'currency',
		'pay_days',
		'contact_uuid_from',
		'contact_uuid_to',
		'billing_notes',
		'lcr_profile',
		'balance',
		'old_balance',
		'billing_creation_date',
		'referred_by_uuid',
		'referred_depth',
		'whmcs_user_id',
		'force_postpaid_full_payment',
		'max_rate',
		'auto_topup_minimum_balance',
		'auto_topup_charge',
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

}
