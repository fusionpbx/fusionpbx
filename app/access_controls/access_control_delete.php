<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('access_control_delete')) {
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
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
	}

//delete the data
	if (strlen($id)>0) {
		//delete access_control
			$sql = "delete from v_access_controls ";
			$sql .= "where access_control_uuid = '$id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);

		//delete access_control_node
			$sql = "delete from v_access_control_nodes ";
			$sql .= "where access_control_uuid = '$id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);

		//clear the cache
			$cache = new cache;
			$cache->delete("configuration:acl.conf");
	}

//redirect the user
	messages::add($text['message-delete']);
	header('Location: access_controls.php');


?>
