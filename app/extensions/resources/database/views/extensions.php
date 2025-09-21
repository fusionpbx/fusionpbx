<?php

$view['name'] = "view_extensions";
$view['version'] = "20250920";
$view['description'] = "Show the extensions with assigned users";
$view['sql'] = "SELECT \n";
$view['sql'] .= "e.*, \n";
$view['sql'] .= "( \n";
$view['sql'] .= "	SELECT json_agg(u.*) \n";
$view['sql'] .= "	FROM v_extension_users as u \n";
$view['sql'] .= "	WHERE u.extension_uuid = e.extension_uuid \n";
$view['sql'] .= ") AS extension_users \n";
$view['sql'] .= "FROM \n";
$view['sql'] .= "	v_extensions as e \n";
$view['sql'] .= "); \n";
