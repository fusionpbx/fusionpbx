<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('conference_profile_add') || permission_exists('conference_profile_edit')) {
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
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$conference_profile_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$profile_name = check_str($_POST["profile_name"]);
		$profile_enabled = check_str($_POST["profile_enabled"]);
		$profile_description = check_str($_POST["profile_description"]);
	}
//check to see if the http post exists
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
	
		//get the uuid
			if ($action == "update") {
				$conference_profile_uuid = check_str($_POST["conference_profile_uuid"]);
			}
	
		//check for all required data
			$msg = '';
			if (strlen($profile_name) == 0) { $msg .= $text['message-required']." ".$text['label-profile_name']."<br>\n"; }
			if (strlen($profile_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-profile_enabled']."<br>\n"; }
			//if (strlen($profile_description) == 0) { $msg .= $text['message-required']." ".$text['label-profile_description']."<br>\n"; }
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
				if ($action == "add" && permission_exists('conference_profile_add')) {
					$sql = "insert into v_conference_profiles ";
					$sql .= "(";
					//$sql .= "domain_uuid, ";
					$sql .= "conference_profile_uuid, ";
					$sql .= "profile_name, ";
					$sql .= "profile_enabled, ";
					$sql .= "profile_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					//$sql .= "'$domain_uuid', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'$profile_name', ";
					$sql .= "'$profile_enabled', ";
					$sql .= "'$profile_description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
	
					messages::add($text['message-add']);
					header("Location: conference_profiles.php");
					return;
	
				} //if ($action == "add")
	
				if ($action == "update" && permission_exists('conference_profile_edit')) {
					$sql = "update v_conference_profiles set ";
					$sql .= "profile_name = '$profile_name', ";
					$sql .= "profile_enabled = '$profile_enabled', ";
					$sql .= "profile_description = '$profile_description' ";
					$sql .= "where conference_profile_uuid = '$conference_profile_uuid'";
					//$sql .= "and domain_uuid = '$domain_uuid' ";
					$db->exec(check_sql($sql));
					unset($sql);
	
					messages::add($text['message-update']);
					header("Location: conference_profiles.php");
					return;
	
				} //if ($action == "update")
			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$conference_profile_uuid = check_str($_GET["id"]);
		$sql = "select * from v_conference_profiles ";
		$sql .= "where conference_profile_uuid = '$conference_profile_uuid' ";
		//$sql .= "and domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

		foreach ($result as &$row) {
			$profile_name = $row["profile_name"];
			$profile_enabled = $row["profile_enabled"];
			$profile_description = $row["profile_description"];
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-conference_profile']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='conference_profiles.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='profile_name' maxlength='255' value=\"".escape($profile_name)."\">\n";
	echo "<br />\n";
	echo $text['description-profile_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='profile_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($profile_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($profile_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-profile_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='profile_description' maxlength='255' value=\"".escape($profile_description)."\">\n";
	echo "<br />\n";
	echo $text['description-profile_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='conference_profile_uuid' value='".escape($conference_profile_uuid)."'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

	if ($action == "update") {
		require "conference_profile_params.php";
	}

//include the footer
	require_once "resources/footer.php";

?>
