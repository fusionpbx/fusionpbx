<?php

namespace App\Repositories;

use App\Models\Dialplan;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class DialplanRepository
{
    protected $model;

    public function __construct(Dialplan $dialplan)
    {
        $this->model = $dialplan;
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findByUuid(string $uuid): ?Dialplan
    {
        return $this->model->where('dialplan_uuid', $uuid)->first();
    }

    public function findByUuidWithDetails(string $uuid): ?Dialplan
    {
        return $this->model->with('dialplanDetails')->where('dialplan_uuid', $uuid)->first();
    }

    public function create(array $data): Dialplan
    {
        if (!isset($data['dialplan_uuid'])) {
            $data['dialplan_uuid'] = Str::uuid();
        }

        return $this->model->create($data);
    }

    public function update(string $uuid, array $data): bool
    {
        $dialplan = $this->findByUuid($uuid);

        if (!$dialplan) {
            return false;
        }

        return $dialplan->update($data);
    }

    public function delete(string $uuid): bool
    {
        $dialplan = $this->findByUuid($uuid);

        if (!$dialplan) {
            return false;
        }

        return $dialplan->delete();
    }

    public function getAllDomains(): Collection
    {
        return Domain::all();
    }

    public function getTypesList(): array
    {
        return [
            ['key' => 'ani', 'value' => 'ANI'],
            ['key' => 'ani2', 'value' => 'ANI2'],
            ['key' => 'caller_id_name', 'value' => 'Caller ID Name'],
            ['key' => 'caller_id_number', 'value' => 'Caller ID Number'],
            ['key' => 'chan_name', 'value' => 'Channel Name'],
            ['key' => 'context', 'value' => 'Context'],
            ['key' => 'destination_number', 'value' => 'Destination Number'],
            ['key' => 'dialplan', 'value' => 'Dialplan'],
            ['key' => 'network_addr', 'value' => 'Network Address'],
            ['key' => 'rdnis', 'value' => 'RDNIS'],
            ['key' => 'source', 'value' => 'Source'],
            ['key' => 'username', 'value' => 'Username'],
            ['key' => 'uuid', 'value' => 'UUID'],
            ['key' => '${call_direction}', 'value' => '${call_direction}'],
            ['key' => '${number_alias}', 'value' => '${number_alias}'],
            ['key' => '${sip_contact_host}', 'value' => '${sip_contact_host}'],
            ['key' => '${sip_contact_uri}', 'value' => '${sip_contact_uri}'],
            ['key' => '${sip_contact_user}', 'value' => '${sip_contact_user}'],
            ['key' => '${sip_h_Diversion}', 'value' => '${sip_h_Diversion}'],
            ['key' => '${sip_from_host}', 'value' => '${sip_from_host}'],
            ['key' => '${sip_from_uri}', 'value' => '${sip_from_uri}'],
            ['key' => '${sip_from_user}', 'value' => '${sip_from_user}'],
            ['key' => '${sip_to_uri}', 'value' => '${sip_to_uri}'],
            ['key' => '${sip_to_user}', 'value' => '${sip_to_user}'],
            ['key' => '${toll_allow}', 'value' => '${toll_allow}'],
            ['key' => 'acknowledge_call', 'value' => 'acknowledge_call'],
            ['key' => 'answer', 'value' => 'answer'],
            ['key' => 'att_xfer', 'value' => 'att_xfer'],
            ['key' => 'avmd', 'value' => 'avmd'],
            ['key' => 'avmd_start', 'value' => 'avmd_start'],
            ['key' => 'avmd_stop', 'value' => 'avmd_stop'],
            ['key' => 'bind_digit_action', 'value' => 'bind_digit_action'],
            ['key' => 'bind_meta_app', 'value' => 'bind_meta_app'],
            ['key' => 'block_dtmf', 'value' => 'block_dtmf'],
            ['key' => 'break', 'value' => 'break'],
            ['key' => 'bridge', 'value' => 'bridge'],
            ['key' => 'bridge_export', 'value' => 'bridge_export'],
            ['key' => 'broadcast', 'value' => 'broadcast'],
            ['key' => 'callcenter', 'value' => 'callcenter'],
            ['key' => 'callcenter_track', 'value' => 'callcenter_track'],
            ['key' => 'capture', 'value' => 'capture'],
            ['key' => 'capture_text', 'value' => 'capture_text'],
            ['key' => 'check_acl', 'value' => 'check_acl'],
            ['key' => 'clear_digit_action', 'value' => 'clear_digit_action'],
            ['key' => 'clear_speech_cache', 'value' => 'clear_speech_cache'],
            ['key' => 'cng_plc', 'value' => 'cng_plc'],
            ['key' => 'conference', 'value' => 'conference'],
            ['key' => 'conference_set_auto_outcall', 'value' => 'conference_set_auto_outcall'],
            ['key' => 'curl', 'value' => 'curl'],
            ['key' => 'curl_sendfile', 'value' => 'curl_sendfile'],
            ['key' => 'db', 'value' => 'db'],
            ['key' => 'debug_media', 'value' => 'debug_media'],
            ['key' => 'decode_video', 'value' => 'decode_video'],
            ['key' => 'deduplicate_dtmf', 'value' => 'deduplicate_dtmf'],
            ['key' => 'deflect', 'value' => 'deflect'],
            ['key' => 'delay_echo', 'value' => 'delay_echo'],
            ['key' => 'detect_audio', 'value' => 'detect_audio'],
            ['key' => 'detect_silence', 'value' => 'detect_silence'],
            ['key' => 'detect_speech', 'value' => 'detect_speech'],
            ['key' => 'digit_action_set_realm', 'value' => 'digit_action_set_realm'],
            ['key' => 'displace_session', 'value' => 'displace_session'],
            ['key' => 'early_hangup', 'value' => 'early_hangup'],
            ['key' => 'eavesdrop', 'value' => 'eavesdrop'],
            ['key' => 'echo', 'value' => 'echo'],
            ['key' => 'enable_heartbeat', 'value' => 'enable_heartbeat'],
            ['key' => 'enable_keepalive', 'value' => 'enable_keepalive'],
            ['key' => 'endless_playback', 'value' => 'endless_playback'],
            ['key' => 'enum', 'value' => 'enum'],
            ['key' => 'eval', 'value' => 'eval'],
            ['key' => 'event', 'value' => 'event'],
            ['key' => 'execute_extension', 'value' => 'execute_extension'],
            ['key' => 'export', 'value' => 'export'],
            ['key' => 'fax_detect', 'value' => 'fax_detect'],
            ['key' => 'fifo', 'value' => 'fifo'],
            ['key' => 'fifo_track_call', 'value' => 'fifo_track_call'],
            ['key' => 'filter_codecs', 'value' => 'filter_codecs'],
            ['key' => 'final', 'value' => 'final'],
            ['key' => 'fire', 'value' => 'fire'],
            ['key' => 'flush_dtmf', 'value' => 'flush_dtmf'],
            ['key' => 'gentones', 'value' => 'gentones'],
            ['key' => 'group', 'value' => 'group'],
            ['key' => 'hangup', 'value' => 'hangup'],
            ['key' => 'hash', 'value' => 'hash'],
            ['key' => 'hold', 'value' => 'hold'],
            ['key' => 'httapi', 'value' => 'httapi'],
            ['key' => 'info', 'value' => 'info'],
            ['key' => 'intercept', 'value' => 'intercept'],
            ['key' => 'ivr', 'value' => 'ivr'],
            ['key' => 'jitterbuffer', 'value' => 'jitterbuffer'],
            ['key' => 'limit', 'value' => 'limit'],
            ['key' => 'limit_execute', 'value' => 'limit_execute'],
            ['key' => 'limit_hash', 'value' => 'limit_hash'],
            ['key' => 'limit_hash_execute', 'value' => 'limit_hash_execute'],
            ['key' => 'log', 'value' => 'log'],
            ['key' => 'loop_playback', 'value' => 'loop_playback'],
            ['key' => 'lua', 'value' => 'lua'],
            ['key' => 'media_reset', 'value' => 'media_reset'],
            ['key' => 'mkdir', 'value' => 'mkdir'],
            ['key' => 'msrp_recv_file', 'value' => 'msrp_recv_file'],
            ['key' => 'msrp_send_file', 'value' => 'msrp_send_file'],
            ['key' => 'multiset', 'value' => 'multiset'],
            ['key' => 'multiunset', 'value' => 'multiunset'],
            ['key' => 'mutex', 'value' => 'mutex'],
            ['key' => 'native_eavesdrop', 'value' => 'native_eavesdrop'],
            ['key' => 'novideo', 'value' => 'novideo'],
            ['key' => 'park', 'value' => 'park'],
            ['key' => 'park_state', 'value' => 'park_state'],
            ['key' => 'phrase', 'value' => 'phrase'],
            ['key' => 'pickup', 'value' => 'pickup'],
            ['key' => 'play_and_detect_speech', 'value' => 'play_and_detect_speech'],
            ['key' => 'play_and_get_digits', 'value' => 'play_and_get_digits'],
            ['key' => 'play_fsv', 'value' => 'play_fsv'],
            ['key' => 'play_yuv', 'value' => 'play_yuv'],
            ['key' => 'playback', 'value' => 'playback'],
            ['key' => 'pre_answer', 'value' => 'pre_answer'],
            ['key' => 'preprocess', 'value' => 'preprocess'],
            ['key' => 'presence', 'value' => 'presence'],
            ['key' => 'privacy', 'value' => 'privacy'],
            ['key' => 'push', 'value' => 'push'],
            ['key' => 'queue_dtmf', 'value' => 'queue_dtmf'],
            ['key' => 'read', 'value' => 'read'],
            ['key' => 'record', 'value' => 'record'],
            ['key' => 'record_fsv', 'value' => 'record_fsv'],
            ['key' => 'record_session', 'value' => 'record_session'],
            ['key' => 'record_session_mask', 'value' => 'record_session_mask'],
            ['key' => 'record_session_pause', 'value' => 'record_session_pause'],
            ['key' => 'record_session_resume', 'value' => 'record_session_resume'],
            ['key' => 'record_session_unmask', 'value' => 'record_session_unmask'],
            ['key' => 'recovery_refresh', 'value' => 'recovery_refresh'],
            ['key' => 'redirect', 'value' => 'redirect'],
            ['key' => 'remove_bugs', 'value' => 'remove_bugs'],
            ['key' => 'rename', 'value' => 'rename'],
            ['key' => 'reply', 'value' => 'reply'],
            ['key' => 'respond', 'value' => 'respond'],
            ['key' => 'reuse_caller_profile', 'value' => 'reuse_caller_profile'],
            ['key' => 'ring_ready', 'value' => 'ring_ready'],
            ['key' => 'rxfax', 'value' => 'rxfax'],
            ['key' => 'say', 'value' => 'say'],
            ['key' => 'sched_broadcast', 'value' => 'sched_broadcast'],
            ['key' => 'sched_cancel', 'value' => 'sched_cancel'],
            ['key' => 'sched_hangup', 'value' => 'sched_hangup'],
            ['key' => 'sched_heartbeat', 'value' => 'sched_heartbeat'],
            ['key' => 'sched_transfer', 'value' => 'sched_transfer'],
            ['key' => 'send', 'value' => 'send'],
            ['key' => 'send_display', 'value' => 'send_display'],
            ['key' => 'send_dtmf', 'value' => 'send_dtmf'],
            ['key' => 'send_info', 'value' => 'send_info'],
            ['key' => 'session_loglevel', 'value' => 'session_loglevel'],
            ['key' => 'set', 'value' => 'set'],
            ['key' => 'set_audio_level', 'value' => 'set_audio_level'],
            ['key' => 'set_global', 'value' => 'set_global'],
            ['key' => 'set_media_stats', 'value' => 'set_media_stats'],
            ['key' => 'set_mute', 'value' => 'set_mute'],
            ['key' => 'set_name', 'value' => 'set_name'],
            ['key' => 'set_profile_var', 'value' => 'set_profile_var'],
            ['key' => 'set_user', 'value' => 'set_user'],
            ['key' => 'set_zombie_exec', 'value' => 'set_zombie_exec'],
            ['key' => 'sleep', 'value' => 'sleep'],
            ['key' => 'socket', 'value' => 'socket'],
            ['key' => 'sofia_sla', 'value' => 'sofia_sla'],
            ['key' => 'sofia_stir_shaken_vs', 'value' => 'sofia_stir_shaken_vs'],
            ['key' => 'soft_hold', 'value' => 'soft_hold'],
            ['key' => 'sound_test', 'value' => 'sound_test'],
            ['key' => 'spandsp_detect_tdd', 'value' => 'spandsp_detect_tdd'],
            ['key' => 'spandsp_inject_tdd', 'value' => 'spandsp_inject_tdd'],
            ['key' => 'spandsp_send_tdd', 'value' => 'spandsp_send_tdd'],
            ['key' => 'spandsp_start_dtmf', 'value' => 'spandsp_start_dtmf'],
            ['key' => 'spandsp_start_fax_detect', 'value' => 'spandsp_start_fax_detect'],
            ['key' => 'spandsp_start_tone_detect', 'value' => 'spandsp_start_tone_detect'],
            ['key' => 'spandsp_stop_detect_tdd', 'value' => 'spandsp_stop_detect_tdd'],
            ['key' => 'spandsp_stop_dtmf', 'value' => 'spandsp_stop_dtmf'],
            ['key' => 'spandsp_stop_fax_detect', 'value' => 'spandsp_stop_fax_detect'],
            ['key' => 'spandsp_stop_inject_tdd', 'value' => 'spandsp_stop_inject_tdd'],
            ['key' => 'spandsp_stop_tone_detect', 'value' => 'spandsp_stop_tone_detect'],
            ['key' => 'speak', 'value' => 'speak'],
            ['key' => 'start_dtmf', 'value' => 'start_dtmf'],
            ['key' => 'start_dtmf_generate', 'value' => 'start_dtmf_generate'],
            ['key' => 'stop', 'value' => 'stop'],
            ['key' => 'stop_displace_session', 'value' => 'stop_displace_session'],
            ['key' => 'stop_dtmf', 'value' => 'stop_dtmf'],
            ['key' => 'stop_dtmf_generate', 'value' => 'stop_dtmf_generate'],
            ['key' => 'stop_record_session', 'value' => 'stop_record_session'],
            ['key' => 'stop_tone_detect', 'value' => 'stop_tone_detect'],
            ['key' => 'stop_video_write_overlay', 'value' => 'stop_video_write_overlay'],
            ['key' => 'stopfax', 'value' => 'stopfax'],
            ['key' => 'strftime', 'value' => 'strftime'],
            ['key' => 't38_gateway', 'value' => 't38_gateway'],
            ['key' => 'three_way', 'value' => 'three_way'],
            ['key' => 'tone_detect', 'value' => 'tone_detect'],
            ['key' => 'transfer', 'value' => 'transfer'],
            ['key' => 'transfer_vars', 'value' => 'transfer_vars'],
            ['key' => 'txfax', 'value' => 'txfax'],
            ['key' => 'unbind_meta_app', 'value' => 'unbind_meta_app'],
            ['key' => 'unblock_dtmf', 'value' => 'unblock_dtmf'],
            ['key' => 'unhold', 'value' => 'unhold'],
            ['key' => 'unloop', 'value' => 'unloop'],
            ['key' => 'unset', 'value' => 'unset'],
            ['key' => 'unshift', 'value' => 'unshift'],
            ['key' => 'vad_test', 'value' => 'vad_test'],
            ['key' => 'valet_park', 'value' => 'valet_park'],
            ['key' => 'verbose_events', 'value' => 'verbose_events'],
            ['key' => 'video_decode', 'value' => 'video_decode'],
            ['key' => 'video_refresh', 'value' => 'video_refresh'],
            ['key' => 'video_write_overlay', 'value' => 'video_write_overlay'],
            ['key' => 'wait_for_answer', 'value' => 'wait_for_answer'],
            ['key' => 'wait_for_silence', 'value' => 'wait_for_silence'],
            ['key' => 'wait_for_video_ready', 'value' => 'wait_for_video_ready'],
        ];
    }

    public function buildXML(Dialplan $dialplan): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        $xml->startElement("extension");
        $xml->writeAttribute("name", $dialplan->dialplan_name);
        $xml->writeAttribute("uuid", $dialplan->dialplan_uuid);
        $xml->writeAttribute("continue", $dialplan->dialplan_continue ? 'true' : 'false');

        $currentCondition = null;

        foreach($dialplan->dialplanDetails as $dialplanDetail)
        {
            $tag = $dialplanDetail->dialplan_detail_tag;

            if($tag === "condition")
            {
                if($currentCondition !== null)
                {
                    $xml->endElement();
                }

                $currentCondition = $dialplanDetail;

                $xml->startElement("condition");
                $xml->writeAttribute("field", htmlspecialchars($dialplanDetail->dialplan_detail_type));
                $xml->writeAttribute("expression", htmlspecialchars($dialplanDetail->dialplan_detail_data));
                if (isset($dialplanDetail->dialplan_detail_break))
                {
                    $xml->writeAttribute("break", $dialplanDetail->dialplan_detail_break);
                }
            }
            else
            {
                $xml->startElement($tag);
                $xml->writeAttribute("application", htmlspecialchars($dialplanDetail->dialplan_detail_type));
                $xml->writeAttribute("data", htmlspecialchars($dialplanDetail->dialplan_detail_data));

                if(isset($dialplanDetail->dialplan_detail_inline))
                {
                    $xml->writeAttribute("inline", $dialplanDetail->dialplan_detail_inline);
                }

                $xml->endElement();
            }
        }

        if($currentCondition !== null)
        {
            $xml->endElement();
        }

        $xml->endElement();

        return $xml->outputMemory();
    }


    public function getDefaultContext(?string $appId = null, ?string $domainName = null): string
    {
        return ($appId == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') ? 'public' : $domainName;
    }
}
