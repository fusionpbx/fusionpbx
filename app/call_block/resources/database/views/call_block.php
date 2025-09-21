<?php

	$view['name'] = "view_call_block";
	$view['version'] = "20250919";
	$view['description'] = "Show the call block details with extension information.";
	$view['sql'] = "	select domain_uuid, group_uuid, group_name, ";
    $view['sql'] .= "	select c.domain_uuid, call_block_uuid, c.call_block_direction, c.extension_uuid, c.call_block_name, c.call_block_country_code, \n";
	$view['sql'] .= "	c.call_block_number, e.extension, e.number_alias, c.call_block_count, c.call_block_app, c.call_block_data, c.date_added, \n";
	$view['sql'] .= "	c.call_block_enabled, c.call_block_description, c.insert_date, c.insert_user, c.update_date, c.update_user \n";
	$view['sql'] .= "	from v_call_block as c \n";
	$view['sql'] .= " left join v_extensions as e \n";
	$view['sql'] .= "	on c.extension_uuid = e.extension_uuid \n";
