<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('fax_log_delete')) {
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
		$fax_uuid = check_str($_GET["fax_uuid"]);
	}

if (strlen($id)>0) {
	//delete fax_log
		$sql = "delete from v_fax_logs ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and fax_log_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);
}

//redirect the user
	$_SESSION['message'] = $text['message-delete'];
	header('Location: fax_logs.php?id='.$fax_uuid);

?>