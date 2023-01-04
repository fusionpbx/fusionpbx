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
 Portions created by the Initial Developer are Copyright (C) 2008-2023
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the registrations class
if (!class_exists('registrations')) {
	class registrations {

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		public $show;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'registrations';
				$this->app_uuid = '5d9e7cd7-629e-3553-4cf5-f26e39fefa39';
				$this->permission_prefix = 'registration_';
				$this->list_page = 'registrations.php';
				$this->show = 'local';

		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * get the registrations
		 */
		public function get($profile = 'all') {

			//initialize the id used in the registrations array
				$id = 0;

			//create the event socket connection
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

			//get the default settings
				$sql = "select sip_profile_name from v_sip_profiles ";
				$sql .= "where sip_profile_enabled = 'true' ";
				if ($profile != 'all' && $profile != '') {
					$sql .= "and sip_profile_name = :sip_profile_name ";
					$parameters['sip_profile_name'] = $profile;
				}
				$sql .= "and sip_profile_enabled = 'true' ";
				$database = new database;
				$sip_profiles = $database->select($sql, $parameters, 'all');
				if (is_array($sip_profiles) && @sizeof($sip_profiles) != 0) {
					foreach ($sip_profiles as $field) {

						//get sofia status profile information including registrations
							$cmd = "api sofia xmlstatus profile '".$field['sip_profile_name']."' reg";
							$xml_response = trim(event_socket_request($fp, $cmd));
							if (function_exists('iconv')) { $xml_response = iconv("utf-8", "utf-8//IGNORE", $xml_response); }
							$xml_response = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $xml_response);
							if ($xml_response == "Invalid Profile!") { $xml_response = "<error_msg>".$text['label-message']."</error_msg>"; }
							$xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
							$xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
							$xml_response = str_replace("&lt;", "", $xml_response);
							$xml_response = str_replace("&gt;", "", $xml_response);
							if (strlen($xml_response) > 101) {
								try {
									$xml = new SimpleXMLElement($xml_response);
								}
								catch(Exception $e) {
									echo basename(__FILE__)."<br />\n";
									echo "line: ".__line__."<br />\n";
									echo "error: ".$e->getMessage()."<br />\n";
									//echo $xml_response;
									exit;
								}
								$array = json_decode(json_encode($xml), true);
							}

						//normalize the array
							if (is_array($array) && !is_array($array['registrations']['registration'][0])) {
								$row = $array['registrations']['registration'];
								unset($array['registrations']['registration']);
								$array['registrations']['registration'][0] = $row;
							}

						//set the registrations array
							if (is_array($array)) {
								foreach ($array['registrations']['registration'] as $row) {

									//build the registrations array
										//$registrations[0] = $row;
										$user_array = explode('@', $row['user']);
										$registrations[$id]['user'] = $row['user'] ?: '';
										$registrations[$id]['call-id'] = $row['call-id'] ?: '';
										$registrations[$id]['contact'] = $row['contact'] ?: '';
										$registrations[$id]['sip-auth-user'] = $row['sip-auth-user'] ?: '';
										$registrations[$id]['agent'] = $row['agent'] ?: '';
										$registrations[$id]['host'] = $row['host'] ?: '';
										$registrations[$id]['network-ip'] = $row['network-ip'] ?: '';
										$registrations[$id]['network-port'] = $row['network-port'] ?: '';
										$registrations[$id]['sip-auth-user'] = $row['sip-auth-user'] ?: '';
										$registrations[$id]['sip-auth-realm'] = $row['sip-auth-realm'] ?: '';
										$registrations[$id]['mwi-account'] = $row['mwi-account'] ?: '';
										$registrations[$id]['status'] = $row['status'] ?: '';
										$registrations[$id]['ping-time'] = $row['ping-time'] ?: '';
										$registrations[$id]['ping-status'] = $row['ping-status'] ?: '';
										$registrations[$id]['sip_profile_name'] = $field['sip_profile_name'];

									//get network-ip to url or blank
										if (isset($row['network-ip'])) {
											$registrations[$id]['network-ip'] = $row['network-ip'];
										}
										else {
											$registrations[$id]['network-ip'] = '';
										}

									//get the LAN IP address if it exists replace the external ip
										$call_id_array = explode('@', $row['call-id']);
										if (isset($call_id_array[1])) {
											$agent = $row['agent'];
											$lan_ip = $call_id_array[1];
											if (false !== stripos($agent, 'grandstream')) {
												$lan_ip = str_ireplace(
													array('A','B','C','D','E','F','G','H','I','J'),
													array('0','1','2','3','4','5','6','7','8','9'),
													$lan_ip);
											}
											elseif (1 === preg_match('/\ACL750A/', $agent)) {
												//required for GIGASET Sculpture CL750A puts _ in it's lan ip account
												$lan_ip = preg_replace('/_/', '.', $lan_ip);
											}
											$registrations[$id]['lan-ip'] = $lan_ip;
										}
										else if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $row['contact'], $ip_match)) {
											$lan_ip = preg_replace('/_/', '.', $ip_match[0]);
											$registrations[$id]['lan-ip'] = "$lan_ip";
										}
										else {
											$registrations[$id]['lan-ip'] = '';
										}

									//remove unrelated domains
										if (!permission_exists('registration_all') || $this->show != 'all') {
											if ($registrations[$id]['sip-auth-realm'] == $_SESSION['domain_name']) {}
											else if ($user_array[1] == $_SESSION['domain_name']) {}
											else {
												unset($registrations[$id]);
											}
										}

									//increment the array id
										$id++;
								}
								unset($array);
							}

					}
				}

			//return the registrations array
				return $registrations;
		}

		/**
		 * get the registration count
		 */
		public function count($profile = 'all') {

			//use get the registrations to count
				$registrations = $this->get($profile);

			//set the count
				$count = @sizeof($registrations);

			//return the registrations count
				return $count;

		}

		/**
		 * unregister registrations
		 */
		public function unregister($registrations) {
			$this->switch_api('unregister', $registrations);
		}

		/**
		 * provision registrations
		 */
		public function provision($registrations) {
			$this->switch_api('provision', $registrations);
		}

		/**
		 * reboot registrations
		 */
		public function reboot($registrations) {
			$this->switch_api('reboot', $registrations);
		}

		/**
		 * switch api calls
		 */
		private function switch_api($action, $records) {
			if (permission_exists($this->permission_prefix.'domain') || permission_exists($this->permission_prefix.'all') || if_group('superadmin')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//filter out unchecked registrations
					if (is_array($records) && @sizeof($records) != 0) {
						foreach($records as $record) {
							if (
								$record['checked'] == 'true' &&
								$record['user'] != '' &&
								$record['profile'] != ''
								) {
								$registrations[] = $record;
							}
						}
					}

				//process checked registrations
					if (is_array($registrations) && @sizeof($registrations) != 0) {

						//retrieve sip profiles list
							$sql = "select sip_profile_name as name from v_sip_profiles ";
							$database = new database;
							$sip_profiles = $database->select($sql, null, 'all');
							unset($sql);

						//create the event socket connection
							$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

						//loop through registrations
							if ($fp) {
								//check if registrations exist
								if (is_array($registrations)) {
									foreach ($registrations as $registration) {

										//validate the submitted profile
											if ($registration['profile'] != '' && is_array($sip_profiles) && @sizeof($sip_profiles) != 0) {
												foreach ($sip_profiles as $field) {
													if ($field['name'] == $registration['profile']) {
														$profile = $registration['profile'];
														break;
													}
												}
											}
											else {
												header('Location: '.$this->list_page);
												exit;
											}

										//validate the submitted user
											if ($registration['user'] != '') {
												$user = preg_replace('#[^a-zA-Z0-9_\-\.\@]#', '', $registration['user']);
											}

										//validate the submitted host
											if ($registration['host'] != '') {
												$host = preg_replace('#[^a-zA-Z0-9_\-\.]#', '', $registration['host']);
											}

										//lookup vendor by agent
											if ($registration['agent'] != '') {
												$vendor = device::get_vendor_by_agent($registration['agent']);
											}

										//prepare the api command
											if ($profile && $user) {
												switch ($action) {
													case 'unregister':
														$command = "sofia profile ".$profile." flush_inbound_reg ".$user." reboot";
														$response_message = $text['message-registrations_unregistered'];
														break;
													case 'provision':
														if ($vendor && $host) {
															$command = "lua app.lua event_notify ".$profile." check_sync ".$user." ".$vendor." ".$host;
															$response_message = $text['message-registrations_provisioned'];
														}
														break;
													case 'reboot':
														if ($vendor && $host) {
															$command = "lua app.lua event_notify ".$profile." reboot ".$user." ".$vendor." ".$host;
															$response_message = $text['message-registrations_rebooted'];
														}
														break;
													default:
														header('Location: '.$this->list_page);
														exit;
												}
											}

										//send the api command
											if ($command && $fp) {
												$response_api[$registration['user']]['command'] = event_socket_request($fp, "api ".$command);
												$response_api[$registration['user']]['log'] = event_socket_request($fp, "api log notice ".$command);
											}

									}
								}

								//set message
									if (is_array($response_api)) {
										$message = $response_message;
										foreach ($response_api as $registration_user => $response) {
											if (trim($response['command']) != '-ERR no reply') {
												$message .= "<br>\n<strong>".$registration_user."</strong>: ".$response['command'];
											}
										}
										message::add($message, 'positive', '7000');
									}

							}
							else {
								message::add($text['error-event-socket'], 'negative', 5000);
							}

					}

			}
		} //method

	} //class
}

?>
