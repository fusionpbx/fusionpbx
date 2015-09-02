<?php

function device_by_mac($db, $mac) {
	$sql = 'SELECT * FROM v_devices ';
	$sql .= 'WHERE device_mac_address=:mac';
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

function device_by_ext($db, $ext) {
	$sql = 'select t1.* ';
	$sql .= 'from v_devices t1 inner join v_device_lines t2 on t1.device_uuid=t2.device_uuid ';
	$sql .= 'where t2.user_id=:ext ';
	$prep = $db->prepare(check_sql($sql));
	if ($prep) {
		$prep->bindParam(':ext', $ext);
		$prep->execute();
		$row = $prep->fetch();
		unset($prep);
		return $row;
	}
	return false;
}

