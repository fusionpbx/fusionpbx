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
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions	
	if (permission_exists('mobile_twinning_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();
	
//get the https values and set as variables
	$extension_uuid = check_str($_GET["extid"]);
	$mobile_twinning_uuid = check_str($_GET["id"]);

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$mobile_twinning_uuid = check_str($_GET["id"]);
	}
	else {
		$action = "add";
	}
	
//get the http values and set them as php variables
	if (count($_POST) > 0 && $_POST["persistform"] != "1") {
		$mobile_twinning_number = check_str($_POST["mobile_twinning_number"]);
		if ($action == "update") {
			$mobile_twinning_uuid = check_str($_POST["mobile_twinning_uuid"]);
			$extension_uuid = check_str($_POST["extension_uuid"]);
			$extension = check_str($_POST["extension"]);
		}
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {


		//check for duplicate mobile in database
			$database = new database;
			$database->table = "v_mobile_twinnings";
			$where[0]["name"] = "mobile_twinning_number";
			$where[0]["operator"] = "=";
			$where[0]["value"] = "$mobile_twinning_number";
			$where[1]["name"] = "mobile_twinning_uuid";
			$where[1]["operator"] = "!=";
			$where[1]["value"] = "$mobile_twinning_uuid";			
			$database->where = $where;
			$result = $database->count();
			if ($result > 0) {
					$msg .= $text['message-warning'].$text['message-duplicate_mobile_twinning_number']."<br>\n";
				}
			unset($result,$database);	

		//check for a valid 10 digit mobile number
			if (strlen($mobile_twinning_number) != 10)  {
				if (strlen($mobile_twinning_number) != 0) {
					$msg .= $text['message-warning'].$text['message-invalid_mobile_twinning_number']."<br>\n";
				}
			}
			
		//display error msg if error found			
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

		//insert into v_mobile_twinnings
			$i = 0;
			$array["mobile_twinnings"][$i]["domain_uuid"] = $_SESSION['domain_uuid'];;
			$array["mobile_twinnings"][$i]["mobile_twinning_uuid"] = $mobile_twinning_uuid;
			$array["mobile_twinnings"][$i]["extension_uuid"] = $extension_uuid;
			$array["mobile_twinnings"][$i]["mobile_twinning_number"] = $mobile_twinning_number;
			
		//save to the datbase
			$database = new database;
			$database->app_name = 'mobile_twinnings';
			$database->app_uuid = null;
			$database->save($array);
			$message = $database->message;
			//echo "<pre>".print_r($message, true)."<pre>\n";
			//exit;
	}
	
//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "SELECT e.extension, m.mobile_twinning_number, e.description, m.mobile_twinning_uuid, e.extension_uuid \n";
		$sql .= "FROM  v_extensions AS e \n ";
		$sql .= "LEFT OUTER JOIN v_mobile_twinnings AS m ON m.extension_uuid = e.extension_uuid \n";
		$sql .= "WHERE e.domain_uuid = '$domain_uuid' ";
		if ($mobile_twinning_uuid != null) {
				$sql .= "AND m.mobile_twinning_uuid = '$mobile_twinning_uuid' \n";
		}
		$sql .= "AND e.extension_uuid = '$extension_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);
	
	//set the variables

	
		foreach ($result as $row) {
			$mobile_twinning_number = $row[mobile_twinning_number];
			$mobile_twinning_uuid = $row[mobile_twinning_uuid];
			$extension = $row[extension];
			$description = $row[description];
			$extension_uuid = $row[extension_uuid];
		}

		if (strlen($mobile_twinning_uuid) == 0) {
			$mobile_twinning_uuid = uuid();
		}			
	}
	
//show the header
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<input type='hidden' name='extension_uuid' id='extension_uuid' value='".$extension_uuid."'>\n";
	echo "<input type='hidden' name='mobile_twinning_uuid' id='mobile_twinning_uuid' value='".$mobile_twinning_uuid."'>\n";
	echo "<input type='hidden' name='extension' id='mobile_twinning_uuid' value='".$extension."'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-mobile_twinning']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='mobile_twinning.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	//Extension
	echo "<tr>\n";
	echo "<td class='vncellreq' width='30%' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'  padding: 5px; >\n";
	echo     $extension."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-mobile_twinning_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='mobile_twinning_number' maxlength='255' value=\"".escape($mobile_twinning_number)."\">\n";
	echo "<br />\n";
	echo $text['description-mobile_twinning_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";	
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
