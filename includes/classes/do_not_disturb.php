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
		public $domain_uuid;
		public $dnd_uuid;
		public $domain_name;
		public $extension;
		public $dnd_enabled;

		//update the user_status
		public function dnd_status() {
			global $db;
			if ($this->dnd_enabled == "true") {
				//update the call center status
					$user_status = "Logged Out";
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp) {
						$switch_cmd .= "callcenter_config agent set status ".$_SESSION['username']."@".$domain_name." '".$user_status."'";
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}

				//update the database user_status
					$user_status = "Do Not Disturb";
					$sql  = "update v_users set ";
					$sql .= "user_status = '$user_status' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and username = '".$_SESSION['username']."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
			}
		} //function

		public function dnd_add() {
			global $db;
			$hunt_group_extension = $this->extension;
			$huntgroup_name = 'dnd_'.$this->extension;
			$hunt_group_type = 'dnd';
			$hunt_group_context = $_SESSION['context'];
			$hunt_group_timeout = '1';
			$hunt_group_timeout_destination = $this->extension;
			$hunt_group_timeout_type = 'voicemail';
			$hunt_group_ring_back = 'us-ring';
			//$hunt_group_cid_name_prefix = '';
			//$hunt_group_pin = '';
			//$hunt_group_call_prompt = 'false';
			$huntgroup_caller_announce = 'false';
			//$hunt_group_user_list = '';
			$hunt_group_enabled = $this->dnd_enabled;
			$hunt_group_description = 'dnd '.$this->extension;

			$sql = "insert into v_hunt_groups ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "hunt_group_uuid, ";
			$sql .= "hunt_group_extension, ";
			$sql .= "hunt_group_name, ";
			$sql .= "hunt_group_type, ";
			$sql .= "hunt_group_context, ";
			$sql .= "hunt_group_timeout, ";
			$sql .= "hunt_group_timeout_destination, ";
			$sql .= "hunt_group_timeout_type, ";
			$sql .= "hunt_group_ringback, ";
			//$sql .= "hunt_group_cid_name_prefix, ";
			//$sql .= "hunt_group_pin, ";
			$sql .= "hunt_group_call_prompt, ";
			$sql .= "hunt_group_caller_announce, ";
			//$sql .= "hunt_group_user_list, ";
			$sql .= "hunt_group_enabled, ";
			$sql .= "hunt_group_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$this->domain_uuid', ";
			$sql .= "'$this->dnd_uuid', ";
			$sql .= "'$hunt_group_extension', ";
			$sql .= "'$huntgroup_name', ";
			$sql .= "'$hunt_group_type', ";
			$sql .= "'$hunt_group_context', ";
			$sql .= "'$hunt_group_timeout', ";
			$sql .= "'$hunt_group_timeout_destination', ";
			$sql .= "'$hunt_group_timeout_type', ";
			$sql .= "'$hunt_group_ring_back', ";
			//$sql .= "'$hunt_group_cid_name_prefix', ";
			//$sql .= "'$hunt_group_pin', ";
			$sql .= "'$hunt_group_call_prompt', ";
			$sql .= "'$huntgroup_caller_announce', ";
			//$sql .= "'$hunt_group_user_list', ";
			$sql .= "'$hunt_group_enabled', ";
			$sql .= "'$hunt_group_description' ";
			$sql .= ")";
			if ($this->debug) {
				echo $sql."<br />";
			}
			$db->exec(check_sql($sql));
			unset($sql);
		} //function

		public function dnd_update() {
			global $db;

			$hunt_group_extension = $this->extension;
			$huntgroup_name = 'dnd_'.$this->extension;
			$hunt_group_type = 'dnd';
			$hunt_group_context = $_SESSION['context'];
			$hunt_group_timeout = '1';
			$hunt_group_timeout_destination = $this->extension;
			$hunt_group_timeout_type = 'voicemail';
			$hunt_group_ring_back = 'us-ring';
			//$hunt_group_cid_name_prefix = '';
			//$hunt_group_pin = '';
			//$hunt_group_call_prompt = 'false';
			$huntgroup_caller_announce = 'false';
			//$hunt_group_user_list = '';
			$hunt_group_enabled = $this->dnd_enabled;
			$hunt_group_description = 'dnd '.$this->extension;

			$sql = "update v_hunt_groups set ";
			$sql .= "hunt_group_extension = '$hunt_group_extension', ";
			$sql .= "hunt_group_name = '$huntgroup_name', ";
			$sql .= "hunt_group_type = '$hunt_group_type', ";
			$sql .= "hunt_group_context = '$hunt_group_context', ";
			$sql .= "hunt_group_timeout = '$hunt_group_timeout', ";
			$sql .= "hunt_group_timeout_destination = '$hunt_group_timeout_destination', ";
			$sql .= "hunt_group_timeout_type = '$hunt_group_timeout_type', ";
			$sql .= "hunt_group_ringback = '$hunt_group_ring_back', ";
			//$sql .= "hunt_group_cid_name_prefix = '$hunt_group_cid_name_prefix', ";
			//$sql .= "hunt_group_pin = '$hunt_group_pin', ";
			$sql .= "hunt_group_call_prompt = '$hunt_group_call_prompt', ";
			$sql .= "hunt_group_caller_announce = 'false', ";
			//$sql .= "hunt_group_user_list = '$hunt_group_user_list', ";
			$sql .= "hunt_group_enabled = '$hunt_group_enabled', ";
			$sql .= "hunt_group_description = '$hunt_group_description' ";
			$sql .= "where domain_uuid = '$this->domain_uuid' ";
			$sql .= "and hunt_group_uuid = '$this->dnd_uuid' ";
			if ($this->debug) {
				echo $sql."<br />";
			}
			$db->exec(check_sql($sql));
			unset($sql);
		} //function
	} //class

?>