<?php

function device_by_mac($db, $mac) {
	$sql = 'SELECT * FROM v_devices ';
	$sql .= 'WHERE device_mac_address=:mac';
	$sql .= 'AND device_enabled = \'true\' ';

	$prep = $db->prepare(check_sql($sql));
	if ($prep) {
		$prep->bindParam(':mac', $mac);
		$prep->execute();
		$row = $prep->fetch();
		unset($prep);
		return $row;
	}
	return false;
}

function device_by_ext($db, $ext, $domain) {
	$sql = 'select t1.* ';
	$sql .= 'from v_devices t1 inner join v_device_lines t2 on t1.device_uuid=t2.device_uuid ';
	$sql .= 'inner join v_domains t3 on t2.domain_uuid=t3.domain_uuid ';
	$sql .= 'where t2.user_id=:ext ';
	$sql .= 'and t3.domain_name=:domain ';
	$sql .= 'and t3.domain_enabled = \'true\' ';
	$sql .= 'and t1.device_enabled = \'true\' ';

	$prep = $db->prepare(check_sql($sql));
	if ($prep) {
		$prep->bindParam(':ext', $ext);
		$prep->bindParam(':domain', $domain);
		$prep->execute();
		$row = $prep->fetch();
		unset($prep);
		return $row;
	}
	return false;
}
