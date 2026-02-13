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

class registrations {

	/**
	 * declare constant variables
	 */
	const app_name = 'registrations';
	const app_uuid = '5d9e7cd7-629e-3553-4cf5-f26e39fefa39';

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 *
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Settings object set in the constructor. Must be a settings object and cannot be null.
	 *
	 * @var settings Settings Object
	 */
	private $settings;

	/**
	 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
	 * @var string
	 */
	private $domain_uuid;

	/**
	 * Domain name set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
	 * @var string
	 */
	private $domain_name;

	/**
	 * Set in the constructor. Must be an event_socket object and cannot be null.
	 *
	 * @var event_socket Event Socket Connection Object
	 */
	private $event_socket;

	/**
	 * declare private variables
	 */
	private $permission_prefix;
	private $list_page;
	public $show;

	/**
	 * Initializes the object with setting array.
	 *
	 * @param array $setting_array An array containing settings for domain, user, and database connections. Defaults to
	 *                             an empty array.
	 *
	 * @return void
	 */
	public function __construct(array $setting_array = []) {
		//set domain and user UUIDs
		$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
		$this->domain_name = $setting_array['domain_name'] ?? $_SESSION['domain_name'] ?? '';

		//set objects
		$this->database     = $setting_array['database'] ?? database::new();
		$this->event_socket = $setting_array['event_socket'] ?? event_socket::create();

		//trap passing an invalid connection object for communicating to the switch
		if (!($this->event_socket instanceof event_socket)) {
			//should never happen but will trap it here just in case
			throw new \InvalidArgumentException('Event socket object passed in the constructor is not a valid event_socket object');
		}

		//assign private variables
		$this->permission_prefix = 'registration_';
		$this->list_page         = 'registrations.php';
		$this->show              = 'local';
	}

