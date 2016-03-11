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
	Errol Samuels <voiptology@gmail.com>

*/
include "root.php";

//define the call_forward class
	class call_forward {
		public $debug;
		public $domain_uuid;
		public $domain_name;
		public $extension_uuid;
		private $extension;
		private $number_alias;
		public $forward_all_destination;
		public $forward_all_enabled;
		private $dial_string;
		private $toll_allow;
		public $accountcode;
		public $forward_caller_id_uuid;
		public $outbound_caller_id_name;
		public $outbound_caller_id_number;

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
						$this->number_alias = $row["number_alias"];
						$this->accountcode = $row["accountcode"];
						$this->toll_allow = $row["toll_allow"];
						$this->outbound_caller_id_name = $row["outbound_caller_id_name"];
						$this->outbound_caller_id_number = $row["outbound_caller_id_number"];
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
					$dial_string .= ",toll_allow='".$this->toll_allow."'";
					if (strlen($this->accountcode) > 0) {
						$dial_string .= ",sip_h_X-accountcode=".$this->accountcode;
						$dial_string .= ",accountcode=".$this->accountcode;
					}

					if (strlen($this->forward_caller_id_uuid) > 0){
						$sql_caller = "select destination_number, destination_description, destination_caller_id_number, destination_caller_id_name from v_destinations where domain_uuid = '$this->domain_uuid' and destination_type = 'inbound' and destination_uuid = '$this->forward_caller_id_uuid'";
						$prep_statement_caller = $db->prepare($sql_caller);
						if ($prep_statement_caller) {
							$prep_statement_caller->execute();
							$row_caller = $prep_statement_caller->fetch(PDO::FETCH_ASSOC);

							$caller_id_number = $row_caller['destination_caller_id_number'];
							if(strlen($caller_id_number) == 0){
								$caller_id_number = $row_caller['destination_number'];
							}
							$caller_id_name = $row_caller['destination_caller_id_name'];
							if(strlen($caller_id_name) == 0){
								$caller_id_name = $row_caller['destination_description'];
							}

							if (strlen($caller_id_name) > 0) {
								$dial_string_caller_id_name = $caller_id_name;
								$dial_string .= ",origination_caller_id_name=$dial_string_caller_id_name";
							}
							if (strlen($caller_id_number) > 0) {
								$dial_string_caller_id_number = $caller_id_number;
								$dial_string .= ",origination_caller_id_number=$dial_string_caller_id_number";
								$dial_string .= ",outbound_caller_id_number=$dial_string_caller_id_number";
							}
						}
					}
					else{
						if ($_SESSION['cdr']['call_forward_fix']['boolean'] == "true"){
							$dial_string .= ",outbound_caller_id_name=".$this->outbound_caller_id_name;
							$dial_string .= ",outbound_caller_id_number=".$this->outbound_caller_id_number;
							$dial_string .= ",origination_caller_id_name=".$this->outbound_caller_id_name;
							$dial_string .= ",origination_caller_id_number=".$this->outbound_caller_id_number;
						}
					}

					$dial_string .= "}";
					if (extension_exists($this->forward_all_destination)) {
						$dial_string .= "user/".$this->forward_all_destination."@".$_SESSION['domain_name'];
					}
					else {
						if ($_SESSION['domain']['bridge']['text'] == "outbound" || $_SESSION['domain']['bridge']['text'] == "bridge") {
							$bridge = outbound_route_to_bridge ($_SESSION['domain_uuid'], $this->forward_all_destination);
							$dial_string .= $bridge[0];
						}
						elseif ($_SESSION['domain']['bridge']['text'] == "lcr") {
							$dial_string .= "lcr/".$_SESSION['lcr']['profile']['text']."/".$_SESSION['domain_name']."/".$this->forward_all_destination;
						}
						elseif ($_SESSION['domain']['bridge']['text'] === "loopback") {
							$dial_string .= "loopback/".$this->forward_all_destination;
						}
						else {
							$dial_string .= "loopback/".$this->forward_all_destination;
						}
					}
					$this->dial_string = $dial_string;
				}
				else {
					$this->dial_string = '';
				}

			//update the extension
				$sql = "update v_extensions set ";
				if (strlen($this->forward_all_destination) == 0) {
					$sql .= "forward_all_destination = null, ";
				}
				else {
					$sql .= "forward_all_destination = '$this->forward_all_destination', ";
				}
				if (strlen($this->forward_all_destination) == 0 || $this->forward_all_enabled == "false") {
					$sql .= "dial_string = null, ";
					$sql .= "forward_all_enabled = 'false' ";
				}
				else {
					$sql .= "dial_string = '".check_str($this->dial_string)."', ";
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
				$cache = new cache;
				$cache->delete("directory:".$this->extension."@".$this->domain_name);
				if(strlen($this->number_alias) > 0){
					$cache->delete("directory:".$this->number_alias."@".$this->domain_name);
				}

		} //function
	} //class

?>
