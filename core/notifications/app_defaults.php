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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/


if ($domains_processed == 1) {

	//update the notifications table
	if (is_array($_SESSION['switch']['scripts'])) {
		$sql = "select count(*) as num_rows from v_notifications ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($row['num_rows'] == 0) {
			$sql = "insert into v_notifications ";
			$sql .= "(";
			$sql .= "notification_uuid, ";
			$sql .= "project_notifications ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".uuid()."', ";
			$sql .= "'false' ";
			$sql .= ")";
			$database = new database;
			$database->execute($sql, null);
			unset($sql);
		}
		unset($prep_statement, $row);
	}

}

?>
