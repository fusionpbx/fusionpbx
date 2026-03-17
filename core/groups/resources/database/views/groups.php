<?php

	$view['name'] = "view_groups";
	$view['version'] = "20250919.1";
	$view['description'] = "Show the groups with the domain_name, permission count and member count.";
	$view['sql'] = "	select domain_uuid, group_uuid, group_name, ";
	$view['sql'] .= "	(select domain_name from v_domains where domain_uuid = g.domain_uuid) as domain_name, ";
	$view['sql'] .= "	(select count(*) from v_group_permissions where group_uuid = g.group_uuid) as group_permissions, ";
	$view['sql'] .= "	(select count(*) from v_user_groups where group_uuid = g.group_uuid) as group_members, ";
	$view['sql'] .= "	group_level, group_protected, group_description ";
	$view['sql'] .= "	from v_groups as g ";
