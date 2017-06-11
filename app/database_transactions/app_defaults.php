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
	Matthew Vale <github@mafoo.org>
*/

	if ($domains_processed == 1) {
		//check if the SYSTEM_USER has been defined
			$sql = "select count(*) as num_rows from v_users ";
			$sql .= "where user_uuid = '00000000-0000-0000-0000-000000000000' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($result['num_rows'] == 0) {
					$sql = "insert into v_users ";
					$sql .= "(";
					$sql .= "user_uuid, ";
					$sql .= "domain_uuid, ";
					$sql .= "username, ";
					$sql .= "user_enabled ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'00000000-0000-0000-0000-000000000000', ";
					$sql .= "'00000000-0000-0000-0000-000000000000', ";
					$sql .= "'SYSTEM_USER', ";
					$sql .= "'false' ";
					$sql .= ");";
					$db->exec($sql);
					if ($display_type == "text") {
						echo "Added SYSTEM_USER\n";
					}
				}
			}
			unset($result, $sql, $prep_statement);
	}
?>
