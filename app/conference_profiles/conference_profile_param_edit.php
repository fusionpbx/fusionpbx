<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('conference_profile_param_add') || permission_exists('conference_profile_param_edit')) {
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
		$conference_profile_param_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (is_uuid($_GET["conference_profile_uuid"])) {
		$conference_profile_uuid = $_GET["conference_profile_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$profile_param_name = $_POST["profile_param_name"];
		$profile_param_value = $_POST["profile_param_value"];
		$profile_param_enabled = $_POST["profile_param_enabled"];
		$profile_param_description = $_POST["profile_param_description"];
	}

//process the http post if it exists
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
	
		//get the uuid
			if ($action == "update") {
				$conference_profile_param_uuid = $_POST["conference_profile_param_uuid"];
			}
	
		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: conference_profiles.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($profile_param_name) == 0) { $msg .= $text['message-required']." ".$text['label-profile_param_name']."<br>\n"; }
			if (strlen($profile_param_value) == 0) { $msg .= $text['message-required']." ".$text['label-profile_param_value']."<br>\n"; }
			if (strlen($profile_param_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-profile_param_enabled']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				$document['title'] = $text['title-conference_profile_param'];
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

				$array['conference_profile_params'][0]['conference_profile_uuid'] = $conference_profile_uuid;
				$array['conference_profile_params'][0]['profile_param_name'] = $profile_param_name;
				$array['conference_profile_params'][0]['profile_param_value'] = $profile_param_value;
				$array['conference_profile_params'][0]['profile_param_enabled'] = $profile_param_enabled;
				$array['conference_profile_params'][0]['profile_param_description'] = $profile_param_description;

				if ($action == "add" && permission_exists('conference_profile_param_add')) {
					$array['conference_profile_params'][0]['conference_profile_param_uuid'] = uuid();
					message::add($text['message-add']);
				}
	
				if ($action == "update" && permission_exists('conference_profile_param_edit')) {
					$array['conference_profile_params'][0]['conference_profile_param_uuid'] = $conference_profile_param_uuid;
					message::add($text['message-update']);
				}

				if (is_uuid($array['conference_profile_params'][0]['conference_profile_param_uuid'])) {
					$database = new database;
					$database->app_name = 'conference_profiles';
					$database->app_uuid = 'c33e2c2a-847f-44c1-8c0d-310df5d65ba9';
					$database->save($array);
					unset($array);
				}

				header('Location: conference_profile_edit.php?id='.$conference_profile_uuid);
				exit;

			}
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$conference_profile_param_uuid = $_GET["id"];
		$sql = "select * from v_conference_profile_params ";
		$sql .= "where conference_profile_param_uuid = :conference_profile_param_uuid ";
		$parameters['conference_profile_param_uuid'] = $conference_profile_param_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row)) {
			$profile_param_name = $row["profile_param_name"];
			$profile_param_value = $row["profile_param_value"];
			$profile_param_enabled = $row["profile_param_enabled"];
			$profile_param_description = $row["profile_param_description"];
		}
		unset($sql, $parameters);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-conference_profile_param'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-conference_profile_param']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'conference_profile_edit.php?id='.urlencode($conference_profile_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_param_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='profile_param_name' maxlength='255' value=\"".escape($profile_param_name)."\">\n";
	echo "<br />\n";
	echo $text['description-profile_param_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_param_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='profile_param_value' maxlength='255' style='min-width: 40%;' value=\"".escape($profile_param_value)."\">\n";
	echo "<br />\n";
	echo $text['description-profile_param_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_param_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='profile_param_enabled'>\n";
	echo "		<option value='true'>".$text['label-true']."</option>\n";
	echo "		<option value='false' ".($profile_param_enabled == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-profile_param_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-profile_param_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='profile_param_description' maxlength='255' value=\"".escape($profile_param_description)."\">\n";
	echo "<br />\n";
	echo $text['description-profile_param_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='conference_profile_uuid' value='".escape($conference_profile_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='conference_profile_param_uuid' value='".escape($conference_profile_param_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>