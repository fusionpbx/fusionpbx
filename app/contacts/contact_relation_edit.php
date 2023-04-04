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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_relation_edit') || permission_exists('contact_relation_add')) {
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
		$contact_relation_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the contact uuid
	if (is_uuid($_GET["contact_uuid"])) {
		$contact_uuid = $_GET["contact_uuid"];
	}

//get http post variables and set them to php variables
	if (is_array($_POST) && @sizeof($_POST) != 0) {
		$relation_label = $_POST["relation_label"];
		$relation_label_custom = $_POST["relation_label_custom"];
		$relation_contact_uuid = $_POST["relation_contact_uuid"];
		$relation_reciprocal = $_POST["relation_reciprocal"];
		$relation_reciprocal_label = $_POST["relation_reciprocal_label"];
		$relation_reciprocal_label_custom = $_POST["relation_reciprocal_label_custom"];

		//use custom label(s), if set
		$relation_label = ($relation_label_custom != '') ? $relation_label_custom : $relation_label;
		$relation_reciprocal_label = ($relation_reciprocal_label_custom != '') ? $relation_reciprocal_label_custom : $relation_reciprocal_label;
	}

//process the form data
	if (is_array($_POST) && @sizeof($_POST) != 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the uuid
			if ($action == "update") {
				$contact_relation_uuid = $_POST["contact_relation_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: contacts.php');
				exit;
			}

		//check for all required data
			$msg = '';
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

				//update last modified
					$array['contacts'][0]['contact_uuid'] = $contact_uuid;
					$array['contacts'][0]['domain_uuid'] = $domain_uuid;
					$array['contacts'][0]['last_mod_date'] = 'now()';
					$array['contacts'][0]['last_mod_user'] = $_SESSION['username'];

					$p = new permissions;
					$p->add('contact_edit', 'temp');

					$database = new database;
					$database->app_name = 'contacts';
					$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
					$database->save($array);
					unset($array);

					$p->delete('contact_edit', 'temp');

				//add the relation
					if ($action == "add" && permission_exists('contact_relation_add')) {
						$contact_relation_uuid = uuid();
						$array['contact_relations'][0]['contact_relation_uuid'] = $contact_relation_uuid;

						if ($relation_reciprocal) {
							$contact_relation_uuid = uuid();
							$array['contact_relations'][1]['contact_relation_uuid'] = $contact_relation_uuid;
							$array['contact_relations'][1]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['contact_relations'][1]['contact_uuid'] = $relation_contact_uuid;
							$array['contact_relations'][1]['relation_label'] = $relation_reciprocal_label;
							$array['contact_relations'][1]['relation_contact_uuid'] = $contact_uuid;
						}

						message::add($text['message-add']);
					}

				//update the relation
					if ($action == "update" && permission_exists('contact_relation_edit')) {
						$array['contact_relations'][0]['contact_relation_uuid'] = $contact_relation_uuid;

						message::add($text['message-update']);
					}

				//execute
					if (is_array($array) && @sizeof($array) != 0) {
						$array['contact_relations'][0]['contact_uuid'] = $contact_uuid;
						$array['contact_relations'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['contact_relations'][0]['relation_label'] = $relation_label;
						$array['contact_relations'][0]['relation_contact_uuid'] = $relation_contact_uuid;

						$database = new database;
						$database->app_name = 'contacts';
						$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
						$database->save($array);
						unset($array);
					}

				//redirect
					header("Location: contact_edit.php?id=".escape($contact_uuid));
					exit;

			}
	}

//pre-populate the form
	if (is_array($_GET) && @sizeof($_GET) != 0 && $_POST["persistformvar"] != "true") {
		$contact_relation_uuid = $_GET["id"];
		$sql = "select * from v_contact_relations ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_relation_uuid = :contact_relation_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['contact_relation_uuid'] = $contact_relation_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$relation_label = $row["relation_label"];
			$relation_contact_uuid = $row["relation_contact_uuid"];
		}
		unset($sql, $parameters, $row);
	}

//get contact details and contact_name
	$sql = "select contact_uuid, contact_organization, contact_name_given, contact_name_family, contact_nickname ";
	$sql .= "from v_contacts ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid <> :contact_uuid ";
	$sql .= "order by contact_organization desc, contact_name_given asc, contact_name_family asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['contact_uuid'] = $contact_relation_uuid;
	$database = new database;
	$contacts = $database->select($sql, $parameters, 'all');
	if (is_array($contacts) && is_uuid($row['relation_contact_uuid'])) {
		foreach($contacts as $field) {
			if ($field['contact_uuid'] == $row['relation_contact_uuid']) {
				$name = array();
				if ($field['contact_organization'] != '') { $name[] = $field['contact_organization']; }
				if ($field['contact_name_family'] != '') { $name[] = $field['contact_name_family']; }
				if ($field['contact_name_given'] != '') { $name[] = $field['contact_name_given']; }
				if ($field['contact_name_family'] == '' && $field['contact_name_given'] == '' && $field['contact_nickname'] != '') { $name[] = $field['contact_nickname']; }
				$contact_name = implode(', ', $name);
				break;
			}
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-contact_relation'];
	require_once "resources/header.php";

?>

<script type="text/javascript">
	function get_contacts(element_id, id, search) {
		var xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				//create a handle for the contact select object
				select = document.getElementById(element_id);

				//remove current options
				while (select.options.length > 0) {
					select.remove(0);
				}

				//add an empty row
				//select.add(new Option('', ''));

				//add new options from the json results
				obj = JSON.parse(this.responseText);
				for (var i=0; i < obj.length; i++) {
					select.add(new Option(obj[i].name, obj[i].id));
				}
			}
		};
		if (search) {
			xhttp.open("GET", "/app/contacts/contact_json.php?search="+search, true);
		}
		else {
			xhttp.open("GET", "/app/contacts/contact_json.php", true);
		}
		xhttp.send();
	}
