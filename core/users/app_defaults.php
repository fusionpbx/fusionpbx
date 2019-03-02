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

	//if the default groups do not exist add them
		$group = new groups;
		$group->defaults();

	//create the user view combines username, organization, contact first and last name
		$sql = "CREATE OR REPLACE VIEW view_users AS (\n";
		$sql .= "	select u.domain_uuid, u.user_uuid, d.domain_name, u.username, u.user_enabled, \n";
		$sql .= "	c.contact_uuid, c.contact_organization, c.contact_name_given, c.contact_name_family,\n";
		$sql .= "	(\n";
		$sql .= "		select\n";
		$sql .= "		string_agg(g.group_name, ', ')\n";
		$sql .= "		from\n";
		$sql .= "		v_user_groups as ug,\n";
		$sql .= "		v_groups as g\n";
		$sql .= "		where\n";
		$sql .= "		ug.group_uuid = g.group_uuid\n";
		$sql .= "		and u.user_uuid = ug.user_uuid\n";
		$sql .= "	) AS groups\n";
		$sql .= "	from v_contacts as c\n";
		$sql .= "	right join v_users u on u.contact_uuid = c.contact_uuid\n";
		$sql .= "	inner join v_domains as d on d.domain_uuid = u.domain_uuid\n";
		$sql .= "	where 1 = 1\n";
		$sql .= "	order by u.username asc\n";
		$sql .= ");\n";
		$db->exec($sql);
		unset($sql);

	//find rows that have a null group_uuid and set the correct group_uuid
		$sql = "select * from v_user_groups ";
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
					//set the user_group_uuid
						$sql = "update v_user_groups set ";
						$sql .= "group_uuid = '".$group_uuid."' ";
						$sql .= "where user_group_uuid = '".$row['user_group_uuid']."'; ";
						$db->exec($sql);
						unset($sql);
				}
			}
			$db->commit();
			unset ($prep_statement);
		}

}

?>
