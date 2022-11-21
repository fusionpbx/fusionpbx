<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('database_add') || permission_exists('database_edit')) {
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
		$database_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//clear the values
	$database_driver = '';
	$database_type = '';
	$database_host = '';
	$database_port = '';
	$database_name = '';
	$database_username = '';
	$database_password = '';
	$database_path = '';
	$database_description = '';

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$database_driver = $_POST["database_driver"];
		$database_type = $_POST["database_type"];
		$database_host = $_POST["database_host"];
		$database_port = $_POST["database_port"];
		$database_name = $_POST["database_name"];
		$database_username = $_POST["database_username"];
		$database_password = $_POST["database_password"];
		$database_path = $_POST["database_path"];
		$database_description = $_POST["database_description"];
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$database_uuid = $_POST["database_uuid"];
	}

	//delete the database
		if (permission_exists('database_delete')) {
			if ($_POST['action'] == 'delete' && is_uuid($database_uuid)) {
				//prepare
					$array[0]['checked'] = 'true';
					$array[0]['uuid'] = $database_uuid;
				//delete
					$obj = new databases;
					$obj->delete($array);
				//redirect
					header('Location: databases.php');
					exit;
			}
		}

	//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: databases.php');
			exit;
		}

	//check for all required data
		//if (strlen($database_driver) == 0) { $msg .= $text['message-required'].$text['label-driver']."<br>\n"; }
		//if (strlen($database_type) == 0) { $msg .= $text['message-required'].$text['label-type']."<br>\n"; }
		//if (strlen($database_host) == 0) { $msg .= $text['message-required'].$text['label-host']."<br>\n"; }
		//if (strlen($database_port) == 0) { $msg .= $text['message-required'].$text['label-port']."<br>\n"; }
		//if (strlen($database_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		//if (strlen($database_username) == 0) { $msg .= $text['message-required'].$text['label-username']."<br>\n"; }
		//if (strlen($database_password) == 0) { $msg .= $text['message-required'].$text['label-password']."<br>\n"; }
		//if (strlen($database_path) == 0) { $msg .= $text['message-required'].$text['label-path']."<br>\n"; }
		//if (strlen($database_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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

		//begin array
			$array['databases'][0]['database_driver'] = $database_driver;
			$array['databases'][0]['database_type'] = $database_type;
			$array['databases'][0]['database_host'] = $database_host;
			$array['databases'][0]['database_port'] = $database_port;
			$array['databases'][0]['database_name'] = $database_name;
			$array['databases'][0]['database_username'] = $database_username;
			$array['databases'][0]['database_password'] = $database_password;
			$array['databases'][0]['database_path'] = $database_path;
			$array['databases'][0]['database_description'] = $database_description;

		if ($action == "add") {
			//add new uuid
				$array['databases'][0]['database_uuid'] = uuid();

				$database = new database;
				$database->app_name = 'databases';
				$database->app_uuid = '8d229b6d-1383-fcec-74c6-4ce1682479e2';
				$database->save($array);
				unset($array);

			//set the defaults
				require_once "app_defaults.php";

			//redirect the browser
				message::add($text['message-add']);
				header("Location: databases.php");
				exit;
		}

		if ($action == "update") {
			//add uuid to update
				$array['databases'][0]['database_uuid'] = $database_uuid;

				$database = new database;
				$database->app_name = 'databases';
				$database->app_uuid = '8d229b6d-1383-fcec-74c6-4ce1682479e2';
				$database->save($array);
				unset($array);

			//set the defaults
				$domains_processed = 1;
				require_once "app_defaults.php";

			//redirect the browser
				message::add($text['message-update']);
				header("Location: databases.php");
				exit;
		}
	}
}

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$database_uuid = $_GET["id"];
		$sql = "select * from v_databases ";
		$sql .= "where database_uuid = :database_uuid ";
		$parameters['database_uuid'] = $database_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$database_driver = $row["database_driver"];
			$database_type = $row["database_type"];
			$database_host = $row["database_host"];
			$database_port = $row["database_port"];
			$database_name = $row["database_name"];
			$database_username = $row["database_username"];
			$database_password = $row["database_password"];
			$database_path = $row["database_path"];
			$database_description = $row["database_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	if ($action == "update") {
		$document['title'] = $text['title-database-edit'];
	}
	if ($action == "add") {
		$document['title'] = $text['title-database-add'];
	}
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "add") {
		echo "<b>".$text['header-database-add']."</b>";
	}
	if ($action == "update") {
		echo "<b>".$text['header-database-edit']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'databases.php']);
	if ($action == 'update' && permission_exists('database_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','name'=>'action','value'=>'save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('database_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	if ($action == "add") {
		echo $text['description-database-add']."\n";
	}
	if ($action == "update") {
		echo $text['description-database-edit']."\n";
	}
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-driver']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='database_driver'>\n";
	echo "	<option value=''></option>\n";
	if ($database_driver == "sqlite") {
		echo "	&nbsp; &nbsp;<option value='sqlite' selected='selected'>SQLite</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='sqlite'>SQLite</option>\n";
	}
	if ($database_driver == "pgsql") {
		echo "	&nbsp; &nbsp;<option value='pgsql' selected='selected'>PostgreSQL</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='pgsql'>PostgreSQL</option>\n";
	}
	if ($database_driver == "mysql") {
		echo "	&nbsp; &nbsp;<option value='mysql' selected='selected'>MySQL</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='mysql'>MySQL</option>\n";
	}
	if ($database_driver == "odbc") {
		echo "	&nbsp; &nbsp;<option value='odbc' selected='selected'>ODBC</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='odbc'>ODBC</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-driver']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='database_type'>\n";
	echo "	<option value=''></option>\n";
	if ($database_type == "sqlite") {
		echo "	&nbsp; &nbsp;<option value='sqlite' selected='selected'>SQLite</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='sqlite'>SQLite</option>\n";
	}
	if ($database_type == "pgsql") {
		echo "	&nbsp; &nbsp;<option value='pgsql' selected='selected'>PostgreSQL</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='pgsql'>PostgreSQL</option>\n";
	}
	if ($database_type == "mysql") {
		echo "	&nbsp; &nbsp;<option value='mysql' selected='selected'>MySQL</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='mysql'>MySQL</option>\n";
	}
	if ($database_type == "mssql") {
		echo "	&nbsp; &nbsp;<option value='mssql' selected='selected'>Microsoft SQL Server</option>\n";
	}
	else {
		echo "	&nbsp; &nbsp;<option value='mssql'>Microsoft SQL Server</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-host']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_host' maxlength='255' value=\"".escape($database_host)."\">\n";
	echo "<br />\n";
	echo $text['description-host']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-port']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_port' maxlength='255' value=\"".escape($database_port)."\">\n";
	echo "<br />\n";
	echo $text['description-port']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_name' maxlength='255' value=\"".escape($database_name)."\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-username']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_username' maxlength='255' value=\"".escape($database_username)."\">\n";
	echo "<br />\n";
	echo $text['description-username']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_password' maxlength='255' value=\"".escape($database_password)."\">\n";
	echo "<br />\n";
	echo $text['description-password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-path']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_path' maxlength='255' value=\"".escape($database_path)."\">\n";
	echo "<br />\n";
	echo $text['description-path']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='database_description' maxlength='255' value=\"".escape($database_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='database_uuid' value='".escape($database_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>