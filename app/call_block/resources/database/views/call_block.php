<?php

$view['name'] = "view_call_block";
$view['version'] = "20251207";
$view['description'] = "Show the call block details with extension information.";

$view['sql']  = "SELECT \n";
$view['sql'] .= "    c.domain_uuid, \n";
$view['sql'] .= "    c.call_block_uuid, \n";
$view['sql'] .= "    c.call_block_direction, \n";
$view['sql'] .= "    c.extension_uuid, \n";
$view['sql'] .= "    c.call_block_name, \n";
$view['sql'] .= "    c.call_block_country_code, \n";
$view['sql'] .= "    c.call_block_number, \n";
$view['sql'] .= "    e.extension, \n";
$view['sql'] .= "    e.number_alias, \n";
$view['sql'] .= "    c.call_block_count, \n";
$view['sql'] .= "    c.call_block_app, \n";
$view['sql'] .= "    c.call_block_data, \n";
$view['sql'] .= "    c.date_added, \n";
$view['sql'] .= "    c.call_block_enabled, \n";
$view['sql'] .= "    c.call_block_description, \n";
$view['sql'] .= "    c.insert_date, \n";
$view['sql'] .= "    c.insert_user, \n";
$view['sql'] .= "    c.update_date, \n";
$view['sql'] .= "    c.update_user \n";
$view['sql'] .= "FROM v_call_block AS c \n";
$view['sql'] .= "LEFT JOIN v_extensions AS e \n";
$view['sql'] .= "    ON c.extension_uuid = e.extension_uuid;\n";

