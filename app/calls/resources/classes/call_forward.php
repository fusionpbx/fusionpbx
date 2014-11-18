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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
include "root.php";

//define the call_forward class
	class call_forward {
		public $debug;
		public $domain_uuid;
		public $domain_name;
		public $extension_uuid;
		private $extension;
		public $forward_all_destination;
		public $forward_all_enabled;
		private $dial_string;
		public $accountcode;

		public function set() {
			//set the global variable
				global $db;

			//determine whether to update the dial string
				$sql = "select * from v_extensions ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and extension_uuid = '".$this->extension_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
						$this->extension = $row["extension"];
						$this->accountcode = $row["accountcode"];
					}
				}
				unset ($prep_statement);

			//set the dial string
				if ($this->forward_all_enabled == "true") {
					$dial_string = "{presence_id=".$this->forward_all_destination."@".$_SESSION['domain_name'];
					$dial_string .= ",instant_ringback=true";
					$dial_string .= ",domain_uuid=".$_SESSION['domain_uuid'];
					$dial_string .= ",sip_invite_domain=".$_SESSION['domain_name'];
					$dial_string .= ",domain_name=".$_SESSION['domain_name'];
					$dial_string .= ",domain=".$_SESSION['domain_name'];
					$dial_string .= ",extension_uuid=".$this->extension_uuid;
					if (strlen($this->accountcode) > 0) {
						$dial_string .= ",accountcode=".$this->accountcode;
					}
					$dial_string .= "}";
					if (extension_exists($this->forward_all_destination)) {
						$dial_string .= "user/".$this->forward_all_destination."@".$_SESSION['domain_name'];
					}
					else {
						$bridge = outbound_route_to_bridge ($_SESSION['domain_uuid'], $this->forward_all_destination);
						//if (strlen($bridge[0]) > 0) {
						//	$dial_string .= $bridge[0];
						//}
						//else {
							$dial_string .= "loopback/".$this->forward_all_destination;
						//}
					}
					$this->dial_string = $dial_string;
				}
				else {
					$this->dial_string = '';
				}

			//update the extension
				$sql = "update v_extensions set ";
				if (strlen($this->forward_all_destination) == 0 || $this->forward_all_enabled == "false") {
					if (strlen($this->forward_all_destination) == 0) {
						$sql .= "forward_all_destination = null, ";
					}
					$sql .= "dial_string = null, ";
					$sql .= "forward_all_enabled = 'false' ";
				}
				else {
					$sql .= "forward_all_destination = '$this->forward_all_destination', ";
					$sql .= "dial_string = '".$this->dial_string."', ";
					$sql .= "forward_all_enabled = 'true' ";
				}
				$sql .= "where domain_uuid = '$this->domain_uuid' ";
				$sql .= "and extension_uuid = '$this->extension_uuid' ";
				if ($this->debug) {
					echo $sql;
				}
				$db->exec(check_sql($sql));
				unset($sql);

			//delete extension from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd = "memcache delete directory:".$this->extension."@".$this->domain_name;
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

		} //function
	} //class

?>
