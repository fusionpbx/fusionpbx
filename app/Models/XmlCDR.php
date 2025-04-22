<?php

namespace App\Models;

use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class XmlCDR extends Model
{
	use HasApiTokens, HasFactory, Notifiable, GetTableName;
	protected $table = 'v_xml_cdr';
	protected $primaryKey = 'xml_cdr_uuid';
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
        'xml_cdr_uuid',     // primary key comes from FreeSWITCH
        'sip_call_id',
        'domain_name',
        'accountcode',
        'direction',
        'default_language',
        'context',
        'caller_id_name',
        'caller_id_number',
        'caller_destination',
        'source_number',
        'destination_number',
        'start_epoch',
        'start_stamp',
        'answer_stamp',
        'answer_epoch',
        'end_epoch',
        'end_stamp',
        'duration',
        'mduration',
        'billsec',
        'billmsec',
        'bridge_uuid',
        'read_codec',
        'read_rate',
        'write_codec',
        'write_rate',
        'remote_media_ip',
        'network_addr',
        'record_path',
        'record_name',
        'record_length',
        'leg',
        'originating_leg_uuid',
        'pdd_ms',
        'rtp_audio_in_mms',
        'last_app',
        'last_arg',
        'voicemail_message',
        'missed_call',
        'call_center_queue_uuid',
        'cc_side',
        'cc_member_uuid',
        'cc_queue_joined_epoch',
        'cc_queue',
        'cc_member_session_uuid',
        'cc_agent_uuid',
        'cc_agent',
        'cc_agent_type',
        'cc_agent_bridged',
        'cc_queue_answered_epoch',
        'cc_queue_terminated_epoch',
        'cc_queue_cancelled_epoch',
        'cc_cancel_reason',
        'cc_cause',
        'waitsec',
        'conference_name',
        'conference_uuid',
        'conference_member_id',
        'digits_dialed',
        'pin_number',
        'hangup_cause',
        'hangup_cause_q850',
        'sip_hangup_disposition',
        'xml',
        'json',
        'call_buy',
        'call_sell',
        'call_sell_local_currency',
        'local_currency',
        'carrier_name',
        'billing_status',
        'billing_json',
        'record_type',
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

    protected function domainUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function extensionUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function sipCallId(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function domainName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function accountcode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function direction(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function defaultLanguage(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function context(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function callerIdName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function callerIdNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function callerDestination(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function sourceNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function destinationNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function startEpoch(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function startStamp(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function answerStamp(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function answerEpoch(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function endEpoch(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function endStamp(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function duration(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function mduration(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function billsec(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function billmsec(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function bridgeUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function readCodec(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function readRate(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function writeCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function writeRate(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function remoteMediaIp(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function networkAddr(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function recordPath(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function recordName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function recordLenght(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function leg(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function originatingLegUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function pddMs(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function rtpAudioInMos(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function lastApp(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function lastArg(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function voicemailMessage(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function missedCall(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function callCenterQueueUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccSide(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccMemberUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccQueueJoinedEpoch(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccQueue(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccMemberSessionUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccAgentUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccAgent(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccAgentType(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccAgentBridged(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccQueueAnsweredEpoch(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccQueueTerminatedEpoch(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccQueueCanceledEpoch(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccCancelReason(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function ccCause(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

        protected function waitsec(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function conferenceName(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function conferenceUuid(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function conferenceMemberId(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }


    protected function digitsDialed(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function pinNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function hangupCause(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }


    protected function hangCauseQ850(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function sipHangupDisposition(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function xml(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function json(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => empty($value) ? NULL : $value,
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: function()
            {
                $call_result = '';

                if($this->direction == 'inbound' || $this->direction == 'local')
                {
                    if($this->answer_stamp != '' && $this->bridge_uuid != '')
                    {
                        $call_result = 'answered';
                    }
                    else if($this->answer_stamp != '' && $this->bridge_uuid == '')
                    {
                        $call_result = 'voicemail';
                    }
                    else if($this->answer_stamp == '' && $this->bridge_uuid == '' && $this->sip_hangup_disposition != 'send_refuse')
                    {
                        $call_result = 'cancelled';
                    }
                    else
                    {
                        $call_result = 'failed';
                    }
                }
                else if($this->direction == 'outbound')
                {
                    if($this->answer_stamp != '' && $this->bridge_uuid != '')
                    {
                        $call_result = 'answered';
                    }
                    else if($this->hangup_cause == 'NORMAL_CLEARING')
                    {
                        $call_result = 'answered';
                    }
                    else if($this->answer_stamp == '' && $this->bridge_uuid != '')
                    {
                        $call_result = 'cancelled';
                    }
                    else
                    {
                        $call_result = 'failed';
                    }
                }

                if($this->record_type == 'text')
                {
                    $call_result = 'answered';
                }

                return $call_result;
            }
        );
    }

    protected function tta(): Attribute
    {
        return Attribute::make(
            get: function()
            {
                return (int)$this->answer_epoch ?? 0 - (int)$this->start_epoch ?? 0;
            }
        );
    }

    protected function pdd_ms(): Attribute
    {
        return Attribute::make(
            get: function()
            {
                $milliseconds = $this->pdd_ms;

                $seconds = $milliseconds / 1000;

                return number_format($seconds, 2) . 's';
            }
        );
    }

	public function domain(): BelongsTo {
		return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
	}

	public function extension(): BelongsTo {
		return $this->belongsTo(Extension::class, 'extension_uuid', 'extension_uuid');
	}
}
