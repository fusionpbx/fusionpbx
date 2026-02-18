<?php
/**
 * FusionPBX - XmlCdr Model
 * 
 * Eloquent model for v_xml_cdr table.
 * Represents call detail records in the FusionPBX system.
 * 
 * @package    FusionPBX
 * @subpackage Models
 */

namespace FusionPBX\Models;

class XmlCdr extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_xml_cdr';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'xml_cdr_uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'xml_cdr_uuid',
        'domain_uuid',
        'extension_uuid',
        'domain_name',
        'accountcode',
        'direction',
        'context',
        'caller_id_name',
        'caller_id_number',
        'caller_destination',
        'source_number',
        'destination_number',
        'start_epoch',
        'start_stamp',
        'answer_epoch',
        'answer_stamp',
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
        'recording_file',
        'record_path',
        'record_name',
        'leg',
        'pdd_ms',
        'rtp_audio_in_mos',
        'last_app',
        'last_arg',
        'cc_side',
        'cc_member_uuid',
        'cc_queue_joined_epoch',
        'cc_queue',
        'cc_member_session_uuid',
        'cc_agent',
        'cc_agent_uuid',
        'cc_agent_type',
        'cc_agent_bridged',
        'cc_queue_answered_epoch',
        'cc_queue_terminated_epoch',
        'cc_queue_canceled_epoch',
        'cc_cancel_reason',
        'cc_cause',
        'waitsec',
        'hangup_cause',
        'hangup_cause_q850',
        'sip_hangup_disposition',
        'xml'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_epoch' => 'integer',
        'start_stamp' => 'datetime',
        'answer_epoch' => 'integer',
        'answer_stamp' => 'datetime',
        'end_epoch' => 'integer',
        'end_stamp' => 'datetime',
        'duration' => 'integer',
        'mduration' => 'integer',
        'billsec' => 'integer',
        'billmsec' => 'integer',
        'pdd_ms' => 'integer',
        'waitsec' => 'integer',
        'hangup_cause_q850' => 'integer',
        'cc_queue_joined_epoch' => 'integer',
        'cc_queue_answered_epoch' => 'integer',
        'cc_queue_terminated_epoch' => 'integer',
        'cc_queue_canceled_epoch' => 'integer',
    ];

    /**
     * Get the domain that the CDR belongs to.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_uuid', 'domain_uuid');
    }

    /**
     * Get the extension associated with the CDR.
     */
    public function extension()
    {
        return $this->belongsTo(Extension::class, 'extension_uuid', 'extension_uuid');
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_stamp', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by direction.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDirection($query, $direction)
    {
        return $query->where('direction', $direction);
    }
}
