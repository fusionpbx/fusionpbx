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

class FaxQueue extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier;
	protected $table = 'v_fax_queue';
	protected $primaryKey = 'fax_queue_uuid';
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
        'fax_uuid',
        'origination_uuid',
        'fax_log_uuid',
        'fax_date',
        'hostname',
        'fax_caller_id_name',
        'fax_caller_id_number',
        'fax_number',
        'fax_prefix',
        'fax_email_address',
        'fax_file',
        'fax_status',
        'fax_retry_date',
        'fax_notify_sent',
        'fax_notify_date',
        'fax_retry_count',
        'fax_accountcode',
        'fax_command',
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

	public function fax(): BelongsTo {
		return $this->belongsTo(Fax::class, 'fax_uuid', 'fax_uuid');
	}
}
