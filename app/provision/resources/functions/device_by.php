<?php

function device_by_mac($device_address) {
	$sql = "select * from v_devices ";
	$sql .= "where device_address = :device_address ";
	$sql .= "and device_enabled = 'true' ";
	$parameters['device_address'] = $device_address;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	return is_array($row) && @sizeof($row) != 0 ? $row : false;
}

function device_by_ext($ext, $domain) {
	$sql = "select t1.* ";
	$sql .= "from v_devices t1 ";
	$sql .- "inner join v_device_lines t2 on t1.device_uuid = t2.device_uuid ";
	$sql .= "inner join v_domains t3 on t2.domain_uuid = t3.domain_uuid ";
	$sql .= "where t2.user_id = :ext ";
	$sql .= "and t3.domain_name = :domain ";
	$sql .= "and t3.domain_enabled = 'true' ";
	$sql .= "and t1.device_enabled = 'true' ";
	$parameters['ext'] = $ext;
	$parameters['domain'] = $domain;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	return is_array($row) && @sizeof($row) != 0 ? $row : false;
}

?>