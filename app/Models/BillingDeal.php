<?php

namespace App\Models;

use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use App\Traits\HasUniqueIdentifier;
use Laravel\Sanctum\HasApiTokens;

class BillingDeal extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier;
	protected $table = 'v_billing_deals';
	protected $primaryKey = 'billing_deal_uuid';
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
		'direction',
		'digits',
		'minutes',
		'rate',
		'currency',
		'billing_deal_notes',
		'label'
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

	public function billingprofiles(): BelongsToMany {
		return $this->belongsToMany(BillingProfile::class, 'v_billing_profile_deals', 'billing_deal_uuid', 'billing_uuid');
//		$this->belongsToMany(User::class)->using(UserGroup::class);
	}

}
