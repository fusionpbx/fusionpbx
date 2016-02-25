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
 Portions created by the Initial Developer are Copyright (C) 2008-2012
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('voicemail_add') || permission_exists('voicemail_edit')) {
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
		$voicemail_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http variables and set them to php variables
	$referer_path = check_str($_REQUEST["referer_path"]);
	$referer_query = check_str($_REQUEST["referer_query"]);
	if (count($_POST)>0) {
		//set the variables from the HTTP values
			$voicemail_id = check_str($_POST["voicemail_id"]);
			$voicemail_password = check_str($_POST["voicemail_password"]);
			$greeting_id = check_str($_POST["greeting_id"]);
			$voicemail_options = $_POST["voicemail_options"];
			$voicemail_mail_to = check_str($_POST["voicemail_mail_to"]);
			$voicemail_file = check_str($_POST["voicemail_file"]);
			$voicemail_local_after_email = check_str($_POST["voicemail_local_after_email"]);
			$voicemail_enabled = check_str($_POST["voicemail_enabled"]);
			$voicemail_description = check_str($_POST["voicemail_description"]);
		//remove the space
			$voicemail_mail_to = str_replace(" ", "", $voicemail_mail_to);

		echo "<pre>"; print_r($voicemail_options); echo "</pre>";
	}

//unassign the voicemail id copy from the voicemail id
	if ($_GET["a"] == "delete" && strlen($voicemail_uuid) > 0 && strlen($_REQUEST["voicemail_destination_uuid"]) > 0) {
		//set the variables
			$voicemail_destination_uuid = check_str($_REQUEST["voicemail_destination_uuid"]);
		//delete the voicemail from the destionations
			$sqld = "
				delete from
					v_voicemail_destinations as d
				where
					d.voicemail_destination_uuid = '".$voicemail_destination_uuid."' and
					d.voicemail_uuid = '".$voicemail_uuid."'";
			$db->exec(check_sql($sqld));
		//redirect the browser
			$_SESSION["message"] = $text['message-delete'];
			header("Location: voicemail_edit.php?id=".$voicemail_uuid);
			return;
	}

//assign the voicemail id copy to the voicemail id
	if (strlen($voicemail_uuid) > 0 && strlen($_REQUEST["voicemail_uuid_copy"]) > 0) {
		//set the variables
			$voicemail_uuid_copy = check_str($_REQUEST["voicemail_uuid_copy"]);
		//assign the user to the extension
			$sqli = "
				insert into
				v_voicemail_destinations
				(
					domain_uuid,
					voicemail_destination_uuid,
					voicemail_uuid,
					voicemail_uuid_copy
				)
				values
				(
					'".$domain_uuid."',
					'".uuid()."',
					'".$voicemail_uuid."',
					'".$voicemail_uuid_copy."'
				)";
			$db->exec(check_sql($sqli));
		//redirect the browser
			$_SESSION["message"] = $text['message-add'];
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$voicemail_uuid = check_str($_POST["voicemail_uuid"]);
	}

	//check for all required data
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
			if ($action == "add" && permission_exists('voicemail_add')) {
				$sql = "insert into v_voicemails ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "voicemail_uuid, ";
				$sql .= "voicemail_id, ";
				$sql .= "voicemail_password, ";
				$sql .= "greeting_id, ";
				$sql .= "voicemail_mail_to, ";
				$sql .= "voicemail_file, ";
				$sql .= "voicemail_local_after_email, ";
				$sql .= "voicemail_enabled, ";
				$sql .= "voicemail_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$domain_uuid."', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'".$voicemail_id."', ";
				$sql .= "'".$voicemail_password."', ";
				$sql .= (($greeting_id != '') ? "'".$greeting_id."'" : 'null').", ";
				$sql .= "'".$voicemail_mail_to."', ";
				$sql .= "'".$voicemail_file."', ";
				$sql .= "'".$voicemail_local_after_email."', ";
				$sql .= "'".$voicemail_enabled."', ";
				$sql .= "'".$voicemail_description."' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-add'];
			} //if ($action == "add")

			if ($action == "update" && permission_exists('voicemail_edit')) {
				$sql = "update v_voicemails set ";
				$sql .= "voicemail_id = '".$voicemail_id."', ";
				$sql .= "voicemail_password = '".$voicemail_password."', ";
				$sql .= "greeting_id = ".(($greeting_id != '') ? "'".$greeting_id."'" : 'null').", ";
				$sql .= "voicemail_mail_to = '".$voicemail_mail_to."', ";
				$sql .= "voicemail_file = '".$voicemail_file."', ";
				$sql .= "voicemail_local_after_email = '".$voicemail_local_after_email."', ";
				$sql .= "voicemail_enabled = '".$voicemail_enabled."', ";
				$sql .= "voicemail_description = '".$voicemail_description."' ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and voicemail_uuid = '".$voicemail_uuid."'";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION["message"] = $text['message-update'];
			} //if ($action == "update")


			// add voicemail options
				if (sizeof($voicemail_options) > 0) {
					foreach ($voicemail_options as $index => $voicemail_option) {
						if ($voicemail_option['voicemail_option_digits'] == '' || $voicemail_option['voicemail_option_param'] == '') { unset($voicemail_options[$index]); }
					}
				}
				if (sizeof($voicemail_options) > 0) {
					$sql = "insert into v_voicemail_options ";
					$sql .= "( ";
					$sql .= "voicemail_option_uuid, ";
					$sql .= "voicemail_uuid, ";
					$sql .= "domain_uuid, ";
					$sql .= "voicemail_option_digits, ";
					$sql .= "voicemail_option_action, ";
					$sql .= "voicemail_option_param, ";
					$sql .= "voicemail_option_order, ";
					$sql .= "voicemail_option_description ";
					$sql .= ") ";
					$sql .= "values ";
					foreach ($voicemail_options as $index => $voicemail_option) {
						$voicemail_option_uuid = uuid();
						//seperate the action and the param
						$option_array = explode(":", $voicemail_option["voicemail_option_param"]);
						$voicemail_option['voicemail_option_action'] = array_shift($option_array);
						$voicemail_option['voicemail_option_param'] = join(':', $option_array);
						//continue building insert query
						$sql_record[$index] = "( ";
						$sql_record[$index] .= "'".$voicemail_option_uuid."', ";
						$sql_record[$index] .= "'".$voicemail_uuid."', ";
						$sql_record[$index] .= "'".$domain_uuid."', ";
						$sql_record[$index] .= "'".trim($voicemail_option['voicemail_option_digits'])."', ";
						$sql_record[$index] .= "'".trim($voicemail_option['voicemail_option_action'])."', ";
						$sql_record[$index] .= "'".trim($voicemail_option['voicemail_option_param'])."', ";
						$sql_record[$index] .= $voicemail_option['voicemail_option_order'].", ";
						$sql_record[$index] .= "'".trim($voicemail_option['voicemail_option_description'])."' ";
						$sql_record[$index] .= ") ";
					}
					$sql .= implode(",", $sql_record);
					$db->exec(check_sql($sql));
					unset($sql);
				}

			//redirect user
				if ($action == 'add') {
					header("Location: voicemails.php");
				}
				else if ($action == "update") {
					header("Location: voicemail_edit.php?id=".$voicemail_uuid);
				}
				exit;

		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//initialize the destinations object
	$destination = new destinations;

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$voicemail_uuid = check_str($_GET["id"]);
		$sql = "select * from v_voicemails ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and voicemail_uuid = '".$voicemail_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$voicemail_id = $row["voicemail_id"];
			$voicemail_password = $row["voicemail_password"];
			$greeting_id = $row["greeting_id"];
			$voicemail_mail_to = $row["voicemail_mail_to"];
			$voicemail_file = $row["voicemail_file"];
			$voicemail_local_after_email = $row["voicemail_local_after_email"];
			$voicemail_enabled = $row["voicemail_enabled"];
			$voicemail_description = $row["voicemail_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}
	else {
		$voicemail_file = $_SESSION['voicemail']['voicemail_file']['text'];
		$voicemail_local_after_email = $_SESSION['voicemail']['keep_local']['boolean'];
	}

//remove the spaces
	$voicemail_mail_to = str_replace(" ", "", $voicemail_mail_to);

//set defaults
	if (strlen($voicemail_local_after_email) == 0) { $voicemail_local_after_email = "true"; }
	if (strlen($voicemail_enabled) == 0) { $voicemail_enabled = "true"; }

//get the greetings list
	$sql = "select * from v_voicemail_greetings ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$sql .= "and voicemail_id = '".$voicemail_id."' ";
	$sql .= "order by greeting_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$greetings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$greeting_count = count($greetings);
	unset ($prep_statement, $sql);

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-voicemail'];

//show the content
	echo "<form method='post' name='frm' id='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'>";
	echo "	<b>".$text['title-voicemail']."</b>";
	echo "	<br><br>";
	echo "</td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"javascript:history.back();\" value='".$text['button-back']."'>\n";
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_id' maxlength='255' value='$voicemail_id'>\n";
	echo "<br />\n";
	echo $text['description-voicemail_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='password' name='voicemail_password' id='password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" autocomplete='off' maxlength='50' value=\"$voicemail_password\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-greeting']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='greeting_id'>\n";
	echo "		<option value=''></option>\n";
	if ($greeting_count > 0) {
		foreach ($greetings as $greeting) {
			$selected = ($greeting['greeting_id'] == $greeting_id) ? 'selected' : null;
			echo "<option value='".$greeting['greeting_id']."' ".$selected.">".$greeting['greeting_name']."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-greeting']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-options']."</td>";
	echo "		<td class='vtable' align='left'>";
	echo "			<table width='59%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "				<tr>\n";
	echo "					<td class='vtable'>".$text['label-option']."</td>\n";
	echo "					<td class='vtable'>".$text['label-destination']."</td>\n";
	echo "					<td class='vtable'>".$text['label-order']."</td>\n";
	echo "					<td class='vtable'>".$text['label-description']."</td>\n";
	echo "					<td></td>\n";
	echo "				</tr>\n";
	if (strlen($voicemail_uuid) > 0) {
		$sql = "select * from v_voicemail_options ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and voicemail_uuid = '".$voicemail_uuid."' ";
		$sql .= "order by voicemail_option_digits, voicemail_option_order asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $field) {
			$voicemail_option_param = $field['voicemail_option_param'];
			if (strlen(trim($voicemail_option_param)) == 0) {
				$voicemail_option_param = $field['voicemail_option_action'];
			}
			$voicemail_option_param = str_replace("menu-", "", $voicemail_option_param);
			$voicemail_option_param = str_replace("XML", "", $voicemail_option_param);
			$voicemail_option_param = str_replace("transfer", "", $voicemail_option_param);
			$voicemail_option_param = str_replace("bridge", "", $voicemail_option_param);
			$voicemail_option_param = str_replace($_SESSION['domain_name'], "", $voicemail_option_param);
			$voicemail_option_param = str_replace("\${domain_name}", "", $voicemail_option_param);
			$voicemail_option_param = str_replace("\${domain}", "", $voicemail_option_param);
			$voicemail_option_param = ucfirst(trim($voicemail_option_param));
			echo "				<tr>\n";
			echo "					<td class='vtable'>\n";
			echo "						".$field['voicemail_option_digits'];
			echo "					</td>\n";
			echo "					<td class='vtable'>\n";
			echo "						".$voicemail_option_param."&nbsp;\n";
			echo "					</td>\n";
			echo "					<td class='vtable'>\n";
			echo "						".$field['voicemail_option_order']."&nbsp;\n";
			echo "					</td>\n";
			echo "					<td class='vtable'>\n";
			echo "						".$field['voicemail_option_description']."&nbsp;\n";
			echo "					</td>\n";
			echo "					<td class='list_control_icons'>";
			echo 						"<a href='voicemail_option_edit.php?id=".$field['voicemail_option_uuid']."&voicemail_uuid=".$field['voicemail_uuid']."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
			echo 						"<a href='voicemail_option_delete.php?id=".$field['voicemail_option_uuid']."&voicemail_uuid=".$field['voicemail_uuid']."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
			echo "					</td>\n";
			echo "				</tr>\n";
		}
	}
	unset($sql, $result);

	for ($c = 0; $c < 1; $c++) {
		echo "				<tr>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' style='width:70px' type='text' name='voicemail_options[".$c."][voicemail_option_digits]' maxlength='255' value='".$voicemail_option_digits."'>\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left' nowrap='nowrap'>\n";
		echo $destination->select('ivr', 'voicemail_options['.$c.'][voicemail_option_param]', '');
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select name='voicemail_options[".$c."][voicemail_option_order]' class='formfld' style='width:55px'>\n";
		//echo "	<option></option>\n";
		if (strlen(htmlspecialchars($voicemail_option_order))> 0) {
			echo "	<option selected='yes' value='".htmlspecialchars($voicemail_option_order)."'>".htmlspecialchars($voicemail_option_order)."</option>\n";
		}
		$i=0;
		while($i<=999) {
			if (strlen($i) == 1) {
				echo "	<option value='00$i'>00$i</option>\n";
			}
			if (strlen($i) == 2) {
				echo "	<option value='0$i'>0$i</option>\n";
			}
			if (strlen($i) == 3) {
				echo "	<option value='$i'>$i</option>\n";
			}
			$i++;
		}
		echo "	</select>\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' style='width:100px' type='text' name='voicemail_options[".$c."][voicemail_option_description]' maxlength='255' value=\"".$voicemail_option_description."\">\n";
		echo "</td>\n";

		echo "					<td>\n";
		echo "						<input type='button' class='btn' value=\"".$text['button-add']."\" onclick='submit_form();'>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
	}
	echo "			</table>\n";

	echo "			".$text['description-options']."\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_mail_to']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_mail_to' maxlength='255' value=\"$voicemail_mail_to\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_mail_to']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_file']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='voicemail_file' id='voicemail_file' onchange=\"if (this.selectedIndex != 2) { document.getElementById('voicemail_local_after_email').selectedIndex = 0; }\">\n";
	echo "    	<option value='' ".(($voicemail_file == "listen") ? "selected='selected'" : null).">".$text['option-voicemail_file_listen']."</option>\n";
	echo "    	<option value='link' ".(($voicemail_file == "link") ? "selected='selected'" : null).">".$text['option-voicemail_file_link']."</option>\n";
	echo "    	<option value='attach' ".(($voicemail_file == "attach") ? "selected='selected'" : null).">".$text['option-voicemail_file_attach']."</option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-voicemail_file']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_local_after_email']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='voicemail_local_after_email' id='voicemail_local_after_email' onchange=\"if (this.selectedIndex == 1) { document.getElementById('voicemail_file').selectedIndex = 2; }\">\n";
	echo "    	<option value='true' ".(($voicemail_local_after_email == "true") ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
	echo "    	<option value='false' ".(($voicemail_local_after_email == "false") ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-voicemail_local_after_email']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == "update") {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-forward_destinations']."</td>";
		echo "		<td class='vtable'>";

		$sql = "
			select
				v.voicemail_id,
				d.voicemail_destination_uuid,
				d.voicemail_uuid_copy
			from
				v_voicemails as v,
				v_voicemail_destinations as d
			where
				d.voicemail_uuid_copy = v.voicemail_uuid and
				v.domain_uuid = '".$_SESSION['domain_uuid']."' and
				v.voicemail_enabled = 'true' and
				d.voicemail_uuid = '".$voicemail_uuid."'
			order by
				v.voicemail_id asc";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		if ($result_count > 0) {
			echo "		<table width='52%'>\n";
			foreach($result as $field) {
				echo "		<tr>\n";
				echo "			<td class='vtable'>".$field['voicemail_id']."</td>\n";
				echo "			<td>\n";
				echo "				<a href='voicemail_edit.php?id=".$voicemail_uuid."&voicemail_destination_uuid=".$field['voicemail_destination_uuid']."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
				echo "			</td>\n";
				echo "		</tr>\n";
				$voicemail_uuid_copied[] = $field['voicemail_uuid_copy'];
			}
			echo "		</table>\n";
			echo "		<br />\n";
		}

		if (sizeof($voicemail_uuid_copied) > 0) {
			// modify sql to remove already copied voicemail uuids from the list
			$sql_mod = " and v.voicemail_uuid not in ('".implode("','", $voicemail_uuid_copied)."') ";
		}

		$sql = "
			select
				v.voicemail_id,
				v.voicemail_uuid
			from
				v_voicemails as v
			where
				v.domain_uuid = '".$_SESSION['domain_uuid']."' and
				v.voicemail_enabled = 'true' and
				v.voicemail_uuid <> '".$voicemail_uuid."'
				".$sql_mod."
			order by
				v.voicemail_id asc";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		echo "			<select name=\"voicemail_uuid_copy\" class='formfld' style='width: auto;'>\n";
		echo "			<option value=\"\"></option>\n";
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach($result as $field) {
			echo "			<option value='".$field['voicemail_uuid']."'>".$field['voicemail_id']."</option>\n";
		}
		echo "			</select>";
		echo "			<input type='button' class='btn' value=\"".$text['button-add']."\" onclick='submit_form();'>\n";
		unset($sql, $result);
		echo "			<br>\n";
		echo "			".$text['description-forward_destinations']."\n";
		echo "			<br />\n";
		echo "		</td>";
		echo "	</tr>";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='voicemail_enabled'>\n";
	if ($voicemail_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($voicemail_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-voicemail_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-voicemail_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_description' maxlength='255' value=\"$voicemail_description\">\n";
	echo "<br />\n";
	echo $text['description-voicemail_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='voicemail_uuid' value='$voicemail_uuid'>\n";
	}
	$http_referer = parse_url($_SERVER["HTTP_REFERER"]);
	echo "				<input type='hidden' name='referer_path' value='".$http_referer['path']."'>\n";
	echo "				<input type='hidden' name='referer_query' value='".$http_referer['query']."'>\n";
	echo "				<br>";
	echo "				<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	echo "<script>\n";
//capture enter key to submit form
	echo "	$(window).keypress(function(event){\n";
	echo "		if (event.which == 13) { submit_form(); }\n";
	echo "	});\n";
// convert password fields to
	echo "	function submit_form() {\n";
	echo "		$('input:password').css('visibility','hidden');\n";
	echo "		$('input:password').attr({type:'text'});\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";
?>