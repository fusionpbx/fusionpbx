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
					$this->number_alias = $row["number_alias"];
					$this->accountcode = $row["accountcode"];
					$this->toll_allow = $row["toll_allow"];
					$this->outbound_caller_id_name = $row["outbound_caller_id_name"];
					$this->outbound_caller_id_number = $row["outbound_caller_id_number"];
				}
				unset($sql, $parameters, $row);

			//build extension update array
				$array['extensions'][0]['extension_uuid'] = $this->extension_uuid;
				$array['extensions'][0]['forward_all_destination'] = strlen($this->forward_all_destination) != 0 ? $this->forward_all_destination : null;
				if (strlen($this->forward_all_destination) == 0 || $this->forward_all_enabled == "false") {
					$array['extensions'][0]['dial_string'] = null;
					$array['extensions'][0]['forward_all_enabled'] = 'false';
				}
				else {
					$array['extensions'][0]['dial_string'] = $this->dial_string;
					$array['extensions'][0]['forward_all_enabled'] = 'true';
				}

			//grant temporary permissions
				$p = new permissions;
				$p->add('extension_add', 'temp');

			//execute update
				$database = new database;
				$database->app_name = 'calls';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$database->save($array);
				unset($array);

			//grant temporary permissions
				$p = new permissions;
				$p->delete('extension_add', 'temp');

			//delete extension from the cache
				$cache = new cache;
				$cache->delete("directory:".$this->extension."@".$this->domain_name);
				if(strlen($this->number_alias) > 0){
					$cache->delete("directory:".$this->number_alias."@".$this->domain_name);
				}

		}
	}

?>