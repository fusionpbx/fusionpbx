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

//define the dnd class
	class do_not_disturb {
		public $debug;
		public $domain_uuid;
		public $domain_name;
		public $extension_uuid;
		public $extension;
		public $enabled;
		private $dial_string;

		//update the user_status
		public function user_status() {
			//set the global variable
				global $db;

			//update the status
				if ($this->enabled == "true") {
					//update the call center status
						$user_status = "Logged Out";
						$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
						if ($fp) {
							$switch_cmd .= "callcenter_config agent set status ".$_SESSION['username']."@".$this->domain_name." '".$user_status."'";
							$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
						}

					//update the database user_status
						$user_status = "Do Not Disturb";
						$sql  = "update v_users set ";
						$sql .= "user_status = '$user_status' ";
						$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
						$sql .= "and username = '".$_SESSION['username']."' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
				}
		}

		public function set() {
			//set the global variable
				global $db;

			//determine whether to update the dial string
				$sql = "select * from v_extensions ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				if (strlen($this->extension_uuid) > 0) {
					$sql .= "and extension_uuid = '".$this->extension_uuid."' ";
				}
				else {
					$sql .= "and extension = '".$this->extension."' ";
				}
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
						if (strlen($this->extension_uuid) == 0) {
							$this->extension_uuid = $row["extension_uuid"];
						}
						if (strlen($this->extension) == 0) {
							$this->extension = $row["extension"];
						}
					}
				}
				unset ($prep_statement);

			//set the dial string
				if ($this->enabled == "true") {
					$this->dial_string = "loopback/*99".$this->extension;
				}
				else {
					$this->dial_string = '';
				}

			//update the extension
				$sql  = "update v_extensions set ";
				$sql .= "dial_string = '".$this->dial_string."', ";
				//$sql .= "dial_domain = '".$this->domain_name."', ";
				$sql .= "do_not_disturb = '".$this->enabled."' ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and extension_uuid = '".$this->extension_uuid."' ";
				if ($this->debug) {
					echo $sql."<br />";
				}

				$db->exec(check_sql($sql));
				unset($sql);

		} //function
	} //class

?>
