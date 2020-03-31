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
			echo "<script type='text/javascript'>\n";
			echo "function send_cmd(url) {\n";
			echo "	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari\n";
			echo "		xmlhttp=new XMLHttpRequest();\n";
			echo "	}\n";
			echo "	else {// code for IE6, IE5\n";
			echo "		xmlhttp=new ActiveXObject('Microsoft.XMLHTTP');\n";
			echo "	}\n";
			echo "	xmlhttp.open('GET',url,true);\n";
			echo "	xmlhttp.send(null);\n";
			echo "	document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;\n";
			echo "}\n";
			echo "</script>\n";

		//show the content
			echo "<div class='grid' style='grid-template-columns: 70px 120px auto;'>\n";
			$x = 0;
			foreach ($contact_phones as $row) {
				echo "<div class='box contact-details-label'>".($row['phone_label'] == strtolower($row['phone_label']) ? ucwords($row['phone_label']) : $row['phone_label'])."</div>\n";
// 				($row['phone_primary'] ? "&nbsp;<i class='fas fa-star fa-xs' style='float: right; margin-top: 0.5em; margin-right: -0.5em;' title=\"".$text['label-primary']."\"></i>" : null)."</td>\n";
				echo "<div class='box'>";
				echo button::create(['type'=>'button','class'=>'link','label'=>escape(format_phone($row['phone_number'])),'title'=>$text['label-click_to_call'],'onclick'=>"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode($row['phone_number'])."&src_cid_number=".urlencode($row['phone_number'])."&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'])."&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'])."&src=".urlencode($_SESSION['user']['extension'][0]['user'])."&dest=".urlencode($row['phone_number'])."&rec=false&ringback=us-ring&auto_answer=true');"]);
				echo "</div>\n";
				echo "<div class='box no-wrap'>";
				if ($row['phone_type_voice']) { $phone_types[] = "<i class='fas fa-phone fa-fw' style='margin-right: 3px;' title=\"".$text['label-voice']."\"></i>"; }
				if ($row['phone_type_fax']) { $phone_types[] = "<i class='fas fa-fax fa-fw' style='margin-right: 3px;' title=\"".$text['label-fax']."\"></i>"; }
				if ($row['phone_type_video']) { $phone_types[] = "<i class='fas fa-video fa-fw' style='margin-right: 3px;' title=\"".$text['label-video']."\"></i>"; }
				if ($row['phone_type_text']) { $phone_types[] = "<i class='fas fa-sms fa-fw' style='margin-right: 3px;' title=\"".$text['label-text']."\"></i>"; }
				if (is_array($phone_types)) {
					echo "	".implode(" ", $phone_types)."\n";
				}
				unset($phone_types);
				echo "</div>\n";
				$x++;
			}
			echo "</div>\n";
			unset($contact_phones);

	}

?>