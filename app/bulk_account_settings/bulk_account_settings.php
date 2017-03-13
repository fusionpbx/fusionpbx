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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	KonradSC <konrd@yahoo.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//include the class
	require_once "resources/check_auth.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('bulk_account_settings_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}
	
//add multi-lingual support
	$language = new text;
	$text = $language->get();
	
	
//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['header-bulk_account_settings']." </b><br>\n";
	echo "	</td>\n";
	echo "		<td align='right' width='100%' style='vertical-align: top;'>";
	echo "		<form method='get' action=''>\n";
	echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo 				"<input type='button' class='btn' alt='".$text['button-devices']."' onclick=\"window.location='bulk_account_settings_devices.php'\" value='".$text['button-devices']."'>\n";
	echo 				"<input type='button' class='btn' alt='".$text['button-extensions']."' onclick=\"window.location='bulk_account_settings_extensions.php'\" value='".$text['button-extensions']."'>\n";
	echo 				"<input type='button' class='btn' alt='".$text['button-users']."' onclick=\"window.location='bulk_account_settings_users.php'\" value='".$text['button-users']."'>\n";
	echo 				"<input type='button' class='btn' alt='".$text['button-voicemails']."' onclick=\"window.location='bulk_account_settings_voicemails.php'\" value='".$text['button-voicemails']."'>\n";
//	echo 				"<input type='button' class='btn' alt='".$text['button-call_routing']."' onclick=\"window.location='bulk_account_settings_call_routing.php'\" value='".$text['button-call_routing']."'>\n";
	echo "			</td>\n";
	echo "		</form>\n";	
	echo "  </tr>\n";
	echo "  </table>\n";
//show the footer
	require_once "resources/footer.php";
?>
