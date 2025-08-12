<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use App\Traits\HandlesStringBooleans;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class Billing extends Model
{
	use HasApiTokens, HasFactory, Notifiable, GetTableName, HasUniqueIdentifier, HandlesStringBooleans;
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
		'domain_uuid',
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
		'referred_percentage',
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

	protected static $stringBooleanFields = [
		'force_postpaid_full_payment',
	];

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function billingauthorizedpaymentsources(): HasMany {
		return $this->hasMany(Billing::class, 'billing_uuid', 'billing_uuid');
	}

	public function billingFixedCharges(): HasMany {
		return $this->hasMany(BillingFixedCharge::class, 'billing_uuid', 'billing_uuid');
	}

	public function billinginvoices(): HasMany {
		return $this->hasMany(BillingInvoice::class, 'billing_uuid', 'billing_uuid');
	}

	public function contactFrom(): BelongsTo {
		return $this->belongsTo(Contact::class, 'contact_uuid_from', 'contact_uuid');
	}

	public function contactTo(): BelongsTo {
		return $this->belongsTo(Contact::class, 'contact_uuid_to', 'contact_uuid');
	}

	public function deals(): BelongsToMany {
		return $this->belongsToMany(BillingDeal::class, 'v_billing_profile_deals', 'billing_uuid', 'billing_deal_uuid');
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}

	public static function parentProfiles(?string $excludeBillingUuid = null)
	{
		return self::join('v_contacts', 'v_contacts.contact_uuid', '=', 'v_billings.contact_uuid_to')
			->select(
				'v_billings.billing_uuid',
				'v_contacts.contact_uuid',
				'v_contacts.contact_organization',
				'v_contacts.contact_name_given',
				'v_contacts.contact_name_family'
			)
			->when($excludeBillingUuid, function ($query, $excludeBillingUuid) {
				$query->where('v_billings.billing_uuid', '<>', $excludeBillingUuid);
			})
			->get();
	}
}
