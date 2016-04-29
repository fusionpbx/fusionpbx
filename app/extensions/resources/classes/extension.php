<?php
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
	Copyright (C) 2010 - 2014
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the directory class
if (!class_exists('extension')) {
	class extension {
		public $db;
		public $domain_uuid;
		public $domain_name;
		private $app_uuid;
		public $extension_uuid;
		public $extension;
		public $voicemail_id;
		public $number_alias;
		public $password;
		public $provisioning_list;
		public $voicemail_password;
		public $accountcode;
		public $effective_caller_id_name;
		public $effective_caller_id_number;
		public $outbound_caller_id_name;
		public $outbound_caller_id_number;
		public $emergency_caller_id_name;
		public $emergency_caller_id_number;
		public $directory_full_name;
		public $directory_visible;
		public $directory_exten_visible;
		public $limit_max;
		public $limit_destination;
		public $voicemail_enabled;
		public $voicemail_mail_to;
		public $voicemail_file;
		public $voicemail_local_after_email;
		public $user_context;
		public $toll_allow;
		public $call_timeout;
		public $call_group;
		public $hold_music;
		public $auth_acl;
		public $cidr;
		public $sip_force_contact;
		public $sip_force_expires;
		public $nibble_account;
		public $mwi_account;
		public $sip_bypass_media;
		public $absolute_codec_string;
		public $dial_string;
		public $enabled;
		public $description;

		public function __construct() {
			require_once "resources/classes/database.php";
			$this->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function get_domain_uuid() {
			return $this->domain_uuid;
		}

		public function set_domain_uuid($domain_uuid){
			$this->domain_uuid = $domain_uuid;
		}

		public function voicemail() {

			//determine the voicemail_id
				if (is_numeric($this->number_alias)) {
					$this->voicemail_id = $this->number_alias;
				}
				else {
					$this->voicemail_id = $this->extension;
				}

			//update the voicemail settings
				$sql = "select * from v_voicemails ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and voicemail_id = '".$this->voicemail_id."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) == 0) {
					//add the voicemail box
						$sql = "insert into v_voicemails ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "voicemail_uuid, ";
						$sql .= "voicemail_id, ";
						$sql .= "voicemail_password, ";
						if (strlen($this->greeting_id) > 0) {
							$sql .= "greeting_id, ";
						}
						$sql .= "voicemail_mail_to, ";
						$sql .= "voicemail_file, ";
						$sql .= "voicemail_local_after_email, ";
						$sql .= "voicemail_enabled, ";
						$sql .= "voicemail_description ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".$this->domain_uuid."', ";
						$sql .= "'".uuid()."', ";
						$sql .= "'".$this->voicemail_id."', ";
						$sql .= "'".$this->voicemail_password."', ";
						$sql .= "'".$this->voicemail_mail_to."', ";
						$sql .= "'".$this->voicemail_file."', ";
						$sql .= "'".$this->voicemail_local_after_email."', ";
						$sql .= "'".$this->voicemail_enabled."', ";
						$sql .= "'".$this->description."' ";
						$sql .= ")";
						$this->db->exec(check_sql($sql));
						unset($sql);
				}
				else {
					//update the voicemail box
						$sql = "update v_voicemails set ";
						$sql .= "voicemail_password = '".$this->voicemail_password."', ";
						$sql .= "voicemail_mail_to = '".$this->voicemail_mail_to."', ";
						$sql .= "voicemail_file = '".$this->voicemail_file."', ";
						$sql .= "voicemail_local_after_email = '".$this->voicemail_local_after_email."', ";
						$sql .= "voicemail_enabled = '".$this->voicemail_enabled."', ";
						$sql .= "voicemail_description = '".$this->description."' ";
						$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
						$sql .= "and voicemail_id = '".$this->voicemail_id."' ";
						$this->db->exec(check_sql($sql));
						unset($sql);
				}
				unset ($prep_statement);
		}

		public function xml() {
			if (isset($_SESSION['switch']['extensions']['dir'])) {
				//declare global variables
					global $config, $db, $domain_uuid;

				//get the domain_name
					$domain_name = $_SESSION['domains'][$domain_uuid]['domain_name'];
					$user_context = $domain_name;

				//delete all old extensions to prepare for new ones
					$dialplan_list = glob($_SESSION['switch']['extensions']['dir']."/".$user_context."/v_*.xml");
					foreach($dialplan_list as $name => $value) {
						unlink($value);
					}

				//write the xml files
					$sql = "SELECT * FROM v_extensions AS e, v_voicemails AS v ";
					$sql .= "WHERE e.domain_uuid = '$domain_uuid' ";
					$sql .= "AND (e.extension = v.voicemail_id or e.number_alias = v.voicemail_id) ";
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
								mkdir($_SESSION['switch']['extensions']['dir']."/".$row['user_context'],0755,true);
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
			}
		}
	}
}

?>