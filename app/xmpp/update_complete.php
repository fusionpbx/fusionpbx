<meta http-equiv="refresh" content="2;url=xmpp.php">
<div align='center'>
<?php
switch ($action) {
	case "add" : 		echo $text['message-add']."\n"; 	break;
	case "update" : 	echo $text['message-update']."\n";	break;
	case "delete" :		echo $text['message-delete']."\n";	break;
}
?>
</div>