	/**
	 * Retrieves the registration list for a given SIP profile.
	 *
	 * @param string|null $profile The name of the SIP profile to retrieve. Defaults to 'all'.
	 *
	 * @return array|null The registration list, or null if no profiles are found.
	 */
	public function get($profile = 'all') {

		//add multi-lingual support
		$language = new text;
		$text     = $language->get(null, '/app/registrations');

		//initialize the id used in the registrations array
		$id = 0;

		//create the event socket connection
		$event_socket = $this->event_socket;

		//make sure the event socket is connected
		if (!$event_socket->is_connected()) {
			//connect to event socket
			$event_socket->connect();

			//check again and throw an error if it can't connect
			if (!$event_socket->is_connected()) {
				message::add($text['error-event-socket'], 'negative', 5000);
				return null;
			}
		}

		//get the default settings
		$sql = "select sip_profile_name from v_sip_profiles ";
		$sql .= "where true ";
		if (!empty($profile) && $profile != 'all') {
			$sql                            .= "and sip_profile_name = :sip_profile_name ";
			$parameters['sip_profile_name'] = $profile;
		}
		$sql          .= "and sip_profile_enabled = true ";
		$sip_profiles = $this->database->select($sql, $parameters ?? null, 'all');

		if (!empty($sip_profiles)) {

			//use a while loop to ensure the event socket stays connected while communicating
			$count = count($sip_profiles);
			$i     = 0;
			while ($event_socket->is_connected() && $i < $count) {
				$field = $sip_profiles[$i++];

				//get sofia status profile information including registrations
				$cmd          = "api sofia xmlstatus profile '" . $field['sip_profile_name'] . "' reg";
				$xml_response = trim($event_socket->request($cmd));

				//show an error message
				if ($xml_response == "Invalid Profile!") {
					//show the error message
					$xml_response = "<error_msg>" . escape($text['label-message']) . "</error_msg>";
				}

				//sanitize the XML
				if (function_exists('iconv')) {
					$xml_response = iconv("utf-8", "utf-8//IGNORE", $xml_response);
				}
				$xml_response = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $xml_response);
				$xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
				$xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
				$xml_response = str_replace("&lt;", "", $xml_response);
				$xml_response = str_replace("&gt;", "", $xml_response);
				if (strlen($xml_response) > 101) {
					try {
						$xml = new SimpleXMLElement($xml_response);
					} catch (Exception $e) {
						echo basename(__FILE__) . "<br />\n";
						echo "line: " . __line__ . "<br />\n";
						echo "error: " . $e->getMessage() . "<br />\n";
						//echo $xml_response;
						exit;
					}
					$array = json_decode(json_encode($xml), true);
				}

				//normalize the array
				if (!empty($array) && is_array($array) && (!isset($array['registrations']['registration'][0]) || !is_array($array['registrations']['registration'][0]))) {
					$row = $array['registrations']['registration'];
					unset($array['registrations']['registration']);
					$array['registrations']['registration'][0] = $row;
				}

				//set the registrations array
				if (!empty($array) && is_array($array)) {
					foreach ($array['registrations']['registration'] as $row) {

						//build the registrations array
						//$registrations[0] = $row;
						$user_array                             = explode('@', $row['user'] ?? '');
						$registrations[$id]['user']             = $row['user'] ?? '';
						$registrations[$id]['call-id']          = $row['call-id'] ?? '';
						$registrations[$id]['contact']          = $row['contact'] ?? '';
						$registrations[$id]['sip-auth-user']    = $row['sip-auth-user'] ?? '';
						$registrations[$id]['agent']            = $row['agent'] ?? '';
						$registrations[$id]['host']             = $row['host'] ?? '';
						$registrations[$id]['network-ip']       = $row['network-ip'] ?? '';
						$registrations[$id]['network-port']     = $row['network-port'] ?? '';
						$registrations[$id]['sip-auth-user']    = $row['sip-auth-user'] ?? '';
						$registrations[$id]['sip-auth-realm']   = $row['sip-auth-realm'] ?? '';
						$registrations[$id]['mwi-account']      = $row['mwi-account'] ?? '';
						$registrations[$id]['status']           = $row['status'] ?? '';
						$registrations[$id]['ping-time']        = $row['ping-time'] ?? '';
						$registrations[$id]['ping-status']      = $row['ping-status'] ?? '';
						$registrations[$id]['sip_profile_name'] = $field['sip_profile_name'];

						//get network-ip to url or blank
						if (isset($row['network-ip'])) {
							$registrations[$id]['network-ip'] = $row['network-ip'];
						} else {
							$registrations[$id]['network-ip'] = '';
						}

						//get the LAN IP address if it exists replace the external ip
						$call_id_array = explode('@', $row['call-id'] ?? '');
						if (isset($call_id_array[1])) {
							$agent  = $row['agent'];
							$lan_ip = $call_id_array[1];
							if (!empty($agent) && (false !== stripos($agent, 'grandstream') || false !== stripos($agent, 'ooma'))) {
								$lan_ip = str_ireplace(
									['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
									['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
									$lan_ip);
							} elseif (!empty($agent) && 1 === preg_match('/\ACL750A/', $agent)) {
								//required for GIGASET Sculpture CL750A puts _ in it's lan ip account
								$lan_ip = preg_replace('/_/', '.', $lan_ip);
							}
							$registrations[$id]['lan-ip'] = $lan_ip;
						} elseif (preg_match('/real=\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $row['contact'] ?? '', $ip_match)) {
							//get ip address for snom phones
							$lan_ip                       = str_replace('real=', '', $ip_match[0]);
							$registrations[$id]['lan-ip'] = $lan_ip;
						} elseif (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $row['contact'] ?? '', $ip_match)) {
							$lan_ip                       = preg_replace('/_/', '.', $ip_match[0]);
							$registrations[$id]['lan-ip'] = $lan_ip;
						} else {
							$registrations[$id]['lan-ip'] = '';
						}

						//remove unrelated domains
						if (!permission_exists('registration_all') || $this->show != 'all') {
							if ($registrations[$id]['sip-auth-realm'] == $this->domain_name) {
							} elseif ($user_array[1] == $this->domain_name) {
							} else {
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
		return $registrations ?? null;
	}

	/**
	 * Retrieves the registration count for a given SIP profile.
	 *
	 * @param string|null $profile The name of the SIP profile to retrieve. Defaults to 'all'.
	 *
	 * @return int The registration count, or 0 if no profiles are found.
	 */
	public function count($profile = 'all') {

		//use get the registrations to count
		$registrations = $this->get($profile);

		//set the count
		$count = !empty($registrations) ? @sizeof($registrations) : 0;

		//return the registrations count
		return $count;

	}

	/**
	 * Unregisters a list of registrations from a given SIP profile or all profiles if no profile is specified.
	 *
	 * @param array $registrations The list of registrations to unregister, keyed by SIP URI.
	 *
	 * @return void This method does not return any value.
	 */
	public function unregister($registrations) {
		$this->switch_api('unregister', $registrations);
	}

	/**
	 * Provision a set of SIP registrations.
	 *
	 * @param array $registrations The list of registrations to provision.
	 *
	 * @returnvoid This method does not return any value.
	 */
	public function provision($registrations) {
		$this->switch_api('provision', $registrations);
	}

	/**
	 * Initiates a system reboot with the specified registrations.
	 *
	 * @param array $registrations The list of registrations to persist before rebooting.
	 *
	 * @return void This method does not return any value.
	 */
	public function reboot($registrations) {
		$this->switch_api('reboot', $registrations);
	}

	/**
	 * Processes API commands for a list of registered devices.
	 *
	 * This will cause execution to exit.
	 *
	 * @param string $action  The action to perform (unregister, provision, reboot).
	 * @param array  $records The list of registered devices.
	 *
	 * @return void
	 */
	private function switch_api($action, $records) {
		if (permission_exists($this->permission_prefix . 'domain') || permission_exists($this->permission_prefix . 'all') || if_group('superadmin')) {

			//add multi-lingual support
			$language = new text;
			$text     = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->list_page);
				exit;
			}

			//filter out unchecked registrations
			if (is_array($records) && @sizeof($records) != 0) {
				foreach ($records as $record) {
					if ($record['checked'] == 'true' && !empty($record['user']) && !empty($record['profile'])) {
						$registrations[] = $record;
					}
				}
			}

			//process checked registrations
			if (is_array($registrations) && @sizeof($registrations) != 0) {

				//retrieve sip profiles list
				$sql          = "select sip_profile_name as name from v_sip_profiles ";
				$sip_profiles = $this->database->select($sql, null, 'all');
				unset($sql);

				//create the event socket connection
				$event_socket = $this->event_socket;

				//loop through registrations
				if ($event_socket->is_connected()) {
					//check if registrations exist
					if (is_array($registrations)) {
						foreach ($registrations as $registration) {

							//validate the submitted profile
							if (!empty($registration['profile']) && is_array($sip_profiles) && @sizeof($sip_profiles) != 0) {
								foreach ($sip_profiles as $field) {
									if ($field['name'] == $registration['profile']) {
										$profile = $registration['profile'];
										break;
									}
								}
							} else {
								header('Location: ' . $this->list_page);
								exit;
							}

							//validate the submitted user
							if (!empty($registration['user'])) {
								$user = preg_replace('#[^a-zA-Z0-9_\-\.\@]#', '', $registration['user']);
							}

							//validate the submitted host
							if (!empty($registration['host'])) {
								$host = preg_replace('#[^a-zA-Z0-9_\-\.]#', '', $registration['host']);
							}

							//lookup vendor by agent
							if (!empty($registration['agent'])) {
								$vendor = device::get_vendor_by_agent($registration['agent']);
							}

							//prepare the api command
							if (!empty($profile) && $user) {
								switch ($action) {
									case 'unregister':
										$command          = "sofia profile " . $profile . " flush_inbound_reg " . $user;
										$response_message = $text['message-registrations_unregistered'];
										break;
									case 'provision':
										if ($vendor && $host) {
											$command          = "lua app.lua event_notify " . $profile . " check_sync " . $user . " " . $vendor . " " . $host;
											$response_message = $text['message-registrations_provisioned'];
										}
										break;
									case 'reboot':
										if ($vendor && $host) {
											$command          = "lua app.lua event_notify " . $profile . " reboot " . $user . " " . $vendor . " " . $host;
											$response_message = $text['message-registrations_rebooted'];
										}
										break;
									default:
										header('Location: ' . $this->list_page);
										exit;
								}
							}

							//send the api command
							if (!empty($command) && $event_socket->is_connected()) {
								$response                       = $event_socket->request('api ' . $command);
								$response_api[$user]['command'] = $command;
								$response_api[$user]['log']     = $response;
							}
						}
					}

					//set message
					if (is_array($response_api)) {
						$message = '';
						foreach ($response_api as $registration_user => $response) {
							if (is_array($response['command'])) {
								foreach ($response['command'] as $command) {
									$command = trim($command ?? '');
									if ($command !== '-ERR no reply') {
										$message .= "<br>\n<strong>" . escape($registration_user) . "</strong>: " . escape($response_message);
									}
								}
							} else {
								if (!empty($response['command']) && $response['command'] !== '-ERR no reply') {
									$message .= "<br>\n<strong>" . escape($registration_user) . "</strong>: " . escape($response_message);
								}
							}
						}
						message::add($message, 'positive', '7000');
					}
				} else {
					message::add($text['error-event-socket'], 'negative', 5000);
				}

			}

		}
	} //method

} //class
