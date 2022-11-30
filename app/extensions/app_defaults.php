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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if the extensions dir doesn't exist then create it
	if ($domains_processed == 1) {

		//create the directory
			if (strlen($_SESSION['switch']['extensions']['dir']) > 0) {
				if (!is_dir($_SESSION['switch']['extensions']['dir'])) { event_socket_mkdir($_SESSION['switch']['extensions']['dir']); }
			}

		//update the directory first and last names
			$sql = "select * from v_extensions ";
			$sql .= "where directory_first_name <> '' and directory_last_name is null ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$extensions = $prep_statement->fetchall(PDO::FETCH_ASSOC);
				foreach($extensions as $row) {
					$name = explode(' ', $row['directory_first_name']);
					if (strlen($name[1]) > 0) {
						$sql = "UPDATE v_extensions ";
						$sql .= "SET directory_first_name = '".$name[0]."', ";
						$sql .= "directory_last_name = '".$name[1]."' ";
						$sql .= "WHERE extension_uuid = '". $row['extension_uuid'] ."' ";
						$db->exec(check_sql($sql));
						unset($sql);
					}
				}
			}

	}

?>