</script>

<?php

//javascript to toggle input/select boxes
	echo "<script type='text/javascript'>";
	echo "	function toggle_custom(field) {";
	echo "		$('#'+field).toggle();";
	echo "		document.getElementById(field).selectedIndex = 0;";
	echo "		document.getElementById(field+'_custom').value = '';";
	echo "		$('#'+field+'_custom').toggle();";
	echo "		if ($('#'+field+'_custom').is(':visible')) { $('#'+field+'_custom').trigger('focus'); } else { $('#'+field).trigger('focus'); }";
	echo "	}";
	echo "</script>";

//show the content
	echo "<form method='post' name='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-contact_relation']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'contact_edit.php?id='.urlencode($contact_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_relation_label']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	if (is_array($_SESSION["contact"]["relation_label"])) {
		sort($_SESSION["contact"]["relation_label"]);
		foreach($_SESSION["contact"]["relation_label"] as $row) {
			$relation_label_options[] = "<option value='".$row."' ".(($row == $relation_label) ? "selected='selected'" : null).">".$row."</option>";
		}
		$relation_label_found = (in_array($relation_label, $_SESSION["contact"]["relation_label"])) ? true : false;
	}
	else {
		$selected[$relation_label] = "selected";
		$default_labels[] = $text['label-contact_relation_option_parent'];
		$default_labels[] = $text['label-contact_relation_option_child'];
		$default_labels[] = $text['label-contact_relation_option_employee'];
		$default_labels[] = $text['label-contact_relation_option_member'];
		$default_labels[] = $text['label-contact_relation_option_associate'];
		$default_labels[] = $text['label-contact_relation_option_other'];
		foreach ($default_labels as $default_label) {
			$relation_label_options[] = "<option value='".$default_label."' ".$selected[$default_label].">".$default_label."</option>";
		}
		$relation_label_found = (in_array($relation_label, $default_labels)) ? true : false;
	}
	echo "	<select class='formfld' ".((!$relation_label_found && $relation_label != '') ? "style='display: none;'" : null)." name='relation_label' id='relation_label' onchange=\"getElementById('relation_label_custom').value='';\">\n";
	echo "		<option value=''></option>\n";
	echo 		(is_array($relation_label_options)) ? implode("\n", $relation_label_options) : null;
	echo "	</select>\n";
	echo "	<input type='text' class='formfld' ".(($relation_label_found || $relation_label == '') ? "style='display: none;'" : null)." name='relation_label_custom' id='relation_label_custom' value=\"".((!$relation_label_found) ? htmlentities($relation_label) : null)."\">\n";
	echo "	<input type='button' id='btn_toggle_label' class='btn' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle_custom('relation_label');\">\n";
	echo "<br />\n";
	echo $text['description-relation_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-contact_relation_contact']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class=\"formfld\" type=\"text\" name=\"contact_search\" placeholder=\"search\" style=\"width: 80px;\" onkeyup=\"get_contacts('contact_select', 'contact_uuid', this.value);\" maxlength=\"255\" value=\"\">\n";
	echo "	<select class='formfld' style=\"width: 150px;\" id=\"contact_select\" name=\"relation_contact_uuid\" >\n";
	echo "		<option value='".escape($relation_contact_uuid)."'>".escape($contact_name)."</option>\n";
	echo "	</select>\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == 'add') {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_relation_reciprocal']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='relation_reciprocal' id='relation_reciprocal' onchange=\"$('#reciprocal_label').slideToggle(400);\">\n";
		echo "		<option value='0'>".$text['option-false']."</option>\n";
		echo "		<option value='1'>".$text['option-true']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-contact_relation_reciprocal']."\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<div id='reciprocal_label' style='display: none;'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-contact_relation_reciprocal_label']."\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='relation_reciprocal_label' id='relation_reciprocal_label' onchange=\"getElementById('relation_reciprocal_label_custom').value='';\">\n";
		echo "		<option value=''></option>\n";
		echo 		(is_array($relation_label_options)) ? implode("\n", $relation_label_options) : null;
		echo "	</select>\n";
		echo "	<input type='text' class='formfld' style='display: none;' name='relation_reciprocal_label_custom' id='relation_reciprocal_label_custom' value=''>\n";
		echo "	<input type='button' id='btn_toggle_reciprocal_label' class='btn' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle_custom('relation_reciprocal_label');\">\n";
		echo "<br />\n";
		echo $text['description-contact_relation_reciprocal_label']."\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}
	else {
		echo "</table>\n";
	}
	echo "<br><br>";

	echo "<input type='hidden' name='contact_uuid' value='".escape($contact_uuid)."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='contact_relation_uuid' value='".escape($contact_relation_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
