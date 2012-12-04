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
		public $domain_uuid;
		public $db_type;
		public $call_forward_uuid;
		public $extension;
		public $call_forward_enabled;
		public $call_forward_number;

		public function call_forward_add() {
			global $db;
			$hunt_group_extension = $this->extension;
			$huntgroup_name = 'call_forward_'.$this->extension;
			$hunt_group_type = 'call_forward';
			$hunt_group_context = $_SESSION['context'];
			$hunt_group_timeout_destination = $this->extension;
			$hunt_group_timeout_type = 'voicemail';
			$hunt_group_ring_back = 'us-ring';
			$hunt_group_cid_name_prefix = '';
			$hunt_group_pin = '';
			$huntgroup_caller_announce = 'false';
			$hunt_group_user_list = '';
			$hunt_group_enabled = $this->call_forward_enabled;
			$hunt_group_description = 'call forward '.$this->extension;

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
			$sql .= "hunt_group_cid_name_prefix, ";
			$sql .= "hunt_group_pin, ";
			$sql .= "hunt_group_call_prompt, ";
			$sql .= "hunt_group_caller_announce, ";
			$sql .= "hunt_group_user_list, ";
			$sql .= "hunt_group_enabled, ";
			$sql .= "hunt_group_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$this->domain_uuid', ";
			$sql .= "'".$this->call_forward_uuid."', ";
			$sql .= "'$hunt_group_extension', ";
			$sql .= "'$huntgroup_name', ";
			$sql .= "'$hunt_group_type', ";
			$sql .= "'$hunt_group_context', ";
			$sql .= "'$hunt_group_timeout', ";
			$sql .= "'$hunt_group_timeout_destination', ";
			$sql .= "'$hunt_group_timeout_type', ";
			$sql .= "'$hunt_group_ring_back', ";
			$sql .= "'$hunt_group_cid_name_prefix', ";
			$sql .= "'$hunt_group_pin', ";
			$sql .= "'$hunt_group_call_prompt', ";
			$sql .= "'$huntgroup_caller_announce', ";
			$sql .= "'$hunt_group_user_list', ";
			$sql .= "'$hunt_group_enabled', ";
			$sql .= "'$hunt_group_description' ";
			$sql .= ")";
			if ($v_debug) {
				echo "add: ".$sql."<br />";
			}
			$db->exec(check_sql($sql));
			unset($sql);
			$this->call_forward_destination();
		}

		public function call_forward_update() {
			global $db;
			$hunt_group_extension = $this->extension;
			$huntgroup_name = 'call_forward_'.$this->extension;
			$hunt_group_type = 'call_forward';
			$hunt_group_context = $_SESSION['context'];
			$hunt_group_timeout_destination = $this->extension;
			$hunt_group_timeout_type = 'voicemail';
			$hunt_group_ring_back = 'us-ring';
			$hunt_group_cid_name_prefix = '';
			$hunt_group_pin = '';
			$huntgroup_caller_announce = 'false';
			$hunt_group_user_list = '';
			$hunt_group_enabled = $this->call_forward_enabled;
			$hunt_group_description = 'call forward '.$this->extension;

			$sql = "update v_hunt_groups set ";
			$sql .= "hunt_group_extension = '$hunt_group_extension', ";
			$sql .= "hunt_group_name = '$huntgroup_name', ";
			$sql .= "hunt_group_type = '$hunt_group_type', ";
			$sql .= "hunt_group_context = '$hunt_group_context', ";
			$sql .= "hunt_group_timeout = '$hunt_group_timeout', ";
			$sql .= "hunt_group_timeout_destination = '$hunt_group_timeout_destination', ";
			$sql .= "hunt_group_timeout_type = '$hunt_group_timeout_type', ";
			$sql .= "hunt_group_ringback = '$hunt_group_ring_back', ";
			$sql .= "hunt_group_cid_name_prefix = '$hunt_group_cid_name_prefix', ";
			$sql .= "hunt_group_pin = '$hunt_group_pin', ";
			$sql .= "hunt_group_call_prompt = '$hunt_group_call_prompt', ";
			$sql .= "hunt_group_caller_announce = '$huntgroup_caller_announce', ";
			$sql .= "hunt_group_user_list = '$hunt_group_user_list', ";
			$sql .= "hunt_group_enabled = '$hunt_group_enabled', ";
			$sql .= "hunt_group_description = '$hunt_group_description' ";
			$sql .= "where domain_uuid = '$this->domain_uuid' ";
			$sql .= "and hunt_group_uuid = '$this->call_forward_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);
			$this->call_forward_destination();
		} //end function

		public function call_forward_destination() {
			global $db;
			//delete related v_hunt_group_destinations
				$sql = "delete from v_hunt_group_destinations where hunt_group_uuid = '$this->call_forward_uuid' ";
				$db->exec(check_sql($sql));
			//check whether the number is an extension or external number
				if (strlen($this->call_forward_number) > 7) {
					$destination_type = 'sip uri';
					$destination_profile = '';
				}
				else {
					$destination_type = 'extension';
					$destination_profile = 'internal';
				}
			//prepare the variables
				$destination_data = $this->call_forward_number;
				$destination_timeout = '';
				$destination_order = '1';
				$destination_enabled = 'true';
				$destination_description = 'call forward';
			//add the hunt group destination
				if ($this->call_forward_uuid) {
					$sql = "insert into v_hunt_group_destinations ";
					$sql .= "(";
					$sql .= "hunt_group_destination_uuid, ";
					$sql .= "domain_uuid, ";
					$sql .= "hunt_group_uuid, ";
					$sql .= "destination_data, ";
					$sql .= "destination_type, ";
					$sql .= "destination_profile, ";
					$sql .= "destination_timeout, ";
					$sql .= "destination_order, ";
					$sql .= "destination_enabled, ";
					$sql .= "destination_description ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'$this->domain_uuid', ";
					$sql .= "'$this->call_forward_uuid', ";
					$sql .= "'$destination_data', ";
					$sql .= "'$destination_type', ";
					$sql .= "'$destination_profile', ";
					$sql .= "'$destination_timeout', ";
					$sql .= "'$destination_order', ";
					$sql .= "'$destination_enabled', ";
					$sql .= "'$destination_description' ";
					$sql .= ")";

					$db->exec(check_sql($sql));
					unset($sql);
				}
		} //end function
	}

?>