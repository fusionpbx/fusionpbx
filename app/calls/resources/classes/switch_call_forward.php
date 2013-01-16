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
	Copyright (C) 2010
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
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
		private $dial_string_update = false;

		public function set() {
			//set the global variable
				global $db;

			//set the dial string
				if ($this->forward_all_enabled == "true") {
					$this->dial_string = "loopback/".$this->forward_all_destination;
				}
				else {
					$this->dial_string = '';
				}

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
						if ($this->forward_all_enabled == "false" && $row["forward_all_enabled"] == "true") {
							$this->dial_string_update = true;
						}
					}
				}
				unset ($prep_statement);
				if ($this->forward_all_enabled == "true") {
					$this->dial_string_update = true;
				}

			//update the extension
				$sql = "update v_extensions set ";
				$sql .= "forward_all_destination = '$this->forward_all_destination', ";
				if ($this->dial_string_update) {
					$sql .= "dial_string = '".$this->dial_string."', ";
				}
				$sql .= "forward_all_enabled = '$this->forward_all_enabled' ";
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

			//syncrhonize configuration
				if ($this->dial_string_update) {
					save_extension_xml();
				}
		} //function
	} //class

?>