<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('conference_control_add') || permission_exists('conference_control_edit')) {
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
		$conference_control_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$control_name = $_POST["control_name"];
		$control_enabled = $_POST["control_enabled"];
		$control_description = $_POST["control_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$conference_control_uuid = $_POST["conference_control_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: conference_controls.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($control_name) == 0) { $msg .= $text['message-required']." ".$text['label-control_name']."<br>\n"; }
			if (strlen($control_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-control_enabled']."<br>\n"; }
			//if (strlen($control_description) == 0) { $msg .= $text['message-required']." ".$text['label-control_description']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				$document['title'] = $text['title-conference_control'];
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

		//add the conference_control_uuid
			if (!is_uuid($_POST["conference_control_uuid"])) {
				$conference_control_uuid = uuid();
			}

		//prepare the array
			$array['conference_controls'][0]['conference_control_uuid'] = $conference_control_uuid;
			$array['conference_controls'][0]['control_name'] = $control_name;
			$array['conference_controls'][0]['control_enabled'] = $control_enabled;
			$array['conference_controls'][0]['control_description'] = $control_description;

		//save to the data
			$database = new database;
			$database->app_name = 'conference_controls';
			$database->app_uuid = 'e1ad84a2-79e1-450c-a5b1-7507a043e048';
			if (strlen($conference_control_uuid) > 0) {
				$database->uuid($conference_control_uuid);
			}
			$database->save($array);
			$message = $database->message;

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					message::add($text['message-add']);
				}
				if ($action == "update") {
					message::add($text['message-update']);
				}
				header("Location: conference_controls.php");
				return;
			}
	} //(is_array($_POST) && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$conference_control_uuid = $_GET["id"];
		$sql = "select * from v_conference_controls ";
		//$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "where conference_control_uuid = :conference_control_uuid ";
		$parameters['conference_control_uuid'] = $conference_control_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$control_name = $row["control_name"];
			$control_enabled = $row["control_enabled"];
			$control_description = $row["control_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-conference_control'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-conference_control']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'conference_controls.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='control_name' maxlength='255' value=\"".escape($control_name)."\">\n";
	echo "<br />\n";
	echo $text['description-control_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='control_enabled'>\n";
	echo "		<option value='true'>".$text['label-true']."</option>\n";
	echo "		<option value='false' ".($control_enabled == "false" ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-control_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='control_description' maxlength='255' value=\"".escape($control_description)."\">\n";
	echo "<br />\n";
	echo $text['description-control_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='conference_control_uuid' value='".escape($conference_control_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

	if ($action == "update") {
		require "conference_control_details.php";
	}

//include the footer
	require_once "resources/footer.php";

?>