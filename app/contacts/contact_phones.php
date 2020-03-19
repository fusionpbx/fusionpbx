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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_phone_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the contact list
	$sql = "select * from v_contact_phones ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by phone_primary desc, phone_label asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_phones = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_phones) && @sizeof($contact_phones) != 0) {

		//javascript function: send_cmd
			echo "<script type=\"text/javascript\">\n";
			echo "function send_cmd(url) {\n";
			echo "	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari\n";
			echo "		xmlhttp=new XMLHttpRequest();\n";
			echo "	}\n";
			echo "	else {// code for IE6, IE5\n";
			echo "		xmlhttp=new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
			echo "	}\n";
			echo "	xmlhttp.open(\"GET\",url,true);\n";
			echo "	xmlhttp.send(null);\n";
			echo "	document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;\n";
			echo "}\n";
			echo "</script>\n";

		//show the content
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['label-phone_numbers']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('contact_phone_delete')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all_phones' name='checkbox_all' onclick=\"edit_all_toggle('phones');\" ".($contact_phones ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "<th class='pct-15'>".$text['label-phone_label']."</th>\n";
			echo "<th>".$text['label-phone_number']."</th>\n";
			echo "<th>".$text['label-phone_type']."</th>\n";
			echo "<th>".$text['label-phone_tools']."</th>\n";
			echo "<th class='hide-md-dn'>".$text['label-phone_description']."</th>\n";
			if (permission_exists('contact_phone_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_phones) && @sizeof($contact_phones) != 0) {
				$x = 0;
				foreach ($contact_phones as $row) {
					if (permission_exists('contact_phone_edit')) {
						$list_row_url = "contact_phone_edit.php?contact_uuid=".urlencode($row['contact_uuid'])."&id=".urlencode($row['contact_phone_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('contact_phone_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='contact_phones[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_phones' value='true' onclick=\"edit_delete_action('phones');\">\n";
						echo "		<input type='hidden' name='contact_phones[$x][uuid]' value='".escape($row['contact_phone_uuid'])."' />\n";
						echo "	</td>\n";
					}
					echo "	<td>".($row['phone_label'] == strtolower($row['phone_label']) ? ucwords($row['phone_label']) : $row['phone_label'])." ".($row['phone_primary'] ? "&nbsp;<i class='fas fa-star fa-xs' style='float: right; margin-top: 0.5em; margin-right: -0.5em;' title=\"".$text['label-primary']."\"></i>" : null)."</td>\n";
					echo "	<td class='no-link'>\n";
					echo button::create(['type'=>'button','class'=>'link','label'=>escape(format_phone($row['phone_number'])),'title'=>$text['label-click_to_call'],'onclick'=>"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode($row['phone_number'])."&src_cid_number=".urlencode($row['phone_number'])."&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'])."&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'])."&src=".urlencode($_SESSION['user']['extension'][0]['user'])."&dest=".urlencode($row['phone_number'])."&rec=false&ringback=us-ring&auto_answer=true');"]);
					echo "	</td>\n";
					echo "	<td class='no-wrap'>\n";
					if ($row['phone_type_voice']) { $phone_types[] = "<i class='fas fa-phone fa-fw' style='margin-right: 3px;' title=\"".$text['label-voice']."\"></i>"; }
					if ($row['phone_type_fax']) { $phone_types[] = "<i class='fas fa-fax fa-fw' style='margin-right: 3px;' title=\"".$text['label-fax']."\"></i>"; }
					if ($row['phone_type_video']) { $phone_types[] = "<i class='fas fa-video fa-fw' style='margin-right: 3px;' title=\"".$text['label-video']."\"></i>"; }
					if ($row['phone_type_text']) { $phone_types[] = "<i class='fas fa-sms fa-fw' style='margin-right: 3px;' title=\"".$text['label-text']."\"></i>"; }
					if (is_array($phone_types)) {
						echo "	".implode(" ", $phone_types)."\n";
					}
					unset($phone_types);
					echo "	</td>\n";
					echo "	<td class='no-link no-wrap'>\n";
					echo "		<a href='../xml_cdr/xml_cdr.php?caller_id_number=".urlencode($row['phone_number'])."'>".$text['button-cdr']."</a>";
					if ($row['phone_type_voice']) {
						echo "&nbsp;<span class='hide-sm-dn'>\n";
						echo button::create(['type'=>'button','class'=>'link','label'=>$text['label-phone_call'],'title'=>$text['label-click_to_call'],'onclick'=>"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode($row['phone_number'])."&src_cid_number=".urlencode($row['phone_number'])."&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'])."&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'])."&src=".urlencode($_SESSION['user']['extension'][0]['user'])."&dest=".urlencode($row['phone_number'])."&rec=false&ringback=us-ring&auto_answer=true');"]);
						echo "</span>";
					}
					echo "	</td>\n";
					echo "	<td class='description overflow hide-md-dn'>".escape($row['phone_description'])."&nbsp;</td>\n";
					if (permission_exists('contact_phone_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
				unset($contact_phones);
			}

			echo "</table>\n";
			echo "<br />\n";

	}

?>