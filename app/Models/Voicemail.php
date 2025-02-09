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

class Voicemail extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier;
	protected $table = 'v_voicemails';
	protected $primaryKey = 'voicemail_uuid';
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
    'voicemail_id',
    'voicemail_password',
    'greeting_id',
    'voicemail_alternate_greet_id',
    'voicemail_mail_to',
    'voicemail_sms_to',
    'voicemail_transcription_enabled',
    'voicemail_attach_file',
    'voicemail_file',
    'voicemail_local_after_email',
    'voicemail_enabled',
    'voicemail_description',
    'voicemail_name_base64',
    'voicemail_tutorial',
	];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
	protected $hidden = [
	    'voicemail_password',
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
	
	public function voicemailmessages(): HasMany {
		return $this->hasMany(VoicemailMessage::class, 'voicemail_uuid', 'voicemail_uuid');
	}
}
