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
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dialplan_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the uuids
	if (is_uuid($_REQUEST['id'])) {
		$dialplan_uuid = $_REQUEST['id'];
	}
	if (is_uuid($_REQUEST['app_uuid'])) {
		$app_uuid = $_REQUEST['app_uuid'];
	}

//set the default app_uuid
	if (!is_uuid($app_uuid)) {
		$app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
	}

//get the dialplan xml
	if (is_uuid($dialplan_uuid)) {
		$sql = "select * from v_dialplans ";
		$sql .= "where dialplan_uuid = :dialplan_uuid ";
		$parameters['dialplan_uuid'] = $dialplan_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			//$app_uuid = $row["app_uuid"];
			$dialplan_name = $row["dialplan_name"];
			$dialplan_number = $row["dialplan_number"];
			$dialplan_order = $row["dialplan_order"];
			$dialplan_continue = $row["dialplan_continue"];
			$dialplan_context = $row["dialplan_context"];
			$dialplan_xml = $row["dialplan_xml"];
			$dialplan_enabled = $row["dialplan_enabled"];
			$dialplan_description = $row["dialplan_description"];
		}
		unset($sql, $parameters, $row);
	}

//process the HTTP POST
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
		
		//build the dialplan array
			$x = 0;
			//$array['dialplans'][$x]["domain_uuid"] = $_SESSION['domain_uuid'];
			$array['dialplans'][$x]["dialplan_uuid"] = $dialplan_uuid;
			$array['dialplans'][$x]["dialplan_xml"] =  $_REQUEST['dialplan_xml'];

		//save to the data
			$database = new database;
			$database->app_name = 'dialplans';
			$database->app_uuid = $app_uuid;
			$database->save($array);
			unset($array);

			//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$dialplan_context);

		//save the message to a session variable
			message::add($text['message-update']);

		//redirect the user
			header("Location: dialplan_edit.php?id=".$dialplan_uuid."&".((strlen($app_uuid) > 0) ? "app_uuid=".$app_uuid : null));
			exit;

	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "	<input type='hidden' name='app_uuid' value='".$app_uuid."'>\n";
	echo "	<input type='hidden' name='dialplan_uuid' value='".$dialplan_uuid."'>\n";
	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n";
	echo "		<tr>\n";
	echo "			<td align='left' width='30%'>\n";
	echo "				<span class=\"title\">".$text['title-dialplan_edit']."</span><br />\n";
	echo "			</td>\n";
	echo "			<td width='70%' align='right'>\n";
	echo "				<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='dialplan_edit.php?id=".$dialplan_uuid."&".((strlen($app_uuid) > 0) ? "app_uuid=".$app_uuid : null)."';\" value='".$text['button-back']."'>\n";
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<td align='left' colspan='2'>\n";
	echo "				".$text['description-dialplan-edit']."\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>";
	echo "	<br />\n";
	echo "	<textarea name=\"dialplan_xml\" class=\"formfld\" style=\"width: 100%; max-width: 100%; height: 450px; padding:20px;\">$dialplan_xml</textarea>\n";
	echo "</form>\n";

//show the footer
	require_once "resources/footer.php";

?>
