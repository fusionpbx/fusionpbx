<?php 
if (PHP_SAPI === 'cli') {
    $argument1 = $argv[1];
} else {
	die();
}

require_once "root.php";
require_once "resources/require.php";

$sql = "select email from v_emails";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
$result_count = count($result);
if ($result_count > 0) {
	foreach($result as $row) {
		$msg = $row['email'];
		
		require_once "secure/v_mailto.php";
		if ($mailer_error == '') {
			$_SESSION["message"] = $text['message-message_resent'];
			if (permission_exists('emails_all') && $_REQUEST['showall'] == 'true') {
				header("Location: email_delete.php?id=".$email_uuid."&showall=true");
			} else {
				header("Location: email_delete.php?id=".$email_uuid);
			}
		}

	}
}
unset ($prep_statement, $sql, $result, $result_count);




exit;

?>