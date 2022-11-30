<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('access_control_node_delete')) {
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
		$access_control_uuid = check_str($_GET["access_control_uuid"]);
	}

//delete access_control_node
	if (strlen($id) > 0) {
		//update the database
		$sql = "delete from v_access_control_nodes ";
		$sql .= "where access_control_node_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

		//clear the cache
		$cache = new cache;
		$cache->delete("configuration:acl.conf");

		//create the event socket connection
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) { event_socket_request($fp, "api reloadacl"); }
	}

//redirect the browser
	messages::add($text['message-delete']);
	header('Location: access_control_edit.php?id='.$access_control_uuid);

?>
