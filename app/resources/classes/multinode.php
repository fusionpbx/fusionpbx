<?php
error_reporting(1);

/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Copyright (C) 2010 - 2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the directory class
if (!class_exists('multinode')) {
	class multinode {
		public $db;
		public $domain_uuid;
		public $domain_name;
        private $app_uuid;
        public $multinode_rabitMQ_id;
        public $name;
        public $node_priority;
        public $switch_name;
        public $hostname;
        public $virtualhost;
        public $username;
        public $password;
        public $port;
        public $exchange_name;
        public $exchange_type;
        public $circuit_breaker_ms;
        public $reconnect_interval_ms;
        public $send_queue_size;
        public $enable_fallback_format_fields;
        public $format_fields;
        public $event_filter;
		

		public function __construct() {
			//connect to the database if not connected
				if (!$this->db) {
					require_once "resources/classes/database.php";
					$database = new database;
					$database->connect();
					$this->db = $database->db;
				}

			//set the application id
				$this->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		// public function exists($domain_uuid, $extension) {
            // 	$sql = "select extension_uuid from v_extensions ";
            // 	$sql .= "where domain_uuid = :domain_uuid ";
            // 	$sql .= "and (extension = :extension or number_alias = :extension) ";
            // 	$sql .= "and enabled = 'true' ";
            // 	$prep_statement = $this->db->prepare($sql);
            // 	$prep_statement->bindParam(':domain_uuid', $domain_uuid);
            // 	$prep_statement->bindParam(':extension', $extension);
            // 	$prep_statement->execute();
            // 	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
            // 	if ($result && count($result) > 0) {
            // 		return true;
            // 	}
            // 	else {
            // 		return false;
            // 	}
		// }

		public function get_domain_uuid() {
			return $this->domain_uuid;
		}

		public function set_domain_uuid($domain_uuid){
			$this->domain_uuid = $domain_uuid;
		}

		public function xml() {
            // die("xml welcome");

			// if (isset($_SESSION['switch']['multinode']['dir'])) {
				//declare global variables
					global $config, $db, $domain_uuid;
                
				//get the domain_name
					$domain_name = $_SESSION['domains'][$domain_uuid]['domain_name'];
					$user_context = $domain_name;

				//delete all old extensions to prepare for new ones
					// $dialplan_list = glob($_SESSION['switch']['extensions']['dir']."/".$user_context."/v_*.xml");
					// foreach($dialplan_list as $name => $value) {
					// 	unlink($value);
					// }

                //write the xml files
                    $sql = "SELECT * FROM v_multinode_rabitMQ ";
                    $sql .= "WHERE domain_uuid = '$domain_uuid' ";
                    // $sql .= "ORDER BY node_priority ASC ";
					$prep_statement = $db->prepare(check_sql($sql));
                    $prep_statement->execute();
                    // $row = $prep_statement->fetch(PDO::FETCH_ASSOC);
                    $row = $prep_statement->fetchAll(PDO::FETCH_NAMED);
    
                    $multinode_xml_condensed = false;
                    
                    
                    
                    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                    $xml = "<configuration name='amqp.conf' description='mod_amqp'> \n";
                    $xml .= "     <producers> \n";
                    $xml .= "        <profile name='default'> \n";
                    $xml .= "        <connections> \n";
                    $xml .= "        <connection name='primary'> \n";
                    $xml .= "           <param name='hostname' value='localhost'/> \n";
                    $xml .= "           <param name='virtualhost' value='/' /> \n";
                    $xml .= "           <param name='username' value='guest' /> \n";
                    $xml .= "           <param name='password' value='guest'/> \n";
                    $xml .= "           <param name='port' value='5673' /> \n";
                    $xml .= "           <param name='heartbeat' value='0' /> \n";
                    $xml .= "        </connection> \n";
                    $xml .= "        <connection name='secondary'> \n";
                    $xml .= "           <param name='hostname' value='localhost'/> \n";
                    $xml .= "           <param name='virtualhost' value='/' /> \n";
                    $xml .= "           <param name='username' value='guest' /> \n";
                    $xml .= "           <param name='password' value='guest'/> \n";
                    $xml .= "           <param name='port' value='5673' /> \n";
                    $xml .= "           <param name='heartbeat' value='0' /> \n";
                    $xml .= "        </connection> \n";
                    $xml .= "        </connections> \n";
                    $xml .= "        <params> \n";
                    $xml .= "       <param name='exchange-name' value='TAP.Events' /> \n";
                    $xml .= "       <param name='exchange-type' value='topic'/> \n";
                    $xml .= "       <param name='circuit_breaker_ms' value='10000' /> \n";
                    $xml .= "       <param name='reconnect_interval_ms' value='1000' /> \n";
                    $xml .= "       <param name='send_queue_size' value='5000' /> \n";
                    $xml .= "        <param name='enable_fallback_format_fields' value='1' /> \n";

                    $xml .= "        <param name='format_fields' value='#FreeSWITCH,FreeSWITCH-Hostname,Event-Name,Event-Subclass,Unique-ID'/> \n";

                    $xml .= "       <!-- If enable_fallback_format_fields is enabled, then you can | separate event headers, and if the first does not exist
	                                            then the system will check additional configured header values. --> \n";
                    $xml .= "       <!-- <param name='format_fields' value='#FreeSWITCH,FreeSWITCH-Hostname|#Unknown,Event-Name,Event-Subclass,Unique-ID'/> --> \n";
                    $xml .= "       <!--    <param name='event_filter' value='SWITCH_EVENT_ALL' /> --> \n";
                    
                    $xml .= "        <param name='event_filter' value='SWITCH_EVENT_CHANNEL_CREATE,SWITCH_EVENT_CHANNEL_DESTROY,SWITCH_EVENT_HEARTBEAT,SWITCH_EVENT_DTMF' /> \n";
                    $xml .= "        </params> \n";
                    $xml .= "        </profile> \n";
                    $xml .= "    </producers> \n";

                    $xml .= "</configuration> \n";

                    echo htmlentities($xml);
                    
                    $Handle = fopen($File, 'w');


                    //write the xml file
					// if (is_readable($extension_dir) && strlen($extension_dir) > 0) {
                        // echo "<pre>";
                        // die(print_r($_SESSION['switch']));
						// $fout = fopen("amqp.conf.xml","w");
						//         fwrite($fout, $xml);
						//         unset($xml);
						//         fclose($fout);
					// }

				//apply settings
					// $_SESSION["reload_xml"] = true;
                    echo "<pre>";
                    die(print_r($row));
                    



















					$sql = "SELECT * FROM v_extensions AS e, v_voicemails AS v ";
					$sql .= "WHERE e.domain_uuid = '$domain_uuid' ";
					$sql .= "AND COALESCE(NULLIF(e.number_alias,''),e.extension) = CAST(v.voicemail_id as VARCHAR) ";
					$sql .= "ORDER BY e.call_group ASC ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$i = 0;
                    $extension_xml_condensed = false;
                    
					while($row = $prep_statement->fetch(PDO::FETCH_ASSOC)) {

                        $call_group = $row['call_group'];
						$call_group = str_replace(";", ",", $call_group);

                        $tmp_array = explode(",", $call_group);

                        foreach ($tmp_array as &$tmp_call_group) {
							$tmp_call_group = trim($tmp_call_group);
							if (strlen($tmp_call_group) > 0) {
								if (strlen($call_group_array[$tmp_call_group]) == 0) {
									$call_group_array[$tmp_call_group] = $row['extension'];
								}
								else {
									$call_group_array[$tmp_call_group] = $call_group_array[$tmp_call_group].','.$row['extension'];
								}
							}
							$i++;
						}
						$call_timeout = $row['call_timeout'];
						$user_context = $row['user_context'];
						$password = $row['password'];
						$voicemail_password = $row['voicemail_password'];
						//$voicemail_password = str_replace("#", "", $voicemail_password); //preserves leading zeros

						//echo "enabled: ".$row['enabled'];
						if ($row['enabled'] != "false") {
							$extension_uuid = $row['extension_uuid'];
							//remove invalid characters from the file names
							$extension = $row['extension'];
							$extension = str_replace(" ", "_", $extension);
							$extension = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $extension);
							$dial_string = $row['dial_string'];
							if (strlen($dial_string) == 0) {
								if (strlen($_SESSION['domain']['dial_string']['text']) > 0) {
									$dial_string = $_SESSION['domain']['dial_string']['text'];
								}
								else {
									$dial_string = "{sip_invite_domain=\${domain_name},leg_timeout=".$call_timeout.",presence_id=\${dialed_user}@\${dialed_domain}}\${sofia_contact(\${dialed_user}@\${dialed_domain})}";
								}
							}

							//set the password hashes
							$a1_hash = md5($extension.":".$domain_name.":".$password);
							$vm_a1_hash = md5($extension.":".$domain_name.":".$voicemail_password);

							$xml .= "<include>\n";
							$cidr = '';
							if (strlen($row['cidr']) > 0) {
								$cidr = " cidr=\"" . $row['cidr'] . "\"";
							}
							$number_alias = '';
							if (strlen($row['number_alias']) > 0) {
								$number_alias = " number-alias=\"".$row['number_alias']."\"";
							}
							$xml .= "  <user id=\"".$row['extension']."\"".$cidr."".$number_alias.">\n";
							$xml .= "    <params>\n";
							//$xml .= "      <param name=\"a1-hash\" value=\"" . $a1_hash . "\"/>\n";
							$xml .= "      <param name=\"password\" value=\"" . $row['password'] . "\"/>\n";
							$xml .= "      <param name=\"reverse-auth-user\" value=\"" . $row['extension'] . "\"/>\n";
							$xml .= "      <param name=\"reverse-auth-pass\" value=\"" . $row['password'] . "\"/>\n";

							//voicemail settings
							//$xml .= "      <param name=\"vm-a1-hash\" value=\"" . $vm_a1_hash. "\"/>\n";
							$xml .= "      <param name=\"vm-password\" value=\"" . $voicemail_password . "\"/>\n";
							switch ($row['voicemail_enabled']) {
							case "true":
								$xml .= "      <param name=\"vm-enabled\" value=\"true\"/>\n";
								break;
							case "false":
								$xml .= "      <param name=\"vm-enabled\" value=\"false\"/>\n";
								break;
							default:
								$xml .= "      <param name=\"vm-enabled\" value=\"true\"/>\n";
							}
							if (strlen($row['voicemail_mail_to']) > 0) {
								$xml .= "      <param name=\"vm-email-all-messages\" value=\"true\"/>\n";
								switch ($row['voicemail_file']) {
								case "attach":
										$xml .= "      <param name=\"vm-attach-file\" value=\"true\"/>\n";
										break;
								default:
										$xml .= "      <param name=\"vm-attach-file\" value=\"false\"/>\n";
								}
								switch ($row['voicemail_local_after_email']) {
								case "true":
										$xml .= "      <param name=\"vm-keep-local-after-email\" value=\"true\"/>\n";
										break;
								case "false":
										$xml .= "      <param name=\"vm-keep-local-after-email\" value=\"false\"/>\n";
										break;
								default:
										$xml .= "      <param name=\"vm-keep-local-after-email\" value=\"true\"/>\n";
								}
								$xml .= "      <param name=\"vm-mailto\" value=\"" . $row['voicemail_mail_to'] . "\"/>\n";
							}

							if (strlen($row['mwi_account']) > 0) {
								$xml .= "      <param name=\"MWI-Account\" value=\"" . $row['mwi_account'] . "\"/>\n";
							}
							if (strlen($row['auth_acl']) > 0) {
								$xml .= "      <param name=\"auth-acl\" value=\"" . $row['auth_acl'] . "\"/>\n";
							}
							if (strlen($row['directory_exten_visible']) > 0) {
								$xml .= "      <param name=\"directory-exten-visible\" value=\"" . $row['directory_exten_visible'] . "\"/>\n";
							}
							$xml .= "      <param name=\"dial-string\" value=\"" . $dial_string . "\"/>\n";
							$xml .= "    </params>\n";
							$xml .= "    <variables>\n";
							$xml .= "      <variable name=\"domain_name\" value=\"" . $_SESSION['domain_name'] . "\"/>\n";
							$xml .= "      <variable name=\"domain_uuid\" value=\"" . $_SESSION['domain_uuid'] . "\"/>\n";
							$xml .= "      <variable name=\"extension_uuid\" value=\"" . $extension_uuid . "\"/>\n";
							if (strlen($row['call_group']) > 0) {
								$xml .= "      <variable name=\"call_group\" value=\"" . $row['call_group'] . "\"/>\n";
							}
							if (strlen($row['user_record']) > 0) {
								$xml .= "      <variable name=\"user_record\" value=\"" . $row['user_record'] . "\"/>\n";
							}
							if (strlen($row['hold_music']) > 0) {
								$xml .= "      <variable name=\"hold_music\" value=\"" . $row['hold_music'] . "\"/>\n";
							}
							$xml .= "      <variable name=\"toll_allow\" value=\"" . $row['toll_allow'] . "\"/>\n";
							if (strlen($row['call_timeout']) > 0) {
								$xml .= "      <variable name=\"call_timeout\" value=\"" . $row['call_timeout'] . "\"/>\n";
							}
							if (strlen($switch_account_code) > 0) {
								$xml .= "      <variable name=\"accountcode\" value=\"" . $switch_account_code . "\"/>\n";
							}
							else {
								$xml .= "      <variable name=\"accountcode\" value=\"" . $row['accountcode'] . "\"/>\n";
							}
							$xml .= "      <variable name=\"user_context\" value=\"" . $row['user_context'] . "\"/>\n";
							if (strlen($row['effective_caller_id_name']) > 0) {
								$xml .= "      <variable name=\"effective_caller_id_name\" value=\"" . $row['effective_caller_id_name'] . "\"/>\n";
							}
							if (strlen($row['effective_caller_id_number']) > 0) {
								$xml .= "      <variable name=\"effective_caller_id_number\" value=\"" . $row['effective_caller_id_number'] . "\"/>\n";
							}
							if (strlen($row['outbound_caller_id_name']) > 0) {
								$xml .= "      <variable name=\"outbound_caller_id_name\" value=\"" . $row['outbound_caller_id_name'] . "\"/>\n";
							}
							if (strlen($row['outbound_caller_id_number']) > 0) {
								$xml .= "      <variable name=\"outbound_caller_id_number\" value=\"" . $row['outbound_caller_id_number'] . "\"/>\n";
							}
							if (strlen($row['emergency_caller_id_name']) > 0) {
								$xml .= "      <variable name=\"emergency_caller_id_name\" value=\"" . $row['emergency_caller_id_name'] . "\"/>\n";
							}
							if (strlen($row['emergency_caller_id_number']) > 0) {
								$xml .= "      <variable name=\"emergency_caller_id_number\" value=\"" . $row['emergency_caller_id_number'] . "\"/>\n";
							}
							if (strlen($row['directory_full_name']) > 0) {
								$xml .= "      <variable name=\"directory_full_name\" value=\"" . $row['directory_full_name'] . "\"/>\n";
							}
							if (strlen($row['directory_visible']) > 0) {
								$xml .= "      <variable name=\"directory-visible\" value=\"" . $row['directory_visible'] . "\"/>\n";
							}
							if (strlen($row['limit_max']) > 0) {
								$xml .= "      <variable name=\"limit_max\" value=\"" . $row['limit_max'] . "\"/>\n";
							}
							else {
								$xml .= "      <variable name=\"limit_max\" value=\"5\"/>\n";
							}
							if (strlen($row['limit_destination']) > 0) {
								$xml .= "      <variable name=\"limit_destination\" value=\"" . $row['limit_destination'] . "\"/>\n";
							}
							if (strlen($row['sip_force_contact']) > 0) {
								$xml .= "      <variable name=\"sip-force-contact\" value=\"" . $row['sip_force_contact'] . "\"/>\n";
							}
							if (strlen($row['sip_force_expires']) > 0) {
								$xml .= "      <variable name=\"sip-force-expires\" value=\"" . $row['sip_force_expires'] . "\"/>\n";
							}
							if (strlen($row['nibble_account']) > 0) {
								$xml .= "      <variable name=\"nibble_account\" value=\"" . $row['nibble_account'] . "\"/>\n";
							}
							switch ($row['sip_bypass_media']) {
								case "bypass-media":
										$xml .= "      <variable name=\"bypass_media\" value=\"true\"/>\n";
										break;
								case "bypass-media-after-bridge":
										$xml .= "      <variable name=\"bypass_media_after_bridge\" value=\"true\"/>\n";
										break;
								case "proxy-media":
										$xml .= "      <variable name=\"proxy_media\" value=\"true\"/>\n";
										break;
							}
							if (strlen($row['absolute_codec_string']) > 0) {
								$xml .= "      <variable name=\"absolute_codec_string\" value=\"" . $row['absolute_codec_string'] . "\"/>\n";
							}
							if (strlen($row['forward_all_enabled']) > 0) {
								$xml .= "      <variable name=\"forward_all_enabled\" value=\"" . $row['forward_all_enabled'] . "\"/>\n";
							}
							if (strlen($row['forward_all_destination']) > 0) {
								$xml .= "      <variable name=\"forward_all_destination\" value=\"" . $row['forward_all_destination'] . "\"/>\n";
							}
							if (strlen($row['forward_busy_enabled']) > 0) {
								$xml .= "      <variable name=\"forward_busy_enabled\" value=\"" . $row['forward_busy_enabled'] . "\"/>\n";
							}
							if (strlen($row['forward_busy_destination']) > 0) {
								$xml .= "      <variable name=\"forward_busy_destination\" value=\"" . $row['forward_busy_destination'] . "\"/>\n";
							}
							if (strlen($row['forward_no_answer_enabled']) > 0) {
								$xml .= "      <variable name=\"forward_no_answer_enabled\" value=\"" . $row['forward_no_answer_enabled'] . "\"/>\n";
							}
							if (strlen($row['forward_no_answer_destination']) > 0) {
								$xml .= "      <variable name=\"forward_no_answer_destination\" value=\"" . $row['forward_no_answer_destination'] . "\"/>\n";
							}
							if (strlen($row['forward_user_not_registered_enabled']) > 0) {
								$xml .= "      <variable name=\"forward_user_not_registered_enabled\" value=\"" . $row['forward_user_not_registered_enabled'] . "\"/>\n";
							}
							if (strlen($row['forward_user_not_registered_destination']) > 0) {
								$xml .= "      <variable name=\"forward_user_not_registered_destination\" value=\"" . $row['forward_user_not_registered_destination'] . "\"/>\n";
							}

							if (strlen($row['do_not_disturb']) > 0) {
								$xml .= "      <variable name=\"do_not_disturb\" value=\"" . $row['do_not_disturb'] . "\"/>\n";
							}
							$xml .= "    </variables>\n";
							$xml .= "  </user>\n";

							if (!is_readable($_SESSION['switch']['extensions']['dir']."/".$row['user_context'])) {
								event_socket_mkdir($_SESSION['switch']['extensions']['dir']."/".$row['user_context']);
							}
							if (strlen($extension) > 0) {
								$fout = fopen($_SESSION['switch']['extensions']['dir']."/".$row['user_context']."/v_".$extension.".xml","w");
							}
							$xml .= "</include>\n";
							fwrite($fout, $xml);
							unset($xml);
							fclose($fout);
						}
					}
					unset ($prep_statement);

				//prepare extension
					$extension_dir = realpath($_SESSION['switch']['extensions']['dir']);
					$user_context = str_replace(" ", "_", $user_context);
					$user_context = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $user_context);

				//define the group members
					$xml = "<!--\n";
					$xml .= "	NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE\n";
					$xml .= "\n";
					$xml .= "	FreeSWITCH works off the concept of users and domains just like email.\n";
					$xml .= "	You have users that are in domains for example 1000@domain.com.\n";
					$xml .= "\n";
					$xml .= "	When freeswitch gets a register packet it looks for the user in the directory\n";
					$xml .= "	based on the from or to domain in the packet depending on how your sofia profile\n";
					$xml .= "	is configured.  Out of the box the default domain will be the IP address of the\n";
					$xml .= "	machine running FreeSWITCH.  This IP can be found by typing \"sofia status\" at the\n";
					$xml .= "	CLI.  You will register your phones to the IP and not the hostname by default.\n";
					$xml .= "	If you wish to register using the domain please open vars.xml in the root conf\n";
					$xml .= "	directory and set the default domain to the hostname you desire.  Then you would\n";
					$xml .= "	use the domain name in the client instead of the IP address to register\n";
					$xml .= "	with FreeSWITCH.\n";
					$xml .= "\n";
					$xml .= "	NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE\n";
					$xml .= "-->\n";
					$xml .= "\n";
					$xml .= "<include>\n";
					$xml .= "	<!--the domain or ip (the right hand side of the @ in the addr-->\n";
					if ($user_context == "default") {
						$xml .= "	<domain name=\"\$\${domain}\">\n";
					}
					else {
						$xml .= "	<domain name=\"".$user_context."\">\n";
					}
					$xml .= "		<params>\n";
					//$xml .= "			<param name=\"dial-string\" value=\"{sip_invite_domain=\${domain_name},presence_id=\${dialed_user}@\${dialed_domain}}\${sofia_contact(\${dialed_user}@\${dialed_domain})}\"/>\n";
					$xml .= "		</params>\n";
					$xml .= "\n";
					$xml .= "		<variables>\n";
					$xml .= "			<variable name=\"record_stereo\" value=\"true\"/>\n";
					$xml .= "			<variable name=\"default_gateway\" value=\"\$\${default_provider}\"/>\n";
					$xml .= "			<variable name=\"default_areacode\" value=\"\$\${default_areacode}\"/>\n";
					$xml .= "			<variable name=\"transfer_fallback_extension\" value=\"operator\"/>\n";
					$xml .= "			<variable name=\"export_vars\" value=\"domain_name\"/>\n";
					$xml .= "		</variables>\n";
					$xml .= "\n";
					$xml .= "		<groups>\n";
					$xml .= "			<group name=\"".$user_context."\">\n";
					$xml .= "			<users>\n";
					$xml .= "				<X-PRE-PROCESS cmd=\"include\" data=\"".$user_context."/*.xml\"/>\n";
					$xml .= "			</users>\n";
					$xml .= "			</group>\n";
					$xml .= "\n";
					$previous_call_group = "";
					foreach ($call_group_array as $key => $value) {
						$call_group = trim($key);
						$extension_list = trim($value);
						if (strlen($call_group) > 0) {
							if ($previous_call_group != $call_group) {
								$xml .= "			<group name=\"$call_group\">\n";
								$xml .= "				<users>\n";
								$xml .= "					<!--\n";
								$xml .= "					type=\"pointer\" is a pointer so you can have the\n";
								$xml .= "					same user in multiple groups.  It basically means\n";
								$xml .= "					to keep searching for the user in the directory.\n";
								$xml .= "					-->\n";
								$extension_array = explode(",", $extension_list);
								foreach ($extension_array as &$tmp_extension) {
									$xml .= "					<user id=\"$tmp_extension\" type=\"pointer\"/>\n";
								}
								$xml .= "				</users>\n";
								$xml .= "			</group>\n";
								$xml .= "\n";
							}
							$previous_call_group = $call_group;
						}
						unset($call_group);
					}
					$xml .= "		</groups>\n";
					$xml .= "\n";
					$xml .= "	</domain>\n";
					$xml .= "</include>";

				//write the xml file
					if (is_readable($extension_dir) && strlen($extension_dir) > 0) {
						$fout = fopen($extension_dir."/".$user_context.".xml","w");
						fwrite($fout, $xml);
						unset($xml);
						fclose($fout);
					}

				//apply settings
					$_SESSION["reload_xml"] = true;
			// }
		}
	}
}

?>
