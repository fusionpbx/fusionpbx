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
	Portions created by the Initial Developer are Copyright (C) 2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Matthew Vale <github@mafoo.org>
*/
include "root.php";

//define the switch_recordings class
	class switch_recordings {

		public $domain_uuid;

		public function __construct() {
			$this->domain_uuid = $_SESSION['domain_uuid'];
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function list_recordings() {
			$sql = "select recording_uuid, recording_filename, recording_base64 ";
			$sql .= "from v_recordings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$database = new database;
			$result = $database->select($sql, $parameters, 'all');
			if (is_array($result) && @sizeof($result) != 0) {
				foreach ($result as &$row) {
					$recordings[$_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name']."/".$row['recording_filename']] = $row['recording_filename'];
				}
			}
			unset($sql, $parameters, $result, $row);
			return $recordings;
		}
	
	}

?>