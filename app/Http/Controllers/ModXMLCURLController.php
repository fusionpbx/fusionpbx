<?php

namespace App\Http\Controllers;

use App\Http\Controllers\DefaultSetting;
use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\FreeSWITCHAPIController;

use App\Models\AccessControl;
use App\Models\AccessControlNode;
use App\Models\CallCenterAgent;
use App\Models\CallCenterTier;
use App\Models\CallCenterQueue;
use App\Models\ConferenceControl;
use App\Models\ConferenceControlDetail;
use App\Models\ConferenceProfile;
use App\Models\ConferenceProfileParam;
use App\Models\Domain;
use App\Models\Extension;
use App\Models\ExtensionSetting;
use App\Models\ExtensionUser;
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
use App\Models\User;
use App\Models\Variable;
use App\Models\Voicemail;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use XMLWriter;

class ModXMLCURLController extends Controller
{
    private string $hostname;

    public function get_hostname(Request $request): ?string{
        $answer = $request->input('hostname') ?? null;

        if (is_null($answer)){
            $ips = $request->ips();
            $answer = end($ips) ?? null;
        }

        return $answer;
    }

    private function dump(Request $request){
        Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] input: '.print_r($request->toArray(), true));
    }

    public function configuration(Request $request): string{
        if(App::hasDebugModeEnabled()){
            $this->dump($request);
        }

        $hostname = $this->get_hostname($request);

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
                    $xml->fullEndElement(); // list
                }

                $xml->fullEndElement(); // network-lists
                break;
            case 'callcenter.conf':
                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'Call Center' );
                $xml->startElement('settings');
                // TODO: Maybe a controller here, since variables use categories (fusion artifact)
                $dsn_callcenter_query = Variable::where('var_enabled', 'true')
                    ->whereIn('var_name', ['dsn','dsn_callcenter'])
                    ->where(function (Builder $query){
                                    global $hostname;
                                    $query->where('var_hostname', $hostname)
                                        ->orWhereNull('var_hostname')
                                        ->orWhere('var_hostname', '');
                                })
                    ->orderByDesc('var_name');
                $dsn_callcenter = $dsn_callcenter_query->first();
                if(App::hasDebugModeEnabled()){
                    \Log::debug('dsn_callcenter: '.$dsn_callcenter_query->toRawSql());
                }
                if(isset($dsn_callcenter->var_value)){
                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('odbc-dsn'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($dsn_callcenter->var_value); $xml->endAttribute();
                    $xml->endElement(); // param
                }
                $xml->fullEndElement(); // settings


                $xml->startElement('queues');
                $callcenter_queues = CallCenterQueue::join(Domain::getTableName(), CallCenterQueue::getTableName().'.domain_uuid', '=', Domain::getTableName().'.domain_uuid')->get();
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
                $xml->fullEndElement(); // queues

                $default_settings = new DefaultSettingController;
                $recordings_dir = $default_settings->get('switch', 'recordings', 'dir');
                $xml->startElement('agents');
                switch(env('DB_CONNECTION', 'mysql')){
                    case 'pgsql':
                        $callcenter_agents = DB::table(CallCenterAgent::getTableName())
                                            ->select(DB::raw('SPLIT_PART(SPLIT_PART(a.agent_contact, "/", 2), "@", 1) AS extension, (SELECT extension_uuid FROM '.Extension::getTableName().' WHERE domain_uuid = '.CallCenterAgent::getTableName().'.domain_uuid AND extension = SPLIT_PART(SPLIT_PART('.CallCenterAgent::getTableName().'.agent_contact, "/", 2), "@", 1) limit 1) as extension_uuid,'.CallCenterAgent::getTableName().'.*, '.Domain::getTableName().'.domain_name'))
                                            ->join(Domain::getTableName(),CallCenterAgent::getTableName().'.domain_uuid',Domain::getTableName().'.domain_uuid')
                                            ->get();
                        break;
                    default:
                        $callcenter_agents = DB::table(CallCenterAgent::getTableName())
                                            ->select(DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(agent_contact,"/", -1), "@", 1) AS extension, (SELECT extension_uuid FROM '.Extension::getTableName().' WHERE domain_uuid = '.CallCenterAgent::getTableName().'.domain_uuid AND extension = SUBSTRING_INDEX(SUBSTRING_INDEX('.CallCenterAgent::getTableName().'.agent_contact, "/", -1), "@", 1) limit 1) as extension_uuid,'.CallCenterAgent::getTableName().'.*, '.Domain::getTableName().'.domain_name'))
                                            ->join(Domain::getTableName(),CallCenterAgent::getTableName().'.domain_uuid',Domain::getTableName().'.domain_uuid')
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
                $xml->fullEndElement(); // agents

                $xml->startElement('tiers');
                $callcenter_tiers = DB::table(CallCenterTier::getTableName())
                                    ->select(DB::raw(CallCenterTier::getTableName().'.domain_uuid, '. Domain::getTableName().'.domain_name, '. CallCenterTier::getTableName().'.call_center_agent_uuid, '. CallCenterTier::getTableName().'.call_center_queue_uuid, '. CallCenterQueue::getTableName().'.queue_extension, '. CallCenterTier::getTableName().'.tier_level, '. CallCenterTier::getTableName().'.tier_position'))
                                    ->join(Domain::getTableName(), CallCenterTier::getTableName().'.domain_uuid', Domain::getTableName().'.domain_uuid')
                                    ->join(CallCenterQueue::getTableName(), CallCenterTier::getTableName().'.call_center_queue_uuid', CallCenterQueue::getTableName().'.call_center_queue_uuid')
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
                $xml->fullEndElement(); // tiers
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
                                                ->get();
                    foreach($conference_profile_params as $conference_profile_param){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text($conference_profile_param->profile_param_name); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($conference_profile_param->profile_param_value); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    $xml->endElement(); // profile
                }
                $xml->fullEndElement(); // profiles
                break;
            case 'curl.conf':
                $default_settings = new DefaultSettingController;
                $max_bytes = $default_settings->get('config', 'curl.max_bytes', 'numeric') ?? 64000;
                $validate_certs = $default_settings->get('config', 'curl.validate_certs', 'boolean') ?? 'false';
                if ($max_bytes > 64000){
                    $max_bytes = 64000; // Hardcode value from FreeSWITCH
                }

                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'cURL Module' );
                $xml->startElement('settings');

                $xml->startElement('param');
                $xml->startAttribute('name'); $xml->text('max-bytes'); $xml->endAttribute();
                $xml->startAttribute('value'); $xml->text($max_bytes); $xml->endAttribute();
                $xml->endElement(); //param

                $xml->startElement('param');
                $xml->startAttribute('name'); $xml->text('validate-certs'); $xml->endAttribute();
                $xml->startAttribute('value'); $xml->text($validate_certs); $xml->endAttribute();
                $xml->endElement(); //param

                $xml->endElement(); // settings
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

                $sql = "WITH RECURSIVE ".IVRMenu::getTableName()." AS (
					SELECT * FROM ".IVRMenuOption::getTableName()."
						WHERE ivr_menu_uuid = '$ivr_menu_uuid' AND ivr_menu_enabled = 'true'
						UNION ALL
						SELECT child.* FROM ".IVRMenuOption::getTableName()." AS child, ".IVRMenu::getTableName()." AS parent
						WHERE child.ivr_menu_parent_uuid = parent.ivr_menu_uuid AND child.ivr_menu_enabled = 'true'
					)
					SELECT * FROM ".IVRMenu::getTableName()." INNER JOIN ".Domain::getTableName()." USING(domain_uuid)";
                $ivr_menus = DB::select($sql);
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
                                $ivr_menu_exit_sound = $sounds_dir . '/${default_language}/${default_dialect}/${default_voice}/ivr-record_exit_sound.wav';
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
                        if (isset($direct_dial_exclude) && is_array($direct_dial_exclude) && (count($direct_dial_exclude) > 0)){
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

                    $xml->fullEndElement(); // menu
                }

                $xml->fullEndElement(); // menus
                break;
            case 'local_stream.conf':
                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'Stream files from local dir');

                $musics_on_hold = DB::table(MusicOnHold::getTableName())
                                    ->leftJoin(Domain::getTableName(), MusicOnHold::getTableName().'.domain_uuid', Domain::getTableName().'.domain_uuid')
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
            case 'memcache.conf':
                $default_settings = new DefaultSettingController;
                $memcache_servers= $default_settings->get('config', 'memcache.servers', 'text') ?? '127.0.0.1';

                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'Memcache Configuration' );
                $xml->startElement('settings');

                $xml->startElement('param');
                $xml->startAttribute('name'); $xml->text('memcache-servers'); $xml->endAttribute();
                $xml->startAttribute('value'); $xml->text($memcache_servers); $xml->endAttribute();
                $xml->endElement(); //param

                $xml->endElement(); // settings
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
                                    global $hostname;
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
                    $xml->fullEndElement(); //aliases
                    $xml->startElement('gateways');  // TODO: research how aliases work

                    $gateways = Gateway::where('profile', $sip_profile->sip_profile_name)
                                        ->where('enabled', 'true')
                                        ->where(function(Builder $query){
                                            global $hostname;
                                            $query->where('hostname', $hostname)
                                                ->orWhereNull('hostname')
                                                ->orWhere('hostname', '');
                                        })
                                        ->get();
                    foreach($gateways as $gateway){
                        $xml->startElement('gateway');
                        $xml->writeAttribute('name', $gateway->gateway_uuid);

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
                        $xml->fullEndElement(); //variables
                        $xml->endElement(); //gateway
                    }

                    $xml->fullEndElement(); //gateways

                    $sip_profile_domains = SipProfileDomain::where('sip_profile_uuid', $sip_profile->sip_profile_uuid)
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

                $number_translations = NumberTranslation::where('number_translation_enabled', 'true')->get();
                foreach($number_translations as $number_translation){
                    $xml->startElement('profile');
                    $xml->writeAttribute('name', $number_translation->number_translation_name);
                    $xml->writeAttribute('description', $number_translation->number_translation_description);

                    $number_translation_details = NumberTranslationDetail::where('number_translation_uuid', $number_translation->number_translation_uuid)
                                                ->orderBy('number_translation_detail_order', 'asc')
                                                ->get();
                    foreach($number_translation_details as $number_translation_detail){
                        $xml->startElement('rule');
                        $xml->startAttribute('regex'); $xml->text($number_translation_detail->number_translation_detail_regex); $xml->endAttribute();
                        $xml->startAttribute('replace'); $xml->text($number_translation_detail->number_translation_detail_replace); $xml->endAttribute();
                        $xml->endElement(); //rule
                    }
                    $xml->fullEndElement(); // profile
                }

                $xml->fullEndElement(); // profiles
                break;
            case 'xml_rpc.conf':
                $xml->startElement('configuration');
                $xml->writeAttribute('name', $request->input('key_value'));
                $xml->writeAttribute('description', 'XML RPC');
                $xml->writeAttribute('autogenerated', 'true');
                $xml->startElement('settings');
                $xml->fullEndElement(); // settings

                $default_settings = new DefaultSettingController;
                $http_port = $default_settings->get('config', 'xml_rpc.http_port', 'numeric') ?? 8080;
                $auth_realm = $default_settings->get('config', 'xml_rpc.auth_realm', 'text') ?? 'freeswitch';
                $auth_user = $default_settings->get('config', 'xml_rpc.auth_user', 'text') ?? 'freeswitch';
                $auth_pass = $default_settings->get('config', 'xml_rpc.auth_pass', 'text') ?? 'works';

                $xml->startElement('param');
                $xml->startAttribute('name'); $xml->text('http-port'); $xml->endAttribute();
                $xml->startAttribute('value'); $xml->text($http_port); $xml->endAttribute();
                $xml->endElement(); //param

                $xml->startElement('param');
                $xml->startAttribute('name'); $xml->text('auth-realm'); $xml->endAttribute();
                $xml->startAttribute('value'); $xml->text($auth_realm); $xml->endAttribute();
                $xml->endElement(); //param

                $xml->startElement('param');
                $xml->startAttribute('name'); $xml->text('auth-user'); $xml->endAttribute();
                $xml->startAttribute('value'); $xml->text($auth_user); $xml->endAttribute();
                $xml->endElement(); //param

                $xml->startElement('param');
                $xml->startAttribute('name'); $xml->text('auth-pass'); $xml->endAttribute();
                $xml->startAttribute('value'); $xml->text($auth_pass); $xml->endAttribute();
                $xml->endElement(); //param
                break;
            default:
                $notfound = true;
                $answer2 = $this->not_found();
        }

        $xml->fullEndElement(); // configuration
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
                if(isset($recording->recording_base64) && (strlen($recording->recording_base64) > 32)){
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
        if(App::hasDebugModeEnabled()){
            $this->dump($request);
        }
        $purpose = $request->input('purpose');
        $action = $request->input('action');
        $event_calling_function = $request->input('Event-Calling-Function');
        $event_calling_file = $request->input('Event-Calling-File');
        $user   = $request->input('user');
        $domain_name = $request->input('doman') ?? ($request->input('doman_name') ?? ($request->input('variable_domain_name') ?? $request->input('variable_sip_from_host')));
        $domain_query = Domain::where('domain_name', $domain_name)
                        ->where('domain_enabled', 'true');
        $domain = $domain_query->first();
        if(App::hasDebugModeEnabled()){
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] query: '.$domain_query->toRawSql());
        }
        $domain_uuid = $domain->domain_uuid;
        unset($domain);
        Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] From DB $domain_uuid: '.$domain_uuid);

        if (isset($domain_uuid) && empty($domain_name)){
            $domain = Domain::where('domain_uuid', $domain_uuid)
                            ->where('domain_enabled', 'true')
                            ->first();
            $domain_name = $domain->domain_name;
            unset($domain);
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] From DB $domain_name: '.$domain_name);
        }
        $answer = '';
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument( '1.0', 'UTF-8', 'no' );
        $xml->startElement( 'document' );
        $xml->writeAttribute('type', 'freeswitch/xml');
        $xml->startElement('section');
        $xml->writeAttribute('name', 'directory');

        if (($purpose == 'gateways') || ($event_calling_function == 'switch_xml_locate_domain')){
            $domains = Domain::all();
            foreach($domains as $domain){
                $xml->startElement('domain');
                $xml->startAttribute('name'); $xml->text($domain->domain_name); $xml->endAttribute();
                $xml->endElement(); //domain
            }

        }
        elseif ($action == 'message-count') {
            // TODO: Fusion has nothing here
        }
        elseif ($action == 'group_call'){
            $extensions = Extension::where('domain_uuid', $domain_uuid)
                            ->orderBy('call_group', 'asc')
                            ->get();
            $call_group_array = array(); $call_group_array_temp = array();
            foreach($extensions as $extension){
                if(isset($extension->call_group)){
                    $tmp_array = explode(',', $extension->call_group);
                    foreach ($tmp_array as $value){
                        $value = trim($value);
                        if(strlen($value) > 0){
                            $call_group_array_temp[$value][] = $extension->extension;
                        }
                    }
                    $call_group_array[$value] = implode(',', $call_group_array_temp);
                }
            }

            $xml->startElement('domain');
            $xml->writeAttribute('name', 'directory');
            $xml->startElement('gropus');
            foreach($call_group_array as $key => $value){
                $call_group = trim($key);
                $extension_list = trim($value);
                if (strlen($call_group) > 0){
                    $xml->startElement('group');
                    $xml->writeAttribute('name', $call_group);
                    $xml->startElement('users');
                    $extension_array = explode(',', $extension_list);
                    foreach($extension_array as $tmp_extension){
                        $xml->startElement('user');
                        $xml->startAttribute('id'); $xml->text($tmp_extension); $xml->endAttribute();
                        $xml->startAttribute('type'); $xml->text('pointer'); $xml->endAttribute();
                        $xml->endElement(); //user
                    }
                    $xml->fullEndElement(); // users
                    $xml->fullEndElement(); // group
                }
            }
            $xml->fullEndElement(); // groups
            $xml->endElement(); // domain
        }
        elseif ($action == 'reverse-auth-lookup'){
            $extension = Extension::where('domain_uuid', $domain_uuid)
                            ->where('enabled', 'true')
                            ->where(function (Builder $query){
                                    global $user;
                                    $query->where('extension', $user)
                                        ->orWhere('number_alias', $user);
                                })
                            ->first();
            if(isset($domain_name) && isset($extension->extension) && isset($extension->password)){
                $xml->startElement('domain');
                $xml->writeAttribute('name', $domain_name);
                $xml->writeAttribute('alias', 'true');
                $xml->startElement('user');
                $xml->writeAttribute('id', $extension->extension);
                if(isset($extension->number_alias)){
                    $xml->writeAttribute('number-alias', $extension->number_alias);
                }
                $xml->startElement('params');
                $xml->startElement('param');
                $xml->startAttribute('name'); $xml->text('reverse-auth-user'); $xml->endAttribute();
                $xml->startAttribute('value'); $xml->text($extension->extension); $xml->endAttribute();
                $xml->endElement(); //param

                $xml->startElement('param');
                $xml->startAttribute('name'); $xml->text('reverse-auth-pass'); $xml->endAttribute();
                $xml->startAttribute('value'); $xml->text($extension->password); $xml->endAttribute();
                $xml->endElement(); //param

                $xml->endElement(); // params
                $xml->endElement(); // user
                $xml->endElement(); // domain
            }

        }
        elseif ($event_calling_function == 'switch_load_network_lists'){
            $extensions = Extensions::join(Domain::getTableName(),Extension::getTableName().'.domain_uuid', Domain::getTableName().'.domain_uuid')
                            ->WhereNotNull('cidr')
                            ->orWhere('cidr','<>','')
                            ->orderBy('domain_name')
                            ->get();
            foreach($extensions as $extension){
                if((isset($domain_name) && ($extension->domain_name == $domain_name)) || empty($domain_name)){
                    $xml->startElement('domain');
                    $xml->writeAttribute('name', $domain_name);
                    $xml->writeAttribute('alias', 'true');
                    $xml->startElement('groups');
                    $xml->startElement('group');
                    $xml->writeAttribute('name', 'default');
                    $xml->startElement('users');

                    $xml->startElement('user');
                    $xml->startAttribute('id'); $xml->text($extension->extension); $xml->endAttribute();
                    if ((isset($extension->cidr) && strlen($extension->cidr) > 0)){
                        $xml->startAttribute('cidr'); $xml->text($extension->cidr); $xml->endAttribute();
                    }
                    $xml->endElement(); //user

                    $xml->endElement(); // users
                    $xml->endElement(); // group
                    $xml->endElement(); // groups
                    $xml->endElement(); // domain
                }
            }

        }
        elseif (($event_calling_function == 'populate_database') && ($event_calling_file == 'mod_directory.c')){
            $extensions = Extensions::join(Domain::getTableName(),Extension::getTableName().'.domain_uuid', Domain::getTableName().'.domain_uuid')
                            ->where('directory_visible','true')
                            ->orWhere('directory_exten_visible','true')
                            ->orderBy('domain_name')
                            ->get();
            foreach($extensions as $extension){
                if((isset($domain_name) && ($extension->domain_name == $domain_name)) || empty($domain_name)){
                    $xml->startElement('domain');
                    $xml->writeAttribute('name', $domain_name);
                    $xml->writeAttribute('alias', 'true');
                    $xml->startElement('groups');
                    $xml->startElement('group');
                    $xml->writeAttribute('name', 'default');
                    $xml->startElement('users');

                    $xml->startElement('user');
                    $xml->startAttribute('id'); $xml->text($extension->extension); $xml->endAttribute();
                    if (isset($extension->number_alias) && (strlen($extension->number_alias) > 0)){
                        $xml->startAttribute('number-alias'); $xml->text($extension->number_alias); $xml->endAttribute();
                    }
                    $xml->startElement('params');

                    if (isset($extension->directory_visible) && (strlen($extension->directory_visible) > 0)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('directory-visible'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($extension->directory_visible); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($extension->directory_exten_visible) && (strlen($extension->directory_exten_visible) > 0)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('directory-exten-visible'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($extension->directory_exten_visible); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    $xml->endElement(); // params

                    $xml->startElement('variables');
                    if (isset($extension->effective_caller_id_name) && (strlen($extension->effective_caller_id_name) > 0)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('effective_caller_id_name'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($extension->effective_caller_id_name); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }
                    if (isset($extension->directory_full_name) && (strlen($extension->directory_full_name) > 0)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('directory_full_name'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($extension->directory_full_name); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }
                    $xml->endElement(); //variables

                    $xml->endElement(); //user

                    $xml->endElement(); // users
                    $xml->endElement(); // group
                    $xml->endElement(); // groups
                    $xml->endElement(); // domain
                }
            }
        }
        else{
            $continue = true;
            $api = new FreeSWITCHAPIController;
            $default_settings = new DefaultSettingController;
            $use_fs_path = $default_settings->get('config', 'xml_handler.fs_path', 'boolean') ?? 'false';
            $number_as_presence_id = $default_settings->get('config', 'xml_handler.number_as_presence_id', 'boolean') ?? 'true';
            $dial_string_based_on_userid = $default_settings->get('config', 'xml_handler.reg_as_number_alias', 'boolean') ?? 'false';
            $sip_auth_method = strtoupper($request->input('sip_auth_method'));
            $user = $request->input('user') ?? '';
            $from_user = (($use_fs_path == 'true') && ($sip_auth_method == 'INVITE')) ? $user : $request->input('sip_auth_method');
            $dialed_extension = $request->input('dialed_extension');
            $source = 'database';
            if (empty($dialed_extension)){
                $use_fs_path = 'false';
            }
            if (empty($from_user)){
                $from_user = $user;
            }
            if (($user == '*97') || (empty($user))){
                $source = '';
                $continue = false;
            }

            $loaded_from_db = false;
            if (($source == 'database') || ($use_fs_path == 'true')){
                $loaded_from_db = true;
                $local_hostname = $this->get_hostname($request);    // TODO: verify this
                $reg_user = $dialed_extension;
                if ($dial_string_based_on_userid == 'false'){
                    $reg_user = $api->execute('user_data', $dialed_extension . '@' . $domain_name . ' attr id');
                }
                else{
                    $reg_user = $dialed_extension;
                }

                $registrations = $api->execute('show', 'registrations as XML');
                $xml = simplexml_load_string($registrations);
                $row_count = $xml->attributes()['row_count'];
                $database_hostname = null;
                if ($row_count > 0){
                    foreach($xml->row as $r){
                        if (($r->reg_user == $reg_user) && ($r->realm == $domain_name) && ($r->expires > time())){
                            $database_hostname = $r->hostname;
                            break;
                        }
                    }
                }

                if (empty($database_hostname) && ($use_fs_path == 'true')){
                    $use_fs_path = 'false';
                }
            }

            if ($continue){
                $continue = false;
                $extension_query = Extension::join(Domain::getTableName(), Extension::getTableName().'.domain_uuid', '=', Domain::getTableName().'.domain_uuid')
                                ->where('domain_enabled', 'true')
                                ->where('enable', 'true')
                                ->where(Domain::getTableName().'.domain_uuid', $domain_uuid)
                                ->where(function (Builder $query){
                                    global $user;
                                    $query->where('extension', $user)
                                        ->orWhere('number_alias', $user);
                                });
                if ($extension_query->count() > 0){
                        $continue = true;
                        $extension1 = $extension_query->first();
                        $extension_uuid = $extension1->extension_uuid;
                        $extension = $extension1->extension;
                        $cidr = $extension1->cidr ?? '';
                        $number_alias = $extension1->number_alias ?? '';
                        $extension_user_query = ExtensionUser::where('domain_uuid', $domain_uuid)
                                            ->where('extension_uuid', $extension_uuid);
                        if($extension_user_query->count() > 0){
                            $extension_user = $extension_user_query->first();
                            $user_uuid = $extension_user->user_uuid;

                            if (isset($user_uuid)){
                                $contact_query = User::where('domain_uuid', $domain_uuid)
                                                ->where('user_uuid', $user_uuid);
                                if($contact_query->count() > 0){
                                    $contact_uuid1 = $contact_query->first();
                                    $contact_uuid = $contact_uuid1->contact_uuid;
                                }
                            }
                        }

                        $password = $extension1->password;
                        $mwi_account = $extension1->mwi_account;
                        $auth_acl = $extension1->auth_acl;
                        $sip_from_user = $extension1->extension;
                        $sip_from_number = $extension1->number_alias ?? $extension1->extension;
                        $call_group = $extension1->call_group;
                        $call_screen_enabled = $extension1->call_screen_enabled;
                        $user_record = $extension1->user_record;
                        $hold_music = $extension1->hold_music;
                        $toll_allow = $extension1->toll_allow;
                        $accountcode = $extension1->accountcode;
                        $user_context = $extension1->user_context;
                        $effective_caller_id_name = $extension1->effective_caller_id_name;
                        $effective_caller_id_number = $extension1->effective_caller_id_number;
                        $outbound_caller_id_name = $extension1->outbound_caller_id_name;
                        $outbound_caller_id_number = $extension1->outbound_caller_id_number;
                        $emergency_caller_id_name = $extension1->emergency_caller_id_name;
                        $emergency_caller_id_number = $extension1->emergency_caller_id_number;
                        $missed_call_app = $extension1->missed_call_app;
                        $missed_call_data = $extension1->missed_call_data;
                        $directory_first_name = $extension1->directory_first_name;
                        $directory_last_name = $extension1->directory_last_name;
                        $directory_exten_visible = $extension1->directory_exten_visible;
                        $limit_max = $extension1->limit_max;
                        $call_timeout = $extension1->call_timeout;
                        $max_registrations = $extension1->max_registrations;
                        $limit_destinations = $extension1->limit_destination;
                        $sip_force_contact = $extension1->sip_force_contact;
                        $sip_force_expires = $extension1->sip_force_expires;
                        $nibble_account = $extension1->nibble_account;
                        $sip_bypass_media = $extension1->sip_bypass_media;
                        $absolute_codec_string = $extension1->absolute_codec_string;
                        $force_ping = $extension1->force_ping;
                        $forward_all_enabled = $extension1->forward_all_enabled;
                        $forward_all_destination = $extension1->forward_all_destination;
                        $forward_busy_enabled = $extension1->forward_busy_enabled;
                        $forward_busy_destination = $extension1->forward_busy_destination;
                        $forward_no_answer_enabled = $extension1->forward_no_answer_enabled;
                        $forward_no_answer_destination = $extension1->forward_no_answer_destination;
                        $forward_user_not_registered_enabled = $extension1->forward_user_not_registered_enabled;
                        $forward_user_not_registered_destination = $extension1->forward_user_not_registered_destination;
                        $do_not_disturb = $extension1->do_not_disturb;
                        if (isset($extension1->follow_me_uuid)){
                            $follow_me_uuid = $extension1->follow_me_uuid;
                            $follow_me_enabled = (($do_not_disturb == 'true') || ($forward_all_enabled == 'true'))? 'false' : $extension1->follow_me_enabled;
                        }
                        $presence_id = ($number_as_presence_id == 'true' ? $sip_from_number : $sip_from_user) . '@' . $domain_name;
                        if ($do_not_disturb == 'true'){
                            $dial_string = 'error/user_busy';
                        }
                        elseif (isset($extension1->dial_string)){
                            $dial_string = $extension1->dial_string;
                        }
                        else{
                            $destination = ($dial_string_based_on_userid == 'true' ? $sip_from_number : $sip_from_user) . '@' . $domain_name;
                            if (empty($dial_string)){
                                $dial_string = 'sip_invite_domain='.$domain_name.', presence_id='.$presence_id.'}${sofia_contact(*/'.$destination.')}';
                            }
                            if($use_fs_path == 'true'){
                                if ($local_hostname == $database_hostname){
                                    Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] local_host and database_host are the same.');
                                }
                                else{
                                    $contact = trim($api->execute('sofia_contact', $destination));
                                    $array = exploce('/', $contact);
                                    $proxy = $database_hostname;
                                    $exchange_profile = $default_settings->get('config', 'xml_handler.exchange_profile', 'text') ?? 'internal';
                                    $profile = $default_settings->get('config', 'xml_handler.exchange_profile', 'text') ??
                                        (($profile == 'user_not_registered')?'internal':$array[1]);
                                    $dial_string = '{sip_h_X-context='.$domain_name.',sip_invite_domain='.$domain_name.',presence_id='.$presence_id.'}sofia/'.$profile.'/'.$destination.';fs_path=sip:'.$proxy;
                                    Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $local_hostname: '.$local_hostname);
                                    Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $database_hostname: '.$database_hostname);
                                    Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $dial_string: '.$dial_string);
                                }
                            }
                        }
                }
            }

            if(isset($extension_uuid)){
                $extension_settings = ExtensionSetting::where('extension_setting_enabled', 'true')
                                    ->where('extension_uuid', $extension_uuid)
                                    ->get();
            }
            else{
                $continue = false;
            }

            if ($continue){
                $vm_enabled = 'true';
                $voicemail_query = VoiceMail::where('domain_uuid', $domain_uuid)
                            ->where('voicemail_id', isset($number_alias)? $number_alias : $user)
                            ->where('voicemail_enabled', 'true');
                if($voicemail_query->count() > 0){
                    // TODO: review if first or last
                    $voicemail = $voicemail_query->first();
                    $vm_passwrod = $voicemail->voicemail_password;
                    $vm_attach_file = $voicemail->voicemail_attach_file ?? 'true';
                    $vm_keep_local_after_email = $voicemail->voicemail_local_after_email ?? 'true';
                    $vm_mailto = $voicemail->voicemail_mail_to ?? '';
                }

                if (isset($password)){
                    $directory_full_name = trim(($directory_first_name ?? '').' '.($directory_last_name ?? ''));

                    $xml->startElement('domain');
                    $xml->writeAttribute('name', $domain_name);
                    $xml->writeAttribute('alias', 'true');
                    $xml->startElement('params');

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('jsonrpc-allowed-methods'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text('verto'); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('jsonrpc-allowed-event-channels'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text('demo,conference,presence'); $xml->endAttribute();        // TODO: find useful values
                    $xml->endElement(); //param

                    $xml->endElement(); // params

                    $xml->startElement('groups');
                    $xml->startElement('group');
                    $xml->writeAttribute('name', 'default');
                    $xml->startElement('users');

                    $xml->startElement('user');
                    if(isset($number_alias)){
                        $xml->writeAttribute('number-alias', $number_alias);
                    }
                    if(isset($cidr)){
                        $xml->writeAttribute('cidr', $cidr);
                    }
                    $xml->startElement('params');

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('password'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($password); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('vm-enabled'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($vm_enabled); $xml->endAttribute();
                    $xml->endElement(); //param

                    if (isset($vm_mailto)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('vm-password'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($vm_password); $xml->endAttribute();
                        $xml->endElement(); //param

                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('vm-email-all-messages'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($vm_enabled); $xml->endAttribute();
                        $xml->endElement(); //param

                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('vm-attach-file'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($vm_attach_file); $xml->endAttribute();
                        $xml->endElement(); //param

                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('vm-keep-local-after-email'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($vm_keep_local_after_email); $xml->endAttribute();
                        $xml->endElement(); //param

                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('vm-mailto'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($vm_mailto); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($mwi_account)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('MWI-Account'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($mwi_account); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    if (isset($auth_acl)){
                        $xml->startElement('param');
                        $xml->startAttribute('name'); $xml->text('auth-acl'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($auth_acl); $xml->endAttribute();
                        $xml->endElement(); //param
                    }

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('dial-string'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($dial_string); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('vento-context'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($user_context); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('verto-dialplan'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text('XML'); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('jsonrpc-allowed-methods'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text('verto'); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('jsonrpc-allowed-event-channels'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text('demo,conference,presence'); $xml->endAttribute();
                    $xml->endElement(); //param

                    $xml->startElement('param');
                    $xml->startAttribute('name'); $xml->text('max-registrations-per-extension'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($max_registrations); $xml->endAttribute();
                    $xml->endElement(); //param

                    foreach ($extension_settings as $extension_setting){
                        if ($extension_setting->extension_setting_type == 'param'){
                            $xml->startElement('param');
                            $xml->startAttribute('name'); $xml->text($extension_setting->extension_setting_name); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($extension_setting->extension_setting_value); $xml->endAttribute();
                            $xml->endElement(); //param
                        }
                    }

                    $xml->endElement(); // params
                    $xml->startElement('variables');

                    $xml->startElement('variable');
                    $xml->startAttribute('name'); $xml->text('domain_uuid'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($domain_uuid); $xml->endAttribute();
                    $xml->endElement(); //variable

                    $xml->startElement('variable');
                    $xml->startAttribute('name'); $xml->text('domain_name'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($domain_name); $xml->endAttribute();
                    $xml->endElement(); //variable

                    $xml->startElement('variable');
                    $xml->startAttribute('name'); $xml->text('extension_uuid'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($extension_uuid); $xml->endAttribute();
                    $xml->endElement(); //variable

                    if(isset($user_uuid)){
                        // TODO: Find out how to put more than one user, user-extension is a many-many relationship
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('user_uuid'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($user_uuid); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if(isset($contact_uuid)){
                        // TODO: same here
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('contact_uuid'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($contact_uuid); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    $xml->startElement('variable');
                    $xml->startAttribute('name'); $xml->text('call_timeout'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($call_timeout); $xml->endAttribute();
                    $xml->endElement(); //variable

                    $xml->startElement('variable');
                    $xml->startAttribute('name'); $xml->text('caller_id_name'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($sip_from_user); $xml->endAttribute();
                    $xml->endElement(); //variable

                    $xml->startElement('variable');
                    $xml->startAttribute('name'); $xml->text('caller_id_number'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($sip_from_number); $xml->endAttribute();
                    $xml->endElement(); //variable

                    $xml->startElement('variable');
                    $xml->startAttribute('name'); $xml->text('presence_id'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($presence_id); $xml->endAttribute();
                    $xml->endElement(); //variable

                    if (isset($call_group)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('call_group'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($call_group); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($call_screen_enabled)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('call_screen_enabled'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($call_screen_enabled); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($user_record)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('user_record'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($user_record); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($hold_music)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('hold_music'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($hold_music); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($toll_allow)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('toll_allow'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($toll_allow); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($accountcode)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('accountcode'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($accountcode); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    $xml->startElement('variable');
                    $xml->startAttribute('name'); $xml->text('user_context'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($user_context); $xml->endAttribute();
                    $xml->endElement(); //variable

                    if (isset($effective_caller_id_name)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('effective_caller_id_name'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($effective_caller_id_name); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($effective_caller_id_number)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('effective_caller_id_number'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($effective_caller_id_number); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($outbound_caller_id_name)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('outbound_caller_id_name'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($outbound_caller_id_name); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($outbound_caller_id_number)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('outbound_caller_id_number'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($outbound_caller_id_number); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($emergency_caller_id_name)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('emergency_caller_id_name'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($emergency_caller_id_name); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($emergency_caller_id_number)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('emergency_caller_id_number'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($emergency_caller_id_number); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($missed_call_app)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('missed_call_app'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($missed_call_app); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($missed_call_data)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('missed_call_data'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($missed_call_data); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($directory_full_name)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('directory_full_name'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($directory_full_name); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($directory_visible)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('directory-visible'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($directory_visible); $xml->endAttribute();
                        $xml->endElement(); //variable

                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('directory_visible'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($directory_visible); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($directory_exten_visible)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('directory-exten-visible'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($directory_exten_visible); $xml->endAttribute();
                        $xml->endElement(); //variable

                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('directory_exten_visible'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($directory_exten_visible); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    $xml->startElement('variable');
                    $xml->startAttribute('name'); $xml->text('limit_max'); $xml->endAttribute();
                    $xml->startAttribute('value'); $xml->text($limit_max ?? 5); $xml->endAttribute();   // TODO: find out why 5
                    $xml->endElement(); //variable

                    if (isset($limit_destination)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('limit_destination'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($limit_destination); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($sip_force_contact)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('sip-force-contact'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($sip_force_contact); $xml->endAttribute();
                        $xml->endElement(); //variable

                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('sip_force_contact'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($sip_force_contact); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($sip_force_expires)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('sip-force-expires'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($sip_force_expires); $xml->endAttribute();
                        $xml->endElement(); //variable

                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('sip_force_expires'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($sip_force_expires); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($nibble_account)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('nibble_account'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($nibble_account); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($absolute_codec_string)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('absolute_codec_string'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($absolute_codec_string); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($force_ping)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('force_ping'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($force_ping); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($sip_bypass_media)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text(Str::snake(Str::of($sip_bypass_media)->camel(), '_')); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text('true'); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($forward_all_enabled)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('forward_all_enabled'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($forward_all_enabled); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($forward_all_destination)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('forward_all_destination'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($forward_all_destination); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($forward_busy_enabled)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('forward_busy_enabled'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($forward_busy_enabled); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($forward_busy_destination)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('forward_busy_destination'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($forward_busy_destination); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($forward_no_answer_enabled)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('forward_no_answer_enabled'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($forward_no_answer_enabled); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($forward_no_answer_destination)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('forward_no_answer_destination'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($forward_no_answer_destination); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($forward_user_not_registered_enabled)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('forward_user_not_registered_enabled'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($forward_user_not_registered_enabled); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($forward_user_not_registered_destination)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('forward_not_registered_destination'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($forward_user_not_registered_destination); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    if (isset($follow_me_enabled)){
                        $xml->startElement('variable');
                        $xml->startAttribute('name'); $xml->text('follow_me_enabled'); $xml->endAttribute();
                        $xml->startAttribute('value'); $xml->text($follow_me_enabled); $xml->endAttribute();
                        $xml->endElement(); //variable
                    }

                    foreach ($extension_settings as $extension_setting){
                        if ($extension_setting->extension_setting_type == 'variable'){
                            $xml->startElement('variable');
                            $xml->startAttribute('name'); $xml->text($extension_setting->extension_setting_name); $xml->endAttribute();
                            $xml->startAttribute('value'); $xml->text($extension_setting->extension_setting_value); $xml->endAttribute();
                            $xml->endElement(); //variable
                        }
                    }

                    $xml->endElement(); // variables
                    $xml->endElement(); // user

                    $xml->endElement(); // users
                    $xml->endElement(); // group
                    $xml->endElement(); // groups
                    $xml->endElement(); // domain
                }
            }
        }
        $xml->fullEndElement(); // section
        $xml->endElement(); // document
        $xml->endDocument();
        $answer = $xml->outputMemory();
        return $answer;

    }

    public function dialplan(Request $request): string{
        if(App::hasDebugModeEnabled()){
            $this->dump($request);
        }
    }

    public function languages(Request $request): string{    // TODO: Verify name
        if(App::hasDebugModeEnabled()){
            $this->dump($request);
        }
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
