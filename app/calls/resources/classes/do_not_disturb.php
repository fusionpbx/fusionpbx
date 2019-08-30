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
	Copyright (C) 2010 - 2016
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
						$sql .= "user_status = :user_status ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and username = :username ";
						$parameters['user_status'] = "Do Not Disturb";
						$parameters['domain_uuid'] = $this->domain_uuid;
						$parameters['username'] = $_SESSION['username'];
						$database = new database;
						$database->execute($sql);
				}
		}

		public function set() {
			//determine whether to update the dial string
				$sql = "select extension_uuid, extension, number_alias ";
				$sql .= "from v_extensions ";
				$sql .= "where domain_uuid = :domain_uuid ";
				if (is_uuid($this->extension_uuid)) {
					$sql .= "and extension_uuid = :extension_uuid ";
					$parameters['extension_uuid'] = $this->extension_uuid;
				}
				else {
					$sql .= "and extension = :extension ";
					$parameters['extension'] = $this->extension;
				}
				$parameters['domain_uuid'] = $this->domain_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					if (is_uuid($this->extension_uuid)) {
						$this->extension_uuid = $row["extension_uuid"];
					}
					if (strlen($this->extension) == 0) {
						if (strlen($row["number_alias"]) == 0) {
							$this->extension = $row["extension"];
						}
						else {
							$this->extension = $row["number_alias"];
						}
					}
				}
				unset($sql, $parameters, $row);

			//set the dial string
				$this->dial_string = $this->enabled == "true" ? "error/user_busy" : '';

			//build extension update array
				$array['extensions'][0]['extension_uuid'] = $this->extension_uuid;
				$array['extensions'][0]['dial_string'] = $this->dial_string;
				$array['extensions'][0]['do_not_disturb'] = $this->enabled;

			//grant temporary permissions
				$p = new permissions;
				$p->add('extension_edit', 'temp');

			//execute update
				$database = new database;
				$database->app_name = 'calls';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$database->save($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('extension_edit', 'temp');
		}
	}

?>