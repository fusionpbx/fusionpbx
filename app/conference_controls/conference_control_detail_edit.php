<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('conference_control_detail_add') || permission_exists('conference_control_detail_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$conference_control_detail_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (is_uuid($_GET["conference_control_uuid"])) {
		$conference_control_uuid = $_GET["conference_control_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$control_digits = $_POST["control_digits"];
		$control_action = $_POST["control_action"];
		$control_data = $_POST["control_data"];
		$control_enabled = $_POST["control_enabled"];
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	//get the uuid
		if ($action == "update") {
			$conference_control_detail_uuid = $_POST["conference_control_detail_uuid"];
		}

	//check for all required data
		$msg = '';
		//if (strlen($control_digits) == 0) { $msg .= $text['message-required']." ".$text['label-control_digits']."<br>\n"; }
		if (strlen($control_action) == 0) { $msg .= $text['message-required']." ".$text['label-control_action']."<br>\n"; }
		//if (strlen($control_data) == 0) { $msg .= $text['message-required']." ".$text['label-control_data']."<br>\n"; }
		if (strlen($control_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-control_enabled']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
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

			$array['conference_control_details'][0]['conference_control_uuid'] = $conference_control_uuid;
			$array['conference_control_details'][0]['control_digits'] = $control_digits;
			$array['conference_control_details'][0]['control_action'] = $control_action;
			$array['conference_control_details'][0]['control_data'] = $control_data;
			$array['conference_control_details'][0]['control_enabled'] = $control_enabled;

			if ($action == "add" && permission_exists('conference_control_detail_add')) {
				$array['conference_control_details'][0]['conference_control_detail_uuid'] = uuid();
				message::add($text['message-add']);
			}

			if ($action == "update" && permission_exists('conference_control_detail_edit')) {
				$array['conference_control_details'][0]['conference_control_detail_uuid'] = $conference_control_detail_uuid;
				message::add($text['message-update']);
			}

			if (is_uuid($array['conference_control_details'][0]['conference_control_detail_uuid'])) {
				$database = new database;
				$database->app_name = 'conference_controls';
				$database->app_uuid = 'e1ad84a2-79e1-450c-a5b1-7507a043e048';
				$database->save($array);
				unset($array);
			}

			header('Location: conference_control_edit.php?id='.$conference_control_uuid);
			exit;

		}
}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$conference_control_detail_uuid = $_GET["id"];
		$sql = "select * from v_conference_control_details ";
		$sql .= "where conference_control_detail_uuid = :conference_control_detail_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['conference_control_detail_uuid'] = $conference_control_detail_uuid;
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$control_digits = $row["control_digits"];
			$control_action = $row["control_action"];
			$control_data = $row["control_data"];
			$control_enabled = $row["control_enabled"];
		}
		unset($sql, $parameters, $row);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-conference_control_detail']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='conference_control_edit.php?id=".escape($conference_control_uuid)."'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_digits']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='control_digits' maxlength='255' value='".escape($control_digits)."'>\n";
	echo "<br />\n";
	echo $text['description-control_digits']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='control_action' maxlength='255' value=\"".escape($control_action)."\">\n";
	echo "<br />\n";
	echo $text['description-control_action']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_data']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='control_data' maxlength='255' value=\"".escape($control_data)."\">\n";
	echo "<br />\n";
	echo $text['description-control_data']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='control_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($control_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($control_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-control_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='conference_control_uuid' value='".escape($conference_control_uuid)."'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='conference_control_detail_uuid' value='".escape($conference_control_detail_uuid)."'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
