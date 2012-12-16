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
		public $extension_uuid;
		public $forward_all_destination;
		public $forward_all_enabled;

		public function update() {
			global $db;
			$sql = "update v_extensions set ";
			$sql .= "forward_all_destination = '$this->forward_all_destination', ";
			$sql .= "forward_all_enabled = '$this->forward_all_enabled' ";
			$sql .= "where domain_uuid = '$this->domain_uuid' ";
			$sql .= "and extension_uuid = '$this->extension_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);
		} //end function

	}

?>