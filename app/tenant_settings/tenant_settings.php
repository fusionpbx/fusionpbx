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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.
	
	Contributor(s):
	Matthew Vale <github@mafoo.org>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('tenant_settings_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
//add multi-lingual support
	$language = new text;
	$text = $language->get();

//header and paging
	require_once "resources/header.php";
	$document['title'] = $text['title-tenant_settings'];
	require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//copy settings javascript
	if (permission_exists("tenant_settings_admin") && count($_SESSION['domains']) > 1) {
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "	var fade_speed = 400;\n";
		echo "	function show_domains() {\n";
		echo "		document.getElementById('action').value = 'copy';\n";
		echo "		$('#button_copy').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_back').fadeIn(fade_speed);\n";
		echo "			$('#target_domain_uuid').fadeIn(fade_speed);\n";
		echo "			$('#button_paste').fadeIn(fade_speed);\n";
		echo "		});";
		echo "	}";
		echo "	function hide_domains() {\n";
		echo "		document.getElementById('action').value = '';\n";
		echo "		$('#button_back').fadeOut(fade_speed);\n";
		echo "		$('#target_domain_uuid').fadeOut(fade_speed);\n";
		echo "		$('#button_paste').fadeOut(fade_speed, function() {\n";
		echo "			$('#button_copy').fadeIn(fade_speed);\n";
		echo "			document.getElementById('target_domain_uuid').selectedIndex = 0;\n";
		echo "		});\n";
		echo "	}\n";
		echo "\n";
		echo "	$( document ).ready(function() {\n";
		echo "		$('#default_setting_search').focus();\n";
		if ($search == '') {
			echo "		// scroll to previous category\n";
			echo "		var category_span_id;\n";
			echo "		var url = document.location.href;\n";
			echo "		var hashindex = url.indexOf('#');\n";
			echo "		if (hashindex == -1) { }\n";
			echo "		else {\n";
			echo "			category_span_id = url.substr(hashindex + 1);\n";
			echo "		}\n";
			echo "		if (category_span_id) {\n";
			echo "			$('#page').animate({scrollTop: $('#anchor_'+category_span_id).offset().top - 200}, 'slow');\n";
			echo "		}\n";
		}
		echo "	});\n";
		echo "</script>";
	}

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>";
	echo "<input type='hidden' name='action' id='action' value=''>";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top' nowrap='nowrap'>";
	echo "			<b>".$text['header-default_settings']."</b>";
	echo "			<br><br>";
	echo "			".$text['description-default_settings'];
	echo "		</td>\n";
	echo "		<td align='right' valign='top' nowrap='nowrap'>";
	echo "			<input type='text' name='search' id='default_setting_search' class='formfld' style='min-width: 150px; width:150px; max-width: 150px;' placeholder=\"".$text['label-search']."\" value=\"".$search."\" onkeyup='setting_search();'>\n";
	if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
		echo "		<input type='button' class='btn' id='button_copy' alt='".$text['button-copy']."' onclick='show_domains();' value='".$text['button-copy']."'>";
		echo "		<input type='button' class='btn' style='display: none;' id='button_back' alt='".$text['button-back']."' onclick='hide_domains();' value='".$text['button-back']."'> ";
		echo "		<select class='formfld' style='display: none; width: auto;' name='target_domain_uuid' id='target_domain_uuid'>\n";
		echo "			<option value=''>Select Domain...</option>\n";
		foreach ($_SESSION['domains'] as $domain) {
			echo "		<option value='".$domain["domain_uuid"]."'>".$domain["domain_name"]."</option>\n";
		}
		echo "		</select>\n";
		echo "		<input type='button' class='btn' id='button_paste' style='display: none;' alt='".$text['button-paste']."' value='".$text['button-paste']."' onclick='document.forms.frm.submit();'>";
	}
	echo "			<input type='button' class='btn' id='button_reload' alt='".$text['button-reload']."' value='".$text['button-reload']."' onclick=\"document.location.href='default_settings_reload.php?search='+$('#default_setting_search').val();\">";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>";


//include the footer
	require_once "resources/footer.php";
?>