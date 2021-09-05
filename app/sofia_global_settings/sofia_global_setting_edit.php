<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('sofia_global_setting_add') || permission_exists('sofia_global_setting_edit')) {
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
		$sofia_global_setting_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$global_setting_name = $_POST["global_setting_name"];
		$global_setting_value = $_POST["global_setting_value"];
		$global_setting_enabled = $_POST["global_setting_enabled"];
		$global_setting_description = $_POST["global_setting_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: sofia_global_settings.php');
				exit;
			}

		//process the http post data by submitted action
			if ($_POST['action'] != '' && strlen($_POST['action']) > 0) {

				//prepare the array(s)
				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('sofia_global_setting_add')) {
							$obj = new database;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('sofia_global_setting_delete')) {
							$obj = new database;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('sofia_global_setting_update')) {
							$obj = new database;
							$obj->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: sofia_global_setting_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			if (strlen($global_setting_name) == 0) { $msg .= $text['message-required']." ".$text['label-global_setting_name']."<br>\n"; }
			if (strlen($global_setting_value) == 0) { $msg .= $text['message-required']." ".$text['label-global_setting_value']."<br>\n"; }
			if (strlen($global_setting_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-global_setting_enabled']."<br>\n"; }
			//if (strlen($global_setting_description) == 0) { $msg .= $text['message-required']." ".$text['label-global_setting_description']."<br>\n"; }
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

		//add the sofia_global_setting_uuid
			if (!is_uuid($_POST["sofia_global_setting_uuid"])) {
				$sofia_global_setting_uuid = uuid();
			}

		//prepare the array
			$array['sofia_global_settings'][0]['sofia_global_setting_uuid'] = $sofia_global_setting_uuid;
			$array['sofia_global_settings'][0]['global_setting_name'] = $global_setting_name;
			$array['sofia_global_settings'][0]['global_setting_value'] = $global_setting_value;
			$array['sofia_global_settings'][0]['global_setting_enabled'] = $global_setting_enabled;
			$array['sofia_global_settings'][0]['global_setting_description'] = $global_setting_description;

		//save the data
			$database = new database;
			$database->app_name = 'sofia_global_settings';
			$database->app_uuid = '240c25a3-a2cf-44ea-a300-0626eca5b945';
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: sofia_global_settings.php');
				header('Location: sofia_global_setting_edit.php?id='.urlencode($sofia_global_setting_uuid));
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select ";
		$sql .= " sofia_global_setting_uuid, ";
		$sql .= " global_setting_name, ";
		$sql .= " global_setting_value, ";
		$sql .= " cast(global_setting_enabled as text), ";
		$sql .= " global_setting_description ";
		$sql .= "from v_sofia_global_settings ";
		$sql .= "where sofia_global_setting_uuid = :sofia_global_setting_uuid ";
		$parameters['sofia_global_setting_uuid'] = $sofia_global_setting_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$global_setting_name = $row["global_setting_name"];
			$global_setting_value = $row["global_setting_value"];
			$global_setting_enabled = $row["global_setting_enabled"];
			$global_setting_description = $row["global_setting_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-sofia_global_setting'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='sofia_global_setting_uuid' value='".escape($sofia_global_setting_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-sofia_global_setting']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'sofia_global_settings.php']);
	if ($action == 'update') {
		if (permission_exists('_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-sofia_global_settings']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('sofia_global_setting_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('sofia_global_setting_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-global_setting_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='global_setting_name' maxlength='255' value='".escape($global_setting_name)."'>\n";
	echo "<br />\n";
	echo $text['description-global_setting_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-global_setting_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='global_setting_value' maxlength='255' value='".escape($global_setting_value)."'>\n";
	echo "<br />\n";
	echo $text['description-global_setting_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-global_setting_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='global_setting_enabled'>\n";
	echo "		<option value=''></option>\n";
	if ($global_setting_enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($global_setting_enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-global_setting_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-global_setting_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='global_setting_description' maxlength='255' value='".escape($global_setting_description)."'>\n";
	echo "<br />\n";
	echo $text['description-global_setting_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>