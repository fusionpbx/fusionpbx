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
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$conference_profile_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$profile_name = $_POST["profile_name"];
		$profile_enabled = $_POST["profile_enabled"];
		$profile_description = $_POST["profile_description"];
	}
//check to see if the http post exists
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
	
		//get the uuid
			if ($action == "update") {
				$conference_profile_uuid = $_POST["conference_profile_uuid"];
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

				$array['conference_profiles'][0]['profile_name'] = $profile_name;
				$array['conference_profiles'][0]['profile_enabled'] = $profile_enabled;
				$array['conference_profiles'][0]['profile_description'] = $profile_description;

				if ($action == "add" && permission_exists('conference_profile_add')) {
					$array['conference_profiles'][0]['conference_profile_uuid'] = uuid();
					message::add($text['message-add']);
				}
	
				if ($action == "update" && permission_exists('conference_profile_edit')) {
					$array['conference_profiles'][0]['conference_profile_uuid'] = $conference_profile_uuid;
					message::add($text['message-update']);
				}

				if (is_uuid($array['conference_profiles'][0]['conference_profile_uuid'])) {
					$database = new database;
					$database->app_name = 'conference_profiles';
					$database->app_uuid = 'c33e2c2a-847f-44c1-8c0d-310df5d65ba9';
					$database->save($array);
					unset($array);
				}

				header("Location: conference_profiles.php");
				exit;

			}
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$conference_profile_uuid = $_GET["id"];
		$sql = "select * from v_conference_profiles ";
		$sql .= "where conference_profile_uuid = :conference_profile_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['conference_profile_uuid'] = $conference_profile_uuid;
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$profile_name = $row["profile_name"];
			$profile_enabled = $row["profile_enabled"];
			$profile_description = $row["profile_description"];
		}
		unset($sql, $parameters);
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
