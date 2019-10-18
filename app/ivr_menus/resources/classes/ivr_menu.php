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
	Copyright (C) 2010-2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the ivr_menu class
	class ivr_menu {
		public $domain_uuid;
		public $domain_name;
		public $app_uuid;
		public $order_by;

		public function __construct() {
			$this->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function find() {
			
			$sql = "select * from v_ivr_menus ";
			$sql .= "where domain_uuid = :domain_uuid ";
			if (isset($this->ivr_menu_uuid)) {
				$sql .= "and ivr_menu_uuid = :ivr_menu_uuid ";
				$parameters['ivr_menu_uuid'] = $this->ivr_menu_uuid;
			}
			if (isset($this->order_by)) {
				$sql .= $this->order_by;
			}
			$parameters['domain_uuid'] = $this->domain_uuid;
			$database = new database;
			return $database->select($sql, $parameters, 'all');
		}

	}

?>
