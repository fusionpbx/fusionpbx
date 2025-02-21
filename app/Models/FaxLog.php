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

class FaxLog extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier;
	protected $table = 'v_fax_logs';
	protected $primaryKey = 'fax_log_uuid';
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
        'fax_success',
        'fax_result_code',
        'fax_result_text',
        'fax_file',
        'fax_ecm_used',
        'fax_local_station_id',
        'fax_document_transferred_pages',
        'fax_document_total_pages',
        'fax_image_resolution',
        'fax_image_size',
        'fax_bad_rows',
        'fax_transfer_rate',
        'fax_retry_attempts',
        'fax_retry_limite',
        'fax_retry_sleep',
        'fax_uri',
        'fax_duration',
        'fax_date',
        'fax_epoch',
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
