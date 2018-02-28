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
		private $number_alias;
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
				if (is_array($result)) foreach ($result as &$row) {
					$this->extension = $row["extension"];
					$this->accountcode = $row["accountcode"];
					$this->toll_allow = $row["toll_allow"];
					$this->number_alias = $row["number_alias"];
					$this->outbound_caller_id_name = $row["outbound_caller_id_name"];
					$this->outbound_caller_id_number = $row["outbound_caller_id_number"];
				}

			//determine whether to update the dial string
				$sql = "select * from v_follow_me ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and follow_me_uuid = '".$this->follow_me_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (is_array($result)) foreach ($result as &$row) {
					$follow_me_uuid = $row["follow_me_uuid"];
					$this->cid_name_prefix = $row["cid_name_prefix"];
					$this->cid_number_prefix = $row["cid_number_prefix"];
				}
				unset ($prep_statement);

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

				$dial_string_caller_id_name = "\${effective_caller_id_name}";
				$dial_string_caller_id_number = "\${effective_caller_id_number}";

				if (strlen($this->follow_me_caller_id_uuid) > 0) {
					$sql_caller = "select destination_number, destination_description, destination_caller_id_number, destination_caller_id_name ";
					$sql_caller .= "from v_destinations ";
					$sql_caller .= "where domain_uuid = '$this->domain_uuid' ";
					$sql_caller .= "and destination_type = 'inbound' ";
					$sql_caller .= "and destination_uuid = '$this->follow_me_caller_id_uuid'";
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
					}
				}

				//accountcode
				if (strlen($this->accountcode) == 0) {
					$dial_string .= ",sip_h_X-accountcode=\${accountcode}";
				}
				else {
					$dial_string .= ",sip_h_X-accountcode=".$this->accountcode;
					$dial_string .= ",accountcode=".$this->accountcode;
				}

				//toll allow
				if ($this->toll_allow != '') {
					$dial_string .= ",toll_allow='".$this->toll_allow."'";
				}

				$dial_string .= "}";
				$x = 0;
				if (is_array($result)) foreach ($result as &$row) {
					if ($x > 0) {
						$dial_string .= ",";
					}
					if (($presence_id = extension_presence_id($row["follow_me_destination"])) !== false) {
						//set the dial string
						// using here `sofia_contact` instead of `user/` allows add registered device
						// so you can make follow me for extension `100` like `100` and avoid recursion
						// but it ignores DND/CallForwad settings
						if (strlen($_SESSION['domain']['dial_string']['text']) == 0) {
							$dial_string .= "[";
							$dial_string .= "presence_id=".$presence_id."@".$_SESSION['domain_name'].',';
							if ($row["follow_me_prompt"] == "1") {
								$dial_string .= "group_confirm_key=exec,group_confirm_file=lua confirm.lua,confirm=true,";
							}
							$dial_string .= "leg_delay_start=".$row["follow_me_delay"].",";
							$dial_string .= "leg_timeout=".$row["follow_me_timeout"]."]";
							$dial_string .= "\${sofia_contact(".$row["follow_me_destination"]."@".$_SESSION['domain_name'].")}";
						}
						else {
							//get the session dial string
							$dial_string_template = $_SESSION['domain']['dial_string']['text'];

							//replace the variables with the values
							$dial_string_template = str_replace("sip_invite_domain=\${domain_name},", "", $dial_string_template);
							$dial_string_template = str_replace("presence_id=\${dialed_user}@\${dialed_domain}", "", $dial_string_template);
							$dial_string_template = str_replace("\${dialed_user}", $row["follow_me_destination"], $dial_string_template);
							$dial_string_template = str_replace("\${dialed_domain}", $_SESSION['domain_name'], $dial_string_template);
							$dial_string_template = str_replace("\${call_timeout}", $row["follow_me_timeout"], $dial_string_template);
							$dial_string_template = str_replace("\${leg_timeout}", $row["follow_me_timeout"], $dial_string_template);

							//seperate the variables from the bridge statement
							preg_match_all('/{((?:[^{}]|(?R))*)}/', $dial_string_template, $matches, PREG_PATTERN_ORDER);
							$dial_string_variables = $matches[1][0];
							$dial_string_bridge = $matches[1][1];

							//add to the dial string
							$dial_string .= "[";
							$dial_string .= $dial_string_variables;

							//group confirm
							if ($row["follow_me_prompt"] == "1") {
								$dial_string .= ",group_confirm_key=exec,group_confirm_file=lua confirm.lua,confirm=true";
							}
							$dial_string .= "]";
							$dial_string .= "\${".$dial_string_bridge."}";
						}
					}
					else {
						$presence_id = extension_presence_id($this->extension, $this->number_alias);
						$dial_string .= "[anyvar=";

						//set the caller id
						if ($_SESSION['cdr']['follow_me_fix']['boolean'] == "true") {
							if (strlen($this->outbound_caller_id_name) > 0) {
								$dial_string .= ",origination_caller_id_name=".$this->cid_name_prefix.$this->outbound_caller_id_name;
								$dial_string .= ",effective_caller_id_name=".$this->cid_name_prefix.$this->outbound_caller_id_name;
							}
							if (strlen($this->outbound_caller_id_number) > 0) {
								$dial_string .= ",origination_caller_id_number=".$this->cid_number_prefix.$this->outbound_caller_id_number;
								$dial_string .= ",effective_caller_id_number=".$this->cid_number_prefix.$this->outbound_caller_id_number;
							}
						}
						else {
							if (strlen($caller_id_number) > 0) {
								//set the caller id if it is set
								if (strlen($caller_id_name) > 0) { 
									$dial_string .= ",origination_caller_id_name=".$this->cid_name_prefix.$caller_id_name; 
									$dial_string .= ",effective_caller_id_name=".$this->cid_name_prefix.$caller_id_name; 
								}
								$dial_string .= ",origination_caller_id_number=".$this->cid_number_prefix.$caller_id_number;
								$dial_string .= ",effective_caller_id_number=".$this->cid_number_prefix.$caller_id_number;
							}
							else {
								//set the outbound caller id number if the caller id number is a user
								$dial_string .=',origination_caller_id_number=${cond(${from_user_exists} == true ? ${outbound_caller_id_number} : ${origination_caller_id_number})}';
								$dial_string .=',effective_caller_id_number=${cond(${from_user_exists} == true ? ${outbound_caller_id_number} : ${effective_caller_id_number})}';
								$dial_string .=',origination_caller_id_name=${cond(${from_user_exists} == true ? ${outbound_caller_id_name} : ${origination_caller_id_name})}';
								$dial_string .=',effective_caller_id_name=${cond(${from_user_exists} == true ? ${outbound_caller_id_name} : ${effective_caller_id_name})}';
							}
						}
						$dial_string .= ",presence_id=".$presence_id."@".$_SESSION['domain_name'];
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
				$dial_string = str_replace(",]", "]", $dial_string);
				$this->dial_string = $dial_string;

				$sql  = "update v_follow_me set ";
				$sql .= "dial_string = '".check_str($this->dial_string)."' ";
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
