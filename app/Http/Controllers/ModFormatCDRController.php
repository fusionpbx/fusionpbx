<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Extension;
use App\Models\XmlCDR;
use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\DomainSettingController;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;



class ModFormatCDRController extends Controller
{
    public function store(Request $request){
        if(App::hasDebugModeEnabled()){
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] input: '.print_r($request->toArray(), true));
        }

        $rules = [
            'cdr' => ['required'],
            'uuid' => ['required'],
        ];

        $validator1 = Validator::make($request->all(), $rules);
        if ($validator1->fails()) {
            return response()->json(['errors' => $validator1->errors()], 422);
        }
        unset($validator1);

        // Detect Format
        $defaultSettings = new DefaultSettingController;
        $format = $defaultSettings->get('config', 'format_cdr.format', 'text') ?? 'xml';
        $recordings = $defaultSettings->get('switch', 'recordings', 'dir');

        $dbType = DB::getConfig("driver");

        if(App::hasDebugModeEnabled()){
                Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $format: '.$format);
        }

        switch ($format){
            case 'json':
                // TODO: implement
                break;
            default:
                // By default, XML
                try {
					if (PHP_VERSION_ID < 80000) { libxml_disable_entity_loader(true); }
					$cdr = simplexml_load_string($request->input('cdr'), 'SimpleXMLElement', LIBXML_NOCDATA);
				}
				catch(Exception $e) {
					echo $e->getMessage();
                    Log::warning('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] XML parsing error: '.$e->getMessage());
					return;
				}
                break;
        }

        $uuid = (string)$cdr->variables->uuid;
        $validator2 = Validator::make(
                ['uuid' => $uuid],
                ['uuid' => 'uuid:4']
            );

        if ($validator2->fails()) {
            return response()->json(['errors' => $validator2->errors()], 422);
        }

