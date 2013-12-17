<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('device_key_delete')) {
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
		$device_uuid = check_str($_GET["device_uuid"]);
	}

if (strlen($id)>0) {
	//delete device_key
		$sql = "delete from v_device_keys ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and device_key_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);
}

//redirect the user
	require_once "resources/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=device_edit.php?id=$device_uuid\">\n";
	echo "<div align='center'>\n";
	echo $text['message-delete']."\n";
	echo "</div>\n";
	require_once "resources/footer.php";
	return;

?>