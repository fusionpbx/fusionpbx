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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('fax_extension_advanced')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

// Set variables from http GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? 'fax_name'));
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = $_GET['search'] ?? '';
	$show = $_GET['show'] ?? '';

// Build the query string
	$param = [];
	if (!empty($page)) {
		$param['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$param['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$param['order'] = $order;
	}
	if (!empty($search)) {
		$param['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('fax_extension_view_all')) {
		$param['show'] = $show;
	}
	$query_string = http_build_query($param);

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
				header('Location: fax.php'.($query_string ? '?'.$query_string : ''));
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

						$database->save($array);
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
					header("Location: fax_advanced.php?id=".$fax_uuid.($query_string ? '&'.$query_string : ''));
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

?>

<style>

	#test_result_layer {
		z-index: 999999;
		position: absolute;
		left: 0px;
		top: 0px;
		right: 0px;
		bottom: 0px;
		text-align: center;
		vertical-align: middle;
	}

	#test_result_container {
		display: block;
		overflow: auto;
		background-color: #fff;
		padding: 25px 25px;
		<?php
		if (http_user_agent('mobile')) {
			echo "	margin: 0;\n";
		}
		else {
			echo "	margin: auto 10%;\n";
		}
		?>
		text-align: left;
		-webkit-box-shadow: 0px 1px 20px #888;
		-moz-box-shadow: 0px 1px 20px #888;
		box-shadow: 0px 1px 20px #888;
	}

	/* clear floats after columns */
	.row:after {
		content: "";
		display: table;
		clear: both;
	}

	/* xs */
	@media screen and (max-width: 600px) {
		div.form_grid {
			width: 100%;
		}

		div.form_set {
			width: 100% !important;
			padding: 20px;
		}
	}

	/* sm+ */
	@media screen and (min-width: 601px) {
		div.form_grid {
			width: calc(100% + 20px);
		}

		div.form_set {
			width: calc(100% - 20px);
			padding: 20px;
		}
	}

@media screen and (min-width: 992px) {
    div.form_grid {
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); /* Wider columns on medium-large screens */
    }

    div.form_set {
        padding: 30px;
    }
}

@media screen and (min-width: 1200px) {
    div.form_grid {
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); /* Even wider columns on large screens */
    }

    div.form_set {
        padding: 35px;
    }
}

	div.form_grid {
		width: calc(100% + 20px);
	}

	div.form_set {
		width: calc(100% - 20px);
		padding: 20px;
	}

	div.heading {
		padding: 5px 0 15px 0;
	}

	div.field.no-wrap {
		white-space: nowrap;
	}

	input[type=text], select, textarea {
		width: 70%;
		padding: 12px;
		border: 1px solid #ccc;
		border-radius: 4px;
		resize: vertical;
	}

	label {
		padding: 12px 12px 12px 0;
		display: inline-block;
	}

	input[type=submit] {
		background-color: #4CAF50;
		color: white;
		padding: 12px 20px;
		border: none;
		border-radius: 100px;
		cursor: pointer;
		float: right;
	}

</style>

<?php

	echo "<div id='test_result_layer' style='display: none;'>\n";
	echo "	<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>\n";
	echo "		<tr>\n";
	echo "			<td align='center' valign='middle'>\n";
	echo "				<span id='test_result_container'></span>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "</div>\n";

