<?php

$view['name'] = "view_extensions";
$view['version'] = "20251207";
$view['description'] = "Show the extensions with assigned users";

$view['sql']  = "SELECT \n";
$view['sql'] .= "    e.*, \n";
$view['sql'] .= "    ( \n";
$view['sql'] .= "        SELECT json_agg(u.*) \n";
$view['sql'] .= "        FROM v_extension_users AS u \n";
$view['sql'] .= "        WHERE u.extension_uuid = e.extension_uuid \n";
$view['sql'] .= "    ) AS extension_users \n";
$view['sql'] .= "FROM v_extensions AS e;\n";
