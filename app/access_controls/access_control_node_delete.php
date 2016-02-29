<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
		$access_control_uuid = check_str($_GET["access_control_uuid"]);
	}

//delete access_control_node
	if (strlen($id)>0) {
		$sql = "delete from v_access_control_nodes ";
		$sql .= "where access_control_node_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);
		remove_config_from_cache('configuration:acl.conf');
	}

//redirect the user
	$_SESSION['message'] = $text['message-delete'];
	header('Location: access_control_edit.php?id='.$access_control_uuid);

?>