<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('device_key_add') || permission_exists('device_key_edit')) {
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

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$device_key_uuid = check_str($_REQUEST["id"]);
		$device_uuid = check_str($_REQUEST["device_uuid"]);
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (strlen($_GET["device_key_uuid"]) > 0) {
		$device_key_uuid = check_str($_GET["device_key_uuid"]);
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$device_key_id = check_str($_POST["device_key_id"]);
		$device_key_type = check_str($_POST["device_key_type"]);
		$device_key_value = check_str($_POST["device_key_value"]);
		$device_key_label = check_str($_POST["device_key_label"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$device_key_uuid = check_str($_POST["device_key_uuid"]);
	}

	//check for all required data
		//if (strlen($device_key_id) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_id']."<br>\n"; }
		//if (strlen($device_key_type) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_type']."<br>\n"; }
		//if (strlen($device_key_value) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_value']."<br>\n"; }
		//if (strlen($device_key_label) == 0) { $msg .= $text['message-required']." ".$text['label-device_key_label']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
			return;
		}

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('device_key_add')) {
				$sql = "insert into v_device_keys ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "device_key_uuid, ";
				$sql .= "device_uuid, ";
				$sql .= "device_key_id, ";
				$sql .= "device_key_type, ";
				$sql .= "device_key_value, ";
				$sql .= "device_key_label ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$device_uuid', ";
				$sql .= "'$device_key_id', ";
				$sql .= "'$device_key_type', ";
				$sql .= "'$device_key_value', ";
				$sql .= "'$device_key_label' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "resources/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=device_edit.php?id=$device_uuid\">\n";
				echo "<div align='center'>\n";
				echo "	".$text['message-add']."\n";
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('device_key_edit')) {
				$sql = "update v_device_keys set ";
				$sql .= "device_key_id = '$device_key_id', ";
				$sql .= "device_key_type = '$device_key_type', ";
				$sql .= "device_key_value = '$device_key_value', ";
				$sql .= "device_key_label = '$device_key_label' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and device_key_uuid = '$device_key_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "resources/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=device_edit.php?id=$device_uuid\">\n";
				echo "<div align='center'>\n";
				echo "	".$text['message-update']."\n";
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true") 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$device_key_uuid = check_str($_GET["id"]);
		$sql = "select * from v_device_keys ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and device_key_uuid = '$device_key_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$device_key_id = $row["device_key_id"];
			$device_key_type = $row["device_key_type"];
			$device_key_value = $row["device_key_value"];
			$device_key_label = $row["device_key_label"];
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-device_key']."</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='device_key_edit.php?id=$device_key_uuid'\" value='".$text['button-back']."'></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_id'].": \n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='device_key_id'>\n";
	echo "	<option value=''></option>\n";
	if ($device_key_id == "1") { 
		echo "	<option value='1' selected='selected'>1</option>\n";
	}
	else {
		echo "	<option value='1'>1</option>\n";
	}
	if ($device_key_id == "2") { 
		echo "	<option value='2' selected='selected'>2</option>\n";
	}
	else {
		echo "	<option value='2'>2</option>\n";
	}
	if ($device_key_id == "3") { 
		echo "	<option value='3' selected='selected'>3</option>\n";
	}
	else {
		echo "	<option value='3'>3</option>\n";
	}
	if ($device_key_id == "4") { 
		echo "	<option value='4' selected='selected'>4</option>\n";
	}
	else {
		echo "	<option value='4'>4</option>\n";
	}
	if ($device_key_id == "5") { 
		echo "	<option value='5' selected='selected'>5</option>\n";
	}
	else {
		echo "	<option value='5'>5</option>\n";
	}
	if ($device_key_id == "6") { 
		echo "	<option value='6' selected='selected'>6</option>\n";
	}
	else {
		echo "	<option value='6'>6</option>\n";
	}
	if ($device_key_id == "7") { 
		echo "	<option value='7' selected='selected'>7</option>\n";
	}
	else {
		echo "	<option value='7'>7</option>\n";
	}
	if ($device_key_id == "8") { 
		echo "	<option value='8' selected='selected'>8</option>\n";
	}
	else {
		echo "	<option value='8'>8</option>\n";
	}
	if ($device_key_id == "9") { 
		echo "	<option value='9' selected='selected'>9</option>\n";
	}
	else {
		echo "	<option value='9'>9</option>\n";
	}
	if ($device_key_id == "10") { 
		echo "	<option value='10' selected='selected'>10</option>\n";
	}
	else {
		echo "	<option value='10'>10</option>\n";
	}
	if ($device_key_id == "11") { 
		echo "	<option value='11' selected='selected'>11</option>\n";
	}
	else {
		echo "	<option value='11'>11</option>\n";
	}
	if ($device_key_id == "12") { 
		echo "	<option value='12' selected='selected'>12</option>\n";
	}
	else {
		echo "	<option value='12'>12</option>\n";
	}
	if ($device_key_id == "13") { 
		echo "	<option value='13' selected='selected'>13</option>\n";
	}
	else {
		echo "	<option value='13'>13</option>\n";
	}
	if ($device_key_id == "14") { 
		echo "	<option value='14' selected='selected'>14</option>\n";
	}
	else {
		echo "	<option value='14'>14</option>\n";
	}
	if ($device_key_id == "15") { 
		echo "	<option value='15' selected='selected'>15</option>\n";
	}
	else {
		echo "	<option value='15'>15</option>\n";
	}
	if ($device_key_id == "16") { 
		echo "	<option value='16' selected='selected'>16</option>\n";
	}
	else {
		echo "	<option value='16'>16</option>\n";
	}
	if ($device_key_id == "17") { 
		echo "	<option value='17' selected='selected'>17</option>\n";
	}
	else {
		echo "	<option value='17'>17</option>\n";
	}
	if ($device_key_id == "18") { 
		echo "	<option value='18' selected='selected'>18</option>\n";
	}
	else {
		echo "	<option value='18'>18</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-device_key_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_type'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='device_key_type'>\n";
	echo "	<option value=''></option>\n";
	if ($device_key_type == "line") { 
		echo "	<option value='line' selected='selected'>".$text['label-line']."</option>\n";
	}
	else {
		echo "	<option value='line'>".$text['label-line']."</option>\n";
	}
	if ($device_key_type == "blf") { 
		echo "	<option value='blf' selected='selected'>".$text['label-blf']."</option>\n";
	}
	else {
		echo "	<option value='blf'>".$text['label-blf']."</option>\n";
	}
	if ($device_key_type == "park") { 
		echo "	<option value='park' selected='selected'>".$text['label-park']."</option>\n";
	}
	else {
		echo "	<option value='park'>".$text['label-park']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-device_key_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_value'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_key_value' maxlength='255' value=\"$device_key_value\">\n";
	echo "<br />\n";
	echo $text['description-device_key_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_key_label'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_key_label' maxlength='255' value=\"$device_key_label\">\n";
	echo "<br />\n";
	echo $text['description-device_key_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='device_uuid' value='$device_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='device_key_uuid' value='$device_key_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "resources/footer.php";
?>