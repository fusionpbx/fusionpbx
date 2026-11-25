<?php

	$view['name'] = "view_domains";
	$view['version'] = "20260119.1";
	$view['description'] = "Show the domains with the parent domain, level and the path of the domain from the top of the tree to the domain.";
	$view['sql']  = "	with recursive relations as ( \n";
	$view['sql'] .= "		select 1 as level, domain_uuid, domain_parent_uuid, domain_name, null as parent_domain, cast(domain_enabled as text), domain_description, array[domain_uuid] as domain_uuid_path, array[domain_name] as domain_name_path \n";
	$view['sql'] .= "			from v_domains \n";
	$view['sql'] .= "			where domain_parent_uuid is null \n";
	$view['sql'] .= "		union all \n";
	$view['sql'] .= "		select r.level + 1 as level, d.domain_uuid, d.domain_parent_uuid, d.domain_name, (select domain_name from v_domains where domain_uuid = d.domain_parent_uuid) as parent_domain, cast(d.domain_enabled as text), d.domain_description, r.domain_uuid_path || d.domain_uuid, r.domain_name_path || d.domain_name \n";
	$view['sql'] .= "			from v_domains as d \n";
	$view['sql'] .= "			join relations as r on d.domain_parent_uuid = r.domain_uuid \n";
	$view['sql'] .= "	) \n";
	$view['sql'] .= "	select level, domain_uuid, domain_parent_uuid, domain_name, parent_domain, domain_enabled, domain_description, domain_uuid_path, domain_name_path \n";
	$view['sql'] .= "	from relations \n";
	$view['sql'] .= "	order by domain_name_path \n";