        $record = XmlCDR::find($uuid);
        if (empty($record)){

            //get the caller ID from call flow caller profile
            $i = 0;
            foreach ($cdr->callflow as $row){
                if ($i == 0){
                    $caller_id_name = urldecode($row->caller_profile->caller_id_name);
                    $caller_id_number = urldecode($row->caller_profile->caller_id_number);
                }
                $i++;
            }
            unset($i);

            //get the caller ID from variables
            if (!isset($caller_id_number) && isset($cdr->variables->caller_id_name)) {
                $caller_id_name = urldecode($cdr->variables->caller_id_name);
            }
            if (!isset($caller_id_number) && isset($cdr->variables->caller_id_number)) {
                $caller_id_number = urldecode($cdr->variables->caller_id_number);
            }
            if (!isset($caller_id_number) && isset($cdr->variables->sip_from_user)) {
                $caller_id_number = urldecode($cdr->variables->sip_from_user);
            }

            //if the origination caller id name and number are set then use them
            if (isset($cdr->variables->origination_caller_id_name)) {
                $caller_id_name = urldecode($cdr->variables->origination_caller_id_name);
            }
            if (isset($cdr->variables->origination_caller_id_number)) {
                $caller_id_number = urldecode($cdr->variables->origination_caller_id_number);
            }

            //if the call is outbound use the external caller ID
            if (isset($cdr->variables->effective_caller_id_name)) {
                $caller_id_name = urldecode($cdr->variables->effective_caller_id_name);
            }

            if (isset($cdr->variables->origination_caller_id_name)) {
                $caller_id_name = urldecode($cdr->variables->origination_caller_id_name);
            }

            if (isset($cdr->variables->origination_caller_id_number)) {
                $caller_id_number = urldecode($cdr->variables->origination_caller_id_number);
            }

            if (isset($cdr->variables->call_direction) && urldecode($cdr->variables->call_direction) == 'outbound' && isset($cdr->variables->effective_caller_id_number)) {
                $caller_id_number = urldecode($cdr->variables->effective_caller_id_number);
            }

            // if the sip_from_domain and domain_name are not the same then original call direction was inbound
            // when an inbound call is forward, the call_direction is set to inbound and then updated to outbound
            // use sip_from_display and sip_from_user to get the original caller ID instead of the updated caller ID info from the forward
            if (isset($cdr->variables->sip_from_domain) && urldecode($cdr->variables->sip_from_domain) != urldecode($cdr->variables->domain_name)) {
                if (isset($cdr->variables->sip_from_display)) {
                    $caller_id_name = urldecode($cdr->variables->sip_from_display);
                }
                if (isset($cdr->variables->sip_from_user)) {
                    $caller_id_number = urldecode($cdr->variables->sip_from_user);
                }
            }

            // get the values from the callflow.
            $i = 0;
            foreach ($cdr->callflow as $row) {
                if ($i == 0) {
                    $context = urldecode($cdr->caller_profile->context);
                    $destination_number = urldecode($cdr->caller_profile->destination_number);
                    $network_addr = urldecode($cdr->caller_profile->network_addr);
                }
                $i++;
            }
            unset($i);

            //if last_sent_callee_id_number is set use it for the destination_number
            if (!empty($cdr->variables->last_sent_callee_id_number)) {
                $destination_number = urldecode($cdr->variables->last_sent_callee_id_number);
            }

            //remove the provider prefix
            if (isset($cdr->variables->provider_prefix) && isset($destination_number)) {
                $provider_prefix = $cdr->variables->provider_prefix;
                if ($provider_prefix == substr($destination_number, 0, strlen($provider_prefix))) {
                    $destination_number = substr($destination_number, strlen($provider_prefix), strlen($destination_number));
                }
            }

            //get the caller_destination
            if (isset($cdr->variables->caller_destination) ) {
                $caller_destination = urldecode($cdr->variables->caller_destination);
            }
            if (isset($cdr->variables->sip_h_caller_destination) ) {
                $caller_destination = urldecode($cdr->variables->sip_h_caller_destination);
            }
            if (!isset($caller_destination) && isset($cdr->variables->dialed_user)) {
                $caller_destination = urldecode($cdr->variables->dialed_user);
            }

            //set missed calls
            if (isset($cdr->variables->missed_call)) {
                //marked as missed
                $missed_call = $cdr->variables->missed_call;
            }
            elseif (isset($cdr->variables->fax_success)) {
                //fax server
                $missed_call = 'false';
                // TODO: add record_type later
            }
            elseif ($cdr->variables->hangup_cause == 'LOSE_RACE') {
                //ring group or multi destination bridge statement
                $missed_call = 'false';
            }
            elseif ($cdr->variables->hangup_cause == 'NO_ANSWER' && isset($cdr->variables->originating_leg_uuid)) {
                //ring group or multi destination bridge statement
                $missed_call = 'false';
            }
            elseif (substr($cdr->variables->destination_number, 0, 3) == '*99') {
                //voicemail
                $missed_call = 'true';
            }
            elseif (isset($cdr->variables->voicemail_message) && $cdr->variables->voicemail_message == true) {
                //voicemail
                $missed_call = 'true';
            }
            elseif (isset($cdr->variables->billsec) && $cdr->variables->billsec > 0) {
                //answered call
                $missed_call = 'false';
            }
            elseif (isset($cdr->variables->cc_side) && $cdr->variables->cc_side == 'agent') {
                //call center
                $missed_call = 'false';
            }
            else {
                //missed call
                $missed_call = 'true';
            }

            //get the last bridge_uuid from the call to preserve previous behavior
            $last_bridge = null;
            foreach ($cdr->variables->bridge_uuids as $bridge) {
                $last_bridge = urldecode($bridge);
            }

            //misc
            //$uuid = urldecode($cdr->variables->uuid);
            $payload['xml_cdr_uuid'] = $uuid;
            $payload['destination_number'] = $destination_number;
            $payload['sip_call_id'] = urldecode($cdr->variables->sip_call_id);
            $payload['source_number'] = urldecode($cdr->variables->effective_caller_id_number);
            $payload['user_context'] = urldecode($cdr->variables->user_context);
            $payload['network_addr'] = urldecode($cdr->variables->sip_network_ip);
            $payload['missed_call'] = $missed_call;
            $payload['caller_id_name'] = $caller_id_name;
            $payload['caller_id_number'] = $caller_id_number;
            $payload['caller_destination'] = $caller_destination;
            $payload['accountcode'] = urldecode($cdr->variables->accountcode);
            $payload['default_language'] = urldecode($cdr->variables->default_language);
            $payload['bridge_uuid'] = urldecode($cdr->variables->bridge_uuid) ?: $last_bridge;
            //$payload['digits_dialed'] = urldecode($cdr->variables->digits_dialed);
            $payload['sip_hangup_disposition'] = urldecode($cdr->variables->sip_hangup_disposition);
            $payload['pin_number'] = urldecode($cdr->variables->pin_number);
            $payload['record_type'] = isset($cdr->variables->record_type)?urldecode($cdr->variables->record_type):($request->input('record_type')??'call');

            //time
            $start_epoch = urldecode($cdr->variables->start_epoch);
            $payload['start_epoch'] = $start_epoch;
            if ($dbType == 'pgsql'){
                $payload['start_stamp'] = is_numeric($start_epoch) ? date('c', $start_epoch) : null;
            }
            else{
                $payload['start_stamp'] = urldecode($cdr->variables->start_stamp);

            }
            $answer_epoch = urldecode($cdr->variables->answer_epoch);
            $payload['answer_epoch'] = $answer_epoch;
            if ($dbType == 'pgsql'){
                $payload['answer_stamp'] = is_numeric($answer_epoch) ? date('c', $answer_epoch) : null;
            }
            else{
                $payload['answer_stamp'] = urldecode($cdr->variables->answer_stamp);
            }
            $end_epoch = urldecode($cdr->variables->end_epoch);
            $payload['end_epoch'] = $end_epoch;
            if ($dbType == 'pgsql'){
                $payload['end_stamp'] = is_numeric($end_epoch) ? date('c', $end_epoch) : null;
            }
            else{
                $payload['end_stamp'] = urldecode($cdr->variables->end_stamp);
            }
            $payload['duration'] = urldecode($cdr->variables->duration);
            $payload['mduration'] = urldecode($cdr->variables->mduration);
            $payload['billsec'] = urldecode($cdr->variables->billsec);
            $payload['billmsec'] = urldecode($cdr->variables->billmsec);

            //codecs
            $payload['read_codec'] = urldecode($cdr->variables->read_codec);
            $payload['read_rate'] = urldecode($cdr->variables->read_rate);
            $payload['write_codec'] = urldecode($cdr->variables->write_codec);
            $payload['write_rate'] = urldecode($cdr->variables->write_rate);
            $payload['remote_media_ip'] = urldecode($cdr->variables->remote_media_ip);
            $payload['hangup_cause'] = urldecode($cdr->variables->hangup_cause);
            $payload['hangup_cause_q850'] = urldecode($cdr->variables->hangup_cause_q850);

            //store the call direction
            $payload['direction'] = urldecode($cdr->variables->call_direction);

            //call center
            if ($cdr->variables->cc_member_uuid == '_undef_') { $cdr->variables->cc_member_uuid = ''; }
            if ($cdr->variables->cc_member_session_uuid == '_undef_') { $cdr->variables->cc_member_session_uuid = ''; }
            if ($cdr->variables->cc_agent_uuid == '_undef_') { $cdr->variables->cc_agent_uuid = ''; }
            if ($cdr->variables->call_center_queue_uuid == '_undef_') { $cdr->variables->call_center_queue_uuid = ''; }
            if ($cdr->variables->cc_queue_joined_epoch == '_undef_') { $cdr->variables->cc_queue_joined_epoch = ''; }
            $payload['cc_side'] = urldecode($cdr->variables->cc_side);
            $payload['cc_member_uuid'] = urldecode($cdr->variables->cc_member_uuid);
            $payload['cc_queue'] = urldecode($cdr->variables->cc_queue);
            $payload['cc_member_session_uuid'] = urldecode($cdr->variables->cc_member_session_uuid);
            $payload['cc_agent_uuid'] = urldecode($cdr->variables->cc_agent_uuid);
            $payload['cc_agent'] = urldecode($cdr->variables->cc_agent);
            $payload['cc_agent_type'] = urldecode($cdr->variables->cc_agent_type);
            $payload['cc_agent_bridged'] = urldecode($cdr->variables->cc_agent_bridged);
            $payload['cc_queue_answered_epoch'] = urldecode($cdr->variables->cc_queue_answered_epoch);
            $payload['cc_queue_terminated_epoch'] = urldecode($cdr->variables->cc_queue_terminated_epoch);
            $payload['cc_queue_canceled_epoch'] = urldecode($cdr->variables->cc_queue_canceled_epoch);
            $payload['cc_cancel_reason'] = urldecode($cdr->variables->cc_cancel_reason);
            $payload['cc_cause'] = urldecode($cdr->variables->cc_cause);
            $payload['waitsec'] = urldecode($cdr->variables->waitsec);
            if (urldecode($cdr->variables->cc_side) == 'agent') {
                $payload['direction'] = 'inbound';
            }
            $payload['cc_queue'] = urldecode($cdr->variables->cc_queue);
            $payload['call_center_queue_uuid'] = urldecode($cdr->variables->call_center_queue_uuid);

            //app info
            $payload['last_app'] = urldecode($cdr->variables->last_app);
            $payload['last_arg'] = urldecode($cdr->variables->last_arg);

            //voicemail message success
            if ($cdr->variables->voicemail_action == "save" && $cdr->variables->voicemail_message_seconds > 0){
                $payload['voicemail_message'] = "true";
            }
            else { //if ($cdr->variables->voicemail_action == "save") {
                $payload['voicemail_message'] = "false";
            }

            //conference
            $payload['conference_name'] = urldecode($cdr->variables->conference_name);
            $payload['conference_uuid'] = urldecode($cdr->variables->conference_uuid);
            $payload['conference_member_id'] = urldecode($cdr->variables->conference_member_id);

            //call quality
            $rtp_audio_in_mos = urldecode($cdr->variables->rtp_audio_in_mos);
            if (!empty($rtp_audio_in_mos)) {
                $payload['rtp_audio_in_mos'] = $rtp_audio_in_mos;
            }

            //store the call leg
            $leg = (substr($request->input('uuid'), 0, 2) == 'a_') ? 'a' : 'b';

            $payload['leg'] = $leg;

            //store the originating leg uuid
            $payload['originating_leg_uuid'] = urldecode($cdr->variables->originating_leg_uuid);

            //store post dial delay, in milliseconds
            $payload['pdd_ms'] = urldecode($cdr->variables->progress_mediamsec) + urldecode($cdr->variables->progressmsec);

            //get break down the date to year, month and day
            $start_stamp = urldecode($cdr->variables->start_stamp);
            $start_time = strtotime($start_stamp);
            $start_year = date("Y", $start_time);
            $start_month = date("M", $start_time);
            $start_day = date("d", $start_time);

            //get the domain values from the xml
            $domain_name = urldecode($cdr->variables->domain_name);
            $domain_uuid = urldecode($cdr->variables->domain_uuid);

            //get the domain name
            if (empty($domain_name)) {
                $domain_name = urldecode($cdr->variables->dialed_domain);
            }
            if (empty($domain_name)) {
                $domain_name = urldecode($cdr->variables->sip_invite_domain);
            }
            if (empty($domain_name)) {
                $domain_name = urldecode($cdr->variables->sip_req_host);
            }
            if (empty($domain_name)) {
                $presence_id = urldecode($cdr->variables->presence_id);
                if (!empty($presence_id)) {
                    $presence_array = explode($presence_id, '%40');
                    $domain_name = $presence_array[1];
                }
            }

            // TODO: See if this feature is useful
            /*
            //dynamic cdr fields
            if (is_array($_SESSION['cdr']['field'])) {
                foreach ($_SESSION['cdr']['field'] as $field) {
                    $fields = explode(",", $field);
                    $field_name = end($fields);
                    $this->fields[] = $field_name;
                    if (!isset($payload[$field_name])) {
                        if (count($fields) == 1) {
                            $payload[$field_name] = urldecode($cdr->variables->{$fields[0]});
                        }
                        if (count($fields) == 2) {
                            $payload[$field_name] = urldecode($cdr->{$fields[0]}->{$fields[1]});
                        }
                        if (count($fields) == 3) {
                            $payload[$field_name] = urldecode($cdr->{$fields[0]}->{$fields[1]}->{$fields[2]});
                        }
                        if (count($fields) == 4) {
                            $payload[$field_name] = urldecode($cdr->{$fields[0]}->{$fields[1]}->{$fields[2]}->{$fields[3]});
                        }
                        if (count($fields) == 5) {
                            $payload[$field_name] = urldecode($cdr->{$fields[0]}->{$fields[1]}->{$fields[2]}->{$fields[3]}->{$fields[4]});
                        }
                    }
                }
            }
            */

            //send the domain name to the cdr log
			Log::info("domain_name is `$domain_name`;\ndomain_uuid is '$domain_uuid'\n");
            if (empty(($domain_uuid))){
                $current_domain= Domain::when(empty($domain_name) && $context != 'public' && $context != 'default',
                    function($query) use($context){
                        return $query->where('domain_name',$context);
                    },
                    function($query) use($domain_name){
                        return $query->where('domain_name', $domain_name);
                    }
                )-first();
                $domain_uuid = $current_domain->domain_uuid;
            }

            //set values in the database
            if (!empty($domain_uuid)) {
                $payload['domain_uuid'] = $domain_uuid;
            }
            if (!empty($domain_name)) {
                $payload['domain_name'] = $domain_name;
            }

            //get the recording details
            if (isset($cdr->variables->record_path)) {
                $record_path = urldecode($cdr->variables->record_path);
                $record_name = urldecode($cdr->variables->record_name);
                if (isset($cdr->variables->record_seconds)) {
                    $record_length = urldecode($cdr->variables->record_seconds);
                }
                else {
                    $record_length = urldecode($cdr->variables->duration);
                }
            }
            elseif (!isset($record_path) && urldecode($cdr->variables->last_app) == "record_session") {
                $record_path = dirname(urldecode($cdr->variables->last_arg));
                $record_name = basename(urldecode($cdr->variables->last_arg));
                $record_length = urldecode($cdr->variables->record_seconds);
            }
            elseif (isset($cdr->variables->record_name)) {
                if (isset($cdr->variables->record_path)) {
                    $record_path = urldecode($cdr->variables->record_path);
                }
                else {
                    $record_path = $recordings.'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
                }
                $record_name = urldecode($cdr->variables->record_name);
                $record_length = urldecode($cdr->variables->duration);
            }
            elseif (!empty($cdr->variables->sofia_record_file)) {
                $record_path = dirname(urldecode($cdr->variables->sofia_record_file));
                $record_name = basename(urldecode($cdr->variables->sofia_record_file));
                $record_length = urldecode($cdr->variables->record_seconds);
            }
            elseif (!empty($cdr->variables->cc_record_filename)) {
                $record_path = dirname(urldecode($cdr->variables->cc_record_filename));
                $record_name = basename(urldecode($cdr->variables->cc_record_filename));
                $record_length = urldecode($cdr->variables->record_seconds);
            }
            elseif (!empty($cdr->variables->api_on_answer)) {
                $command = str_replace("\n", " ", urldecode($cdr->variables->api_on_answer));
                $parts = explode(" ", $command);
                if ($parts[0] == "uuid_record") {
                    $recording = $parts[3];
                    $record_path = dirname($recording);
                    $record_name = basename($recording);
                    $record_length = urldecode($cdr->variables->duration);
                }
            }
            elseif (!empty($cdr->variables->conference_recording)) {
                $conference_recording = urldecode($cdr->variables->conference_recording);
                $record_path = dirname($conference_recording);
                $record_name = basename($conference_recording);
                $record_length = urldecode($cdr->variables->duration);
            }
            elseif (!empty($cdr->variables->current_application_data)) {
                $commands = explode(",", urldecode($cdr->variables->current_application_data));
                foreach ($commands as $command) {
                    $cmd = explode("=", $command);
                    if ($cmd[0] == "api_on_answer") {
                        $a = explode("]", $cmd[1]);
                        $command = str_replace("'", "", $a[0]);
                        $parts = explode(" ", $command);
                        if ($parts[0] == "uuid_record") {
                            $recording = $parts[3];
                            $record_path = dirname($recording);
                            $record_name = basename($recording);
                            $record_length = urldecode($cdr->variables->duration);
                        }
                    }
                }
            }
            if (!isset($record_name)) {
                $bridge_uuid = urldecode($cdr->variables->bridge_uuid) ?: $last_bridge;
                $path = $recordings.'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
                if (file_exists($path.'/'.$bridge_uuid.'.wav')) {
                    $record_path = $path;
                    $record_name = $bridge_uuid.'.wav';
                    $record_length = urldecode($cdr->variables->duration);
                } elseif (file_exists($path.'/'.$bridge_uuid.'.mp3')) {
                    $record_path = $path;
                    $record_name = $bridge_uuid.'.mp3';
                    $record_length = urldecode($cdr->variables->duration);
                }
            }
            if (!isset($record_name)) {
                $path = $recordings.'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
                if (file_exists($path.'/'.$uuid.'.wav')) {
                    $record_path = $path;
                    $record_name = $uuid.'.wav';
                    $record_length = urldecode($cdr->variables->duration);
                } elseif (file_exists($path.'/'.$uuid.'.mp3')) {
                    $record_path = $path;
                    $record_name = $uuid.'.mp3';
                    $record_length = urldecode($cdr->variables->duration);
                }
            }

            //last check
            if (!isset($record_name) || is_null ($record_name) || (empty($record_name))) {
                $bridge_uuid = urldecode($cdr->variables->bridge_uuid) ?: $last_bridge ;
                $path = $recordings.'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
                if (file_exists($path.'/'.$bridge_uuid.'.wav')) {
                    $record_path = $path;
                    $record_name = $bridge_uuid.'.wav';
                    $record_length = urldecode($cdr->variables->duration);
                } elseif (file_exists($path.'/'.$bridge_uuid.'.mp3')) {
                    $record_path = $path;
                    $record_name = $bridge_uuid.'.mp3';
                    $record_length = urldecode($cdr->variables->duration);
                } elseif (file_exists($path.'/'.$bridge_uuid.'.wav')) {
                    $record_path = $path;
                    $record_name = $bridge_uuid.'.wav';
                    $record_length = urldecode($cdr->variables->duration);
                } elseif (file_exists($path.'/'.$bridge_uuid.'.mp3')) {
                    $record_path = $path;
                    $record_name = $bridge_uuid.'.mp3';
                    $record_length = urldecode($cdr->variables->duration);
                }
            }

            //add the call record path, name and length to the database
            if (isset($record_path) && isset($record_name) && file_exists($record_path.'/'.$record_name)) {
                $payload['record_path'] = $record_path;
                $payload['record_name'] = $record_name;
                if (isset($record_length)) {
                    $payload['record_length'] = $record_length;
                }
                else {
                    $payload['record_length'] = urldecode($cdr->variables->duration);
                }
            }

            //add to the call recordings table
                /*
                if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_recordings/app_config.php")) {
                    //build the array
                    $x = 0;
                    $array['call_recordings'][$x]['call_recording_uuid'] = $uuid;
                    $array['call_recordings'][$x]['domain_uuid'] = $domain_uuid;
                    $array['call_recordings'][$x]['call_recording_name'] = $record_name;
                    $array['call_recordings'][$x]['call_recording_path'] = $record_path;
                    $array['call_recordings'][$x]['call_recording_length'] = $record_length;
                    $array['call_recordings'][$x]['call_recording_date'] = date('c', $start_epoch);
                    $array['call_recordings'][$x]['call_direction'] = urldecode($cdr->variables->call_direction);
                    //$array['call_recordings'][$x]['call_recording_description']= $row['zzz'];
                    //$array['call_recordings'][$x]['call_recording_base64']= $row['zzz'];

                    //add the temporary permission
                    $p = new permissions;
                    $p->add("call_recording_add", "temp");
                    $p->add("call_recording_edit", "temp");

                    $database = new database;
                    $database->app_name = 'call_recordings';
                    $database->app_uuid = '56165644-598d-4ed8-be01-d960bcb8ffed';
                    $database->domain_uuid = $domain_uuid;
                    $database->save($array, false);
                    //$message = $database->message;

                    //remove the temporary permission
                    $p->delete("call_recording_add", "temp");
                    $p->delete("call_recording_edit", "temp");
                    unset($array);
                }
                */

                $cdrFormat = $defaultSettings->get('cdr', 'format', 'text') ?? 'xml';
                $cdrStorage = $defaultSettings->get('cdr', 'storage', 'text') ?? 'db';
                if(App::hasDebugModeEnabled()){
                    Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $cdrFormat: '.$cdrFormat);
                    Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $cdrStorage: '.$cdrStorage);
                }
                //save to the database in xml format
                if ($cdrFormat == "xml" && $cdrStorage == "db") {
                    $payload['xml'] = $request->input('cdr');
                }

                //save to the database in json format
                //FIXME
                if ($cdrFormat == "json" && $cdrStorage == "db") {
                    $payload['json'] = json_encode($cdr);
                }

                //get the extension_uuid and then add it to the database fields array
                if (isset($cdr->variables->extension_uuid)) {
                    $payload['extension_uuid'] = urldecode($cdr->variables->extension_uuid);
                }
                else {
                    if (isset($domain_uuid) && isset($cdr->variables->dialed_user)) {
                        $extension = Extension::where('domain_uuid', $domain_uuid)
                                    ->whereAny(['extension', 'number_alias'], $cdr->variables->dialed_user)
                                    ->first();
                        $extension_uuid = $extension->extension_uuid;
                    }
                    if (isset($domain_uuid) && isset($cdr->variables->referred_by_user)) {
                        $extension = Extension::where('domain_uuid', $domain_uuid)
                                    ->whereAny(['extension', 'number_alias'], $cdr->variables->referred_by_user)
                                    ->first();
                        $extension_uuid = $extension->extension_uuid;
                    }
                    if (isset($domain_uuid) && isset($cdr->variables->last_sent_callee_id_number)) {
                        $extension = Extension::where('domain_uuid', $domain_uuid)
                                    ->whereAny(['extension', 'number_alias'], $cdr->variables->last_sent_callee_id_number)
                                    ->first();
                        $extension_uuid = $extension->extension_uuid;
                    }
                    if (isset($extension_uuid))
                        $payload['extension_uuid'] = $extension_uuid;
                }

                if ($cdrStorage == "dir") {
                    if (!empty($uuid)) {
                        $switch_log = $defaultSettings->get('switch', 'log', 'text');
                        $tmp_dir = $switch_log.'/xml_cdr/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
                        if(!file_exists($tmp_dir)) {
                            mkdir($tmp_dir, 0770, true);
                        }
                        if ($cdrFormat == "xml") {
                            $tmp_file = $uuid.'.xml';
                            $fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
                            fwrite($fh, $xml_string);
                        }
                        else {
                            $tmp_file = $uuid.'.json';
                            $fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
                            fwrite($fh, json_encode($xml));
                        }
                        fclose($fh);
                    }
                }

                $newCDR = XmlCDR::create($payload);
                if (empty($newCDR)){
                    return response()->json(['errors' => ['Error inserting a CDR.']], 422);
                }
                 return response($uuid, 200);
        }
        else{
            return response()->json(['errors' => ['UUID already in the CDR.']], 422);
        }
    }
}
