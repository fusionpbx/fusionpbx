<?php

function get_registrations($sip_profile_name) {
	//get the global variables
		global $_SESSION, $fp, $show;

	//get sofia status profile information including registrations
		$cmd = "api sofia xmlstatus profile ".$sip_profile_name." reg";
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
			$array = json_decode(json_encode($xml) , true);
		}

	//normalize the array
		if (is_array($array) && !is_array($array['registrations']['registration'][0])) {
			$row = $array['registrations']['registration'];
			unset($array['registrations']['registration']);
			$array['registrations']['registration'][0] = $row;
		}

	//set the registrations array
		if (is_array($array)) {
			$x=0;
			foreach ($array['registrations']['registration'] as $row) {

				//build the registrations array
					//$registrations[0] = $row;
					$user_array = explode('@', $row['user']);				
					$registrations[$x]['user'] = $row['user'] ?: "&nbsp;";
					$registrations[$x]['call-id'] = $row['call-id'] ?: "&nbsp;";
					$registrations[$x]['contact'] = $row['contact'] ?: "&nbsp;";
					$registrations[$x]['sip-auth-user'] = $row['sip-auth-user'] ?: "&nbsp;";
					$registrations[$x]['agent'] = $row['agent'] ?: "&nbsp;";
					$registrations[$x]['host'] = $row['host'] ?: "&nbsp;";
					$registrations[$x]['network-port'] = $row['network-port'] ?: "&nbsp;";
					$registrations[$x]['sip-auth-realm'] = $row['sip-auth-realm'] ?: "&nbsp;";
					$registrations[$x]['mwi-account'] = $row['mwi-account'] ?: "&nbsp;";
					$registrations[$x]['status'] = $row['status'] ?: "&nbsp;";
					$registrations[$x]['ping-time'] = $row['ping-time'] ?: "&nbsp;";

				//get network-ip to url or blank
					if(isset($row['network-ip'])) {
						$registrations[$x]['network-ip'] = "<a href='http://".$row['network-ip']."' target='_blank'>".$row['network-ip']."</a>";
					} else {
						$registrations[$x]['network-ip'] = "&nbsp;";
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
						$registrations[$x]['lan-ip'] = "<a href='http://".$lan_ip."' target='_blank'>".$lan_ip."</a>";
					} else{
						$registrations[$x]['lan-ip'] = "&nbsp;";
					}

				//remove unrelated domains
					if (count($_SESSION["domains"]) > 1) {
						if (!(permission_exists('registration_all') && $show == "all")) {
							if ($registrations[$x]['sip-auth-realm'] == $_SESSION['domain_name']) {}
							elseif ($user_array[1] == $_SESSION['domain_name']){}
							else {
								unset($registrations[$x]);
							}
						}
					}

				//increment the array id
					$x++;
			}
		}

	//return the registrations array
		return $registrations;
}

?>
