<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('user_log_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the uuid
	$user_log_uuid = $_GET['id'];

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select * from v_user_logs ";
		$sql .= "where user_log_uuid = :user_log_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['user_log_uuid'] = $user_log_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$timestamp = $row["timestamp"];
			$user_uuid = $row["user_uuid"];
			$username = $row["username"];
			$type = $row["type"];
			$result = $row["result"];
			$remote_address = $row["remote_address"];
			$user_agent = $row["user_agent"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-user_log'];
	require_once "resources/header.php";

//get the users
	$sql = "SELECT user_uuid, username FROM v_users ";
	$sql .= "WHERE domain_uuid = :domain_uuid ";
	$sql .= "ORDER by username asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$users = $database->execute($sql, $parameters, 'all');
	unset ($sql, $parameters);

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='user_log_uuid' value='".escape($user_log_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-user_log']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'user_logs.php']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-user_logs']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-domain_uuid']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='domain_uuid'>\n";
	if (strlen($domain_uuid) == 0) {
		echo "		<option value='' selected='selected'>".$text['select-global']."</option>\n";
	}
	else {
		echo "		<option value=''>".$text['label-global']."</option>\n";
	}
	foreach ($_SESSION['domains'] as $row) {
		if ($row['domain_uuid'] == $domain_uuid) {
			echo "		<option value='".$row['domain_uuid']."' selected='selected'>".escape($row['domain_name'])."</option>\n";
		}
		else {
			echo "		<option value='".$row['domain_uuid']."'>".$row['domain_name']."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-domain_uuid']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-timestamp']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='timestamp' maxlength='255' value='".escape($timestamp)."'>\n";
	echo "<br />\n";
	echo $text['description-timestamp']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-user_uuid']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='user_uuid'>\n";
	echo "		<option value=''></option>\n";
	foreach($users as $field) {
		if ($field['user_uuid'] == $user_uuid) { $selected = "selected='selected'"; } else { $selected = ''; }
		echo "		<option value='".$field['user_uuid']."' $selected>".escape($field['username'])."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-user_uuid']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-username']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='username' maxlength='255' value='".escape($username)."'>\n";
	echo "<br />\n";
	echo $text['description-username']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='type' maxlength='255' value='".escape($type)."'>\n";
	echo "<br />\n";
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-result']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<select class='formfld' name='result'>\n";
		echo "		<option value=''></option>\n";
		if ($result == "success") {
			echo "		<option value='success' selected='selected'>".$text['label-success']."</option>\n";
		}
		else {
			echo "		<option value='success'>".$text['label-success']."</option>\n";
		}
		if ($result == "failure") {
			echo "		<option value='failure' selected='selected'>".$text['label-failure']."</option>\n";
		}
		else {
			echo "		<option value='failure'>".$text['label-failure']."</option>\n";
		}
		echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-result']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-remote_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='remote_address' maxlength='255' value='".escape($remote_address)."'>\n";
	echo "<br />\n";
	echo $text['description-remote_address']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-user_agent']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='user_agent' maxlength='255' value='".escape($user_agent)."'>\n";
	echo "<br />\n";
	echo $text['description-user_agent']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>