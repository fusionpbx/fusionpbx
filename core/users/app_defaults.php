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
	//if the are no groups add the default groups
		$sql = "SELECT * FROM v_groups ";
		$sql .= "WHERE domain_uuid is null ";
		$sub_result = $db->query($sql)->fetch();
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$sub_result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			if (count($sub_result) == 0) {
				$x = 0;
				$tmp[$x]['group_name'] = 'superadmin';
				$tmp[$x]['group_description'] = 'Super Administrator Group';
				$tmp[$x]['group_protected'] = 'false';
				$x++;
				$tmp[$x]['group_name'] = 'admin';
				$tmp[$x]['group_description'] = 'Administrator Group';
				$tmp[$x]['group_protected'] = 'false';
				$x++;
				$tmp[$x]['group_name'] = 'user';
				$tmp[$x]['group_description'] = 'User Group';
				$tmp[$x]['group_protected'] = 'false';
				$x++;
				$tmp[$x]['group_name'] = 'public';
				$tmp[$x]['group_description'] = 'Public Group';
				$tmp[$x]['group_protected'] = 'false';
				$x++;
				$tmp[$x]['group_name'] = 'agent';
				$tmp[$x]['group_description'] = 'Call Center Agent Group';
				$tmp[$x]['group_protected'] = 'false';
				foreach($tmp as $row) {
					if (strlen($row['group_name']) > 0) {
						$sql = "insert into v_groups ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "group_uuid, ";
						$sql .= "group_name, ";
						$sql .= "group_description, ";
						$sql .= "group_protected ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "null, ";
						$sql .= "'".uuid()."', ";
						$sql .= "'".$row['group_name']."', ";
						$sql .= "'".$row['group_description']."', ";
						$sql .= "'".$row['group_protected']."' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
					}
				}
			}
			unset($prep_statement, $sub_result);
		}

	//if there are no permissions listed in v_group_permissions then set the default permissions
		$sql = "select count(*) as count from v_group_permissions ";
		$sql .= "where domain_uuid is null ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$sub_result = $prep_statement->fetch(PDO::FETCH_ASSOC);
		unset ($prep_statement);
		if ($sub_result['count'] > 0) {
			if ($display_type == "text") {
				echo "	Group Permissions:	no change\n";
			}
		}
		else {
			if ($display_type == "text") {
				echo "	Group Permissions:	added\n";
			}
			//no permissions found add the defaults
			$db->beginTransaction();
			foreach($apps as $app) {
				foreach ($app['permissions'] as $sub_row) {
					foreach ($sub_row['groups'] as $group) {
						//add the record
						$sql = "insert into v_group_permissions ";
						$sql .= "(";
						$sql .= "group_permission_uuid, ";
						$sql .= "domain_uuid, ";
						$sql .= "permission_name, ";
						$sql .= "group_name ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".uuid()."', ";
						$sql .= "null, ";
						$sql .= "'".$sub_row['name']."', ";
						$sql .= "'".$group."' ";
						$sql .= ")";
						$db->exec($sql);
						unset($sql);
					}
				}
			}
			$db->commit();
		}

	//find rows that have a null group_uuid and set the correct group_uuid
		$sql = "select * from v_group_users ";
		$sql .= "where group_uuid is null; ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
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
			unset ($prep_statement);
		}

	//if there are no permissions listed in v_group_permissions then set the default permissions
		$sql = "select count(*) as count from v_users ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and user_enabled is null ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$sub_result = $prep_statement->fetch(PDO::FETCH_ASSOC);
		unset ($prep_statement);
		if ($sub_result['count'] > 0) {
			//send output
				if ($display_type == "text") {
					echo "	Users:	set enabled=true\n";
				}
			//set the user_enabled to true
				$sql = "update v_users set ";
				$sql .= "user_enabled = 'true' ";
				$db->exec($sql);
				unset($sql);
		}
}

?>