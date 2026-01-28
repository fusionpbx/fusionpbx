<?php

$view['name'] = "view_xml_cdr";
$view['version'] = "20250920";
$view['description'] = "Show the XML CDR combined with json";
$view['sql'] = "	SELECT c.xml_cdr_uuid, c.domain_uuid, c.provider_uuid, c.extension_uuid, ";
$view['sql'] .= "  c.sip_call_id, c.domain_name, c.accountcode, c.direction, c.default_language, ";
$view['sql'] .= "  c.context, c.caller_id_name, c.caller_id_number, c.caller_destination, ";
$view['sql'] .= "  c.source_number, c.destination_number, c.start_epoch, c.start_stamp, c.answer_stamp, ";
$view['sql'] .= "  c.answer_epoch, c.end_epoch, c.end_stamp, c.duration, c.mduration, c.billsec, ";
$view['sql'] .= "  c.billmsec, c.bridge_uuid, c.read_codec, c.read_rate, c.write_codec, c.write_rate, ";
$view['sql'] .= "  c.remote_media_ip, c.network_addr, c.record_path, c.record_name, c.record_length, ";
$view['sql'] .= "  c.leg, c.originating_leg_uuid, c.pdd_ms, c.rtp_audio_in_mos, c.last_app, c.last_arg, ";
$view['sql'] .= "  c.voicemail_message, c.missed_call, c.call_center_queue_uuid, c.cc_side, ";
$view['sql'] .= "  c.cc_member_uuid, c.cc_queue_joined_epoch, c.cc_queue, c.cc_member_session_uuid, ";
$view['sql'] .= "  c.cc_agent_uuid, c.cc_agent, c.cc_agent_type, c.cc_agent_bridged, ";
$view['sql'] .= "  c.cc_queue_answered_epoch, c.cc_queue_terminated_epoch, c.cc_queue_canceled_epoch, ";
$view['sql'] .= "  c.cc_cancel_reason, c.cc_cause, c.waitsec, c.conference_name, c.conference_uuid, ";
$view['sql'] .= "  c.conference_member_id, c.digits_dialed, c.pin_number, c.status, c.hangup_cause, ";
$view['sql'] .= "  c.hangup_cause_q850, c.sip_hangup_disposition, c.call_flow, c.xml, c.insert_date, ";
$view['sql'] .= "  c.insert_user, c.update_date, c.update_user, ";
$view['sql'] .= "  CASE ";
$view['sql'] .= "   WHEN c.json IS NOT NULL THEN c.json ";
$view['sql'] .= "  ELSE j.json ";
$view['sql'] .= "  END AS json ";
$view['sql'] .= "  FROM v_xml_cdr as c, v_xml_cdr_json as j ";
$view['sql'] .= "  WHERE c.xml_cdr_uuid = j.xml_cdr_uuid; ";
