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
	if (permission_exists('contact_extension_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the extension list
	$sql = "select e.extension_uuid, e.extension, e.enabled, e.description ";
	$sql .= "from v_extensions e, v_extension_users eu, v_users u ";
	$sql .= "where e.extension_uuid = eu.extension_uuid ";
	$sql .= "and u.user_uuid = eu.user_uuid ";
	$sql .= "and e.domain_uuid = :domain_uuid ";
	$sql .= "and u.contact_uuid = :contact_uuid ";
	$sql .= "order by e.extension asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_extensions) && @sizeof($contact_extensions) != 0) {

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
			echo "<div class='grid' style='grid-template-columns: 70px 100px auto;'>\n";
			$x = 0;
			foreach ($contact_extensions as $row) {
				if ($row['enabled'] != 'true') { continue; } //skip disabled extensions
				echo "<div class='box contact-details-label'>".$text['label-extension']."</div>\n";
// 				($row['url_primary'] ? "style='font-weight: bold;'" : null).">\n";
				echo "<div class='box'>";
				echo button::create(['type'=>'button','class'=>'link','label'=>escape($row['extension']),'title'=>$text['label-click_to_call'],'onclick'=>"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode($row['extension'])."&src_cid_number=".urlencode($row['extension'])."&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'])."&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'])."&src=".urlencode($_SESSION['user']['extension'][0]['user'])."&dest=".urlencode($row['extension'])."&rec=false&ringback=us-ring&auto_answer=true');"]);
				echo "</div>\n";
				echo "<div class='box'>".$row['description']."</div>\n";
				$x++;
			}
			echo "</div>\n";
			unset($contact_extensions);

	}

?>