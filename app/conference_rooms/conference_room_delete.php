<?php
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('conference_room_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if (count($_GET)>0) {
	$id = check_str($_GET["id"]);
}

if (strlen($id)>0) {
	//get the meeting_uuid
		if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
			$conference_room_uuid = check_str($_GET["id"]);
			$sql = "select * from v_conference_rooms ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and conference_room_uuid = '$conference_room_uuid' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll();
			foreach ($result as &$row) {
				$meeting_uuid = $row["meeting_uuid"];
			}
			unset ($prep_statement);
		}

	//delete the conference session
		$sql = "delete from v_conference_rooms ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and conference_room_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//delete the meeting users
		$sql = "delete from v_meeting_users ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and meeting_uuid = '$meeting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//delete the meeting pins
		$sql = "delete from v_meeting_pins ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and meeting_uuid = '$meeting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//delete the meetings
		$sql = "delete from v_meetings ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and meeting_uuid = '$meeting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);
}

//redirect the user
	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=conference_rooms.php\">\n";
	echo "<div align='center'>\n";
	echo "Delete Complete\n";
	echo "</div>\n";
	require_once "includes/footer.php";
	return;

?>