<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasUniqueIdentifier;

class Fax extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier;
	protected $table = 'v_fax';
	protected $primaryKey = 'fax_uuid';
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
        'fax_extension',
        'fax_destination_number',
        'fax_prefix',
        'fax_name',
        'fax_email',
        'fax_email_connection_type',
        'fax_email_connection_host',
        'fax_email_connection_port',
        'fax_email_connection_security',
        'fax_email_connection_validate',
        'fax_email_connection_username',
        'fax_email_connection_password',
        'fax_email_connection_mailbox',
        'fax_email_inbound_subject_tag',
        'fax_email_outbound_subject_tag',
        'fax_email_outbout_authorized_senders',
        'fax_pin_number',
        'fax_caller_id_name',
        'fax_caller_id_number',
        'fax_toll_allow',
        'fax_forward_number',
        'fax_send_greeting',
        'fax_description',
        'accountcode',
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
		return $this->HasOne(Dialplan::class, 'dialplan_uuid', 'dialplan_uuid');
	}

	public function users(): BelongsToMany {
		return $this->belongsToMany(User::class, 'v_fax_users', 'fax_user', 'user_uuid')->withTimestamps();
//		$this->belongsToMany(Group::class)->using(UserGroup::class);
	}

    public function files(): HasMany {
		return $this->HasMany(FaxFile::class, 'fax_uuid', 'fax_uuid');
	}

	public function logs(): HasMany {
		return $this->HasMany(FaxLog::class, 'fax_log_uuid', 'fax_log_uuid');
	}

	public function queues(): HasMany {
		return $this->HasMany(FaxQueue::class, 'fax_queue_uuid', 'fax_queue_uuid');
	}

    public function tasks(): HasMany {
		return $this->HasMany(FaxTask::class, 'fax_task_uuid', 'fax_task_uuid');
	}
}
