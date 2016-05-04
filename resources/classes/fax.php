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
	Copyright (C) 2010-2015
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the directory class
	class switch_fax {

		public $db;
		public $domain_uuid;
		public $domain_name;
		public $dialplan_uuid;
		public $context;
		public $fax_uuid;
		public $fax_name;
		public $fax_extension;
		public $fax_email;
		public $fax_pin_number;
		public $fax_caller_id_name;
		public $fax_caller_id_number;
		public $fax_forward_number;
		public $fax_user_list;
		public $fax_description;

		public function __construct() {
			require_once "resources/classes/database.php";
			$this->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function count() {
			$database = new database;
			$database->domain_uuid = $this->domain_uuid;
			$database->table = "v_fax";
			$database->where[0]['name'] = 'domain_uuid';
			$database->where[0]['value'] = $this->domain_uuid;
			$database->where[0]['operator'] = '=';
			return $database->count();
		}

		public function find() {
			$database = new database;
			$database->table = "v_fax";
			$database->where[0]['name'] = 'domain_uuid';
			$database->where[0]['value'] = $this->domain_uuid;
			$database->where[0]['operator'] = '=';
			if ($this->fax_uuid) {
				$database->where[1]['name'] = 'fax_uuid';
				$database->where[1]['value'] = $this->fax_uuid;
				$database->where[1]['operator'] = '=';
			}
			if ($this->order_by) {
				$database->order_by = $this->order_by;
			}
			if ($this->order_type) {
				$database->order_type = $this->order_type;
			}
			return $database->find();
		}

		public function add() {

			//add the fax
				if (strlen($this->fax_extension) > 0) {

					//add the dialplan
						$database = new database;
						$database->table = "v_dialplans";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_name'] = $this->fax_name;
						$database->fields['dialplan_order'] = '333';
						$database->fields['dialplan_context'] = $this->context;
						$database->fields['dialplan_enabled'] = $this->fax_enabled;
						$database->fields['dialplan_description'] = $this->fax_description;
						$database->fields['app_uuid'] = $this->app_uuid;
						$database->add();

					//add the dialplan details
						$detail_data = '^'.$this->fax_extension.'$';
						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'condition'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'destination_number';
						$database->fields['dialplan_detail_data'] = $detail_data;
						$database->fields['dialplan_detail_order'] = '005';
						$database->add();

						if (file_exists(PHP_BINDIR."/php5")) { define(PHP_BIN, 'php5'); }
						if (file_exists(PHP_BINDIR."/php.exe")) {  define(PHP_BIN, 'php.exe'); }
						$dialplan_detail_data = "api_hangup_hook=system ".PHP_BINDIR."/".PHP_BIN." ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/fax_to_email.php ";
						$dialplan_detail_data .= "email=".$this->fax_email." ";
						$dialplan_detail_data .= "extension=".$this->fax_extension." ";
						$dialplan_detail_data .= "name=\\\\\\\${last_fax} ";
						$dialplan_detail_data .= "messages='result: \\\\\\\${fax_result_text} sender:\\\\\\\${fax_remote_station_id} pages:\\\\\\\${fax_document_total_pages}' ";
						$dialplan_detail_data .= "domain=".$domain_name." ";
						$dialplan_detail_data .= "caller_id_name='\\\\\\\${caller_id_name}' ";
						$dialplan_detail_data .= "caller_id_number=\\\\\\\${caller_id_number} ";
						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'set';
						$database->fields['dialplan_detail_data'] = $dialplan_detail_data;
						$database->fields['dialplan_detail_order'] = '010';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'answer';
						$database->fields['dialplan_detail_data'] = '';
						$database->fields['dialplan_detail_order'] = '015';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'set';
						$database->fields['dialplan_detail_data'] = 'fax_enable_t38=true';
						$database->fields['dialplan_detail_order'] = '020';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'set';
						$database->fields['dialplan_detail_data'] = 'fax_enable_t38_request=true';
						$database->fields['dialplan_detail_order'] = '025';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'playback';
						$database->fields['dialplan_detail_data'] = 'silence_stream://2000';
						$database->fields['dialplan_detail_order'] = '030';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'set';
						$database->fields['dialplan_detail_data'] = 'last_fax=${caller_id_number}-${strftime(%Y-%m-%d-%H-%M-%S)}';
						$database->fields['dialplan_detail_order'] = '035';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'rxfax';
						$dialplan_detail_data = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domains'][$this->domain_uuid]['domain_name'].'/'.$this->fax_extension.'/inbox/${last_fax}.tif';
						$database->fields['dialplan_detail_data'] = $dialplan_detail_data;
						$database->fields['dialplan_detail_order'] = '040';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'hangup';
						$database->fields['dialplan_detail_data'] = '';
						$database->fields['dialplan_detail_order'] = '045';
						$database->add();
					}

				//add the fax
					$fax_uuid = uuid();
					$database = new database;
					$database->table = "v_fax";
					$database->fields['domain_uuid'] = $this->domain_uuid;
					if (strlen($this->fax_extension) > 0) {
						$database->fields['fax_extension'] = $this->fax_extension;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
					}
					$database->fields['fax_uuid'] = $this->fax_uuid;
					$database->fields['fax_name'] = $this->fax_name;
					$database->fields['fax_email'] = $this->fax_email;
					$database->fields['fax_pin_number'] = $this->fax_pin_number;
					$database->fields['fax_caller_id_name'] = $this->fax_caller_id_name;
					$database->fields['fax_caller_id_number'] = $this->fax_caller_id_number;
					$database->fields['fax_forward_number'] = $this->fax_forward_number;
					$database->fields['fax_user_list'] = $this->fax_user_list;
					$database->fields['fax_description'] = $this->fax_description;
					$database->add();
		}

		public function update() {

			//udate the fax
				//get the dialplan uuid
					$database = new database;
					$database->table = "v_fax";
					$database->where[0]['name'] = 'domain_uuid';
					$database->where[0]['value'] = $this->domain_uuid;
					$database->where[0]['operator'] = '=';
					$database->where[1]['name'] = 'fax_uuid';
					$database->where[1]['value'] = $this->fax_uuid;
					$database->where[1]['operator'] = '=';
					$result = $database->find();
					foreach($result as $row) {
						$this->dialplan_uuid = $row['dialplan_uuid'];
					}

				//if the extension number is empty and the dialplan exists then delete the dialplan
					if (strlen($this->fax_extension) == 0) {
						if (strlen($this->dialplan_uuid) > 0) {
							//delete dialplan entry
								$database = new database;
								$database->table = "v_dialplan_details";
								$database->where[0]['name'] = 'domain_uuid';
								$database->where[0]['value'] = $this->domain_uuid;
								$database->where[0]['operator'] = '=';
								$database->where[1]['name'] = 'dialplan_uuid';
								$database->where[1]['value'] = $this->dialplan_uuid;
								$database->where[1]['operator'] = '=';
								$database->delete();

							//delete the child dialplan information
								$database = new database;
								$database->table = "v_dialplans";
								$database->where[0]['name'] = 'domain_uuid';
								$database->where[0]['value'] = $this->domain_uuid;
								$database->where[0]['operator'] = '=';
								$database->where[1]['name'] = 'dialplan_uuid';
								$database->where[1]['value'] = $this->dialplan_uuid;
								$database->where[1]['operator'] = '=';
								$database->delete();
							//update the table to remove the dialplan_uuid
								$this->dialplan_uuid = '';
						}
					}

				//update the fax
					$fax_uuid = uuid();
					$database = new database;
					$database->table = "v_fax";
					$database->fields['fax_uuid'] = $this->fax_uuid;
					$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
					$database->fields['domain_uuid'] = $this->domain_uuid;
					$database->fields['fax_name'] = $this->fax_name;
					$database->fields['fax_extension'] = $this->fax_extension;
					$database->fields['fax_email'] = $this->fax_email;
					$database->fields['fax_pin_number'] = $this->fax_pin_number;
					$database->fields['fax_caller_id_name'] = $this->fax_caller_id_name;
					$database->fields['fax_caller_id_number'] = $this->fax_caller_id_number;
					$database->fields['fax_forward_number'] = $this->fax_forward_number;
					$database->fields['fax_user_list'] = $this->fax_user_list;
					$database->fields['fax_description'] = $this->fax_description;
					$database->where[0]['name'] = 'domain_uuid';
					$database->where[0]['value'] = $this->domain_uuid;
					$database->where[0]['operator'] = '=';
					$database->where[1]['name'] = 'fax_uuid';
					$database->where[1]['value'] = $this->fax_uuid;
					$database->where[1]['operator'] = '=';
					$database->update();

				if (strlen($this->fax_extension) > 0) {
					//update the dialplan
						$database = new database;
						$database->table = "v_dialplans";
						$database->fields['dialplan_name'] = $this->fax_name;
						$database->fields['dialplan_order'] = '333';
						$database->fields['dialplan_context'] = $this->context;
						$database->fields['dialplan_enabled'] = $this->fax_enabled;
						$database->fields['dialplan_description'] = $this->dialplan_description;
						$database->fields['app_uuid'] = $this->app_uuid;
						if ($this->dialplan_uuid) {
							$database->where[0]['name'] = 'domain_uuid';
							$database->where[0]['value'] = $this->domain_uuid;
							$database->where[0]['operator'] = '=';
							$database->where[1]['name'] = 'dialplan_uuid';
							$database->where[1]['value'] = $this->dialplan_uuid;
							$database->where[1]['operator'] = '=';
							$database->update();
						}
						else {
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->add();
						}

					//delete the old dialplan details to prepare for new details
						$database = new database;
						$database->table = "v_dialplan_details";
						$database->where[0]['name'] = 'domain_uuid';
						$database->where[0]['value'] = $this->domain_uuid;
						$database->where[0]['operator'] = '=';
						$database->where[1]['name'] = 'dialplan_uuid';
						$database->where[1]['value'] = $this->dialplan_uuid;
						$database->where[1]['operator'] = '=';
						$database->delete();

					//add the dialplan details
						$detail_data = '^'.$this->fax_extension.'$';
						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'condition'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'destination_number';
						$database->fields['dialplan_detail_data'] = $detail_data;
						$database->fields['dialplan_detail_order'] = '005';
						$database->add();

						if (file_exists(PHP_BINDIR."/php")) { define(PHP_BIN, 'php'); }
						if (file_exists(PHP_BINDIR."/php.exe")) {  define(PHP_BIN, 'php.exe'); }
						$dialplan_detail_data = "api_hangup_hook=system ".PHP_BINDIR."/".PHP_BIN." ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/fax_to_email.php ";
						$dialplan_detail_data .= "email=".$this->fax_email." ";
						$dialplan_detail_data .= "extension=".$this->fax_extension." ";
						$dialplan_detail_data .= "name=\\\\\\\${last_fax} ";
						$dialplan_detail_data .= "messages='result: \\\\\\\${fax_result_text} sender:\\\\\\\${fax_remote_station_id} pages:\\\\\\\${fax_document_total_pages}' ";
						$dialplan_detail_data .= "domain=".$domain_name." ";
						$dialplan_detail_data .= "caller_id_name='\\\\\\\${caller_id_name}' ";
						$dialplan_detail_data .= "caller_id_number=\\\\\\\${caller_id_number} ";
						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'set';
						$database->fields['dialplan_detail_data'] = $dialplan_detail_data;
						$database->fields['dialplan_detail_order'] = '010';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'answer';
						$database->fields['dialplan_detail_data'] = '';
						$database->fields['dialplan_detail_order'] = '015';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'set';
						$database->fields['dialplan_detail_data'] = 'fax_enable_t38=true';
						$database->fields['dialplan_detail_order'] = '020';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'set';
						$database->fields['dialplan_detail_data'] = 'fax_enable_t38_request=true';
						$database->fields['dialplan_detail_order'] = '025';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'playback';
						$database->fields['dialplan_detail_data'] = 'silence_stream://2000';
						$database->fields['dialplan_detail_order'] = '030';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'set';
						$database->fields['dialplan_detail_data'] = 'last_fax=${caller_id_number}-${strftime(%Y-%m-%d-%H-%M-%S)}';
						$database->fields['dialplan_detail_order'] = '035';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'rxfax';
						$dialplan_detail_data = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domains'][$this->domain_uuid]['domain_name'].'/'.$this->fax_extension.'/inbox/${last_fax}.tif';
						$database->fields['dialplan_detail_data'] = $dialplan_detail_data;
						$database->fields['dialplan_detail_order'] = '040';
						$database->add();

						$database->table = "v_dialplan_details";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['dialplan_detail_uuid'] = uuid();
						$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
						$database->fields['dialplan_detail_type'] = 'hangup';
						$database->fields['dialplan_detail_data'] = '';
						$database->fields['dialplan_detail_order'] = '045';
						$database->add();
				}
		}

		function delete() {
			//create the database object
				$database = new database;

			//start the transaction
				//$count = $database->db->exec("BEGIN;");

			//delete the fax
				if (strlen($this->fax_uuid) > 0) {
					$database->table = "v_fax";
					$database->where[0]['name'] = 'domain_uuid';
					$database->where[0]['value'] = $this->domain_uuid;
					$database->where[0]['operator'] = '=';
					$database->where[1]['name'] = 'fax_uuid';
					$database->where[1]['value'] = $this->fax_uuid;
					$database->where[1]['operator'] = '=';
					$database->delete();
					unset($this->fax_uuid);
				}

			//delete the fax
				if (strlen($this->fax_uuid) == 0) {
					//select the dialplan entries
						$database->table = "v_fax";
						$database->where[0]['name'] = 'domain_uuid';
						$database->where[0]['value'] = $this->domain_uuid;
						$database->where[0]['operator'] = '=';
						$database->where[1]['name'] = 'fax_uuid';
						$database->where[1]['value'] = $this->fax_uuid;
						$database->where[1]['operator'] = '=';
						$result = $database->find();
						foreach($result as $row) {
							$this->dialplan_uuid = $row['dialplan_uuid'];
							//delete the child dialplan information
								$database->table = "v_dialplan_details";
								$database->where[0]['name'] = 'domain_uuid';
								$database->where[0]['value'] = $this->domain_uuid;
								$database->where[0]['operator'] = '=';
								$database->where[1]['name'] = 'dialplan_uuid';
								$database->where[1]['value'] = $this->dialplan_uuid;
								$database->where[1]['operator'] = '=';
								$database->delete();
							//delete the dialplan information
								$database->table = "v_dialplans";
								$database->where[0]['name'] = 'domain_uuid';
								$database->where[0]['value'] = $this->domain_uuid;
								$database->where[0]['operator'] = '=';
								$database->where[1]['name'] = 'dialplan_uuid';
								$database->where[1]['value'] = $this->dialplan_uuid;
								$database->where[1]['operator'] = '=';
								$database->delete();
						}

						//delete the fax
							if (strlen($this->fax_uuid) > 0) {
								$database->table = "v_fax";
								$database->where[0]['name'] = 'domain_uuid';
								$database->where[0]['value'] = $this->domain_uuid;
								$database->where[0]['operator'] = '=';
								$database->where[1]['name'] = 'fax_uuid';
								$database->where[1]['value'] = $this->fax_uuid;
								$database->where[1]['operator'] = '=';
								$database->delete();
								unset($this->fax_uuid);
							}

					//commit the transaction
						//$count = $database->db->exec("COMMIT;");
				}
		}
	}
/*
require_once "resources/classes/database.php";
require_once "resources/classes/fax.php";
$fax = new switch_fax;
$fax->domain_uuid = $_SESSION["domain_uuid"];
print_r($fax->find());
*/
?>