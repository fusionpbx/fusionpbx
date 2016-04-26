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
	Salvatore Caruso <salvatore.caruso@nems.it>
	Riccardo Granchi <riccardo.granchi@nems.it>
	Errol Samuels <voiptology@gmail.com>
*/
include "root.php";

//define the follow me class
	class follow_me {
		public $domain_uuid;
		public $db_type;
		public $follow_me_uuid;
		public $cid_name_prefix;
		public $cid_number_prefix;
		public $accountcode;
		public $follow_me_enabled;
		public $follow_me_caller_id_uuid;
		public $follow_me_ignore_busy;
		public $outbound_caller_id_name;
		public $outbound_caller_id_number;
		private $extension;
		private $toll_allow;

		public $destination_data_1;
		public $destination_type_1;
		public $destination_delay_1;
		public $destination_prompt_1;
		public $destination_timeout_1;

		public $destination_data_2;
		public $destination_type_2;
		public $destination_delay_2;
		public $destination_prompt_2;
		public $destination_timeout_2;

		public $destination_data_3;
		public $destination_type_3;
		public $destination_delay_3;
		public $destination_prompt_3;
		public $destination_timeout_3;

		public $destination_data_4;
		public $destination_type_4;
		public $destination_delay_4;
		public $destination_prompt_4;
		public $destination_timeout_4;

		public $destination_data_5;
		public $destination_type_5;
		public $destination_delay_5;
		public $destination_prompt_5;
		public $destination_timeout_5;

		public $destination_timeout = 0;
		public $destination_order = 1;

		public function add() {
			//set the global variable
				global $db;

			//add a new follow me
				$sql = "insert into v_follow_me ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "follow_me_uuid, ";
				$sql .= "cid_name_prefix, ";
				if (strlen($this->cid_number_prefix) > 0) {
					$sql .= "cid_number_prefix, ";
				}
				$sql .= "follow_me_caller_id_uuid, ";
				$sql .= "follow_me_enabled, ";
				$sql .= "follow_me_ignore_busy ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$this->domain_uuid', ";
				$sql .= "'$this->follow_me_uuid', ";
				$sql .= "'$this->cid_name_prefix', ";
				if (strlen($this->cid_number_prefix) > 0) {
					$sql .= "'$this->cid_number_prefix', ";
				}
				if (strlen($this->follow_me_caller_id_uuid) > 0) {
					$sql .= "'$this->follow_me_caller_id_uuid', ";
				}
				else {
					$sql .= 'null, ';
				}
				$sql .= "'$this->follow_me_enabled', ";
				$sql .= "'$this->follow_me_ignore_busy' ";
				$sql .= ")";
				if ($v_debug) {
					echo $sql."<br />";
				}
				$db->exec(check_sql($sql));
				unset($sql);
				$this->follow_me_destinations();
		} //end function

		public function update() {
			//set the global variable
				global $db;
			//update follow me table
				$sql = "update v_follow_me set ";
				$sql .= "follow_me_enabled = '$this->follow_me_enabled', ";
				$sql .= "follow_me_ignore_busy = '$this->follow_me_ignore_busy', ";
				$sql .= "cid_name_prefix = '$this->cid_name_prefix', ";
				if (strlen($this->follow_me_caller_id_uuid) > 0) {
					$sql .= "follow_me_caller_id_uuid = '$this->follow_me_caller_id_uuid', ";
				}
				else {
					$sql .= "follow_me_caller_id_uuid = null, ";
				}
				$sql .= "cid_number_prefix = '$this->cid_number_prefix' ";
				$sql .= "where domain_uuid = '$this->domain_uuid' ";
				$sql .= "and follow_me_uuid = '$this->follow_me_uuid' ";
				$db->exec(check_sql($sql));
				unset($sql);
				$this->follow_me_destinations();
		} //end function

		public function follow_me_destinations() {
			//set the global variable
				global $db;

			//prepare insert statement
				$stmt = $db->prepare(
					"insert into v_follow_me_destinations("
						. "follow_me_destination_uuid,"
						. "domain_uuid,"
						. "follow_me_uuid,"
						. "follow_me_destination,"
						. "follow_me_timeout,"
						. "follow_me_delay,"
						. "follow_me_prompt,"
						. "follow_me_order"
					. ")values(?,?,?,?,?,?,?,?)"
				);

			//delete related follow me destinations
				$sql = "delete from v_follow_me_destinations where follow_me_uuid = '$this->follow_me_uuid' ";
				$db->exec(check_sql($sql));

			//insert the follow me destinations
				if (strlen($this->destination_data_1) > 0) {
					$stmt->execute(array(
						uuid(),
						$this->domain_uuid,
						$this->follow_me_uuid,
						$this->destination_data_1,
						$this->destination_timeout_1,
						$this->destination_delay_1,
						$this->destination_prompt_1,
						'1'
					));
					$this->destination_order++;
				}
				if (strlen($this->destination_data_2) > 0) {
					$stmt->execute(array(
						uuid(),
						$this->domain_uuid,
						$this->follow_me_uuid,
						$this->destination_data_2,
						$this->destination_timeout_2,
						$this->destination_delay_2,
						$this->destination_prompt_2,
						'2'
					));
					$this->destination_order++;
				}
				if (strlen($this->destination_data_3) > 0) {
					$stmt->execute(array(
						uuid(),
						$this->domain_uuid,
						$this->follow_me_uuid,
						$this->destination_data_3,
						$this->destination_timeout_3,
						$this->destination_delay_3,
						$this->destination_prompt_3,
						'3'
					));
					$this->destination_order++;
				}
				if (strlen($this->destination_data_4) > 0) {
					$stmt->execute(array(
						uuid(),
						$this->domain_uuid,
						$this->follow_me_uuid,
						$this->destination_data_4,
						$this->destination_timeout_4,
						$this->destination_delay_4,
						$this->destination_prompt_4,
						'4'
					));
					$this->destination_order++;
				}
				if (strlen($this->destination_data_5) > 0) {
					$stmt->execute(array(
						uuid(),
						$this->domain_uuid,
						$this->follow_me_uuid,
						$this->destination_data_5,
						$this->destination_timeout_5,
						$this->destination_delay_5,
						$this->destination_prompt_5,
						'5'
					));
					$this->destination_order++;
				}
				unset($stmt);
		} //function

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
						$this->toll_allow = $row["toll_allow"];
						$this->outbound_caller_id_name = $row["outbound_caller_id_name"];
						$this->outbound_caller_id_number = $row["outbound_caller_id_number"];
					}
				}

			//determine whether to update the dial string
				$sql = "select * from v_follow_me ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and follow_me_uuid = '".$this->follow_me_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
						$follow_me_uuid = $row["follow_me_uuid"];
						$this->cid_name_prefix = $row["cid_name_prefix"];
						$this->cid_number_prefix = $row["cid_number_prefix"];
					}
				}
				unset ($prep_statement);

			//add follow me
				if (strlen($follow_me_uuid) == 0) {
					$this->add();
				}

				//set the extension dial string
					$sql = "select * from v_follow_me_destinations ";
					$sql .= "where follow_me_uuid = '".$this->follow_me_uuid."' ";
					$sql .= "order by follow_me_order asc ";
					$prep_statement_2 = $db->prepare(check_sql($sql));
					$prep_statement_2->execute();
					$result = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
					$dial_string = "{";
					if ($this->follow_me_ignore_busy != 'true') {
						$dial_string .= "fail_on_single_reject=USER_BUSY,";
					}
					$dial_string .= "instant_ringback=true,";
					$dial_string .= "ignore_early_media=true";
					$dial_string .= ",domain_uuid=".$_SESSION['domain_uuid'];
					$dial_string .= ",sip_invite_domain=".$_SESSION['domain_name'];
					$dial_string .= ",domain_name=".$_SESSION['domain_name'];
					$dial_string .= ",domain=".$_SESSION['domain_name'];
					$dial_string .= ",extension_uuid=".$this->extension_uuid;
					$dial_string .= ",group_confirm_key=exec,group_confirm_file=lua confirm.lua";

					$dial_string_caller_id_name = "\${caller_id_name}";
					$dial_string_caller_id_number = "\${caller_id_number}";

					if (strlen($this->follow_me_caller_id_uuid) > 0){
						$sql_caller = "select destination_number, destination_description, destination_caller_id_number, destination_caller_id_name from v_destinations where domain_uuid = '$this->domain_uuid' and destination_type = 'inbound' and destination_uuid = '$this->follow_me_caller_id_uuid'";
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
							}
							if (strlen($caller_id_number) > 0) {
								$dial_string_caller_id_number = $caller_id_number;
							}
						}
					}

					if (strlen($this->cid_name_prefix) > 0) {
						$dial_string .= ",origination_caller_id_name=".$this->cid_name_prefix."$dial_string_caller_id_name";
					}
					else {
						$dial_string .= ",origination_caller_id_name=$dial_string_caller_id_name";
					}

					if (strlen($this->cid_number_prefix) > 0) {
						//$dial_string .= ",origination_caller_id_number=".$this->cid_number_prefix."";
					$dial_string .= ",origination_caller_id_number=".$this->cid_number_prefix."$dial_string_caller_id_number";
					}
					else {
						$dial_string .= ",origination_caller_id_number=$dial_string_caller_id_number";
					}

					if (strlen($this->accountcode) > 0) {
						$dial_string .= ",sip_h_X-accountcode=".$this->accountcode;
						$dial_string .= ",accountcode=".$this->accountcode;
					}
					$dial_string .= ",toll_allow='".$this->toll_allow."'";
					$dial_string .= "}";
					$x = 0;
					foreach ($result as &$row) {
						if ($x > 0) {
							$dial_string .= ",";
						}
						if (extension_exists($row["follow_me_destination"])) {
							//set the dial string
							if (strlen($_SESSION['domain']['dial_string']['text']) == 0) {
								$dial_string .= "[";
								$dial_string .= "outbound_caller_id_number=$dial_string_caller_id_number,";
								$dial_string .= "presence_id=".$row["follow_me_destination"]."@".$_SESSION['domain_name'].",";
								if ($row["follow_me_prompt"] == "1") {
									$dial_string .= "group_confirm_key=exec,group_confirm_file=lua confirm.lua,confirm=true,";
								}
								$dial_string .= "leg_delay_start=".$row["follow_me_delay"].",";
								$dial_string .= "leg_timeout=".$row["follow_me_timeout"]."]";
								$dial_string .= "\${sofia_contact(".$row["follow_me_destination"]."@".$_SESSION['domain_name'].")}";
							}
							else {
								$replace_value = $row["follow_me_destination"];
								if ($row["follow_me_prompt"] == "1") {
									$replace_value .= "[group_confirm_key=exec,group_confirm_file=lua confirm.lua,confirm=true]";
								}
								$local_dial_string = $_SESSION['domain']['dial_string']['text'];
								$local_dial_string = str_replace("\${dialed_user}", $replace_value, $local_dial_string);
								$local_dial_string = str_replace("\${dialed_domain}", $_SESSION['domain_name'], $local_dial_string);
								$local_dial_string = str_replace("\${call_timeout}", $row["follow_me_timeout"], $local_dial_string);
								$local_dial_string = str_replace("\${leg_timeout}", $row["follow_me_timeout"], $local_dial_string);
								$dial_string .= $local_dial_string;
							}
						}
						else {
							$dial_string .= "[";
							if ($_SESSION['cdr']['follow_me_fix']['boolean'] == "true"){
								$dial_string .= "outbound_caller_id_name=".$this->outbound_caller_id_name;
								$dial_string .= ",outbound_caller_id_number=".$this->outbound_caller_id_number;
								$dial_string .= ",origination_caller_id_name=".$this->outbound_caller_id_name;
								$dial_string .= ",origination_caller_id_number=".$this->outbound_caller_id_number;
							}
							else{
								$dial_string .= "outbound_caller_id_number=$dial_string_caller_id_number";
							}
							$dial_string .= ",presence_id=".$this->extension."@".$_SESSION['domain_name'];
							if ($row["follow_me_prompt"] == "1") {
								$dial_string .= ",group_confirm_key=exec,group_confirm_file=lua confirm.lua,confirm=true,";
							}
							$dial_string .= ",leg_delay_start=".$row["follow_me_delay"];
							$dial_string .= ",leg_timeout=".$row["follow_me_timeout"]."]";
							if (is_numeric($row["follow_me_destination"])) {
								if ($_SESSION['domain']['bridge']['text'] == "outbound" || $_SESSION['domain']['bridge']['text'] == "bridge") {
									$bridge = outbound_route_to_bridge ($_SESSION['domain_uuid'], $row["follow_me_destination"]);
									$dial_string .= $bridge[0];
								}
								elseif ($_SESSION['domain']['bridge']['text'] == "loopback") {
									$dial_string .= "loopback/".$row["follow_me_destination"]."/".$_SESSION['domain_name'];
								}
								elseif ($_SESSION['domain']['bridge']['text'] == "lcr") {
									$dial_string .= "lcr/".$_SESSION['lcr']['profile']['text']."/".$_SESSION['domain_name']."/".$row["follow_me_destination"];
								}
								else {
									$dial_string .= "loopback/".$row["follow_me_destination"]."/".$_SESSION['domain_name'];
								}
							}
							else {
								$dial_string .= $row["follow_me_destination"];
							}
						}
						$x++;
					}
					$this->dial_string = $dial_string;

				$sql  = "update v_follow_me set ";
				$sql .= "dial_string = '".$this->dial_string."' ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and follow_me_uuid = '".$this->follow_me_uuid."' ";
				if ($this->debug) {
					echo $sql."<br />";
				}
				$db->exec($sql);
				unset($sql);

			//is follow me enabled
				$dial_string = '';
				if ($this->follow_me_enabled == "true") {
					$dial_string = $this->dial_string;
				}

				$sql  = "update v_extensions set ";
				$sql .= "dial_string = '".check_str($dial_string)."', ";
				$sql .= "dial_domain = '".$_SESSION['domain_name']."' ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and follow_me_uuid = '".$this->follow_me_uuid."' ";
				if ($this->debug) {
					echo $sql."<br />";
				}
				$db->exec($sql);
				unset($sql);

		} //function
	} //class

?>
