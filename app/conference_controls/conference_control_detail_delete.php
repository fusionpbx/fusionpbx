<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('conference_control_detail_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	if (count($_GET) > 0) {
		$id = check_str($_GET["id"]);
		$conference_control_uuid = check_str($_GET["conference_control_uuid"]);
	}

//delete the data
	if (strlen($id) > 0) {
		//delete conference_control_detail
			$sql = "delete from v_conference_control_details ";
			$sql .= "where conference_control_detail_uuid = '$id' ";
			//$sql .= "and domain_uuid = '$domain_uuid' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);
	}

//redirect the user
	messages::add($text['message-delete']);
	header('Location: conference_control_detail_edit.php?id='.$conference_control_uuid);

?>