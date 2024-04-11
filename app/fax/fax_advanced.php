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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('fax_extension_advanced')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$fax_uuid = $_REQUEST["id"];
		//$dialplan_uuid = $_REQUEST["dialplan_uuid"] ?? null;
	}
	else {
		$action = "add";
	}

//get the http post values and set them as php variables
	if (count($_POST) > 0) {

		//set the variables
		//$fax_name = $_POST["fax_name"];
		//$fax_extension = $_POST["fax_extension"];
		//$fax_accountcode = $_POST["accountcode"];
		//$fax_destination_number = $_POST["fax_destination_number"];
		//$fax_prefix = $_POST["fax_prefix"];
		//$fax_email = implode(',',array_filter($_POST["fax_email"] ?? []));
		$fax_email_connection_type = $_POST["fax_email_connection_type"];
		$fax_email_connection_host = $_POST["fax_email_connection_host"];
		$fax_email_connection_port = $_POST["fax_email_connection_port"];
		$fax_email_connection_security = $_POST["fax_email_connection_security"];
		$fax_email_connection_validate = $_POST["fax_email_connection_validate"];
		$fax_email_connection_username = $_POST["fax_email_connection_username"];
		$fax_email_connection_password = $_POST["fax_email_connection_password"];
		$fax_email_connection_mailbox = $_POST["fax_email_connection_mailbox"];
		$fax_email_inbound_subject_tag = $_POST["fax_email_inbound_subject_tag"];
		$fax_email_outbound_subject_tag = $_POST["fax_email_outbound_subject_tag"];
		$fax_email_outbound_authorized_senders = $_POST["fax_email_outbound_authorized_senders"];
		//$fax_caller_id_name = $_POST["fax_caller_id_name"];
		//$fax_caller_id_number = $_POST["fax_caller_id_number"];
		//$fax_toll_allow = $_POST["fax_toll_allow"];
		//$fax_forward_number = $_POST["fax_forward_number"];
		//if (empty($fax_destination_number)) {
		//	$fax_destination_number = $fax_extension;
		//}
		//if (strlen($fax_forward_number) > 3) {
		//	//$fax_forward_number = preg_replace("~[^0-9]~", "",$fax_forward_number);
		//	$fax_forward_number = str_replace(" ", "", $fax_forward_number);
		//	$fax_forward_number = str_replace("-", "", $fax_forward_number);
		//}
		//if (strripos($fax_forward_number, '$1') === false) {
		//	$forward_prefix = ''; //not found
		//}
		//else {
		//	$forward_prefix = $forward_prefix.$fax_forward_number.'#'; //found
		//}
		//$fax_local = $_POST["fax_local"] ?? null; //! @todo check in database
		//$fax_description = $_POST["fax_description"];
		//$fax_send_channels = $_POST["fax_send_channels"];

		//restrict size of user data
		//$fax_name = substr($fax_name, 0, 30);
		//$fax_extension = substr($fax_extension, 0, 15);
		//$accountcode = substr($accountcode ?? '', 0, 80);
		//$fax_prefix = substr($fax_prefix ?? '', 0, 12);
		//$fax_caller_id_name = substr($fax_caller_id_name, 0, 40);
		//$fax_caller_id_number = substr($fax_caller_id_number, 0, 20);
		//$fax_forward_number = substr($fax_forward_number, 0, 20);
	}

