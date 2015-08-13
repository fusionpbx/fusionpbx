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
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get the id
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
		$access_control_uuid = check_str($_GET["access_control_uuid"]);
	}

if (strlen($id)>0) {
	//delete access_control_node
		$sql = "delete from v_access_control_nodes ";
		$sql .= "where access_control_node_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);
}

//redirect the user
	$_SESSION['message'] = $text['message-delete'];
	header('Location: access_control_node_edit.php?id='.$access_control_uuid);


?>