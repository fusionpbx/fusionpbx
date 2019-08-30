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
	Copyright (C) 2010 - 2019
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
		private $domain_name;
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

			//build follow me insert array
				$array['follow_me'][0]['follow_me_uuid'] = $this->follow_me_uuid;
				$array['follow_me'][0]['domain_uuid'] = $this->domain_uuid;
				$array['follow_me'][0]['cid_name_prefix'] = $this->cid_name_prefix;
				if (strlen($this->cid_number_prefix) > 0) {
					$array['follow_me'][0]['cid_number_prefix'] = $this->cid_number_prefix;
				}
				$array['follow_me'][0]['follow_me_caller_id_uuid'] = is_uuid($this->follow_me_caller_id_uuid) ? $this->follow_me_caller_id_uuid : null;
				$array['follow_me'][0]['follow_me_enabled'] = $this->follow_me_enabled;
				$array['follow_me'][0]['follow_me_ignore_busy'] = $this->follow_me_ignore_busy;
			//grant temporary permissions
				$p = new permissions;
				$p->add('follow_me_add', 'temp');
			//execute insert
				$database = new database;
				$database->app_name = 'calls';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$database->save($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('follow_me_add', 'temp');

				$this->follow_me_destinations();

		}

		public function update() {

			//build follow me update array
				$array['follow_me'][0]['follow_me_uuid'] = $this->follow_me_uuid;
				$array['follow_me'][0]['cid_name_prefix'] = $this->cid_name_prefix;
				$array['follow_me'][0]['cid_number_prefix'] = $this->cid_number_prefix;
				$array['follow_me'][0]['follow_me_caller_id_uuid'] = is_uuid($this->follow_me_caller_id_uuid) ? $this->follow_me_caller_id_uuid : null;
				$array['follow_me'][0]['follow_me_enabled'] = $this->follow_me_enabled;
				$array['follow_me'][0]['follow_me_ignore_busy'] = $this->follow_me_ignore_busy;
			//grant temporary permissions
				$p = new permissions;
				$p->add('follow_me_add', 'temp');
			//execute update
				$database = new database;
				$database->app_name = 'calls';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$database->save($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('follow_me_add', 'temp');

				$this->follow_me_destinations();

		}

		public function follow_me_destinations() {

			//delete related follow me destinations
				$array['follow_me_destinations'][0]['follow_me_uuid'] = $this->follow_me_uuid;
				//grant temporary permissions
					$p = new permissions;
					$p->add('follow_me_destination_delete', 'temp');
				//execute delete
					$database = new database;
					$database->app_name = 'calls';
					$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
					$database->delete($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('follow_me_destination_delete', 'temp');

			//build follow me destinations insert array
				$x = 0;
				if (strlen($this->destination_data_1) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_1;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_1;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_1;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_1;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '1';
					$this->destination_order++;
					$x++;
				}
				if (strlen($this->destination_data_2) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_2;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_2;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_2;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_2;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '2';
					$this->destination_order++;
					$x++;
				}
				if (strlen($this->destination_data_3) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_3;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_3;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_3;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_3;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '3';
					$this->destination_order++;
					$x++;
				}
				if (strlen($this->destination_data_4) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_4;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_4;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_4;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_4;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '4';
					$this->destination_order++;
					$x++;
				}
				if (strlen($this->destination_data_5) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_5;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_5;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_5;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_5;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '5';
					$this->destination_order++;
					$x++;
				}
				if (is_array($array) && @sizeof($array) != 0) {
					//grant temporary permissions
						$p = new permissions;
						$p->add('follow_me_destination_add', 'temp');
					//execute insert
						$database = new database;
						$database->app_name = 'calls';
						$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
						$database->save($array);
						unset($array);
					//revoke temporary permissions
						$p->delete('follow_me_destination_add', 'temp');
				}
		}

		public function set() {

			//determine whether to update the dial string
				$sql = "select * from v_extensions ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and extension_uuid = :extension_uuid ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['extension_uuid'] = $this->extension_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					$this->extension = $row["extension"];
					$this->accountcode = $row["accountcode"];
					$this->toll_allow = $row["toll_allow"];
					$this->number_alias = $row["number_alias"];
					$this->outbound_caller_id_name = $row["outbound_caller_id_name"];
					$this->outbound_caller_id_number = $row["outbound_caller_id_number"];
				}
				unset($sql, $parameters, $row);

			//determine whether to update the dial string
				$sql = "select d.domain_name, f.* ";
				$sql .= "from v_follow_me as f, v_domains as d ";
				$sql .= "where f.domain_uuid = :domain_uuid ";
				$sql .= "and f.follow_me_uuid = :follow_me_uuid ";
				$sql .= "and d.domain_uuid = f.domain_uuid ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['follow_me_uuid'] = $this->follow_me_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					$follow_me_uuid = $row["follow_me_uuid"];
					$this->domain_name = $row["domain_name"];
					$this->follow_me_enabled = $row["follow_me_enabled"];
					$this->cid_name_prefix = $row["cid_name_prefix"];
					$this->cid_number_prefix = $row["cid_number_prefix"];
				}
				unset($sql, $parameters, $row);

			//set the extension dial string
				$sql = "select * from v_follow_me_destinations ";
				$sql .= "where follow_me_uuid = :follow_me_uuid ";
				$sql .= "order by follow_me_order asc ";
				$parameters['follow_me_uuid'] = $this->follow_me_uuid;
				$database = new database;
				$result = $database->select($sql, $parameters, 'all');

				/*
				$dial_string_caller_id_name = "\${effective_caller_id_name}";
				$dial_string_caller_id_number = "\${effective_caller_id_number}";
				if (strlen($this->follow_me_caller_id_uuid) > 0) {
					$sql_caller = "select destination_number, destination_description, destination_caller_id_number, destination_caller_id_name ";
					$sql_caller .= "from v_destinations ";
					$sql_caller .= "where domain_uuid = :domain_uuid ";
					$sql_caller .= "and destination_type = 'inbound' ";
					$sql_caller .= "and destination_uuid = :destination_uuid ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$parameters['destination_uuid'] = $this->follow_me_caller_id_uuid;
					$database = new database;
					$row_caller = $database->select($sql_caller, $parameters, 'row');
					if (is_array($row_caller) && @sizeof($row_caller) != 0) {
						$caller_id_number = $row_caller['destination_caller_id_number'];
						if (strlen($caller_id_number) == 0){
							$caller_id_number = $row_caller['destination_number'];
						}
						$caller_id_name = $row_caller['destination_caller_id_name'];
						if (strlen($caller_id_name) == 0){
							$caller_id_name = $row_caller['destination_description'];
						}
					}
					unset($sql_caller, $parameters, $row_caller);
				}
				*/

				$x = 0;
				if (is_array($result) && @sizeof($result) != 0) {
					foreach ($result as &$row) {
						if ($x > 0) {
							$dial_string .= ",";
						}

						//determine if the destination is a local sip user
						$sql = "select extension, number_alias from v_extensions ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and ( ";
						$sql .= "extension = :extension ";
						$sql .= "or number_alias = :number_alias ";
						$sql .= ") ";
						$parameters['domain_uuid'] = $this->domain_uuid;
						$parameters['extension'] = $row["follow_me_destination"];
						$parameters['number_alias'] = $row["follow_me_destination"];
						$database = new database;
						$field = $database->select($sql, $parameters, 'row');
						if (isset($field['extension'])) {
							if (is_numeric($field['extension'])) {
								$presence_id = $field['extension'];
							}
							else {
								$presence_id = $field['number_alias'];
							}
							$variables[] = "presence_id=".$presence_id."@".$this->domain_name;
							if ($row["follow_me_prompt"] == "1") {
								$variables[] = "group_confirm_key=exec";
								$variables[] = "group_confirm_file=lua confirm.lua";
								$variables[] = "confirm=true";
							}
							if ($this->follow_me_ignore_busy != 'true') {
								$variables[] = "fail_on_single_reject=USER_BUSY";
							}
							//accountcode
							if (strlen($this->accountcode) == 0) {
								$variables[] = "sip_h_X-accountcode=\${accountcode}";
							}
							else {
								$variables[] = "sip_h_X-accountcode=".$this->accountcode;
								$variables[] = "accountcode=".$this->accountcode;
							}
							//toll allow
							if ($this->toll_allow != '') {
								$variables[] = "toll_allow=''".str_replace(",", "\,", $this->toll_allow)."''";
							}

							$variables[] = "instant_ringback=true";
							$variables[] = "ignore_early_media=true";
							$variables[] = "domain_uuid=".$this->domain_uuid;
							$variables[] = "sip_invite_domain=".$this->domain_name;
							$variables[] = "domain_name=".$this->domain_name;
							$variables[] = "domain=".$this->domain_name;
							$variables[] = "extension_uuid=".$this->extension_uuid;
							$variables[] = "leg_delay_start=".$row["follow_me_delay"];
							$variables[] = "originate_delay_start=".$row["follow_me_delay"];
							$variables[] = "leg_timeout=".$row["follow_me_timeout"];

							$dial_string .= "[".implode(",", $variables)."]\${sofia_contact(*/".$row["follow_me_destination"]."@".$this->domain_name.")}";
							//$dial_string .= "[".implode(",", $variables)."]user/".$row["follow_me_destination"]."@".$this->domain_name;
							//$dial_string .= "loopback/export:".implode("\,export:", $variables)."\,transfer:".$row["follow_me_destination"]."/".$this->domain_name."/inline";
							unset($variables);
						}
						else {
							if (is_numeric($this->extension)) {
								$presence_id = $this->extension;
							}
							else {
								$presence_id = $this->number_alias;
							}
							$variables[] = "presence_id=".$presence_id."@".$this->domain_name;

							//set the caller id
							if ($_SESSION['follow_me']['outbound_caller_id']['boolean'] == "true") {
								if (strlen($this->outbound_caller_id_name) > 0) {
									$variables[] = "origination_caller_id_name=".$this->cid_name_prefix.$this->outbound_caller_id_name;
									$variables[] = "effective_caller_id_name=".$this->cid_name_prefix.$this->outbound_caller_id_name;
								}
								if (strlen($this->outbound_caller_id_number) > 0) {
									$variables[] = "origination_caller_id_number=".$this->cid_number_prefix.$this->outbound_caller_id_number;
									$variables[] = "effective_caller_id_number=".$this->cid_number_prefix.$this->outbound_caller_id_number;
								}
							}
							else {
								if ($_SESSION['domain']['bridge']['text'] == "loopback") {
									//set the outbound caller id number if the caller id number is a user
									//$variables[] = "origination_caller_id_number=\${cond(\${from_user_exists} == true ? ".$this->outbound_caller_id_number." : \${origination_caller_id_number})}";
									$variables[] = "effective_caller_id_number=\${cond(\${from_user_exists} == true ? ".$this->outbound_caller_id_number." : \${effective_caller_id_number})}";
									//$variables[] = "origination_caller_id_name=\${cond(\${from_user_exists} == true ? ".$this->outbound_caller_id_name." : \${origination_caller_id_name})}";
									$variables[] = "effective_caller_id_name=\${cond(\${from_user_exists} == true ? ".$this->outbound_caller_id_name." : \${effective_caller_id_name})}";
								}
								else {
									//$variables[] .="origination_caller_id_number=\${cond(\${from_user_exists} == true ? \${outbound_caller_id_number} : )}";
									$variables[] .="effective_caller_id_number=\${cond(\${from_user_exists} == true ? \${outbound_caller_id_number} : )}";
									//$variables[] .="origination_caller_id_name=\${cond(\${from_user_exists} == true ? \${outbound_caller_id_name} : )}";
									$variables[] .="effective_caller_id_name=\${cond(\${from_user_exists} == true ? \${outbound_caller_id_name} : )}";
								}
							}

							//accountcode
							if (strlen($this->accountcode) == 0) {
								$variables[] = "sip_h_X-accountcode=\${accountcode}";
							}
							else {
								$variables[] = "sip_h_X-accountcode=".$this->accountcode;
								$variables[] = "accountcode=".$this->accountcode;
							}

							//toll allow
							if ($this->toll_allow != '') {
								$variables[] = "toll_allow=''".str_replace(",", "\,", $this->toll_allow)."''";
							}

							if ($this->follow_me_ignore_busy != 'true') {
								$variables[] = "fail_on_single_reject=USER_BUSY";
							}

							if ($row["follow_me_prompt"] == "1") {
								$variables[] = "group_confirm_key=exec";
								$variables[] = "group_confirm_file=lua confirm.lua";
								$variables[] = "confirm=true";
							}

							$variables[] = "instant_ringback=true";
							$variables[] = "ignore_early_media=true";
							$variables[] = "domain_uuid=".$this->domain_uuid;
							$variables[] = "sip_invite_domain=".$this->domain_name;
							$variables[] = "domain_name=".$this->domain_name;
							//$variables[] = "domain=".$this->domain_name;
							$variables[] = "extension_uuid=".$this->extension_uuid;
							$variables[] = "leg_delay_start=".$row["follow_me_delay"];
							$variables[] = "originate_delay_start=".$row["follow_me_delay"];
							$variables[] = "sleep=".($row["follow_me_delay"] * 1000);
							$variables[] = "leg_timeout=".$row["follow_me_timeout"];
							if (is_numeric($row["follow_me_destination"])) {
								if ($_SESSION['domain']['bridge']['text'] == "outbound" || $_SESSION['domain']['bridge']['text'] == "bridge") {
									$bridge = outbound_route_to_bridge ($this->domain_uuid, $row["follow_me_destination"]);
									$dial_string .= "[".implode(",", $variables)."]".$bridge[0];
								}
								else if ($_SESSION['domain']['bridge']['text'] == "loopback") {
									$variables[] = "is_follow_me_loopback=true";
									$sleep_time = "sleep:".($row["follow_me_delay"] * 1000);
									//$dial_string .= "loopback/".$row["follow_me_destination"]."/".$this->domain_name;
									$dial_string .= "loopback/".$sleep_time."\,export:".implode("\,export:", $variables)."\,transfer:".$row["follow_me_destination"]."/".$this->domain_name."/inline";
								}
								else if ($_SESSION['domain']['bridge']['text'] == "lcr") {
									$dial_string .= "[".implode(",", $variables)."]lcr/".$_SESSION['lcr']['profile']['text']."/".$this->domain_name."/".$row["follow_me_destination"];
								}
								else {
									//$dial_string .= "loopback/".$row["follow_me_destination"]."/".$this->domain_name;
									$sleep_time = "sleep:".($row["follow_me_delay"] * 1000);
									$dial_string .= "loopback/".$sleep_time."\,export:".implode("\,export:", $variables)."\,transfer:".$row["follow_me_destination"]."/".$this->domain_name."/inline";
								}
							}
							else {
								$dial_string .= $row["follow_me_destination"];
							}
						}
						unset($sql, $parameters, $field);
						$x++;
					}
				}
				//$dial_string = str_replace(",]", "]", $dial_string);
				$this->dial_string = "{ignore_early_media=true}".$dial_string;
				unset($variables);

			//get the extension_uuid
				$parameters['follow_me_uuid'] = $this->follow_me_uuid;
				$sql = "select extension_uuid from v_extensions ";
				$sql .= "where follow_me_uuid = :follow_me_uuid ";
				$database = new database;
				$result = $database->select($sql, $parameters);
				$extension_uuid = $result[0]['extension_uuid'];

			//grant temporary permissions
				$p = new permissions;
				$p->add("follow_me_edit", 'temp');
				$p->add("extension_edit", 'temp');

			//add follow me to the array
				$array['follow_me'][0]["follow_me_uuid"] = $this->follow_me_uuid;
				$array['follow_me'][0]["domain_uuid"] = $this->domain_uuid;
				$array['follow_me'][0]["dial_string"] = $this->dial_string;

			//is follow me enabled
				$dial_string = '';
				if ($this->follow_me_enabled == "true") {
					$dial_string = $this->dial_string;
				}

			//add extensions to the array
				$array['extensions'][0]["extension_uuid"] = $extension_uuid;
				$array['extensions'][0]["dial_domain"] = $this->domain_name;
				$array['extensions'][0]["dial_string"] = $dial_string;
				$array['extensions'][0]["follow_me_destinations"] = $dial_string;
				$array['extensions'][0]["follow_me_enabled"] = $this->follow_me_enabled;

			//save the destination
				$database = new database;
				$database->app_name = 'follow_me';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$database->save($array);

			//remove the temporary permission
				$p->delete("follow_me_edit", 'temp');
				$p->delete("extension_edit", 'temp');
		} //function
	} //class

?>