//delete the user from the fax users
	if (!empty($_REQUEST["user_uuid"]) && is_uuid($_REQUEST["user_uuid"]) && is_uuid($_REQUEST["id"]) && !empty($_GET["a"]) && $_GET["a"] == "delete" && permission_exists("fax_extension_delete")) {
		//set the variables
			$user_uuid = $_REQUEST["user_uuid"];
			$fax_uuid = $_REQUEST["id"];

		//delete the group from the users
			$array['fax_users'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['fax_users'][0]['fax_uuid'] = $fax_uuid;
			$array['fax_users'][0]['user_uuid'] = $user_uuid;

			$p = new permissions;
			$p->add('fax_user_delete', 'temp');

			$database = new database;
			$database->app_name = 'fax';
			$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
			$database->delete($array);
			unset($array);

			$p->delete('fax_user_delete', 'temp');

		//redirect the browser
			message::add($text['message-delete']);
			header("Location: fax_edit.php?id=".$fax_uuid);
			return;
	}

//add the user to the fax users
	if (!empty($_REQUEST["user_uuid"]) && is_uuid($_REQUEST["user_uuid"]) && is_uuid($_REQUEST["id"]) && (empty($_GET["a"]) || $_GET["a"] != "delete")) {
		//set the variables
			$user_uuid = $_REQUEST["user_uuid"];
			$fax_uuid = $_REQUEST["id"];
		//assign the user to the fax extension
			$array['fax_users'][0]['fax_user_uuid'] = uuid();
			$array['fax_users'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['fax_users'][0]['fax_uuid'] = $fax_uuid;
			$array['fax_users'][0]['user_uuid'] = $user_uuid;

			$p = new permissions;
			$p->add('fax_user_add', 'temp');

			$database = new database;
			$database->app_name = 'fax';
			$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
			$database->save($array);
			unset($array);

			$p->delete('fax_user_add', 'temp');

		//redirect the browser
			message::add($text['confirm-add']);
			header("Location: fax_edit.php?id=".$fax_uuid);
			return;
	}

//clear file status cache
	clearstatcache();

//process the data
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//get the fax_uuid
		if ($action == "update" && is_uuid($_POST["fax_uuid"]) && permission_exists('fax_extension_edit')) {
			$fax_uuid = $_POST["fax_uuid"];
		}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: fax.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (permission_exists('fax_extension') && empty($fax_extension)) { $msg .= "".$text['confirm-ext']."<br>\n"; }
			//if (empty($fax_name)) { $msg .= "".$text['confirm-fax']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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

		//sanitize the fax extension number
			//$fax_extension = preg_replace('#[^0-9]#', '', $fax_extension);

		//replace the spaces with a dash
			$fax_name = str_replace(" ", "-", $fax_name);

		//escape the commas with a backslash and remove the spaces
			$fax_email = str_replace(" ", "", $fax_email);

		//set the $php_bin
			//if (file_exists(PHP_BINDIR."/php")) { $php_bin = 'php'; }
			if (substr(strtoupper(PHP_OS), 0, 3) == "WIN") {
				$php_bin = 'php.exe';
			}
			elseif (file_exists(PHP_BINDIR."/php5")) {
				$php_bin = 'php5';
			}
			else {
				$php_bin = 'php';
			}

		//add or update the database
			if (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true") {

				//prep authorized senders
					if (is_array($fax_email_outbound_authorized_senders) && (sizeof($fax_email_outbound_authorized_senders) > 0)) {
						foreach ($fax_email_outbound_authorized_senders as $sender_num => $sender) {
							if ($sender == '' || (substr_count($sender, '@') == 1 && !valid_email($sender)) || substr_count($sender, '.') == 0) {
								unset($fax_email_outbound_authorized_senders[$sender_num]);
							}
						}
						$fax_email_outbound_authorized_senders = strtolower(implode(',', $fax_email_outbound_authorized_senders));
					}

				if ($action == "add" && permission_exists('fax_extension_add')) {
					//prepare the unique identifiers
						$fax_uuid = uuid();
						//$dialplan_uuid = uuid();

					//begin insert array
						$array['fax'][0]['fax_uuid'] = $fax_uuid;
						//$array['fax'][0]['dialplan_uuid'] = $dialplan_uuid;

					//assign temp permission
						$p = new permissions;
						$p->add('fax_add', 'temp');

					//set the dialplan action
						$dialplan_type = "add";
				}

				if ($action == "update" && permission_exists('fax_extension_edit')) {
					//begin update array
						$array['fax'][0]['fax_uuid'] = $fax_uuid;

					//assign temp permission
						$p = new permissions;
						$p->add('fax_edit', 'temp');
				}

				if (is_array($array) && @sizeof($array) != 0) {
					//add common columns to array
						$array['fax'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
						/*
						if (permission_exists('fax_extension')) {
							$array['fax'][0]['fax_extension'] = $fax_extension;
						}
						if (permission_exists('fax_accountcode')) {
							$array['fax'][0]['accountcode'] = $fax_accountcode;
						}
						if (permission_exists('fax_destination_number')) {
							$array['fax'][0]['fax_destination_number'] = $fax_destination_number;
						}
						if (permission_exists('fax_prefix')) {
							$array['fax'][0]['fax_prefix'] = $fax_prefix;
						}
						$array['fax'][0]['fax_name'] = $fax_name;
						if (permission_exists('fax_email')) {
							$array['fax'][0]['fax_email'] = $fax_email;
						}
						*/
						if (function_exists("imap_open") && file_exists("fax_files_remote.php")) {
							$array['fax'][0]['fax_email_connection_type'] = $fax_email_connection_type;
							$array['fax'][0]['fax_email_connection_host'] = $fax_email_connection_host;
							$array['fax'][0]['fax_email_connection_port'] = $fax_email_connection_port;
							$array['fax'][0]['fax_email_connection_security'] = $fax_email_connection_security;
							$array['fax'][0]['fax_email_connection_validate'] = $fax_email_connection_validate;
							$array['fax'][0]['fax_email_connection_username'] = $fax_email_connection_username;
							$array['fax'][0]['fax_email_connection_password'] = $fax_email_connection_password;
							$array['fax'][0]['fax_email_connection_mailbox'] = $fax_email_connection_mailbox;
							$array['fax'][0]['fax_email_inbound_subject_tag'] = $fax_email_inbound_subject_tag;
							$array['fax'][0]['fax_email_outbound_subject_tag'] = $fax_email_outbound_subject_tag;
							$array['fax'][0]['fax_email_outbound_authorized_senders'] = $fax_email_outbound_authorized_senders;
						}
						if (permission_exists('fax_caller_id_name')) {
							$array['fax'][0]['fax_caller_id_name'] = $fax_caller_id_name;
						}
						if (permission_exists('fax_caller_id_number')) {
							$array['fax'][0]['fax_caller_id_number'] = $fax_caller_id_number;
						}
						if (permission_exists('fax_toll_allow')) {
							$array['fax'][0]['fax_toll_allow'] = $fax_toll_allow;
						}
						if (permission_exists('fax_forward_number')) {
							if ($action == "add" && !empty($fax_forward_number)) {
								$array['fax'][0]['fax_forward_number'] = $fax_forward_number;
							}
							if ($action == "update") {
								$array['fax'][0]['fax_forward_number'] = !empty($fax_forward_number) ? $fax_forward_number : null;
							}
						}
						if (permission_exists('fax_send_channels')) {
							$array['fax'][0]['fax_send_channels'] = strlen($fax_send_channels) != 0 ? $fax_send_channels : null;
						}
						$array['fax'][0]['fax_description'] = $fax_description;

					//execute
						$database = new database;
						$database->app_name = 'fax';
						$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
						$database->save($array);
						unset($array);

					//revoke temp permissions
						$p->delete('fax_add', 'temp');
						$p->delete('fax_edit', 'temp');

					//clear the destinations session array
						if (isset($_SESSION['destinations']['array'])) {
							unset($_SESSION['destinations']['array']);
						}

				}

				//get the dialplan_uuid
					$sql = "select dialplan_uuid from v_fax ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and fax_uuid = :fax_uuid ";
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$parameters['fax_uuid'] = $fax_uuid;
					$database = new database;
					$dialplan_uuid = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);

				//dialplan add or update
					$c = new fax;
					$c->domain_uuid = $_SESSION['domain_uuid'];
					$c->dialplan_uuid = $dialplan_uuid;
					$c->fax_name = $fax_name;
					$c->fax_uuid = $fax_uuid;
					$c->fax_extension = $fax_extension;
					$c->fax_forward_number = $fax_forward_number;
					$c->destination_number = $fax_destination_number;
					$c->fax_description = $fax_description;
					$a = $c->dialplan();

				//redirect the browser
					if ($action == "update" && permission_exists('fax_extension_edit')) {
						message::add($text['confirm-update']);
					}
					if ($action == "add" && permission_exists('fax_extension_add')) {
						message::add($text['confirm-add']);
					}
					header("Location: fax.php");
					return;

			}
	}

//pre-populate the form
	if (!empty($_GET['id']) && is_uuid($_GET['id']) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$fax_uuid = $_GET["id"];
		$sql = "select * from v_fax ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and fax_uuid = :fax_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['fax_uuid'] = $fax_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			/*
			$dialplan_uuid = $row["dialplan_uuid"];
			$fax_extension = $row["fax_extension"];
			$fax_accountcode = $row["accountcode"];
			$fax_destination_number = $row["fax_destination_number"];
			$fax_prefix = $row["fax_prefix"];
			$fax_name = $row["fax_name"];
			$fax_email = $row["fax_email"];
			*/
			$fax_email_connection_type = $row["fax_email_connection_type"];
			$fax_email_connection_host = $row["fax_email_connection_host"];
			$fax_email_connection_port = $row["fax_email_connection_port"];
			$fax_email_connection_security = $row["fax_email_connection_security"];
			$fax_email_connection_validate = $row["fax_email_connection_validate"];
			$fax_email_connection_username = $row["fax_email_connection_username"];
			$fax_email_connection_password = $row["fax_email_connection_password"];
			$fax_email_connection_mailbox = $row["fax_email_connection_mailbox"];
			$fax_email_inbound_subject_tag = $row["fax_email_inbound_subject_tag"];
			$fax_email_outbound_subject_tag = $row["fax_email_outbound_subject_tag"];
			$fax_email_outbound_authorized_senders = $row["fax_email_outbound_authorized_senders"];
			/*
			$fax_caller_id_name = $row["fax_caller_id_name"];
			$fax_caller_id_number = $row["fax_caller_id_number"];
			$fax_toll_allow = $row["fax_toll_allow"];
			$fax_forward_number = $row["fax_forward_number"];
			$fax_description = $row["fax_description"];
			$fax_send_channels = $row["fax_send_channels"];
			*/
		}
		unset($sql, $parameters, $row);
	}
	else{
		$fax_send_channels = 10;
	}
