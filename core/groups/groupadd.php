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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('group_add')) {
		//access allowed
	}
	else {
		echo "access denied";
		return;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	if (count($_POST) > 0) {
		//set the variables
			$group_name = $_POST["group_name"];
			if (permission_exists('group_domain')) {
				$domain_uuid = $_POST["domain_uuid"];
			}
			else {
				$domain_uuid = $_SESSION['domain_uuid'];
			}
			$group_description = $_POST["group_description"];

		//check for global/domain duplicates
			$sql = "select count(*) from v_groups where ";
			$sql .= "group_name = :group_name ";
			if (is_uuid($domain_uuid)) {
				$sql .= "and domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
			}
			else {
				$sql .= "and domain_uuid is null ";
			}
			$parameters['group_name'] = $group_name;
			$database = new database;
			$num_rows = $database->select($sql, $parameters, 'column');
			$group_exists = ($num_rows > 0) ? true : false;
			unset($sql, $parameters, $num_rows);

		//insert group
			if (!$group_exists) {
				$array['groups'][0]['group_uuid'] = uuid();
				$array['groups'][0]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
				$array['groups'][0]['group_name'] = $group_name;
				$array['groups'][0]['group_description'] = $group_description;
				$database = new database;
				$database->app_name = 'groups';
				$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
				$database->save($array);
				unset($array);

				message::add($text['message-add']);
				header("Location: groups.php");
			}
			else {
				message::add($text['message-group_exists'], 'negative');
				header("Location: groupadd.php");
			}

		//redirect the user
			return;
	}

//include the header
	include "resources/header.php";
	$document['title'] = $text['title-group_add'];

//show the content
	echo "<form name='login' method='post' action=''>\n";

	echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top'>\n";
	echo "			<b>".$text['header-group_add']."</b>\n";
	echo "			<br><br>\n";
	echo "			".$text['description-group_add']."\n";
	echo "		</td>\n";
	echo "		<td align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='groups.php'\" value='".$text['button-back']."'> ";
	echo "  		<input type='submit' class='btn' value=\"".$text['button-save']."\">\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>";

	echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq'>\n";
	echo $text['label-group_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' align='left' class='vtable'>\n";
	echo "  <input type='text' class='formfld' name='group_name'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('group_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='domain_uuid'>\n";
		echo "    	<option value='' ".((strlen($domain_uuid) == 0) ? "selected='selected'" : null).">".$text['option-global']."</option>\n";
		foreach ($_SESSION['domains'] as $row) {
			echo "	<option value='".$row['domain_uuid']."' ".(($row['domain_uuid'] == $domain_uuid) ? "selected='selected'" : null).">".$row['domain_name']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-domain_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell'>\n";
	echo $text['label-group_description']."\n";
	echo "</td>\n";
	echo "<td align='left' class='vtable'>\n";
	echo "<textarea name='group_description' class='formfld' style='width: 250px; height: 50px;'></textarea>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2' align='right'>\n";
	echo "	<br />";
	echo "	<input type='submit' class='btn' value=\"".$text['button-save']."\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>";
	echo "</form>";

//include the footer
	include "resources/footer.php";

?>