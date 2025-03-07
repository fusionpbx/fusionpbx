<?php

namespace App\Http\Controllers;

use App\Http\Controllers\DefaultSetting;
use App\Http\Controllers\DefaultSettingController;

use App\Models\AccessControl;
use App\Models\AccessControlNode;
use App\Models\CallCenterAgent;
use App\Models\CallCenterTier;
use App\Models\CallCenterQueue;
use App\Models\ConferenceControl;
use App\Models\ConferenceControlDetail;
use App\Models\ConferenceProfile;
use App\Models\ConferenceProfileParam;
use App\Models\Gateway;
use App\Models\IVRMenu;
use App\Models\IVRMenuOption;
use App\Models\MusicOnHold;
use App\Models\NumberTranslation;
use App\Models\NumberTranslationDetail;
use App\Models\Recording;
use App\Models\SipProfile;
use App\Models\SipProfileDomain;
use App\Models\SipProfileSetting;
use App\Models\SofiaGlobalSetting;
use App\Models\Variable;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use XMLWriter;

class ModXMLCURLController extends Controller
{

    public function hostname(Request $request): ?string{
        $answer = $request->input('hostname') ?? null;

        if (is_null($answer)){
            $answer = $request->header('Host') ?? null;
        }

        if (is_null($answer)){
            $ips = $request->ips();
            $answer = end($ips) ?? null;
        }

        return $answer;
    }