/*
//get the fax users
	if (!empty($fax_uuid) && is_uuid($fax_uuid)) {
		$sql = "select * from v_fax_users as e, v_users as u ";
		$sql .= "where e.user_uuid = u.user_uuid  ";
		$sql .= "and e.domain_uuid = :domain_uuid ";
		$sql .= "and e.fax_uuid = :fax_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['fax_uuid'] = $fax_uuid;
		$database = new database;
		$fax_users = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the users that are not assigned to this fax server
	if (!empty($fax_uuid) && is_uuid($fax_uuid)) {
		$sql = "select * from v_users \n";
		$sql .= "where domain_uuid = :domain_uuid \n";
		$sql .= "and user_uuid not in (\n";
		$sql .= "	select user_uuid from v_fax_users ";
		$sql .= "	where domain_uuid = :domain_uuid ";
		$sql .= "	and fax_uuid = :fax_uuid ";
		$sql .= "	and user_uuid is not null ";
		$sql .= ")\n";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['fax_uuid'] = $fax_uuid;
		$database = new database;
		$available_users = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//replace the dash with a space
	$fax_name = str_replace("-", " ", $fax_name ?? '');
*/

//build the fax_emails array
	$fax_emails = explode(',', $fax_email ?? '');

//set the dialplan_uuid
	if (empty($dialplan_uuid) || !is_uuid($dialplan_uuid)) {
		$dialplan_uuid = uuid();
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-fax_server_settings'];
	require_once "resources/header.php";

//advanced button js
	echo "<script type='text/javascript' language='JavaScript'>\n";
	echo "	function add_sender() {\n";
	echo "		var newdiv = document.createElement('div');\n";
	echo "		newdiv.innerHTML = \"<input type='text' class='formfld' style='width: 225px; min-width: 225px; max-width: 225px; margin-top: 3px;' name='fax_email_outbound_authorized_senders[]' maxlength='255'>\";";
	echo "		document.getElementById('authorized_senders').appendChild(newdiv);";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['label-advanced_settings']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'fax.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";
	echo $text['description-advanced_settings']."\n";
	echo "<br><br>\n";

	if ($action == 'update') {
		if (permission_exists('fax_extension_copy')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('fax_extension_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	if (function_exists("imap_open") && file_exists("fax_files_remote.php")) {

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>";
		echo "		<td width='50%' valign='top'>";

		echo "			<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "			<tr>";
		echo "			<td colspan='2'>";
		echo "				<span style='font-weight: bold;'>".$text['label-email_account_connection']."</span><br><br>";
		echo "			</td>";
		echo "			</tr>\n";

		echo "			<tr>\n";
		echo "			<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "				".$text['label-email_connection_type']."\n";
		echo "			</td>\n";
		echo "			<td width='70%' class='vtable' align='left'>\n";
		echo "				<select class='formfld' name='fax_email_connection_type'>\n";
		echo "					<option value='imap'>IMAP</option>\n";
		echo "					<option value='pop3' ".(!empty($fax_email_connection_type) && $fax_email_connection_type == 'pop3' ? "selected" : null).">POP3</option>\n";
		echo "				</select>\n";
		echo "			<br />\n";
		echo "				".$text['description-email_connection_type']."\n";
		echo "			</td>\n";
		echo "			</tr>\n";

		echo "			<tr>\n";
		echo "			<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "				".$text['label-email_connection_server']."\n";
		echo "			</td>\n";
		echo "			<td class='vtable' style='white-space: nowrap;' align='left'>\n";
		echo "				<input class='formfld' type='text' name='fax_email_connection_host' maxlength='255' value=\"".escape($fax_email_connection_host ?? '')."\">&nbsp;<strong style='font-size: 15px;'>:</strong>&nbsp;";
		echo 				"<input class='formfld' style='width: 50px; min-width: 50px; max-width: 50px;' type='text' name='fax_email_connection_port' maxlength='5' value='".($fax_email_connection_port ?? '')."'>\n";
		echo "			<br />\n";
		echo "				".$text['description-email_connection_server']."\n";
		echo "			</td>\n";
		echo "			</tr>\n";

		echo "			<tr>\n";
		echo "			<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "				".$text['label-email_connection_security']."\n";
		echo "			</td>\n";
		echo "			<td class='vtable' align='left'>\n";
		echo "				<select class='formfld' name='fax_email_connection_security'>\n";
		echo "					<option value=''></option>\n";
		echo "					<option value='ssl' ".(!empty($fax_email_connection_security) && $fax_email_connection_security == 'ssl' ? "selected" : null).">SSL</option>\n";
		echo "					<option value='tls' ".(!empty($fax_email_connection_security) && $fax_email_connection_security == 'tls' ? "selected" : null).">TLS</option>\n";
		echo "				</select>\n";
		echo "			<br />\n";
		echo "				".$text['description-email_connection_security']."\n";
		echo "			</td>\n";
		echo "			</tr>\n";

		echo "			<tr>\n";
		echo "			<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "				".$text['label-email_connection_validate']."\n";
		echo "			</td>\n";
		echo "			<td class='vtable' align='left'>\n";
		echo "				<select class='formfld' name='fax_email_connection_validate'>\n";
		echo "					<option value='true'>".$text['option-true']."</option>\n";
		echo "					<option value='false' ".(!empty($fax_email_connection_validate) && $fax_email_connection_validate == 'false' ? "selected" : null).">".$text['option-false']."</option>\n";
		echo "				</select>\n";
		echo "			<br />\n";
		echo "				".$text['description-email_connection_validate']."\n";
		echo "			</td>\n";
		echo "			</tr>\n";

		echo "			<tr>\n";
		echo "			<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "				".$text['label-email_connection_username']."\n";
		echo "			</td>\n";
		echo "			<td class='vtable' align='left'>\n";
		echo "				<input class='formfld' type='text' name='fax_email_connection_username' maxlength='255' value=\"".escape($fax_email_connection_username ?? '')."\">\n";
		echo "			  <input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
		echo "			<br />\n";
		echo "				".$text['description-email_connection_username']."\n";
		echo "			</td>\n";
		echo "			</tr>\n";

		echo "			<tr>\n";
		echo "			<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "				".$text['label-email_connection_password']."\n";
		echo "			</td>\n";
		echo "			<td class='vtable' align='left'>\n";
		echo "			  <input type='password' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
		echo "				<input class='formfld' type='password' name='fax_email_connection_password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete='off' maxlength='50' value=\"".escape($fax_email_connection_password ?? '')."\">\n";
		echo "			<br />\n";
		echo "				".$text['description-email_connection_password']."\n";
		echo "			</td>\n";
		echo "			</tr>\n";

		echo "			<tr>\n";
		echo "			<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "				".$text['label-email_connection_mailbox']."\n";
		echo "			</td>\n";
		echo "			<td class='vtable' align='left'>\n";
		echo "				<input class='formfld' type='text' name='fax_email_connection_mailbox' maxlength='255' value=\"".escape($fax_email_connection_mailbox ?? '')."\">\n";
		echo "			<br />\n";
		echo "				".$text['description-email_connection_mailbox']."\n";
		echo "			</td>\n";
		echo "			</tr>\n";

		echo "			</table>\n";

		echo "		</td>";
		echo "		<td style='white-space: nowrap;'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		echo "		<td width='50%' valign='top'>";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

		echo "<tr>";
		echo "<td colspan='2'>";
		echo "	<span style='font-weight: bold;'>".$text['label-email_remote_inbox']."</span><br><br>";
		echo "</td>";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-email_inbound_subject_tag']."\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "	<span style='font-size: 18px;'>[ <input class='formfld' type='text' name='fax_email_inbound_subject_tag' maxlength='255' value=\"".escape($fax_email_inbound_subject_tag ?? '')."\"> ]</span>\n";
		echo "<br />\n";
		echo "	".$text['description-email_inbound_subject_tag']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (file_exists("fax_emails.php")) {
			echo "<tr>";
			echo "<td colspan='2'>";
			echo "	<br><br>";
			echo "	<span style='font-weight: bold;'>".$text['label-email_email-to-fax']."</span><br><br>";
			echo "</td>";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-email_outbound_subject_tag']."\n";
			echo "</td>\n";
			echo "<td width='70%' class='vtable' align='left'>\n";
			echo "	<span style='font-size: 18px;'>[ <input class='formfld' type='text' name='fax_email_outbound_subject_tag' maxlength='255' value=\"".($fax_email_outbound_subject_tag ?? '')."\"> ]</span>\n";
			echo "<br />\n";
			echo "	".$text['description-email_outbound_subject_tag']."\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-email_outbound_authorized_senders']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left' valign='top'>\n";
			echo "	<table cellpadding='0' cellspacing='0' border='0'>";
			echo "		<tr>";
			echo "			<td id='authorized_senders'>";
			if (!empty($fax_email_outbound_authorized_senders)) {
				if (substr_count($fax_email_outbound_authorized_senders, ',') > 0) {
					$senders = explode(',', $fax_email_outbound_authorized_senders);
				}
				else {
					$senders[] = $fax_email_outbound_authorized_senders;
				}
			}
			$senders[] = ''; // add empty field
			foreach ($senders as $sender_num => $sender) {
				echo "	<input class='formfld' style='width: 225px; min-width: 225px; max-width: 225px; ".($sender_num > 0 ? "margin-top: 3px;" : null)."' type='text' name='fax_email_outbound_authorized_senders[]' maxlength='255' value=\"$sender\">".(sizeof($senders) > 0 && $sender_num < (sizeof($senders) - 1) ? "<br>" : null);
			}
			echo "			</td>";
			echo "			<td style='vertical-align: bottom;'>";
			echo "				<a href='javascript:void(0);' onclick='add_sender();'>$v_link_label_add</a>";
			echo "			</td>";
			echo "		</tr>";
			echo "	</table>";
			echo "	".$text['description-email_outbound_authorized_senders']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";

		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "<br><br>\n";
	}
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<br>";
	if ($action == "update") {
		echo "		<input type='hidden' name='fax_uuid' value='".escape($fax_uuid)."'>\n";
		echo "		<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";

	echo "</form>";
	echo "<br />\n";

//show the footer
	require_once "resources/footer.php";

?>