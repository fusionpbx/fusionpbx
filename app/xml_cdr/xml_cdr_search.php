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
	Copyright (C) 2008-2018
	All Rights Reserved.

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
	if (permission_exists('xml_cdr_search_advanced')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//send the header
	$document['title'] = $text['title-advanced_search'];
	require_once "resources/header.php";

//javascript to toggle input/select boxes
	echo "<script type='text/javascript'>";
	echo "	function toggle(field) {";
	echo "		if (field == 'source') {";
	echo "			document.getElementById('caller_extension_uuid').selectedIndex = 0;";
	echo "			document.getElementById('caller_id_number').value = '';";
	echo "			$('#caller_extension_uuid').toggle();";
	echo "			$('#caller_id_number').toggle();";
	echo "			if ($('#caller_id_number').is(':visible')) { $('#caller_id_number').trigger('focus'); } else { $('#caller_extension_uuid').trigger('focus'); }";
	echo "		}";
	echo "	}";
	echo "</script>";

//start the html form
	if ($_GET['redirect'] == 'xml_cdr_statistics') {
		echo "<form method='get' action='xml_cdr_statistics.php'>\n";
	}
	else {
		echo "<form method='get' action='xml_cdr.php'>\n";
	}
	
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-advanced_search']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'xml_cdr.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";
	
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' style='vertical-align: top;'>\n";
	
		echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<td width='30%' class='vncell' valign='top' nowrap='nowrap'>\n";
		echo "			".$text['label-direction']."\n";
		echo "		</td>\n";
		echo "		<td width='70%' class='vtable' align='left'>\n";
		echo "			<select name='direction' class='formfld'>\n";
		echo "				<option value=''></option>\n";
		if ($direction == "inbound") {
			echo "			<option value='inbound' selected='selected'>".$text['label-inbound']."</option>\n";
		}
		else {
			echo "			<option value='inbound'>".$text['label-inbound']."</option>\n";
		}
		if ($direction == "outbound") {
			echo "			<option value='outbound' selected='selected'>".$text['label-outbound']."</option>\n";
		}
		else {
			echo "			<option value='outbound'>".$text['label-outbound']."</option>\n";
		}
		if ($direction == "local") {
			echo "			<option value='local' selected='selected'>".$text['label-local']."</option>\n";
		}
		else {
			echo "			<option value='local'>".$text['label-local']."</option>\n";
		}
		echo "			</select>\n";

		if (permission_exists('xml_cdr_b_leg')){
			echo "			<select name='leg' class='formfld'>\n";
			echo "			<option value='' selected='selected'></option>\n";
			echo "			<option value='a'>a-leg</option>\n";
			echo "			<option value='b'>b-leg</option>\n";
			echo "			</select>\n";
		}

		echo "		</td>\n";
		echo "	</tr>\n";

		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-caller_id_name']."</td>"; //source name
		echo "		<td class='vtable'><input type='text' class='formfld' name='caller_id_name' value='".escape($caller_id_name)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-extension']."</td>"; //source number
		echo "		<td class='vtable'>";
		echo "			<select class='formfld' name='extension_uuid' id='extension_uuid'>\n";
		echo "				<option value=''></option>";
		$sql = "select extension_uuid, extension, number_alias from v_extensions ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "order by extension asc, number_alias asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$database = new database;
		$result_e = $database->select($sql, $parameters, 'all');
		if (is_array($result_e) && @sizeof($result_e) != 0) {
			foreach ($result_e as &$row) {
				$selected = ($row['extension_uuid'] == $caller_extension_uuid) ? "selected" : null;
				echo "			<option value='".escape($row['extension_uuid'])."' ".escape($selected).">".((is_numeric($row['extension'])) ? escape($row['extension']) : escape($row['number_alias'])." (".escape($row['extension']).")")."</option>";
			}
		}
		unset($sql, $parameters, $result_e, $row, $selected);
		echo "			</select>\n";
		echo "			<input type='text' class='formfld' style='display: none;' name='caller_id_number' id='caller_id_number' value='".escape($caller_id_number)."'>\n";
		echo "			<input type='button' id='btn_toggle_source' class='btn' name='' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle('source');\">\n";
		echo "		</td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-destination']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='destination_number' value='".escape($destination_number)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-context']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='context' value='".escape($context)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-start_range']."</td>";
		echo "		<td class='vtable'>";
		echo "			<div class='row'>\n";
		echo "				<div class='col-sm-12'>";
		echo "					<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#start_stamp_begin' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px;' name='start_stamp_begin' id='start_stamp_begin' placeholder='".$text['label-from']."' value='".escape($start_stamp_begin)."'>";
		echo "					<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#start_stamp_end' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px;' name='start_stamp_end' id='start_stamp_end' placeholder='".$text['label-to']."' value='".escape($start_stamp_end)."'>";
		echo "				</div>\n";
		echo "			</div>\n";
		echo "		</td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-answer_range']."</td>";
		echo "		<td class='vtable'>";
		echo "			<div class='row'>\n";
		echo "				<div class='col-sm-12'>";
		echo "					<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#answer_stamp_begin' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px;' name='answer_stamp_begin' id='answer_stamp_begin' placeholder='".$text['label-from']."' value='".escape($answer_stamp_begin)."'>";
		echo "					<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#answer_stamp_end' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px;' name='answer_stamp_end' id='answer_stamp_end' placeholder='".$text['label-to']."' value='".escape($answer_stamp_end)."'>";
		echo "				</div>\n";
		echo "			</div>\n";
		echo "		</td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-end_range']."</td>";
		echo "		<td class='vtable'>";
		echo "			<div class='row'>\n";
		echo "				<div class='col-sm-12'>";
		echo "					<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#end_stamp_begin' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px;' name='end_stamp_begin' id='end_stamp_begin' placeholder='".$text['label-from']."' value='".escape($end_stamp_begin)."'>";
		echo "					<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#end_stamp_end' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px;' name='end_stamp_end' id='end_stamp_end' placeholder='".$text['label-to']."' value='".escape($end_stamp_end)."'>";
		echo "				</div>\n";
		echo "			</div>\n";
		echo "		</td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-duration']." (".$text['label-seconds'].")</td>";
		echo "		<td class='vtable'>\n";
		echo "			<input type='text' class='formfld' style='min-width: 75px; width: 75px;' name='duration_min' value='".escape($duration_min)."' placeholder=\"".$text['label-minimum']."\">\n";
		echo "			<input type='text' class='formfld' style='min-width: 75px; width: 75px;' name='duration_max' value='".escape($duration_max)."' placeholder=\"".$text['label-maximum']."\">\n";
		echo "		</td>";
		echo "	</tr>";
		if (permission_exists('xml_cdr_all')) {
			echo "	<tr>";
			echo "		<td class='vncell'>".$text['button-show_all']."</td>";
			echo "		<td class='vtable'>\n";
			if (permission_exists('xml_cdr_all') && $_REQUEST['showall'] == "true") {
				echo "			<input type='checkbox' class='formfld' name='showall' checked='checked' value='true'>";
			}
			else {
				echo "			<input type='checkbox' class='formfld' name='showall' value='true'>";
			}
			echo "		<td>";
			echo "	</tr>";
		}
		echo "</table>";
	
	echo "		</td>";
	echo "		<td width='50%' style='vertical-align: top;'>\n";
	
		echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>";
		echo "		<td width='30%' class='vncell'>".$text['label-billsec']."</td>";
		echo "		<td width='70%' class='vtable'><input type='text' class='formfld' name='billsec' value='".escape($billsec)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-hangup_cause']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='hangup_cause' value='".escape($hangup_cause)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-uuid']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='xml_cdr_uuid' value='".escape($xml_cdr_uuid)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-bridge_uuid']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='bleg_uuid' value='".escape($bridge_uuid)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-accountcode']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='accountcode' value='".escape($accountcode)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-read_codec']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='read_codec' value='".escape($read_codec)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-write_codec']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='write_codec' value='".escape($write_codec)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-remote_media_ip']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='remote_media_ip' value='".escape($remote_media_ip)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-network_addr']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='network_addr' value='".escape($network_addr)."'></td>";
		echo "	</tr>";
		if (is_array($_SESSION['cdr']['field'])) {
			foreach ($_SESSION['cdr']['field'] as $field) {
				$array = explode(",", $field);
				$field_name = end($array);
				$field_label = ucwords(str_replace("_", " ", $field_name));
				$field_label = str_replace("Sip", "SIP", $field_label);
				if ($field_name != "destination_number") {
					echo "	<tr>";
					echo "		<td class='vncell'>".escape($field_label)."</td>";
					echo "		<td class='vtable'><input type='text' class='formfld' name='".escape($field_name)."' value='".escape($$field_name)."'></td>";
					echo "	</tr>";
				}
			}
		}
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-mos_score']."</td>";
		echo "		<td class='vtable'>";
		echo "			<select name='mos_comparison' class='formfld'>\n";
		echo "			<option value=''></option>\n";
		echo "			<option value='less'>&lt;</option>\n";
		echo "			<option value='greater'>&gt;</option>\n";
		echo "			<option value='lessorequal'>&lt;&#61;</option>\n";
		echo "			<option value='greaterorequal'>&gt;&#61;</option>\n";
		echo "			<option value='equal'>&#61;</option>\n";
		echo "			<option value='notequal'>&lt;&gt;</option>\n";
		echo "			</select>\n";
		echo "			<input type='text' class='formfld' name='mos_score' value='".escape($mos_score)."'>\n";
		echo "		</td>";
		echo "	</tr>\n";

		echo "</table>\n";
	
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	
	echo "</form>";

//include footer
	require_once "resources/footer.php";

?>