//advanced button js
	echo "<script type='text/javascript' language='JavaScript'>\n";
	// echo "	function add_sender() {\n";
	// echo "		var newdiv = document.createElement('div');\n";
	// echo "		newdiv.innerHTML = \"<input type='text' class='formfld' style='width: 225px; min-width: 225px; max-width: 225px; margin-top: 3px;' name='fax_email_outbound_authorized_senders[]' maxlength='255'>\";";
	// echo "		document.getElementById('authorized_senders').appendChild(newdiv);";
	// echo "	}\n";

	echo "function add_sender() {\n";
	echo "	var newdiv = document.createElement('div');\n";
	echo "	newdiv.innerHTML = \"<input type='text' class='formfld' style='width: 225px; min-width: 200px; max-width: 225px; margin-top: 3px;' name='fax_email_outbound_authorized_senders[]' maxlength='255'>\"\n";
	echo "	document.getElementById('authorized_senders').appendChild(newdiv);\n";
	echo "}\n";
	echo "</script>\n";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['label-advanced_settings']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','collapse'=>'hide-xs','link'=>'fax_edit.php?id='.$fax_uuid.($query_string ? '&'.$query_string : '')]);
	echo button::create(['type'=>'button','label'=>$text['button-test'],'icon'=>'tools','id'=>'test_button','collapse'=>'hide-sm-dn','style'=>'margin-left: 15px;','onclick'=>"this.blur(); fax_advanced_test();"]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','collapse'=>'hide-xs','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'>\n";
	echo "		".$text['description-advanced_settings']."\n";
	echo "	</div>\n";
	echo "</div>\n";

	if ($action == 'update') {
		if (permission_exists('fax_extension_copy')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('fax_extension_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	if (function_exists("imap_open")) {

		echo "<div class='form_grid'>\n";

		echo "	<div class='form_set card'>\n";
		echo "		<div class='heading'>\n";
		echo "			<span style='font-weight: bold;'>".$text['label-email_account_connection']."</span>\n";
		echo "		</div>\n";
		echo "		<div style='clear: both;'></div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_connection_type']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				<select class='formfld' name='fax_email_connection_type'>\n";
		echo "					<option value='imap'>IMAP</option>\n";
		echo "					<option value='pop3' ".(!empty($fax_email_connection_type) && $fax_email_connection_type == 'pop3' ? "selected" : null).">POP3</option>\n";
		echo "				</select>\n";
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				".$text['description-email_connection_type']."\n";
		echo "			</div>\n";
		echo "		</div>\n";


		echo "		<div class='label'>\n";
		echo "			".$text['label-email_connection_server']."\n";
		echo "		</div>\n";
		echo "		<div class='field no-wrap'>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				<input class='formfld' type='text' name='fax_email_connection_host' maxlength='255' value=\"".escape($fax_email_connection_host ?? '')."\">&nbsp;<strong style='font-size: 15px;'>:</strong>&nbsp;";
		echo "				<input class='formfld' style='width: 50px; min-width: 50px; max-width: 50px;' type='text' name='fax_email_connection_port' maxlength='5' value='".($fax_email_connection_port ?? '')."'>";
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				".$text['description-email_connection_server']."\n";
		echo "			</div>\n";
		echo "		</div>\n";
		// echo "		<div class='label' style='padding: 0;'>&nbsp;</div>\n";
		// echo "		<div class='field' style='padding: 0;'>\n";
		// echo "			".$text['description-email_connection_server']."\n";
		// echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_connection_security']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				<select class='formfld' name='fax_email_connection_security'>\n";
		echo "					<option value=''></option>\n";
		echo "					<option value='ssl' ".(!empty($fax_email_connection_security) && $fax_email_connection_security == 'ssl' ? "selected" : null).">SSL</option>\n";
		echo "					<option value='tls' ".(!empty($fax_email_connection_security) && $fax_email_connection_security == 'tls' ? "selected" : null).">TLS</option>\n";
		echo "				</select>\n";
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				&nbsp;\n";
		echo "			</div>\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_connection_validate']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<div style='clear: both;'>\n";
		if ($input_toggle_style_switch) {
			echo "	<span class='switch'>\n";
		}
		echo "	<select class='formfld' id='fax_email_connection_validate' name='fax_email_connection_validate'>\n";
		echo "		<option value='true' ".($fax_email_connection_validate == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($fax_email_connection_validate == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
		if ($input_toggle_style_switch) {
			echo "		<span class='slider'></span>\n";
			echo "	</span>\n";
		}
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				".$text['description-email_connection_validate']."\n";
		echo "			</div>\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_connection_username']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				<input class='formfld' type='text' name='fax_email_connection_username' maxlength='255' value=\"".escape($fax_email_connection_username ?? '')."\">\n";
		echo "				<input type='text' style='display: none;' disabled='disabled'>\n";
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				".$text['description-email_connection_username']."\n";
		echo "			</div>\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_connection_password']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				<input type='password' style='display: none;' disabled='disabled'>\n";
		echo "				<input class='formfld password' type='password' name='fax_email_connection_password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete='off' maxlength='50' value=\"".escape($fax_email_connection_password ?? '')."\">\n";
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				".$text['description-email_connection_password']."\n";
		echo "			</div>\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_connection_mailbox']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				<input class='formfld' type='text' name='fax_email_connection_mailbox' maxlength='255' value=\"".escape($fax_email_connection_mailbox ?? '')."\">\n";
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				".$text['description-email_connection_mailbox']."\n";
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</div>\n";

		echo "	<div class='form_set card'>\n";
		echo "		<div class='heading'>\n";
		echo "			<span style='font-weight: bold;'>".$text['label-email_remote_inbox']."</span>\n";
		echo "		</div>\n";
		echo "		<div style='clear: both;'></div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_inbound_subject_tag']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				<span style='font-size: 18px;'>[ <input class='formfld' type='text' name='fax_email_inbound_subject_tag' maxlength='255' value=\"".escape($fax_email_inbound_subject_tag ?? '')."\"> ]</span>\n";
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				".$text['description-email_inbound_subject_tag']."\n";
		echo "			</div>\n";
		echo "		</div>\n";

		echo "		<div class=''>\n";
		echo "			&nbsp;\n";
		echo "		</div>\n";
		echo "		<div class=''>\n";
		echo "			&nbsp;\n";
		echo "		</div>\n";
	
		echo "		<div style='clear: both;'>\n";
		echo "			<span style='font-weight: bold;'>".$text['label-email_email-to-fax']."</span><br><br>";
		echo "		</div>\n";
		echo "		<div style='clear: both;'>\n";
		echo "			&nbsp;\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_outbound_subject_tag']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				<span style='font-size: 18px;'>[ <input class='formfld' type='text' name='fax_email_outbound_subject_tag' maxlength='255' value=\"".($fax_email_outbound_subject_tag ?? '')."\"> ]</span>\n";
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				".$text['description-email_outbound_subject_tag']."\n";
		echo "			</div>\n";
		echo "		</div>\n";

		echo "		<div class='label'>\n";
		echo "			".$text['label-email_outbound_authorized_senders']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<div id='authorized_senders'>\n";
		if (!empty($fax_email_outbound_authorized_senders)) {
			if (substr_count($fax_email_outbound_authorized_senders, ',') > 0) {
				$senders = explode(',', $fax_email_outbound_authorized_senders);
			}
			else {
				$senders[] = $fax_email_outbound_authorized_senders;
			}
		}
		$senders[] = '';
		foreach ($senders as $sender_num => $sender) {
			echo "			<input class='formfld' style='width: 225px; min-width: 200px; max-width: 225px; ".($sender_num > 0 ? "margin-top: 3px;" : null)."' type='text' name='fax_email_outbound_authorized_senders[]' maxlength='255' value=\"$sender\">".(sizeof($senders) > 0 && $sender_num < (sizeof($senders) - 1) ? "<br>" : null);
		}

		echo "				<a href='javascript:void(0);' onclick='add_sender();'>$v_link_label_add</a>";
		echo "			</div>\n";
		echo "			<div style='clear: both;'>\n";
		echo "				".$text['description-email_outbound_authorized_senders']."\n";
		echo "			</div>\n";
		echo "		</div>\n";

		echo "		<div class=''>\n";
		echo "			<br /><br /><br />\n";
		echo "			<br /><br /><br />\n";
		echo "			<br /><br /><br />\n";
		echo "		</div>\n";
		echo "		<div class=''>\n";
		echo "			&nbsp;\n";
		echo "		</div>\n";

		echo "	</div>\n";

		echo "</div>\n";

		echo "<br><br>\n";
	}

	if ($action == "update") {
		echo "	<input type='hidden' name='fax_uuid' value='".escape($fax_uuid)."'>\n";
		echo "	<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid ?? '')."'>\n";
	}
	echo "	<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";
	echo "<br />\n";

//test script
	echo "<script>\n";
	echo "	function fax_advanced_test() {\n";
	echo "		document.getElementById('test_button').innerHTML = \"<span class='fa-solid fa-gear fa-fw fa-spin'></span><span class='button-label pad'>".$text['label-testing']."</span>\";\n";
	echo "		$.ajax({\n";
	echo "			url: 'fax_advanced_test.php?id=".$fax_uuid.($query_string ? '&'.$query_string : '')."',\n";
	echo "			type: 'get',\n";
	echo "			processData: false,\n";
	echo "			contentType: false,\n";
	echo "			cache: false,\n";
	echo "			success: function(response){\n";
	echo "				$('#test_result_container').html(response);\n";
	echo "				$('#test_result_layer').fadeIn(400);\n";
	echo "				$('#test_button').html(\"<span class='fa-solid fa-tools fa-fw'></span><span class='button-label pad'>".$text['button-test']."</span>\");\n";
	echo "			}\n";
	echo "		});\n";
	echo "	};\n";
	echo "</script>\n";

//show the footer
	require_once "resources/footer.php";

?>