    public function configuration(Request $request): string{

        $hostname = $this->hostname($request);

        $answer = ''; $notfound = false;
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument( '1.0', 'UTF-8', 'no' );
        $xml->startElement( 'document' );
        $xml->writeAttribute('type', 'freeswitch/xml');
        $xml->startElement('section');
        $xml->writeAttribute('name', 'configuration' );

        switch ($request->input('key_value')){
            case 'acl.conf':
                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'Network Lists' );
                $xml->startElement('network-lists');

                // NOTE: Fusion does order by access_control_name asc
                foreach (AccessControl::all() as $access_control){
                    $xml->startElement('list');
                    $xml->writeAttribute('name', $access_control->access_control_name);
                    $xml->writeAttribute('default', $access_control->access_control_default );

                    $access_control_nodes = AccessControlNode::where('access_control_uuid', $access_control->access_control_uuid)
                        ->whereRaw('length(node_cidr) > 0')
                        ->get();
                    foreach ($access_control_nodes as $access_control_node){
                        $xml->startElement('node');
                        $xml->writeAttribute('type', $access_control_node->node_type);
                        $xml->writeAttribute('cidr', $access_control_node->node_cidr);
                        $xml->writeAttribute('type', $access_control_node->node_description);
                        $xml->endElement(); // node
                    }
                    $xml->endElement(); // list
                }

                $xml->endElement(); // network-lists
                break;
            case 'callcenter.conf':
                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'Call Center' );
                $xml->startElement('settings');
                $dsn_callcenter = Variable::whereIn('var_name', ['dsn','dsn_callcenter'])
                    ->orderByDesc('var_name')
                    ->first();
                if ($dsn_callcenter->count() == 1){
                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('odbc-dsn'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($dsn_callcenter->var_value); $xml->endAttribute();
                    $xml->endElement(); // param
                }
                $xml->endElement(); // settings


                $xml->startElement('queues');
                $callcenter_queues = CallCenterQueue::join('v_domains', CallCenterQueue::getTableName().'.domain_uuid', '=', 'v_domains.domain_uuid')->get();
                foreach ($callcenter_queues as $callcenter_queue){
                    $callcenter_queue->queue_name = str_replace(' ','-',$callcenter_queue->queue_name);
                    $xml->startElement('queue');
                    $xml->writeAttribute('name', $callcenter_queue->queue_extension.'@'.$callcenter_queue->domain_name);
                    $xml->writeAttribute('label', $callcenter_queue->queue_name.'@'.$callcenter_queue->domain_name);

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('strategy'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_strategy); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('moh-sound'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_ringback); $xml->endAttribute();
                    $xml->endElement(); //param

                    if (isset($callcenter_queue->queue_record_template)){
                        // TODO: find a better way to do this
                        $callcenter_queue->queue_record_template = str_replace('{strftime','${strftime', $callcenter_queue->queue_record_template);
                        $callcenter_queue->queue_record_template = str_replace('{uuid}','${uuid}', $callcenter_queue->queue_record_template);
                        $callcenter_queue->queue_record_template = str_replace('{record_ext}','${record_ext}', $callcenter_queue->queue_record_template);
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('record-template'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_record_template); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_time_base_score)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('time-base-score'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_time_base_score); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_max_wait_time)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('max-wait-time'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_max_wait_time); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_max_wait_time_with_no_agent)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('max-wait-time-with-no-agent'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_max_wait_time_with_no_agent); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_max_wait_time_with_no_agent_time_reached)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('max-wait-time-with-no-agent-time-reached'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_max_wait_time_with_no_agent_time_reached); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_tier_rules_apply)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('tier-rules-apply'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_tier_rules_apply); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_tier_rule_wait_second)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('tier-rule-wait-second'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_tier_rule_wait_second); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_tier_rule_wait_multiply_level)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('tier-rule-wait-multiply-level'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_tier_rule_wait_multiply_level); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_tier_rule_no_agent_no_wait)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('tier-rule-no-agent-no-wait'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_tier_rule_no_agent_no_wait); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_discard_abandoned_after)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('discard-abandoned-after'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_discard_abandoned_after); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_abandoned_resume_allowed)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('abandoned-resume-allowed'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_abandoned_resume_allowed); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_announce_sound)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('announce-sound'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_announce_sound); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($callcenter_queue->queue_announce_frequency)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('announce-frequency'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($callcenter_queue->queue_announce_frequency); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    $xml->endElement(); // queue
                }
                $xml->endElement(); // queues

                $default_settings = new DefaultSettingController;
                $recordings_dir = $default_settings->get('switch', 'recordings', 'dir');
                $xml->startElement('agents');
                switch(env('DB_CONNECTION', 'mysql')){
                    case 'pgsql':
                        $callcenter_agents = DB::table(CallCenterAgent::getTableName())
                                            ->select(DB::raw('SPLIT_PART(SPLIT_PART(a.agent_contact, "/", 2), "@", 1) AS extension, (SELECT extension_uuid FROM v_extensions WHERE domain_uuid = '.CallCenterAgent::getTableName().'.domain_uuid AND extension = SPLIT_PART(SPLIT_PART('.CallCenterAgent::getTableName().'.agent_contact, "/", 2), "@", 1) limit 1) as extension_uuid,'.CallCenterAgent::getTableName().'.*, v_domains.domain_name'))
                                            ->join('v_domains',CallCenterAgent::getTableName().'.domain_uuid','v_domains.domain_uuid')
                                            ->get();
                        break;
                    default:
                        $callcenter_agents = DB::table(CallCenterAgent::getTableName())
                                            ->select(DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(agent_contact,"/", -1), "@", 1) AS extension, (SELECT extension_uuid FROM v_extensions WHERE domain_uuid = '.CallCenterAgent::getTableName().'.domain_uuid AND extension = SUBSTRING_INDEX(SUBSTRING_INDEX('.CallCenterAgent::getTableName().'.agent_contact, "/", -1), "@", 1) limit 1) as extension_uuid,'.CallCenterAgent::getTableName().'.*, v_domains.domain_name'))
                                            ->join('v_domains',CallCenterAgent::getTableName().'.domain_uuid','v_domains.domain_uuid')
                                            ->get();
                        break;
                }

                foreach ($callcenter_agents as $callcenter_agent){
                    $confirm = "group_confirm_file=ivr/ivr-accept_reject_voicemail.wav,group_confirm_key=1,group_confirm_read_timeout=2000,leg_timeout=".$callcenter_agent->agent_call_timeout;
                    if ($callcenter_agent->agent_record == "true"){
                        $record = sprintf(",execute_on_pre_bridge='record_session %s/%s/archive/\${strftime(%%Y)}/\${strftime(%%b)}/\${strftime(%%d)}/\${uuid}.\${record_ext}'", $recordings_dir, $callcenter_agent->domain_name);
                    }

                    //TODO: Find a better way to do this
                    if(($pos = strpos($callcenter_agent->agent_contact, '}')) === false){
                        if (strstr($callcenter_agent->agent_contact, 'sofia/gateway') === false){
                            // add the call_timeout
                            $agent_contact = "{call_timeout=".$callcenter_agent->agent_call_timeout.",domain_name=".$callcenter_agent->domain_name.",domain_uuid=".$callcenter_agent->domain_uuid.",extension_uuid=".$callcenter_agent->extension_uuid.',sip_h_caller_destination=${caller_destination}'.$record."}".$callcenter_agent->agent_contact;
                        }
                        else{
                            // add the call_timeout and confirm
                            $agent_contact = "{".$confirm.",call_timeout=".$callcenter_agent->agent_call_timeout.",domain_name=".$callcenter_agent->domain_name.",domain_uuid=".$callcenter_agent->domain_uuid.',sip_h_caller_destination=${caller_destination}}'.$callcenter_agent->agent_contact;
                        }
                    }
                    else{
                        if (strstr($callcenter_agent->agent_contact, 'sofia/gateway') === false){
                            if (strstr($callcenter_agent->agent_contact, 'call_timeout') === false){
                                $first = substr($callcenter_agent->agent_contact, 0, $pos);
                                $last = substr($callcenter_agent->agent_contact, $pos);
                                $agent_contact = $first.',domain_name='.$callcenter_agent->domain_name.',domain_uuid='.$callcenter_agent->domain_uuid.',sip_h_caller_destination=${caller_destination},call_timeout='.$callcenter_agent->agent_call_timeout.$last;
                            }
                            else{
                                $first = substr($callcenter_agent->agent_contact, 0, $pos);
                                $last = substr($callcenter_agent->agent_contact, $pos);
                                $agent_contact = $first.',sip_h_caller_destination=${caller_destination},call_timeout='.$callcenter_agent->agent_call_timeout.$last;
                            }
                        }
                        else{
                            $first = substr($callcenter_agent->agent_contact, 0, $pos);
                            $last = substr($callcenter_agent->agent_contact, $pos);
                            if (strstr($callcenter_agent->agent_contact, 'call_timeout') === false){
                                // add call_timeout and confirm
                                $agent_contact = $first.','.$confirm.',sip_h_caller_destination=${caller_destination},domain_name='.$callcenter_agent->domain_name.',domain_uuid='.$callcenter_agent->domain_uuid.',sip_h_caller_destination=${caller_destination},call_timeout='.$callcenter_agent->agent_call_timeout.$last;
                            }
                            else{
                                // add confirm
                                $agent_contact = $first.',domain_name='.$callcenter_agent->domain_name.',domain_uuid='.$callcenter_agent->domain_uuid.',sip_h_caller_destination=${caller_destination},'.$confirm.$last;
                            }
                        }
                    }
                    //$agent_contact = str_replace('{caller_destination}','${caller_destination}', $agent_contact);

                    $xml->startElement('agent');
                    $xml->startAttribute('name'); $xml->text($callcenter_agent->call_center_agent_uuid); $xml->endAttribute();
                    $xml->startAttribute('label'); $xml->text($callcenter_agent->agent_name.'@'.$callcenter_agent->domain_name); $xml->endAttribute();
                    $xml->startAttribute('type'); $xml->text($callcenter_agent->agent_type); $xml->endAttribute();
                    $xml->startAttribute('contact'); $xml->text($agent_contact); $xml->endAttribute();
                    $xml->startAttribute('status'); $xml->text($callcenter_agent->agent_status); $xml->endAttribute();

                    if (isset($callcenter_agent->agent_no_answer_delay_time)){
                        $xml->startAttribute('no-answer-delay'); $xml->text($callcenter_agent->agent_no_answer_delay_time); $xml->endAttribute();
                    }

                    if (isset($callcenter_agent->agent_max_no_answer)){
                        $xml->startAttribute('max-no-answer'); $xml->text($callcenter_agent->agent_max_no_answer); $xml->endAttribute();
                    }

                    if (isset($callcenter_agent->agent_wrap_up_time)){
                        $xml->startAttribute('wrap-up-time'); $xml->text($callcenter_agent->agent_wrap_up_time); $xml->endAttribute();
                    }

                    if (isset($callcenter_agent->agent_reject_delay_time)){
                        $xml->startAttribute('reject-delay-time'); $xml->text($callcenter_agent->agent_reject_delay_time); $xml->endAttribute();
                    }

                    if (isset($callcenter_agent->agent_busy_delay_time)){
                        $xml->startAttribute('busy-delay-time'); $xml->text($callcenter_agent->agent_busy_delay_time); $xml->endAttribute();
                    }
                    $xml->endElement(); // agent
                }
                $xml->endElement(); // agents

                $xml->startElement('tiers');
                $callcenter_tiers = DB::table(CallCenterTier::getTableName())
                                    ->select(DB::raw('v_call_center_tiers.domain_uuid, v_domains.domain_name, v_call_center_tiers.call_center_agent_uuid, v_call_center_tiers.call_center_queue_uuid, v_call_center_queues.queue_extension, v_call_center_tiers.tier_level, v_call_center_tiers.tier_position'))
                                    ->join('v_domains', CallCenterTier::getTableName().'.domain_uuid', 'v_domains.domain_uuid')
                                    ->join('v_call_center_queues', CallCenterTier::getTableName().'.call_center_queue_uuid', 'v_call_center_queues.call_center_queue_uuid')
                                    ->get();

                foreach ($callcenter_tiers as $callcenter_tier){
                    $xml->startElement('tier');
                    $xml->startAttribute('agent'); $xml->text($callcenter_tier->call_center_agent_uuid); $xml->endAttribute();
                    $xml->startAttribute('queue'); $xml->text($callcenter_tier->queue_extension.'@'.$callcenter_tier->domain_name); $xml->endAttribute();
                    $xml->startAttribute('domain_name'); $xml->text($callcenter_tier->domain_name); $xml->endAttribute();
                    $xml->startAttribute('level'); $xml->text($callcenter_tier->tier_level); $xml->endAttribute();
                    $xml->startAttribute('position'); $xml->text($callcenter_tier->tier_position); $xml->endAttribute();
                    $xml->endElement(); // tier
                }
                $xml->endElement(); // tiers
                break;
            case 'conference.conf':
                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'Audio Conference' );
                $xml->startElement('caller-controls');

                $conference_controls = ConferenceControl::where('control_enabled', 'true')->get();
                foreach($conference_controls as $conference_control){
                    $xml->startElement('group');
                    $xml->writeAttribute('name', $conference_control->control_name);

                    $conference_control_details = ConferenceControlDetail::where('conference_control_uuid', $conference_control->conference_control_uuid)
                                                ->where('control_enabled', 'true')
                                                ->get();
                    foreach($conference_control_details as $conference_control_detail){
                        $xml->startElement('control');
                        $xml->startAttribute('digits'); $xml->text($conference_control_detail->control_digits); $xml->endAttribute();
                        $xml->startAttribute('action'); $xml->text($conference_control_detail->control_action); $xml->endAttribute();
                        $xml->startAttribute('data'); $xml->text($conference_control_detail->control_data); $xml->endAttribute();
                        $xml->endElement(); //control
                    }

                    $xml->endElement(); // group
                }

                $xml->endElement(); // caller-controls
                $xml->startElement('profiles');
                $conference_profiles = ConferenceProfile::where('profile_enabled', 'true')->get();
                foreach ($conference_profiles as $conference_profile){
                    $xml->startElement('profile');
                     $xml->writeAttribute('name', $conference_profile->profile_name);

                     $conference_profile_params = ConferenceProfileParam::where('conference_profile_uuid', $conference_profile->conference_profile_uuid)
                                                ->where('profile_param_enabled', 'true')
                                                ->get;
                    foreach($conference_profile_params as $conference_profile_param){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text($conference_profile_param->profile_param_name); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($conference_profile_param->profile_param_value); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    $xml->endElement(); // profile
                }
                $xml->endElement(); // profiles
                break;
            case 'ivr.conf':
                $default_settings = new DefaultSettingController;
                $sounds_dir = $default_settings->get('switch', 'sounds', 'dir');
                $sound_prefix = $sounds_dir . '/${default_language}/${default_dialect}/${default_voice}/';
                $ivr_menu_uuid =$request->input('Menu-Name');

                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'IVR Menus' );
                $xml->startElement('menus');

                $sql = "WITH RECURSIVE ivr_menus AS (
					SELECT * FROM v_ivr_menus
						WHERE ivr_menu_uuid = '$ivr_menu_uuid' AND ivr_menu_enabled = 'true'
						UNION ALL
						SELECT child.* FROM v_ivr_menus AS child, ivr_menus AS parent
						WHERE child.ivr_menu_parent_uuid = parent.ivr_menu_uuid AND child.ivr_menu_enabled = 'true'
					)
					SELECT * FROM ivr_menus INNER JOIN v_domains USING(domain_uuid)";
                $ivr_menus = DB::select($sql)->get();
                foreach($ivr_menus as $ivr_menu){
                    $domain_settings = new DefaultSettingController;
                    Session::put('domain_uuid', $ivr_menu->domain_uuid);
                    $direct_dial_digits_min = $domain_settings->get('ivr_menu', 'direct_dial_digits_min', 'numeric') ?? 2;
                    $direct_dial_digits_max = $domain_settings->get('ivr_menu', 'direct_dial_digits_max', 'numeric') ?? 11;
                    $storage_type = $domain_settings->get('recordings', 'storage_type', 'text');
                    $storage_path = $domain_settings->get('recordings', 'storage_path', 'text');

                    if (isset($storage_path)){
                        $storage_path = str_replace('${domain_name}',$ivr_menu->domain_name, $storage_path);
                        $storage_path = str_replace('${domain_uuid}',$ivr_menu->domain_uuid, $storage_path);
                    }

                    switch($storage_type){
                        case 'base64':
                            // For BASE64, and external sync software should be used if Freeswitch is not local
                            $recordings_dir = $domain_settings->get('switch', 'recordings', 'dir');
                            $base_path = $recordings_dir . '/' . $ivr_menu->domain_name;

                            if (isset($ivr_menu->ivr_menu_greet_long)){
                                $this->write($recordings_dir, $ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_greet_long, $ivr_menu);
                                $ivr_menu_greet_long = $base_path . '/' . $ivr_menu->ivr_menu_greet_long;
                            }

                            if (isset($ivr_menu->ivr_menu_greet_short)){
                                $this->write($recordings_dir, $ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_greet_short, $ivr_menu);
                                $ivr_menu_greet_short = $base_path . '/' . $ivr_menu->ivr_menu_greet_short;
                            }

                            if (isset($ivr_menu->ivr_menu_invalid_sound)){
                                $this->write($recordings_dir, $ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_invalid_sound, $ivr_menu);
                                $ivr_menu_invalid_sound = $base_path . '/' . $ivr_menu->ivr_menu_invalid_sound;
                            }

                            if (isset($ivr_menu->ivr_menu_exit_sound)){
                                $this->write($recordings_dir, $ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_exit_sound, $ivr_menu);
                                $ivr_menu_exit_sound = $base_path . '/' . $ivr_menu->ivr_menu_exit_sound;
                            }
                            break;
                        case 'http_cache':
                            // For HTTP Cache, we publish the files in /storage/
                            $recordings_dir = $domain_settings->get('switch', 'recordings', 'dir');
                            $base_path = $recordings_dir . '/' . $ivr_menu->domain_name;

                            if (isset($ivr_menu->ivr_menu_greet_long)){
                                $this->write(storage_path('app/public'), 'storage/recordings'.$ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_greet_long, $ivr_menu);
                                $ivr_menu_greet_long = asset('storage/recordings'.$ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_greet_long);
                            }

                            if (isset($ivr_menu->ivr_menu_greet_short)){
                                $this->write(storage_path('app/public'), 'storage/recordings'.$ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_greet_short, $ivr_menu);
                                $ivr_menu_greet_short = asset('storage/recordings'.$ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_greet_short);
                            }

                            if (isset($ivr_menu->ivr_menu_invalid_sound)){
                                $this->write(storage_path('app/public'), 'storage/recordings'.$ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_invalid_sound, $ivr_menu);
                                $ivr_menu_invalid_sound = asset('storage/recordings'.$ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_invalid_sound);
                            }

                            if (isset($ivr_menu->ivr_menu_exit_sound)){
                                $this->write(storage_path('app/public'), 'storage/recordings'.$ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_exit_sound, $ivr_menu);
                                $ivr_menu_exit_sound = asset('storage/recordings'.$ivr_menu->domain_name.'/'.$ivr_menu->ivr_menu_exit_sound);
                            }
                            break;

                        default:
                            $sounds_dir = $default_settings->get('switch', 'sounds', 'dir');
                            $recordings_dir = $domain_settings->get('switch', 'recordings', 'dir');
                            $base_path = $recordings_dir . '/' . $ivr_menu->domain_name;
                            $ivr_menu_greet_long = $base_path . '/' . $ivr_menu->ivr_menu_greet_long;

                            if (isset($ivr_menu->ivr_menu_greet_short)){
                                $ivr_menu_greet_short = $base_path . '/' . $ivr_menu->ivr_menu_greet_short;
                            }
                            else{
                                $ivr_menu_greet_short = $ivr_menu_greet_long;
                            }

                            if (isset($ivr_menu->ivr_menu_invalid_sound)){
                                $ivr_menu_invalid_sound = $base_path . '/' . $ivr_menu->ivr_menu_invalid_sound;
                            }
                            else{
                                $ivr_menu_invalid_sound = $sounds_dir . '/${default_language}/${default_dialect}/${default_voice}/ivr-that_was_an_invalid_entry.wav';
                            }

                            if (isset($ivr_menu->ivr_menu_exit_sound)){
                                $ivr_menu_exit_sound = $base_path . '/' . $ivr_menu->ivr_menu_exit_sound;
                            }
                            else{
                                $ivr_menu_invalid_sound = $sounds_dir . '/${default_language}/${default_dialect}/${default_voice}/ivr-record_exit_sound.wav';
                            }
                            break;
                    }

                    $xml->startElement('menu');
                    $xml->writeAttribute('name', $ivr_menu->ivr_menu_uuid);
                    $xml->writeAttribute('description', $ivr_menu->ivr_menu_name);
                    $xml->writeAttribute('greet-long', $ivr_menu_greet_long);
                    $xml->writeAttribute('greet-short', $ivr_menu_greet_short);
                    $xml->writeAttribute('invalid-sound', $ivr_menu_invalid_sound);
                    $xml->writeAttribute('exit-sound', $ivr_menu_exit_sound);
                    $xml->writeAttribute('pin', $ivr_menu->ivr_menu_pin_number);
                    $xml->writeAttribute('confirm-macro', $ivr_menu->ivr_menu_confirm_macro);
                    $xml->writeAttribute('confirm-key', $ivr_menu->ivr_menu_confirm_key);
                    $xml->writeAttribute('tts-engine', $ivr_menu->ivr_menu_tts_engine);
                    $xml->writeAttribute('tts-voice', $ivr_menu->ivr_menu_tts_voice);
                    $xml->writeAttribute('confirm_attempts', $ivr_menu->ivr_menu_confirm_attempts);
                    $xml->writeAttribute('timeout', $ivr_menu->ivr_menu_timeout);
                    $xml->writeAttribute('inter-digit-timeout', $ivr_menu->ivr_menu_inter_digit_timeout);
                    $xml->writeAttribute('max-failures', $ivr_menu->ivr_menu_max_failures);
                    $xml->writeAttribute('max-timeouts', $ivr_menu->ivr_menu_max_timeouts);
                    $xml->writeAttribute('digit-len', $ivr_menu->ivr_menu_digit_len);
                    $xml->writeAttribute('ivr_menu_exit_app', $ivr_menu->ivr_menu_exit_app);
                    $xml->writeAttribute('ivr_menu_exit_data', $ivr_menu->ivr_menu_exit_data);

                    $ivr_menu_options = IVRMenuOption::where('ivr_menu_uuid', $ivr_menu->ivr_menu_uuid)
                                        ->where('ivr_menu_option_enabled', 'true')
                                        ->orderBy('ivr_menu_option_order', 'asc')
                                        ->get();
                    foreach($ivr_menu_options as $ivr_menu_option){
                        if (strlen($ivr_menu_option->ivr_menu_option_action) > 0){
                            $ivr_menu_option_param = str_replace('{accountcode}','${accountcode}', $ivr_menu_option->ivr_menu_option_param);
                            $xml->startElement('entry');
                            $xml->startAttribute('action'); $xml->text($ivr_menu_option->ivr_menu_option_action); $xml->endAttribute();
                            $xml->startAttribute('digits'); $xml->text($ivr_menu_option->ivr_menu_option_digits); $xml->endAttribute();
                            $xml->startAttribute('param'); $xml->text($ivr_menu_option->ivr_menu_option_param); $xml->endAttribute();
                            $xml->startAttribute('description'); $xml->text($ivr_menu_option->ivr_menu_option_description); $xml->endAttribute();
                            $xml->endElement(); //entry

                            if (is_int($ivr_menu_option->ivr_menu_option_digits) && (strlen($ivr_menu_option->ivr_menu_option_digits) >= (int)$direct_dial_digits_min)){
                                $direct_dial_exclude[] = $ivr_menu_option->ivr_menu_option_digits;
                            }
                        }
                    }

                    if ($ivr_menu->ivr_menu_direct_dial == 'true'){
                        $negative_lookahead = '';
                        if (is_array($direct_dial_exclude) && (count($direct_dial_exclude) > 0)){
                            $negative_lookahead = '(?!^('.implode('|', $direct_dial_exclude).')$)';
                        }
                        $direct_dial_regex = sprintf("/^(%s\\d{%s,%s})$/", $negative_lookahead, $direct_dial_digits_min, $direct_dial_digits_max);

                        $xml->startElement('entry');
                        $xml->startAttribute('action'); $xml->text('menu-exec-app'); $xml->endAttribute();
                        $xml->startAttribute('digits'); $xml->text($direct_dial_regex); $xml->endAttribute();
                        $xml->startAttribute('param'); $xml->text('set ${cond(${user_exists id $1 '. $ivr_menu->domain_name . '} == true ? user_exists=true : user_exists=false)}'); $xml->endAttribute();
                        $xml->startAttribute('description'); $xml->text('direct dial transfer'); $xml->endAttribute();
                        $xml->endElement(); //entry

                        $xml->startElement('entry');
                        $xml->startAttribute('action'); $xml->text('menu-exec-app'); $xml->endAttribute();
                        $xml->startAttribute('digits'); $xml->text($direct_dial_regex); $xml->endAttribute();
                        $xml->startAttribute('param'); $xml->text('playback ${cond(${user_exists} == true ? ' .$sound_prefix . 'ivr/ivr-call_being_transferred.wav : ' .$sound_prefix .'ivr/ivr-that_was_an_invalid_entry.wav)}'); $xml->endAttribute();
                        $xml->startAttribute('description'); $xml->text('play sound'); $xml->endAttribute();
                        $xml->endElement(); //entry

                        $xml->startElement('entry');
                        $xml->startAttribute('action'); $xml->text('menu-exec-app'); $xml->endAttribute();
                        $xml->startAttribute('digits'); $xml->text($direct_dial_regex); $xml->endAttribute();
                        $xml->startAttribute('param'); $xml->text('transfer ${cond(${user_exists} == true ? $1 XML ' . $ivr_menu->domain_name.')}'); $xml->endAttribute();
                        $xml->startAttribute('description'); $xml->text('direct dial transfer'); $xml->endAttribute();
                        $xml->endElement(); //entry
                    }

                    $xml->endElement(); // menu
                }

                $xml->endElement(); // menus
                break;
            case 'local_stream.conf':
                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'Stream files from local dir');

                $musics_on_hold = DB::table(MusicOnHold::getTableName())
                                    ->select('v_domains.domain_name, v_music_on_hold.*')
                                    ->leftJoin('v_domains', 'domain_uuid', 'v_domains.domain_uuid')
                                    ->orderBy('music_on_hold_name', 'asc')
                                    ->get();

                foreach($musics_on_hold as $music_on_hold){
                    $name = '';
                    Session::put('domain_uuid', $music_on_hold->domain_uuid);
                    if (isset($music_on_hold->domain_uuid)){
                        $name = $music_on_hold->domain_name . '/';
                        $domain_settings = new DefaultSettingController;
                        $sounds_dir = $domain_settings->get('switch', 'sounds', 'dir');
                    }
                    else{
                        $default_settings = new DefaultSettingController;
                        $sounds_dir = $default_settings->get('switch', 'sounds', 'dir');
                    }
                    $name .= $music_on_hold->music_on_hold_name;
                    if (isset($music_on_hold->music_on_hold_rate)){
                        $name .= ('/' . $music_on_hold->music_on_hold_rate);
                    }

                    $music_on_hold_path = str_replace('$${sounds_dir}', $sounds_dir, $music_on_hold->music_on_hold_path);
                    $rate = $music_on_hold->music_on_hold_rate;
                    if (!is_int($rate)){
                        $rate = 48000;
                    }

                    $chime_list = $music_on_hold->music_on_hold_chime_list;
                    if (isset($chime_list)){
                        $chime_array = explode(',', $chime_list);
                        $chime_list = '';
                        $local_disk = Storage::build([
                            'driver' => 'local',
                            'root' => '/',
                        ]);
                        foreach($chime_array as $v){
                            $f = explode('/', $v);
                            if (isset($f[0]) && isset($f[1]) &&  $local_disk->exists($sounds_dir . '/en/us/callie/'.$f[0].'/'.$rate.'/'.$f[1])){
                                $chime_list .= ($sounds_dir . '/en/us/callie/' . $v);
                            }
                            else{
                                $chime_list .= $v;
                            }
                        }
                    }

                    if (empty($music_on_hold->music_on_hold_timer_name) || (strlen($music_on_hold->music_on_hold_timer_name) == 0)){
                        $timer_name = 'soft';
                    }
                    else{
                        $timer_name = $music_on_hold->music_on_hold_timer_name;
                    }

                    $xml->startElement('directory');
                    $xml->writeAttribute('name', $name);
                    $xml->writeAttribute('uuid', $music_on_hold->music_on_hold_uuid);
                    $xml->writeAttribute('path', $music_on_hold_path);

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('rate'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($rate); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('shuffle'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($music_on_hold->music_on_hold_shuffle); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('channels'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text(1); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('interval'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text(20); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('timer-name'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($timer_name); $xml->endAttribute();
                    $xml->endElement(); //param

                    if(isset($chime_list)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('chime-list'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($chime_list); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if(isset($music_on_hold->music_on_hold_chime_freq)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('chime-freq'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($music_on_hold->music_on_hold_chime_freq); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if(isset($music_on_hold->music_on_hold_chime_max)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('chime-max'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($music_on_hold->music_on_hold_chime_max); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    $xml->endElement(); // directory
                }
                break;
            case 'sofia.conf':
                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'Sofía Endpoint');
                $xml->startElement('global_settings');

                $sofia_global_settings = SofiaGlobalSetting::where('global_setting_enabled', 'true')
                                        ->orderBy('global_setting_name', 'asc')
                                        ->get();
                foreach ($sofia_global_settings as $sofia_global_setting){
                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text($sofia_global_setting->global_setting_name); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($sofia_global_setting->global_setting_value); $xml->endAttribute();
                    $xml->endElement(); //param
                }
                $xml->endElement(); //global_settings

                $xml->startElement('profiles');
                $sip_profiles = SipProfile::where('sip_profile_enabled', 'true')
                                ->where(function (Builder $query){
                                    $query->where('sip_profile_hostname', $hostname)
                                        ->orWhereNull('sip_profile_hostname')
                                        ->orWhere('sip_profile_hostname', '');
                                })
                                ->orderBy('sip_profile_name', 'asc')
                                ->get();
                foreach ($sip_profiles as $sip_profile){
                    $xml->startElement('profile');
                    $xml->writeAttribute('name', $sip_profile->sip_profile_name);
                    $xml->startElement('aliases');  // TODO: research how aliases work
                    $xml->endElement(); //aliases
                    $xml->startElement('gateways');  // TODO: research how aliases work

                    $gateways = Gateway::where('profile', $sip_profile->sip_profile_name)
                                        ->where('enable', 'true')
                                        ->where(function(Builder $query){
                                            $query->where('sip_profile_hostname', $hostname)
                                                ->orWhereNull('sip_profile_hostname')
                                                ->orWhere('sip_profile_hostname', '');
                                        })
                                        ->get();
                    foreach($gateways as $gateway){
                        $xml->startElement('gateway');
                        $xml->writeAttribute('name', gateway->gateway_uuid);

                        if(isset($gateway->username)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('username'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->username); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->distinct_to)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('distinct-to'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->distinct_to); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->auth_username)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('auth-username'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->auth_username); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->password)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('password'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->password); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->realm)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('realm'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->realm); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->from_user)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('from-user'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->from_user); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->from_domain)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('from-domain'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->from_domain); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->proxy)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('proxy'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->proxy); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->register_proxy)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('register-proxy'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->register_proxy); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->outbound_proxy)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('outbound-proxy'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->outbound_proxy); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->expire_seconds)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('expire-seconds'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->expire_seconds); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->register)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('register'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->register); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->register_transport)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('register-transport'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->register_transport); $xml->endAttribute();
                            $xml->endElement(); //param
                        }
                        else{
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('register-transport'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text('udp'); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->contact_params)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('contact-params'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->contact_params); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->retry_seconds)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('retry-seconds'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->retry_seconds); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->extension)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('extension'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->extension); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->ping)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('ping'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->ping); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->ping_min)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('ping-min'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->ping_min); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->ping_max)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('ping-max'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->ping_max); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->contact_in_ping)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('contact-in-ping'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->contact_in_ping); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->context)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('context'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->context); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->caller_id_in_from)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('caller-id-in-from'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->caller_id_in_from); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->supress_cng)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('supress-cng'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->supress-cng); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        if(isset($gateway->extension_in_contact)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('extension-in-contact'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->extension_in_contact); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                        $xml->startElement('variables');
                        if(isset($gateway->sip_cid_type)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text('sip_cid_type'); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($gateway->sip_cid_type); $xml->endAttribute();
                            $xml->endElement(); //param
                        }
                        $xml->endElement(); //variables
                        $xml->endElement(); //gateway
                    }

                    $xml->endElement(); //gateways

                    $sip_profile_domains = SipProfileDomain::where('sip_profie_uuid', $sip_profile->sip_profie_uuid)
                                            ->get();
                    foreach($sip_profile_domains as $sip_profile_domain){
                        $name = (is_null($sip_profile_domain->sip_profile_domain_name) || (strlen($sip_profile_domain->sip_profile_domain_name) == 0))?'false':$sip_profile_domain->sip_profile_domain_name;
                        $alias = (is_null($sip_profile_domain->sip_profile_domain_alias) || (strlen($sip_profile_domain->sip_profile_domain_alias) == 0))?'false':$sip_profile_domain->sip_profile_domain_alias;
                        $parse = (is_null($sip_profile_domain->sip_profile_domain_parse) || (strlen($sip_profile_domain->sip_profile_domain_parse) == 0))?'false':$sip_profile_domain->sip_profile_domain_parse;
                        $xml->startElement('domain');
                        $xml->startAttribute('name'); $xml->text($name); $xml->endAttribute();
                        $xml->startAttribute('alias'); $xml->text($alias); $xml->endAttribute();
                        $xml->startAttribute('parse'); $xml->text($parse); $xml->endAttribute();
                        $xml->endElement(); //domain
                    }

                    $sip_profile_settings = SipProfileSetting::where('sip_profile_uuid', $sip_profile->sip_profile_uuid)
                                            ->where('sip_profile_setting_enabled', 'true')
                                            ->orderBy('sip_profile_setting_name', 'asc')
                                            ->get();
                    foreach($sip_profile_settings as $sip_profile_setting){
                        if (isset($sip_profile_setting->sip_profile_setting_name)){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text($sip_profile_setting->sip_profile_setting_name); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($sip_profile_setting->sip_profile_setting_value); $xml->endAttribute();
                            $xml->endElement(); //param
                        }

                    }
                    $xml->endElement(); //profile
                }
                $xml->endElement(); //profiles
                break;
            case 'translate.conf':
                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'Sofía Endpoint');
                $xml->writeAttribute('autogenerated', 'true');
                $xml->startElement('profiles');

                $number_tanslations = NumberTranslation::where('number_translation_enabled', 'true')->get();
                foreach($number_translations as $number_translation){
                    $xml->startElement('profile');
                    $xml->writeAttribute('name', $number_translation->number_translation_name);
                    $xml->writeAttribute('description', $number_translation->number_translation_description);
                    $xml->endElement(); // profile

                    $number_translation_details = NumberTranslationDetail::where('number_translation_uuid', $number_translation->number_translation_uuid)
                                                ->orderBy('number_translation_detail_order', 'asc')
                                                ->get();
                    foreach($number_translation_details as $number_translation_detail){
                        $xml->startElement('rule');
                        $xml->startAttribute('regex'); $xml->text($number_translation_detail->number_translation_detail_regex); $xml->endAttribute();
                        $xml->startAttribute('replace'); $xml->text($number_translation_detail->number_translation_detail_replace); $xml->endAttribute();
                        $xml->endElement(); //rule
                    }
                    $xml->endElement(); // profile
                }

                $xml->endElement(); // profiles
                break;
            default:
                $notfound = true;
                $answer2 = $this->not_found();
        }

        $xml->endElement(); // configuration
        $xml->endElement(); // section
        $xml->endElement(); // document
        $xml->endDocument();
        $answer = $xml->outputMemory();

        if ($notfound)
            $answer = $answer2;

        return $answer;

    }

    // base64: /var/lib/freeswitch/recordings, {domain_name}/file.wav
    // http_cache: storage/app/public, storage/recordings/{domain_name}/file.wav
    private function write(string $root, string $path, $ivr_menu){
        $disk = Storage::build([
            'driver' => 'local',
            'root' => $root,
        ]);
        $disk->makeDirectory(dirname($path));

        if ($disk->missing($path)){
            $recording_query = Recording::where('domain_uuid', $ivr_menu->domain_uuid)
                        ->where('recording_filename', $ivr_menu->ivr_menu_greet_long);
            if ($recording_query->count() > 0){
                $recording = $recording_query->first();
                if(strlen($recording->recording_base64) > 32){
                    $s = $disk->put($path, base64_decode($recording->recording_base64, true));
                }

                if((strlen($recording->recording_base64) <= 32) || (!$s)){
                    // No Base64, lets look in the local filesystem
                    $local_disk = Storage::build([
                        'driver' => 'local',
                        'root' => '/',
                    ]);
                    $recordings_dir = $domain_settings->get('switch', 'recordings', 'dir');
                    list($t, $rel_path) = explode('/', $path, 2); unset($t);
                    $full_path = $recordings_dir . '/' . $rel_path;
                    if($local_disk->exists($full_path)){
                        // Copy to public storage
                        $disk->writeStream($path, $local_disk->readStream($full_path));
                    }
                }
            }
        }

    }

    public function directory(Request $request): string{

    }

    public function dialplan(Request $request): string{

    }

    public function languages(Request $request): string{    // TODO: Verify name

    }

    public function not_found(): string{                    // TODO: this or XMLWriter
        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<document type="freeswitch/xml">
  <section name="result">
    <result status="not found" />
  </section>
</document>';
    }
}
