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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//if the default groups do not exist add them
		$group = new groups;
		$group->defaults();

	//find rows that have a null group_uuid and set the correct group_uuid
		$sql = "select * from v_group_users ";
		$sql .= "where group_uuid is null; ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$db->beginTransaction();
			foreach($result as $row) {
				if (strlen($row['group_name']) > 0) {
					//get the group_uuid
						$sql = "select group_uuid from v_groups ";
						$sql .= "where group_name = '".$row['group_name']."' ";
						$prep_statement_sub = $db->prepare($sql);
						$prep_statement_sub->execute();
						$sub_result = $prep_statement_sub->fetch(PDO::FETCH_ASSOC);
						unset ($prep_statement_sub);
						$group_uuid = $sub_result['group_uuid'];
					//set the group_uuid
						$sql = "update v_group_users set ";
						$sql .= "group_uuid = '".$group_uuid."' ";
						$sql .= "where group_user_uuid = '".$row['group_user_uuid']."'; ";
						$db->exec($sql);
						unset($sql);
				}
			}
			$db->commit();
			unset ($prep_statement);
		}

	//if user_enabled is null then set to enabled true
		$sql = "select count(*) as count from v_users ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and user_enabled is null ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$sub_result = $prep_statement->fetch(PDO::FETCH_ASSOC);
		unset ($prep_statement);
		if ($sub_result['count'] > 0) {
			//begin the transaction
				$db->beginTransaction();
			//send output
				if ($display_type == "text") {
					echo "	Users:	set enabled=true\n";
				}
			//set the user_enabled to true
				$sql = "update v_users set ";
				$sql .= "user_enabled = 'true' ";
				$db->exec($sql);
				unset($sql);
			//end the transaction
				$db->commit();
		}
}

?>