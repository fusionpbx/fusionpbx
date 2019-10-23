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
				$array['follow_me'][0]["dial_string"] = '';

			//add extensions to the array
				$array['extensions'][0]["extension_uuid"] = $extension_uuid;
				$array['extensions'][0]["dial_domain"] = $this->domain_name;
				$array['extensions'][0]["dial_string"] = '';
				$array['extensions'][0]["follow_me_destinations"] = '';
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
