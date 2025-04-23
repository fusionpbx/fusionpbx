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
	}
	else {
		$action = "add";
	}

//get the http post values and set them as php variables
	if (!empty($_POST)) {
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
	}

//process the data
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

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
			//$fax_name = str_replace(" ", "-", $fax_name);

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

				//prepare the unique identifiers
					if ($action == "add" && permission_exists('fax_extension_add')) {
						$fax_uuid = uuid();
					}

				//add common columns to array
					$array['fax'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					$array['fax'][0]['fax_uuid'] = $fax_uuid;
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

				//execute
					if (isset($array) && is_array($array)) {
						//assign temp permission
						$p = permissions::new();
						$p->add('fax_add', 'temp');
						$p->add('fax_edit', 'temp');

						$database = new database;
						$database->app_name = 'fax';
						$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
						$message = $database->save($array);
						unset($array);

						//revoke temp permissions
						$p->delete('fax_add', 'temp');
						$p->delete('fax_edit', 'temp');
					}

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
		$sql = "select * from v_fax ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and fax_uuid = :fax_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['fax_uuid'] = $fax_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
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
		}
		unset($sql, $parameters, $row);
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
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','link'=>'fax_edit.php?id='.$fax_uuid]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','style'=>'margin-left: 15px;']);
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
		echo "			<div class='card'>\n";
		echo "				<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "				<tr>";
		echo "				<td colspan='2'>";
		echo "					<span style='font-weight: bold;'>".$text['label-email_account_connection']."</span><br><br>";
		echo "				</td>";
		echo "				</tr>\n";

		echo "				<tr>\n";
		echo "				<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "					".$text['label-email_connection_type']."\n";
		echo "				</td>\n";
		echo "				<td width='70%' class='vtable' align='left'>\n";
		echo "					<select class='formfld' name='fax_email_connection_type'>\n";
		echo "						<option value='imap'>IMAP</option>\n";
		echo "						<option value='pop3' ".(!empty($fax_email_connection_type) && $fax_email_connection_type == 'pop3' ? "selected" : null).">POP3</option>\n";
		echo "					</select>\n";
		echo "				<br />\n";
		echo "					".$text['description-email_connection_type']."\n";
		echo "				</td>\n";
		echo "				</tr>\n";

		echo "				<tr>\n";
		echo "				<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "					".$text['label-email_connection_server']."\n";
		echo "				</td>\n";
		echo "				<td class='vtable' style='white-space: nowrap;' align='left'>\n";
		echo "					<input class='formfld' type='text' name='fax_email_connection_host' maxlength='255' value=\"".escape($fax_email_connection_host ?? '')."\">&nbsp;<strong style='font-size: 15px;'>:</strong>&nbsp;";
		echo "				<input class='formfld' style='width: 50px; min-width: 50px; max-width: 50px;' type='text' name='fax_email_connection_port' maxlength='5' value='".($fax_email_connection_port ?? '')."'>\n";
		echo "				<br />\n";
		echo "					".$text['description-email_connection_server']."\n";
		echo "				</td>\n";
		echo "				</tr>\n";

		echo "				<tr>\n";
		echo "				<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "					".$text['label-email_connection_security']."\n";
		echo "				</td>\n";
		echo "				<td class='vtable' align='left'>\n";
		echo "					<select class='formfld' name='fax_email_connection_security'>\n";
		echo "						<option value=''></option>\n";
		echo "						<option value='ssl' ".(!empty($fax_email_connection_security) && $fax_email_connection_security == 'ssl' ? "selected" : null).">SSL</option>\n";
		echo "						<option value='tls' ".(!empty($fax_email_connection_security) && $fax_email_connection_security == 'tls' ? "selected" : null).">TLS</option>\n";
		echo "					</select>\n";
		echo "				<br />\n";
		echo "					".$text['description-email_connection_security']."\n";
		echo "				</td>\n";
		echo "				</tr>\n";

		echo "				<tr>\n";
		echo "				<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "					".$text['label-email_connection_validate']."\n";
		echo "				</td>\n";
		echo "				<td class='vtable' align='left'>\n";
		echo "					<select class='formfld' name='fax_email_connection_validate'>\n";
		echo "						<option value='true'>".$text['option-true']."</option>\n";
		echo "						<option value='false' ".(!empty($fax_email_connection_validate) && $fax_email_connection_validate == 'false' ? "selected" : null).">".$text['option-false']."</option>\n";
		echo "					</select>\n";
		echo "				<br />\n";
		echo "					".$text['description-email_connection_validate']."\n";
		echo "				</td>\n";
		echo "				</tr>\n";

		echo "				<tr>\n";
		echo "				<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "					".$text['label-email_connection_username']."\n";
		echo "				</td>\n";
		echo "				<td class='vtable' align='left'>\n";
		echo "					<input class='formfld' type='text' name='fax_email_connection_username' maxlength='255' value=\"".escape($fax_email_connection_username ?? '')."\">\n";
		echo "				  <input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
		echo "				<br />\n";
		echo "					".$text['description-email_connection_username']."\n";
		echo "				</td>\n";
		echo "				</tr>\n";

		echo "				<tr>\n";
		echo "				<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "					".$text['label-email_connection_password']."\n";
		echo "				</td>\n";
		echo "				<td class='vtable' align='left'>\n";
		echo "				  <input type='password' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
		echo "					<input class='formfld password' type='password' name='fax_email_connection_password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete='off' maxlength='50' value=\"".escape($fax_email_connection_password ?? '')."\">\n";
		echo "				<br />\n";
		echo "					".$text['description-email_connection_password']."\n";
		echo "				</td>\n";
		echo "				</tr>\n";

		echo "				<tr>\n";
		echo "				<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "					".$text['label-email_connection_mailbox']."\n";
		echo "				</td>\n";
		echo "				<td class='vtable' align='left'>\n";
		echo "					<input class='formfld' type='text' name='fax_email_connection_mailbox' maxlength='255' value=\"".escape($fax_email_connection_mailbox ?? '')."\">\n";
		echo "				<br />\n";
		echo "					".$text['description-email_connection_mailbox']."\n";
		echo "				</td>\n";
		echo "				</tr>\n";

		echo "				</table>\n";
		echo "			</div>\n";
		echo "		</td>";
		echo "		<td style='white-space: nowrap;'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
		echo "		<td width='50%' valign='top'>";

		echo "<div class='card'>\n";
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
		echo "</div>\n";

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
