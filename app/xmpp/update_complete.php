<?php

switch ($action) {
	case "add" : 		$_SESSION["message"] = $text['message-add']; 		break;
	case "update" : 	$_SESSION["message"] = $text['message-update']; 	break;
	case "delete" : 	$_SESSION["message"] = $text['message-delete']; 	break;
}
header("Location: xmpp.php");

?>