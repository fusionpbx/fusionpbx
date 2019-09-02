<?php

/**
 * registrations class
 *
 * @method array get
 */
if (!class_exists('registrations')) {
	class registrations {

		/**
		 * Called when the object is created
		 */
		public function __construct() {

		}

		/**
		 * Called when there are no references to a particular object
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
		public function get($profile) {

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
				$database = new database;
				$sip_profiles = $database->select($sql, $parameters, 'all');
				if (is_array($sip_profiles) && @sizeof($sip_profiles) != 0) {
					foreach ($sip_profiles as $field) {

						//get sofia status profile information including registrations
							$cmd = "api sofia xmlstatus profile ".$field['sip_profile_name']." reg";
							$xml_response = trim(event_socket_request($fp, $cmd));
							if ($xml_response == "Invalid Profile!") { $xml_response = "<error_msg>".$text['label-message']."</error_msg>"; }
							$xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
							$xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
							if (strlen($xml_response) > 101) {
								try {
									$xml = new SimpleXMLElement($xml_response);
								}
								catch(Exception $e) {
									echo $e->getMessage();
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
										$registrations[$id]['network-port'] = $row['network-port'] ?: '';
										$registrations[$id]['sip-auth-realm'] = $row['sip-auth-realm'] ?: '';
										$registrations[$id]['mwi-account'] = $row['mwi-account'] ?: '';
										$registrations[$id]['status'] = $row['status'] ?: '';
										$registrations[$id]['ping-time'] = $row['ping-time'] ?: '';
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
																elseif(1 === preg_match('/\ACL750A/', $agent)) {
												//required for GIGASET Sculpture CL750A puts _ in it's lan ip account
												$lan_ip = preg_replace('/_/', '.', $lan_ip);
																}
											$registrations[$id]['lan-ip'] = $lan_ip;
										}
										else {
											$registrations[$id]['lan-ip'] = '';
										}

									//remove unrelated domains
										if (count($_SESSION["domains"]) > 1) {
											if (!(permission_exists('registration_all') && $profile == "all")) {
												if ($registrations[$id]['sip-auth-realm'] == $_SESSION['domain_name']) {}
												else if ($user_array[1] == $_SESSION['domain_name']) {}
												else {
													unset($registrations[$id]);
												}
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

			//set the initial count value to 0
				$count = 0;

			//create the event socket connection
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

			//get the default settings
				$sql = "select sip_profile_name from v_sip_profiles ";
				$sql .= "where sip_profile_enabled = 'true' ";
				if ($profile != 'all' && $profile != '') {
					$sql .= "and sip_profile_name = :sip_profile_name ";
					$parameters['sip_profile_name'] = $profile;
				}
				$database = new database;
				$sip_profiles = $database->select($sql, $parameters, 'all');
				if (is_array($sip_profiles) && @sizeof($sip_profiles) != 0) {
					foreach ($sip_profiles as $field) {

					//get sofia status profile information including registrations
						$cmd = "api sofia xmlstatus profile ".$field['sip_profile_name']." reg";
						$xml_response = trim(event_socket_request($fp, $cmd));

						if ($xml_response == "Invalid Profile!") { $xml_response = "<error_msg>".$text['label-message']."</error_msg>"; }
						$xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
						$xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
						if (strlen($xml_response) > 101) {
							try {
								$xml = new SimpleXMLElement($xml_response);
							}
							catch(Exception $e) {
								echo $e->getMessage();
								exit;
							}
							$array = json_decode(json_encode($xml), true);
							$count = $count + count($array['registrations']['registration']);
						}

					}
				}

			//return the registrations count
				return $count;
		}

	}
}

/*
$obj = new registrations;
$registrations = $obj->get('all');
print($registrations);
*/

?>