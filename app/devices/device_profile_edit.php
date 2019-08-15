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
	Copyright (C) 2019 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('device_profile_add') || permission_exists('device_profile_edit')) {
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
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$device_profile_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$device_profile_uuid = $_POST["device_profile_uuid"];
		$device_profile_name = $_POST["device_profile_name"];
		$device_profile_keys = $_POST["device_profile_keys"];
		$device_profile_settings = $_POST["device_profile_settings"];
		$device_profile_enabled = $_POST["device_profile_enabled"];
		$device_profile_description = $_POST["device_profile_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {


		//check for all required data
			$msg = '';
			if (strlen($device_profile_name) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_name']."<br>\n"; }
			//if (strlen($device_profile_keys) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_keys']."<br>\n"; }
			//if (strlen($device_profile_settings) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_settings']."<br>\n"; }
			//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			if (strlen($device_profile_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_enabled']."<br>\n"; }
			//if (strlen($device_profile_description) == 0) { $msg .= $text['message-required']." ".$text['label-device_profile_description']."<br>\n"; }
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

		//set the domain_uuid
			$_POST["domain_uuid"] = $_SESSION["domain_uuid"];

		//add the device_profile_uuid
			if (strlen($_POST["device_profile_uuid"]) == 0) {
				$device_profile_uuid = uuid();
			}

		//prepare the array
			$array['device_profiles'][0]["device_profile_uuid"] = $device_profile_uuid;
			$array['device_profiles'][0]["device_profile_name"] = $device_profile_name;
			$array['device_profiles'][0]["domain_uuid"] = $domain_uuid;
			$array['device_profiles'][0]["device_profile_enabled"] = $device_profile_enabled;
			$array['device_profiles'][0]["device_profile_description"] = $device_profile_description;
			$y = 0;
			foreach ($device_profile_keys as $row) {
				if (strlen($row['profile_key_category']) > 0) {
					$array['device_profiles'][0]['device_profile_keys'][$y]["device_profile_key_uuid"] = $row["device_profile_key_uuid"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_category"] = $row["profile_key_category"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_id"] = $row["profile_key_id"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_vendor"] = $row["profile_key_vendor"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_type"] = $row["profile_key_type"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_line"] = $row["profile_key_line"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_value"] = $row["profile_key_value"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_extension"] = $row["profile_key_extension"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_protected"] = $row["profile_key_protected"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_label"] = $row["profile_key_label"];
					$array['device_profiles'][0]['device_profile_keys'][$y]["profile_key_icon"] = $row["profile_key_icon"];
					$y++;
				}
			}
			$y = 0;
			foreach ($device_profile_settings as $row) {
				if (strlen($row['profile_setting_name']) > 0) {
					$array['device_profiles'][0]['device_profile_settings'][$y]["device_profile_setting_uuid"] = $row["device_profile_setting_uuid"];
					$array['device_profiles'][0]['device_profile_settings'][$y]["profile_setting_name"] = $row["profile_setting_name"];
					$array['device_profiles'][0]['device_profile_settings'][$y]["profile_setting_value"] = $row["profile_setting_value"];
					$array['device_profiles'][0]['device_profile_settings'][$y]["profile_setting_enabled"] = $row["profile_setting_enabled"];
					$array['device_profiles'][0]['device_profile_settings'][$y]["profile_setting_description"] = $row["profile_setting_description"];
					$y++;
				}
			}

		//save to the data
			$database = new database;
			$database->app_name = 'Device Profiles';
			$database->app_uuid = 'bb2531c3-97e6-428f-9a19-cbac1b96f5b7';
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: device_profile_edit.php?id='.$device_profile_uuid);
				return;
			}
	} //(is_array($_POST) && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$device_profile_uuid = $_GET["id"];
		$sql = "select * from v_device_profiles ";
		$sql .= "where device_profile_uuid = :device_profile_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['device_profile_uuid'] = $device_profile_uuid;
		$database = new database;
		$result = $database->execute($sql, $parameters, 'all');
		foreach ($result as &$row) {
			$device_profile_name = $row["device_profile_name"];
			$device_profile_keys = $row["device_profile_keys"];
			$device_profile_settings = $row["device_profile_settings"];
			$device_profile_enabled = $row["device_profile_enabled"];
			$device_profile_description = $row["device_profile_description"];
		}
		unset ($sql, $parameters);
	}

//get the child data
	if (strlen($device_profile_uuid) > 0) {
		$sql = "select * from v_device_profile_keys ";
		$sql .= "where device_profile_uuid = :device_profile_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		$sql .= "order by profile_key_id asc";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['device_profile_uuid'] = $device_profile_uuid;
		$database = new database;
		$device_profile_keys = $database->execute($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $device_profile_key_uuid
	if (strlen($device_profile_key_uuid) == 0) {
		$device_profile_key_uuid = uuid();
	}

//add an empty row
	$x = count($device_profile_keys);
	$device_profile_keys[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
	$device_profile_keys[$x]['device_profile_uuid'] = $device_profile_uuid;
	$device_profile_keys[$x]['device_profile_key_uuid'] = uuid();
	$device_profile_keys[$x]['profile_key_category'] = '';
	$device_profile_keys[$x]['profile_key_id'] = '';
	$device_profile_keys[$x]['profile_key_vendor'] = '';
	$device_profile_keys[$x]['profile_key_type'] = '';
	$device_profile_keys[$x]['profile_key_line'] = '';
	$device_profile_keys[$x]['profile_key_value'] = '';
	$device_profile_keys[$x]['profile_key_extension'] = '';
	$device_profile_keys[$x]['profile_key_protected'] = '';
	$device_profile_keys[$x]['profile_key_label'] = '';
	$device_profile_keys[$x]['profile_key_icon'] = '';

//get the child data
	if (strlen($device_profile_uuid) > 0) {
		$sql = "select * from v_device_profile_settings ";
		$sql .= "where device_profile_uuid = :device_profile_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		$sql .= "order by profile_setting_name asc";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['device_profile_uuid'] = $device_profile_uuid;
		$database = new database;
		$device_profile_settings = $database->execute($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $device_profile_setting_uuid
	if (strlen($device_profile_setting_uuid) == 0) {
		$device_profile_setting_uuid = uuid();
	}

//add an empty row
	$x = count($device_profile_settings);
	$device_profile_settings[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
	$device_profile_settings[$x]['device_profile_uuid'] = $device_profile_uuid;
	$device_profile_settings[$x]['device_profile_setting_uuid'] = uuid();
	$device_profile_settings[$x]['profile_setting_name'] = '';
	$device_profile_settings[$x]['profile_setting_value'] = '';
	$device_profile_settings[$x]['profile_setting_enabled'] = '';
	$device_profile_settings[$x]['profile_setting_description'] = '';

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-device_profile']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='device_profiles.php'\" value='".$text['button-back']."'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"window.location='device_profile_copy.php'\" value='".$text['button-copy']."'>";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	".$text['description-device_profiles']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_profile_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_profile_name' maxlength='255' value='".escape($device_profile_name)."'>\n";
	echo "<br />\n";
	echo $text['description-device_profile_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_profile_keys']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<th class='vtablereq'>".$text['label-device_key_category']."</th>\n";
	echo "			<th class='vtablereq'>".$text['label-device_key_id']."</th>\n";
	echo "			<th class='vtablereq'>".$text['label-device_key_vendor']."</th>\n";
	echo "			<th class='vtablereq'>".$text['label-device_key_type']."</th>\n";
	echo "			<th class='vtablereq'>".$text['label-device_key_line']."</th>\n";
	echo "			<td class='vtable'>".$text['label-device_key_value']."</td>\n";
	echo "			<td class='vtable'>".$text['label-device_key_extension']."</td>\n";
	echo "			<td class='vtable'>".$text['label-device_key_protected']."</td>\n";
	echo "			<td class='vtable'>".$text['label-device_key_label']."</td>\n";
	echo "			<td class='vtable'>".$text['label-device_key_icon']."</td>\n";
	echo "			<td class='vtable'></td>\n";
	echo "		</tr>\n";
	$x = 0;
	foreach($device_profile_keys as $row) {
		echo "		<tr>\n";
		echo "			<input type='hidden' name='device_profile_keys[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
		echo "			<input type='hidden' name='device_profile_keys[$x][device_profile_uuid]' value=\"".escape($row["device_profile_uuid"])."\">\n";
		echo "			<input type='hidden' name='device_profile_keys[$x][device_profile_key_uuid]' value=\"".escape($row["device_profile_key_uuid"])."\">\n";
		echo "			<td>\n";
		echo "				<select class='formfld' name='device_profile_keys[$x][profile_key_category]'>\n";
		echo "					<option value=''></option>\n";
		if ($row['profile_key_category'] == "line") {
			echo "					<option value='line' selected='selected'>".$text['label-line']."</option>\n";
		}
		else {
			echo "					<option value='line'>".$text['label-line']."</option>\n";
		}
		if ($row['profile_key_category'] == "memory") {
			echo "					<option value='memory' selected='selected'>".$text['label-memory']."</option>\n";
		}
		else {
			echo "					<option value='memory'>".$text['label-memory']."</option>\n";
		}
		if ($row['profile_key_category'] == "programmable") {
			echo "					<option value='programmable' selected='selected'>".$text['label-programmable']."</option>\n";
		}
		else {
			echo "					<option value='programmable'>".$text['label-programmable']."</option>\n";
		}
		if ($row['profile_key_category'] == "expansion-1") {
			echo "					<option value='expansion-1' selected='selected'>".$text['label-expansion-1']."</option>\n";
		}
		else {
			echo "					<option value='expansion-1'>".$text['label-expansion-1']."</option>\n";
		}
		if ($row['profile_key_category'] == "expansion-2") {
			echo "					<option value='expansion-2' selected='selected'>".$text['label-expansion-2']."</option>\n";
		}
		else {
			echo "					<option value='expansion-2'>".$text['label-expansion-2']."</option>\n";
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<select class='formfld' name='device_profile_keys[$x][profile_key_id]'>\n";
		echo "					<option value=''></option>\n";
		if ($row['profile_key_id'] == "1") {
			echo "					<option value='1' selected='selected'>1</option>\n";
		}
		else {
			echo "					<option value='1'>1</option>\n";
		}
		if ($row['profile_key_id'] == "2") {
			echo "					<option value='2' selected='selected'>2</option>\n";
		}
		else {
			echo "					<option value='2'>2</option>\n";
		}
		if ($row['profile_key_id'] == "3") {
			echo "					<option value='3' selected='selected'>3</option>\n";
		}
		else {
			echo "					<option value='3'>3</option>\n";
		}
		if ($row['profile_key_id'] == "4") {
			echo "					<option value='4' selected='selected'>4</option>\n";
		}
		else {
			echo "					<option value='4'>4</option>\n";
		}
		if ($row['profile_key_id'] == "5") {
			echo "					<option value='5' selected='selected'>5</option>\n";
		}
		else {
			echo "					<option value='5'>5</option>\n";
		}
		if ($row['profile_key_id'] == "6") {
			echo "					<option value='6' selected='selected'>6</option>\n";
		}
		else {
			echo "					<option value='6'>6</option>\n";
		}
		if ($row['profile_key_id'] == "7") {
			echo "					<option value='7' selected='selected'>7</option>\n";
		}
		else {
			echo "					<option value='7'>7</option>\n";
		}
		if ($row['profile_key_id'] == "8") {
			echo "					<option value='8' selected='selected'>8</option>\n";
		}
		else {
			echo "					<option value='8'>8</option>\n";
		}
		if ($row['profile_key_id'] == "9") {
			echo "					<option value='9' selected='selected'>9</option>\n";
		}
		else {
			echo "					<option value='9'>9</option>\n";
		}
		if ($row['profile_key_id'] == "10") {
			echo "					<option value='10' selected='selected'>10</option>\n";
		}
		else {
			echo "					<option value='10'>10</option>\n";
		}
		if ($row['profile_key_id'] == "11") {
			echo "					<option value='11' selected='selected'>11</option>\n";
		}
		else {
			echo "					<option value='11'>11</option>\n";
		}
		if ($row['profile_key_id'] == "12") {
			echo "					<option value='12' selected='selected'>12</option>\n";
		}
		else {
			echo "					<option value='12'>12</option>\n";
		}
		if ($row['profile_key_id'] == "13") {
			echo "					<option value='13' selected='selected'>13</option>\n";
		}
		else {
			echo "					<option value='13'>13</option>\n";
		}
		if ($row['profile_key_id'] == "14") {
			echo "					<option value='14' selected='selected'>14</option>\n";
		}
		else {
			echo "					<option value='14'>14</option>\n";
		}
		if ($row['profile_key_id'] == "15") {
			echo "					<option value='15' selected='selected'>15</option>\n";
		}
		else {
			echo "					<option value='15'>15</option>\n";
		}
		if ($row['profile_key_id'] == "16") {
			echo "					<option value='16' selected='selected'>16</option>\n";
		}
		else {
			echo "					<option value='16'>16</option>\n";
		}
		if ($row['profile_key_id'] == "17") {
			echo "					<option value='17' selected='selected'>17</option>\n";
		}
		else {
			echo "					<option value='17'>17</option>\n";
		}
		if ($row['profile_key_id'] == "18") {
			echo "					<option value='18' selected='selected'>18</option>\n";
		}
		else {
			echo "					<option value='18'>18</option>\n";
		}
		if ($row['profile_key_id'] == "19") {
			echo "					<option value='19' selected='selected'>19</option>\n";
		}
		else {
			echo "					<option value='19'>19</option>\n";
		}
		if ($row['profile_key_id'] == "20") {
			echo "					<option value='20' selected='selected'>20</option>\n";
		}
		else {
			echo "					<option value='20'>20</option>\n";
		}
		if ($row['profile_key_id'] == "21") {
			echo "					<option value='21' selected='selected'>21</option>\n";
		}
		else {
			echo "					<option value='21'>21</option>\n";
		}
		if ($row['profile_key_id'] == "22") {
			echo "					<option value='22' selected='selected'>22</option>\n";
		}
		else {
			echo "					<option value='22'>22</option>\n";
		}
		if ($row['profile_key_id'] == "23") {
			echo "					<option value='23' selected='selected'>23</option>\n";
		}
		else {
			echo "					<option value='23'>23</option>\n";
		}
		if ($row['profile_key_id'] == "24") {
			echo "					<option value='24' selected='selected'>24</option>\n";
		}
		else {
			echo "					<option value='24'>24</option>\n";
		}
		if ($row['profile_key_id'] == "25") {
			echo "					<option value='25' selected='selected'>25</option>\n";
		}
		else {
			echo "					<option value='25'>25</option>\n";
		}
		if ($row['profile_key_id'] == "26") {
			echo "					<option value='26' selected='selected'>26</option>\n";
		}
		else {
			echo "					<option value='26'>26</option>\n";
		}
		if ($row['profile_key_id'] == "27") {
			echo "					<option value='27' selected='selected'>27</option>\n";
		}
		else {
			echo "					<option value='27'>27</option>\n";
		}
		if ($row['profile_key_id'] == "28") {
			echo "					<option value='28' selected='selected'>28</option>\n";
		}
		else {
			echo "					<option value='28'>28</option>\n";
		}
		if ($row['profile_key_id'] == "29") {
			echo "					<option value='29' selected='selected'>29</option>\n";
		}
		else {
			echo "					<option value='29'>29</option>\n";
		}
		if ($row['profile_key_id'] == "30") {
			echo "					<option value='30' selected='selected'>30</option>\n";
		}
		else {
			echo "					<option value='30'>30</option>\n";
		}
		if ($row['profile_key_id'] == "31") {
			echo "					<option value='31' selected='selected'>31</option>\n";
		}
		else {
			echo "					<option value='31'>31</option>\n";
		}
		if ($row['profile_key_id'] == "32") {
			echo "					<option value='32' selected='selected'>32</option>\n";
		}
		else {
			echo "					<option value='32'>32</option>\n";
		}
		if ($row['profile_key_id'] == "33") {
			echo "					<option value='33' selected='selected'>33</option>\n";
		}
		else {
			echo "					<option value='33'>33</option>\n";
		}
		if ($row['profile_key_id'] == "34") {
			echo "					<option value='34' selected='selected'>34</option>\n";
		}
		else {
			echo "					<option value='34'>34</option>\n";
		}
		if ($row['profile_key_id'] == "35") {
			echo "					<option value='35' selected='selected'>35</option>\n";
		}
		else {
			echo "					<option value='35'>35</option>\n";
		}
		if ($row['profile_key_id'] == "36") {
			echo "					<option value='36' selected='selected'>36</option>\n";
		}
		else {
			echo "					<option value='36'>36</option>\n";
		}
		if ($row['profile_key_id'] == "37") {
			echo "					<option value='37' selected='selected'>37</option>\n";
		}
		else {
			echo "					<option value='37'>37</option>\n";
		}
		if ($row['profile_key_id'] == "38") {
			echo "					<option value='38' selected='selected'>38</option>\n";
		}
		else {
			echo "					<option value='38'>38</option>\n";
		}
		if ($row['profile_key_id'] == "39") {
			echo "					<option value='39' selected='selected'>39</option>\n";
		}
		else {
			echo "					<option value='39'>39</option>\n";
		}
		if ($row['profile_key_id'] == "40") {
			echo "					<option value='40' selected='selected'>40</option>\n";
		}
		else {
			echo "					<option value='40'>40</option>\n";
		}
		if ($row['profile_key_id'] == "41") {
			echo "					<option value='41' selected='selected'>41</option>\n";
		}
		else {
			echo "					<option value='41'>41</option>\n";
		}
		if ($row['profile_key_id'] == "42") {
			echo "					<option value='42' selected='selected'>42</option>\n";
		}
		else {
			echo "					<option value='42'>42</option>\n";
		}
		if ($row['profile_key_id'] == "43") {
			echo "					<option value='43' selected='selected'>43</option>\n";
		}
		else {
			echo "					<option value='43'>43</option>\n";
		}
		if ($row['profile_key_id'] == "44") {
			echo "					<option value='44' selected='selected'>44</option>\n";
		}
		else {
			echo "					<option value='44'>44</option>\n";
		}
		if ($row['profile_key_id'] == "45") {
			echo "					<option value='45' selected='selected'>45</option>\n";
		}
		else {
			echo "					<option value='45'>45</option>\n";
		}
		if ($row['profile_key_id'] == "46") {
			echo "					<option value='46' selected='selected'>46</option>\n";
		}
		else {
			echo "					<option value='46'>46</option>\n";
		}
		if ($row['profile_key_id'] == "47") {
			echo "					<option value='47' selected='selected'>47</option>\n";
		}
		else {
			echo "					<option value='47'>47</option>\n";
		}
		if ($row['profile_key_id'] == "48") {
			echo "					<option value='48' selected='selected'>48</option>\n";
		}
		else {
			echo "					<option value='48'>48</option>\n";
		}
		if ($row['profile_key_id'] == "49") {
			echo "					<option value='49' selected='selected'>49</option>\n";
		}
		else {
			echo "					<option value='49'>49</option>\n";
		}
		if ($row['profile_key_id'] == "50") {
			echo "					<option value='50' selected='selected'>50</option>\n";
		}
		else {
			echo "					<option value='50'>50</option>\n";
		}
		if ($row['profile_key_id'] == "51") {
			echo "					<option value='51' selected='selected'>51</option>\n";
		}
		else {
			echo "					<option value='51'>51</option>\n";
		}
		if ($row['profile_key_id'] == "52") {
			echo "					<option value='52' selected='selected'>52</option>\n";
		}
		else {
			echo "					<option value='52'>52</option>\n";
		}
		if ($row['profile_key_id'] == "53") {
			echo "					<option value='53' selected='selected'>53</option>\n";
		}
		else {
			echo "					<option value='53'>53</option>\n";
		}
		if ($row['profile_key_id'] == "54") {
			echo "					<option value='54' selected='selected'>54</option>\n";
		}
		else {
			echo "					<option value='54'>54</option>\n";
		}
		if ($row['profile_key_id'] == "55") {
			echo "					<option value='55' selected='selected'>55</option>\n";
		}
		else {
			echo "					<option value='55'>55</option>\n";
		}
		if ($row['profile_key_id'] == "56") {
			echo "					<option value='56' selected='selected'>56</option>\n";
		}
		else {
			echo "					<option value='56'>56</option>\n";
		}
		if ($row['profile_key_id'] == "57") {
			echo "					<option value='57' selected='selected'>57</option>\n";
		}
		else {
			echo "					<option value='57'>57</option>\n";
		}
		if ($row['profile_key_id'] == "58") {
			echo "					<option value='58' selected='selected'>58</option>\n";
		}
		else {
			echo "					<option value='58'>58</option>\n";
		}
		if ($row['profile_key_id'] == "59") {
			echo "					<option value='59' selected='selected'>59</option>\n";
		}
		else {
			echo "					<option value='59'>59</option>\n";
		}
		if ($row['profile_key_id'] == "60") {
			echo "					<option value='60' selected='selected'>60</option>\n";
		}
		else {
			echo "					<option value='60'>60</option>\n";
		}
		if ($row['profile_key_id'] == "61") {
			echo "					<option value='61' selected='selected'>61</option>\n";
		}
		else {
			echo "					<option value='61'>61</option>\n";
		}
		if ($row['profile_key_id'] == "62") {
			echo "					<option value='62' selected='selected'>62</option>\n";
		}
		else {
			echo "					<option value='62'>62</option>\n";
		}
		if ($row['profile_key_id'] == "63") {
			echo "					<option value='63' selected='selected'>63</option>\n";
		}
		else {
			echo "					<option value='63'>63</option>\n";
		}
		if ($row['profile_key_id'] == "64") {
			echo "					<option value='64' selected='selected'>64</option>\n";
		}
		else {
			echo "					<option value='64'>64</option>\n";
		}
		if ($row['profile_key_id'] == "65") {
			echo "					<option value='65' selected='selected'>65</option>\n";
		}
		else {
			echo "					<option value='65'>65</option>\n";
		}
		if ($row['profile_key_id'] == "66") {
			echo "					<option value='66' selected='selected'>66</option>\n";
		}
		else {
			echo "					<option value='66'>66</option>\n";
		}
		if ($row['profile_key_id'] == "67") {
			echo "					<option value='67' selected='selected'>67</option>\n";
		}
		else {
			echo "					<option value='67'>67</option>\n";
		}
		if ($row['profile_key_id'] == "68") {
			echo "					<option value='68' selected='selected'>68</option>\n";
		}
		else {
			echo "					<option value='68'>68</option>\n";
		}
		if ($row['profile_key_id'] == "69") {
			echo "					<option value='69' selected='selected'>69</option>\n";
		}
		else {
			echo "					<option value='69'>69</option>\n";
		}
		if ($row['profile_key_id'] == "70") {
			echo "					<option value='70' selected='selected'>70</option>\n";
		}
		else {
			echo "					<option value='70'>70</option>\n";
		}
		if ($row['profile_key_id'] == "71") {
			echo "					<option value='71' selected='selected'>71</option>\n";
		}
		else {
			echo "					<option value='71'>71</option>\n";
		}
		if ($row['profile_key_id'] == "72") {
			echo "					<option value='72' selected='selected'>72</option>\n";
		}
		else {
			echo "					<option value='72'>72</option>\n";
		}
		if ($row['profile_key_id'] == "73") {
			echo "					<option value='73' selected='selected'>73</option>\n";
		}
		else {
			echo "					<option value='73'>73</option>\n";
		}
		if ($row['profile_key_id'] == "74") {
			echo "					<option value='74' selected='selected'>74</option>\n";
		}
		else {
			echo "					<option value='74'>74</option>\n";
		}
		if ($row['profile_key_id'] == "75") {
			echo "					<option value='75' selected='selected'>75</option>\n";
		}
		else {
			echo "					<option value='75'>75</option>\n";
		}
		if ($row['profile_key_id'] == "76") {
			echo "					<option value='76' selected='selected'>76</option>\n";
		}
		else {
			echo "					<option value='76'>76</option>\n";
		}
		if ($row['profile_key_id'] == "77") {
			echo "					<option value='77' selected='selected'>77</option>\n";
		}
		else {
			echo "					<option value='77'>77</option>\n";
		}
		if ($row['profile_key_id'] == "78") {
			echo "					<option value='78' selected='selected'>78</option>\n";
		}
		else {
			echo "					<option value='78'>78</option>\n";
		}
		if ($row['profile_key_id'] == "79") {
			echo "					<option value='79' selected='selected'>79</option>\n";
		}
		else {
			echo "					<option value='79'>79</option>\n";
		}
		if ($row['profile_key_id'] == "80") {
			echo "					<option value='80' selected='selected'>80</option>\n";
		}
		else {
			echo "					<option value='80'>80</option>\n";
		}
		if ($row['profile_key_id'] == "81") {
			echo "					<option value='81' selected='selected'>81</option>\n";
		}
		else {
			echo "					<option value='81'>81</option>\n";
		}
		if ($row['profile_key_id'] == "82") {
			echo "					<option value='82' selected='selected'>82</option>\n";
		}
		else {
			echo "					<option value='82'>82</option>\n";
		}
		if ($row['profile_key_id'] == "83") {
			echo "					<option value='83' selected='selected'>83</option>\n";
		}
		else {
			echo "					<option value='83'>83</option>\n";
		}
		if ($row['profile_key_id'] == "84") {
			echo "					<option value='84' selected='selected'>84</option>\n";
		}
		else {
			echo "					<option value='84'>84</option>\n";
		}
		if ($row['profile_key_id'] == "85") {
			echo "					<option value='85' selected='selected'>85</option>\n";
		}
		else {
			echo "					<option value='85'>85</option>\n";
		}
		if ($row['profile_key_id'] == "86") {
			echo "					<option value='86' selected='selected'>86</option>\n";
		}
		else {
			echo "					<option value='86'>86</option>\n";
		}
		if ($row['profile_key_id'] == "87") {
			echo "					<option value='87' selected='selected'>87</option>\n";
		}
		else {
			echo "					<option value='87'>87</option>\n";
		}
		if ($row['profile_key_id'] == "88") {
			echo "					<option value='88' selected='selected'>88</option>\n";
		}
		else {
			echo "					<option value='88'>88</option>\n";
		}
		if ($row['profile_key_id'] == "89") {
			echo "					<option value='89' selected='selected'>89</option>\n";
		}
		else {
			echo "					<option value='89'>89</option>\n";
		}
		if ($row['profile_key_id'] == "90") {
			echo "					<option value='90' selected='selected'>90</option>\n";
		}
		else {
			echo "					<option value='90'>90</option>\n";
		}
		if ($row['profile_key_id'] == "91") {
			echo "					<option value='91' selected='selected'>91</option>\n";
		}
		else {
			echo "					<option value='91'>91</option>\n";
		}
		if ($row['profile_key_id'] == "92") {
			echo "					<option value='92' selected='selected'>92</option>\n";
		}
		else {
			echo "					<option value='92'>92</option>\n";
		}
		if ($row['profile_key_id'] == "93") {
			echo "					<option value='93' selected='selected'>93</option>\n";
		}
		else {
			echo "					<option value='93'>93</option>\n";
		}
		if ($row['profile_key_id'] == "94") {
			echo "					<option value='94' selected='selected'>94</option>\n";
		}
		else {
			echo "					<option value='94'>94</option>\n";
		}
		if ($row['profile_key_id'] == "95") {
			echo "					<option value='95' selected='selected'>95</option>\n";
		}
		else {
			echo "					<option value='95'>95</option>\n";
		}
		if ($row['profile_key_id'] == "96") {
			echo "					<option value='96' selected='selected'>96</option>\n";
		}
		else {
			echo "					<option value='96'>96</option>\n";
		}
		if ($row['profile_key_id'] == "97") {
			echo "					<option value='97' selected='selected'>97</option>\n";
		}
		else {
			echo "					<option value='97'>97</option>\n";
		}
		if ($row['profile_key_id'] == "98") {
			echo "					<option value='98' selected='selected'>98</option>\n";
		}
		else {
			echo "					<option value='98'>98</option>\n";
		}
		if ($row['profile_key_id'] == "99") {
			echo "					<option value='99' selected='selected'>99</option>\n";
		}
		else {
			echo "					<option value='99'>99</option>\n";
		}
		if ($row['profile_key_id'] == "100") {
			echo "					<option value='100' selected='selected'>100</option>\n";
		}
		else {
			echo "					<option value='100'>100</option>\n";
		}
		if ($row['profile_key_id'] == "101") {
			echo "					<option value='101' selected='selected'>101</option>\n";
		}
		else {
			echo "					<option value='101'>101</option>\n";
		}
		if ($row['profile_key_id'] == "102") {
			echo "					<option value='102' selected='selected'>102</option>\n";
		}
		else {
			echo "					<option value='102'>102</option>\n";
		}
		if ($row['profile_key_id'] == "103") {
			echo "					<option value='103' selected='selected'>103</option>\n";
		}
		else {
			echo "					<option value='103'>103</option>\n";
		}
		if ($row['profile_key_id'] == "104") {
			echo "					<option value='104' selected='selected'>104</option>\n";
		}
		else {
			echo "					<option value='104'>104</option>\n";
		}
		if ($row['profile_key_id'] == "105") {
			echo "					<option value='105' selected='selected'>105</option>\n";
		}
		else {
			echo "					<option value='105'>105</option>\n";
		}
		if ($row['profile_key_id'] == "106") {
			echo "					<option value='106' selected='selected'>106</option>\n";
		}
		else {
			echo "					<option value='106'>106</option>\n";
		}
		if ($row['profile_key_id'] == "107") {
			echo "					<option value='107' selected='selected'>107</option>\n";
		}
		else {
			echo "					<option value='107'>107</option>\n";
		}
		if ($row['profile_key_id'] == "108") {
			echo "					<option value='108' selected='selected'>108</option>\n";
		}
		else {
			echo "					<option value='108'>108</option>\n";
		}
		if ($row['profile_key_id'] == "109") {
			echo "					<option value='109' selected='selected'>109</option>\n";
		}
		else {
			echo "					<option value='109'>109</option>\n";
		}
		if ($row['profile_key_id'] == "110") {
			echo "					<option value='110' selected='selected'>110</option>\n";
		}
		else {
			echo "					<option value='110'>110</option>\n";
		}
		if ($row['profile_key_id'] == "111") {
			echo "					<option value='111' selected='selected'>111</option>\n";
		}
		else {
			echo "					<option value='111'>111</option>\n";
		}
		if ($row['profile_key_id'] == "112") {
			echo "					<option value='112' selected='selected'>112</option>\n";
		}
		else {
			echo "					<option value='112'>112</option>\n";
		}
		if ($row['profile_key_id'] == "113") {
			echo "					<option value='113' selected='selected'>113</option>\n";
		}
		else {
			echo "					<option value='113'>113</option>\n";
		}
		if ($row['profile_key_id'] == "114") {
			echo "					<option value='114' selected='selected'>114</option>\n";
		}
		else {
			echo "					<option value='114'>114</option>\n";
		}
		if ($row['profile_key_id'] == "115") {
			echo "					<option value='115' selected='selected'>115</option>\n";
		}
		else {
			echo "					<option value='115'>115</option>\n";
		}
		if ($row['profile_key_id'] == "116") {
			echo "					<option value='116' selected='selected'>116</option>\n";
		}
		else {
			echo "					<option value='116'>116</option>\n";
		}
		if ($row['profile_key_id'] == "117") {
			echo "					<option value='117' selected='selected'>117</option>\n";
		}
		else {
			echo "					<option value='117'>117</option>\n";
		}
		if ($row['profile_key_id'] == "118") {
			echo "					<option value='118' selected='selected'>118</option>\n";
		}
		else {
			echo "					<option value='118'>118</option>\n";
		}
		if ($row['profile_key_id'] == "119") {
			echo "					<option value='119' selected='selected'>119</option>\n";
		}
		else {
			echo "					<option value='119'>119</option>\n";
		}
		if ($row['profile_key_id'] == "120") {
			echo "					<option value='120' selected='selected'>120</option>\n";
		}
		else {
			echo "					<option value='120'>120</option>\n";
		}
		if ($row['profile_key_id'] == "121") {
			echo "					<option value='121' selected='selected'>121</option>\n";
		}
		else {
			echo "					<option value='121'>121</option>\n";
		}
		if ($row['profile_key_id'] == "122") {
			echo "					<option value='122' selected='selected'>122</option>\n";
		}
		else {
			echo "					<option value='122'>122</option>\n";
		}
		if ($row['profile_key_id'] == "123") {
			echo "					<option value='123' selected='selected'>123</option>\n";
		}
		else {
			echo "					<option value='123'>123</option>\n";
		}
		if ($row['profile_key_id'] == "124") {
			echo "					<option value='124' selected='selected'>124</option>\n";
		}
		else {
			echo "					<option value='124'>124</option>\n";
		}
		if ($row['profile_key_id'] == "125") {
			echo "					<option value='125' selected='selected'>125</option>\n";
		}
		else {
			echo "					<option value='125'>125</option>\n";
		}
		if ($row['profile_key_id'] == "126") {
			echo "					<option value='126' selected='selected'>126</option>\n";
		}
		else {
			echo "					<option value='126'>126</option>\n";
		}
		if ($row['profile_key_id'] == "127") {
			echo "					<option value='127' selected='selected'>127</option>\n";
		}
		else {
			echo "					<option value='127'>127</option>\n";
		}
		if ($row['profile_key_id'] == "128") {
			echo "					<option value='128' selected='selected'>128</option>\n";
		}
		else {
			echo "					<option value='128'>128</option>\n";
		}
		if ($row['profile_key_id'] == "129") {
			echo "					<option value='129' selected='selected'>129</option>\n";
		}
		else {
			echo "					<option value='129'>129</option>\n";
		}
		if ($row['profile_key_id'] == "130") {
			echo "					<option value='130' selected='selected'>130</option>\n";
		}
		else {
			echo "					<option value='130'>130</option>\n";
		}
		if ($row['profile_key_id'] == "131") {
			echo "					<option value='131' selected='selected'>131</option>\n";
		}
		else {
			echo "					<option value='131'>131</option>\n";
		}
		if ($row['profile_key_id'] == "132") {
			echo "					<option value='132' selected='selected'>132</option>\n";
		}
		else {
			echo "					<option value='132'>132</option>\n";
		}
		if ($row['profile_key_id'] == "133") {
			echo "					<option value='133' selected='selected'>133</option>\n";
		}
		else {
			echo "					<option value='133'>133</option>\n";
		}
		if ($row['profile_key_id'] == "134") {
			echo "					<option value='134' selected='selected'>134</option>\n";
		}
		else {
			echo "					<option value='134'>134</option>\n";
		}
		if ($row['profile_key_id'] == "135") {
			echo "					<option value='135' selected='selected'>135</option>\n";
		}
		else {
			echo "					<option value='135'>135</option>\n";
		}
		if ($row['profile_key_id'] == "136") {
			echo "					<option value='136' selected='selected'>136</option>\n";
		}
		else {
			echo "					<option value='136'>136</option>\n";
		}
		if ($row['profile_key_id'] == "137") {
			echo "					<option value='137' selected='selected'>137</option>\n";
		}
		else {
			echo "					<option value='137'>137</option>\n";
		}
		if ($row['profile_key_id'] == "138") {
			echo "					<option value='138' selected='selected'>138</option>\n";
		}
		else {
			echo "					<option value='138'>138</option>\n";
		}
		if ($row['profile_key_id'] == "139") {
			echo "					<option value='139' selected='selected'>139</option>\n";
		}
		else {
			echo "					<option value='139'>139</option>\n";
		}
		if ($row['profile_key_id'] == "140") {
			echo "					<option value='140' selected='selected'>140</option>\n";
		}
		else {
			echo "					<option value='140'>140</option>\n";
		}
		if ($row['profile_key_id'] == "141") {
			echo "					<option value='141' selected='selected'>141</option>\n";
		}
		else {
			echo "					<option value='141'>141</option>\n";
		}
		if ($row['profile_key_id'] == "142") {
			echo "					<option value='142' selected='selected'>142</option>\n";
		}
		else {
			echo "					<option value='142'>142</option>\n";
		}
		if ($row['profile_key_id'] == "143") {
			echo "					<option value='143' selected='selected'>143</option>\n";
		}
		else {
			echo "					<option value='143'>143</option>\n";
		}
		if ($row['profile_key_id'] == "144") {
			echo "					<option value='144' selected='selected'>144</option>\n";
		}
		else {
			echo "					<option value='144'>144</option>\n";
		}
		if ($row['profile_key_id'] == "145") {
			echo "					<option value='145' selected='selected'>145</option>\n";
		}
		else {
			echo "					<option value='145'>145</option>\n";
		}
		if ($row['profile_key_id'] == "146") {
			echo "					<option value='146' selected='selected'>146</option>\n";
		}
		else {
			echo "					<option value='146'>146</option>\n";
		}
		if ($row['profile_key_id'] == "147") {
			echo "					<option value='147' selected='selected'>147</option>\n";
		}
		else {
			echo "					<option value='147'>147</option>\n";
		}
		if ($row['profile_key_id'] == "148") {
			echo "					<option value='148' selected='selected'>148</option>\n";
		}
		else {
			echo "					<option value='148'>148</option>\n";
		}
		if ($row['profile_key_id'] == "149") {
			echo "					<option value='149' selected='selected'>149</option>\n";
		}
		else {
			echo "					<option value='149'>149</option>\n";
		}
		if ($row['profile_key_id'] == "150") {
			echo "					<option value='150' selected='selected'>150</option>\n";
		}
		else {
			echo "					<option value='150'>150</option>\n";
		}
		if ($row['profile_key_id'] == "151") {
			echo "					<option value='151' selected='selected'>151</option>\n";
		}
		else {
			echo "					<option value='151'>151</option>\n";
		}
		if ($row['profile_key_id'] == "152") {
			echo "					<option value='152' selected='selected'>152</option>\n";
		}
		else {
			echo "					<option value='152'>152</option>\n";
		}
		if ($row['profile_key_id'] == "153") {
			echo "					<option value='153' selected='selected'>153</option>\n";
		}
		else {
			echo "					<option value='153'>153</option>\n";
		}
		if ($row['profile_key_id'] == "154") {
			echo "					<option value='154' selected='selected'>154</option>\n";
		}
		else {
			echo "					<option value='154'>154</option>\n";
		}
		if ($row['profile_key_id'] == "155") {
			echo "					<option value='155' selected='selected'>155</option>\n";
		}
		else {
			echo "					<option value='155'>155</option>\n";
		}
		if ($row['profile_key_id'] == "156") {
			echo "					<option value='156' selected='selected'>156</option>\n";
		}
		else {
			echo "					<option value='156'>156</option>\n";
		}
		if ($row['profile_key_id'] == "157") {
			echo "					<option value='157' selected='selected'>157</option>\n";
		}
		else {
			echo "					<option value='157'>157</option>\n";
		}
		if ($row['profile_key_id'] == "158") {
			echo "					<option value='158' selected='selected'>158</option>\n";
		}
		else {
			echo "					<option value='158'>158</option>\n";
		}
		if ($row['profile_key_id'] == "159") {
			echo "					<option value='159' selected='selected'>159</option>\n";
		}
		else {
			echo "					<option value='159'>159</option>\n";
		}
		if ($row['profile_key_id'] == "160") {
			echo "					<option value='160' selected='selected'>160</option>\n";
		}
		else {
			echo "					<option value='160'>160</option>\n";
		}
		if ($row['profile_key_id'] == "161") {
			echo "					<option value='161' selected='selected'>161</option>\n";
		}
		else {
			echo "					<option value='161'>161</option>\n";
		}
		if ($row['profile_key_id'] == "162") {
			echo "					<option value='162' selected='selected'>162</option>\n";
		}
		else {
			echo "					<option value='162'>162</option>\n";
		}
		if ($row['profile_key_id'] == "163") {
			echo "					<option value='163' selected='selected'>163</option>\n";
		}
		else {
			echo "					<option value='163'>163</option>\n";
		}
		if ($row['profile_key_id'] == "164") {
			echo "					<option value='164' selected='selected'>164</option>\n";
		}
		else {
			echo "					<option value='164'>164</option>\n";
		}
		if ($row['profile_key_id'] == "165") {
			echo "					<option value='165' selected='selected'>165</option>\n";
		}
		else {
			echo "					<option value='165'>165</option>\n";
		}
		if ($row['profile_key_id'] == "166") {
			echo "					<option value='166' selected='selected'>166</option>\n";
		}
		else {
			echo "					<option value='166'>166</option>\n";
		}
		if ($row['profile_key_id'] == "167") {
			echo "					<option value='167' selected='selected'>167</option>\n";
		}
		else {
			echo "					<option value='167'>167</option>\n";
		}
		if ($row['profile_key_id'] == "168") {
			echo "					<option value='168' selected='selected'>168</option>\n";
		}
		else {
			echo "					<option value='168'>168</option>\n";
		}
		if ($row['profile_key_id'] == "169") {
			echo "					<option value='169' selected='selected'>169</option>\n";
		}
		else {
			echo "					<option value='169'>169</option>\n";
		}
		if ($row['profile_key_id'] == "170") {
			echo "					<option value='170' selected='selected'>170</option>\n";
		}
		else {
			echo "					<option value='170'>170</option>\n";
		}
		if ($row['profile_key_id'] == "171") {
			echo "					<option value='171' selected='selected'>171</option>\n";
		}
		else {
			echo "					<option value='171'>171</option>\n";
		}
		if ($row['profile_key_id'] == "172") {
			echo "					<option value='172' selected='selected'>172</option>\n";
		}
		else {
			echo "					<option value='172'>172</option>\n";
		}
		if ($row['profile_key_id'] == "173") {
			echo "					<option value='173' selected='selected'>173</option>\n";
		}
		else {
			echo "					<option value='173'>173</option>\n";
		}
		if ($row['profile_key_id'] == "174") {
			echo "					<option value='174' selected='selected'>174</option>\n";
		}
		else {
			echo "					<option value='174'>174</option>\n";
		}
		if ($row['profile_key_id'] == "175") {
			echo "					<option value='175' selected='selected'>175</option>\n";
		}
		else {
			echo "					<option value='175'>175</option>\n";
		}
		if ($row['profile_key_id'] == "176") {
			echo "					<option value='176' selected='selected'>176</option>\n";
		}
		else {
			echo "					<option value='176'>176</option>\n";
		}
		if ($row['profile_key_id'] == "177") {
			echo "					<option value='177' selected='selected'>177</option>\n";
		}
		else {
			echo "					<option value='177'>177</option>\n";
		}
		if ($row['profile_key_id'] == "178") {
			echo "					<option value='178' selected='selected'>178</option>\n";
		}
		else {
			echo "					<option value='178'>178</option>\n";
		}
		if ($row['profile_key_id'] == "179") {
			echo "					<option value='179' selected='selected'>179</option>\n";
		}
		else {
			echo "					<option value='179'>179</option>\n";
		}
		if ($row['profile_key_id'] == "180") {
			echo "					<option value='180' selected='selected'>180</option>\n";
		}
		else {
			echo "					<option value='180'>180</option>\n";
		}
		if ($row['profile_key_id'] == "181") {
			echo "					<option value='181' selected='selected'>181</option>\n";
		}
		else {
			echo "					<option value='181'>181</option>\n";
		}
		if ($row['profile_key_id'] == "182") {
			echo "					<option value='182' selected='selected'>182</option>\n";
		}
		else {
			echo "					<option value='182'>182</option>\n";
		}
		if ($row['profile_key_id'] == "183") {
			echo "					<option value='183' selected='selected'>183</option>\n";
		}
		else {
			echo "					<option value='183'>183</option>\n";
		}
		if ($row['profile_key_id'] == "184") {
			echo "					<option value='184' selected='selected'>184</option>\n";
		}
		else {
			echo "					<option value='184'>184</option>\n";
		}
		if ($row['profile_key_id'] == "185") {
			echo "					<option value='185' selected='selected'>185</option>\n";
		}
		else {
			echo "					<option value='185'>185</option>\n";
		}
		if ($row['profile_key_id'] == "186") {
			echo "					<option value='186' selected='selected'>186</option>\n";
		}
		else {
			echo "					<option value='186'>186</option>\n";
		}
		if ($row['profile_key_id'] == "187") {
			echo "					<option value='187' selected='selected'>187</option>\n";
		}
		else {
			echo "					<option value='187'>187</option>\n";
		}
		if ($row['profile_key_id'] == "188") {
			echo "					<option value='188' selected='selected'>188</option>\n";
		}
		else {
			echo "					<option value='188'>188</option>\n";
		}
		if ($row['profile_key_id'] == "189") {
			echo "					<option value='189' selected='selected'>189</option>\n";
		}
		else {
			echo "					<option value='189'>189</option>\n";
		}
		if ($row['profile_key_id'] == "190") {
			echo "					<option value='190' selected='selected'>190</option>\n";
		}
		else {
			echo "					<option value='190'>190</option>\n";
		}
		if ($row['profile_key_id'] == "191") {
			echo "					<option value='191' selected='selected'>191</option>\n";
		}
		else {
			echo "					<option value='191'>191</option>\n";
		}
		if ($row['profile_key_id'] == "192") {
			echo "					<option value='192' selected='selected'>192</option>\n";
		}
		else {
			echo "					<option value='192'>192</option>\n";
		}
		if ($row['profile_key_id'] == "193") {
			echo "					<option value='193' selected='selected'>193</option>\n";
		}
		else {
			echo "					<option value='193'>193</option>\n";
		}
		if ($row['profile_key_id'] == "194") {
			echo "					<option value='194' selected='selected'>194</option>\n";
		}
		else {
			echo "					<option value='194'>194</option>\n";
		}
		if ($row['profile_key_id'] == "195") {
			echo "					<option value='195' selected='selected'>195</option>\n";
		}
		else {
			echo "					<option value='195'>195</option>\n";
		}
		if ($row['profile_key_id'] == "196") {
			echo "					<option value='196' selected='selected'>196</option>\n";
		}
		else {
			echo "					<option value='196'>196</option>\n";
		}
		if ($row['profile_key_id'] == "197") {
			echo "					<option value='197' selected='selected'>197</option>\n";
		}
		else {
			echo "					<option value='197'>197</option>\n";
		}
		if ($row['profile_key_id'] == "198") {
			echo "					<option value='198' selected='selected'>198</option>\n";
		}
		else {
			echo "					<option value='198'>198</option>\n";
		}
		if ($row['profile_key_id'] == "199") {
			echo "					<option value='199' selected='selected'>199</option>\n";
		}
		else {
			echo "					<option value='199'>199</option>\n";
		}
		if ($row['profile_key_id'] == "200") {
			echo "					<option value='200' selected='selected'>200</option>\n";
		}
		else {
			echo "					<option value='200'>200</option>\n";
		}
		if ($row['profile_key_id'] == "201") {
			echo "					<option value='201' selected='selected'>201</option>\n";
		}
		else {
			echo "					<option value='201'>201</option>\n";
		}
		if ($row['profile_key_id'] == "202") {
			echo "					<option value='202' selected='selected'>202</option>\n";
		}
		else {
			echo "					<option value='202'>202</option>\n";
		}
		if ($row['profile_key_id'] == "203") {
			echo "					<option value='203' selected='selected'>203</option>\n";
		}
		else {
			echo "					<option value='203'>203</option>\n";
		}
		if ($row['profile_key_id'] == "204") {
			echo "					<option value='204' selected='selected'>204</option>\n";
		}
		else {
			echo "					<option value='204'>204</option>\n";
		}
		if ($row['profile_key_id'] == "205") {
			echo "					<option value='205' selected='selected'>205</option>\n";
		}
		else {
			echo "					<option value='205'>205</option>\n";
		}
		if ($row['profile_key_id'] == "206") {
			echo "					<option value='206' selected='selected'>206</option>\n";
		}
		else {
			echo "					<option value='206'>206</option>\n";
		}
		if ($row['profile_key_id'] == "207") {
			echo "					<option value='207' selected='selected'>207</option>\n";
		}
		else {
			echo "					<option value='207'>207</option>\n";
		}
		if ($row['profile_key_id'] == "208") {
			echo "					<option value='208' selected='selected'>208</option>\n";
		}
		else {
			echo "					<option value='208'>208</option>\n";
		}
		if ($row['profile_key_id'] == "209") {
			echo "					<option value='209' selected='selected'>209</option>\n";
		}
		else {
			echo "					<option value='209'>209</option>\n";
		}
		if ($row['profile_key_id'] == "210") {
			echo "					<option value='210' selected='selected'>210</option>\n";
		}
		else {
			echo "					<option value='210'>210</option>\n";
		}
		if ($row['profile_key_id'] == "211") {
			echo "					<option value='211' selected='selected'>211</option>\n";
		}
		else {
			echo "					<option value='211'>211</option>\n";
		}
		if ($row['profile_key_id'] == "212") {
			echo "					<option value='212' selected='selected'>212</option>\n";
		}
		else {
			echo "					<option value='212'>212</option>\n";
		}
		if ($row['profile_key_id'] == "213") {
			echo "					<option value='213' selected='selected'>213</option>\n";
		}
		else {
			echo "					<option value='213'>213</option>\n";
		}
		if ($row['profile_key_id'] == "214") {
			echo "					<option value='214' selected='selected'>214</option>\n";
		}
		else {
			echo "					<option value='214'>214</option>\n";
		}
		if ($row['profile_key_id'] == "215") {
			echo "					<option value='215' selected='selected'>215</option>\n";
		}
		else {
			echo "					<option value='215'>215</option>\n";
		}
		if ($row['profile_key_id'] == "216") {
			echo "					<option value='216' selected='selected'>216</option>\n";
		}
		else {
			echo "					<option value='216'>216</option>\n";
		}
		if ($row['profile_key_id'] == "217") {
			echo "					<option value='217' selected='selected'>217</option>\n";
		}
		else {
			echo "					<option value='217'>217</option>\n";
		}
		if ($row['profile_key_id'] == "218") {
			echo "					<option value='218' selected='selected'>218</option>\n";
		}
		else {
			echo "					<option value='218'>218</option>\n";
		}
		if ($row['profile_key_id'] == "219") {
			echo "					<option value='219' selected='selected'>219</option>\n";
		}
		else {
			echo "					<option value='219'>219</option>\n";
		}
		if ($row['profile_key_id'] == "220") {
			echo "					<option value='220' selected='selected'>220</option>\n";
		}
		else {
			echo "					<option value='220'>220</option>\n";
		}
		if ($row['profile_key_id'] == "221") {
			echo "					<option value='221' selected='selected'>221</option>\n";
		}
		else {
			echo "					<option value='221'>221</option>\n";
		}
		if ($row['profile_key_id'] == "222") {
			echo "					<option value='222' selected='selected'>222</option>\n";
		}
		else {
			echo "					<option value='222'>222</option>\n";
		}
		if ($row['profile_key_id'] == "223") {
			echo "					<option value='223' selected='selected'>223</option>\n";
		}
		else {
			echo "					<option value='223'>223</option>\n";
		}
		if ($row['profile_key_id'] == "224") {
			echo "					<option value='224' selected='selected'>224</option>\n";
		}
		else {
			echo "					<option value='224'>224</option>\n";
		}
		if ($row['profile_key_id'] == "225") {
			echo "					<option value='225' selected='selected'>225</option>\n";
		}
		else {
			echo "					<option value='225'>225</option>\n";
		}
		if ($row['profile_key_id'] == "226") {
			echo "					<option value='226' selected='selected'>226</option>\n";
		}
		else {
			echo "					<option value='226'>226</option>\n";
		}
		if ($row['profile_key_id'] == "227") {
			echo "					<option value='227' selected='selected'>227</option>\n";
		}
		else {
			echo "					<option value='227'>227</option>\n";
		}
		if ($row['profile_key_id'] == "228") {
			echo "					<option value='228' selected='selected'>228</option>\n";
		}
		else {
			echo "					<option value='228'>228</option>\n";
		}
		if ($row['profile_key_id'] == "229") {
			echo "					<option value='229' selected='selected'>229</option>\n";
		}
		else {
			echo "					<option value='229'>229</option>\n";
		}
		if ($row['profile_key_id'] == "230") {
			echo "					<option value='230' selected='selected'>230</option>\n";
		}
		else {
			echo "					<option value='230'>230</option>\n";
		}
		if ($row['profile_key_id'] == "231") {
			echo "					<option value='231' selected='selected'>231</option>\n";
		}
		else {
			echo "					<option value='231'>231</option>\n";
		}
		if ($row['profile_key_id'] == "232") {
			echo "					<option value='232' selected='selected'>232</option>\n";
		}
		else {
			echo "					<option value='232'>232</option>\n";
		}
		if ($row['profile_key_id'] == "233") {
			echo "					<option value='233' selected='selected'>233</option>\n";
		}
		else {
			echo "					<option value='233'>233</option>\n";
		}
		if ($row['profile_key_id'] == "234") {
			echo "					<option value='234' selected='selected'>234</option>\n";
		}
		else {
			echo "					<option value='234'>234</option>\n";
		}
		if ($row['profile_key_id'] == "235") {
			echo "					<option value='235' selected='selected'>235</option>\n";
		}
		else {
			echo "					<option value='235'>235</option>\n";
		}
		if ($row['profile_key_id'] == "236") {
			echo "					<option value='236' selected='selected'>236</option>\n";
		}
		else {
			echo "					<option value='236'>236</option>\n";
		}
		if ($row['profile_key_id'] == "237") {
			echo "					<option value='237' selected='selected'>237</option>\n";
		}
		else {
			echo "					<option value='237'>237</option>\n";
		}
		if ($row['profile_key_id'] == "238") {
			echo "					<option value='238' selected='selected'>238</option>\n";
		}
		else {
			echo "					<option value='238'>238</option>\n";
		}
		if ($row['profile_key_id'] == "239") {
			echo "					<option value='239' selected='selected'>239</option>\n";
		}
		else {
			echo "					<option value='239'>239</option>\n";
		}
		if ($row['profile_key_id'] == "240") {
			echo "					<option value='240' selected='selected'>240</option>\n";
		}
		else {
			echo "					<option value='240'>240</option>\n";
		}
		if ($row['profile_key_id'] == "241") {
			echo "					<option value='241' selected='selected'>241</option>\n";
		}
		else {
			echo "					<option value='241'>241</option>\n";
		}
		if ($row['profile_key_id'] == "242") {
			echo "					<option value='242' selected='selected'>242</option>\n";
		}
		else {
			echo "					<option value='242'>242</option>\n";
		}
		if ($row['profile_key_id'] == "243") {
			echo "					<option value='243' selected='selected'>243</option>\n";
		}
		else {
			echo "					<option value='243'>243</option>\n";
		}
		if ($row['profile_key_id'] == "244") {
			echo "					<option value='244' selected='selected'>244</option>\n";
		}
		else {
			echo "					<option value='244'>244</option>\n";
		}
		if ($row['profile_key_id'] == "245") {
			echo "					<option value='245' selected='selected'>245</option>\n";
		}
		else {
			echo "					<option value='245'>245</option>\n";
		}
		if ($row['profile_key_id'] == "246") {
			echo "					<option value='246' selected='selected'>246</option>\n";
		}
		else {
			echo "					<option value='246'>246</option>\n";
		}
		if ($row['profile_key_id'] == "247") {
			echo "					<option value='247' selected='selected'>247</option>\n";
		}
		else {
			echo "					<option value='247'>247</option>\n";
		}
		if ($row['profile_key_id'] == "248") {
			echo "					<option value='248' selected='selected'>248</option>\n";
		}
		else {
			echo "					<option value='248'>248</option>\n";
		}
		if ($row['profile_key_id'] == "249") {
			echo "					<option value='249' selected='selected'>249</option>\n";
		}
		else {
			echo "					<option value='249'>249</option>\n";
		}
		if ($row['profile_key_id'] == "250") {
			echo "					<option value='250' selected='selected'>250</option>\n";
		}
		else {
			echo "					<option value='250'>250</option>\n";
		}
		if ($row['profile_key_id'] == "251") {
			echo "					<option value='251' selected='selected'>251</option>\n";
		}
		else {
			echo "					<option value='251'>251</option>\n";
		}
		if ($row['profile_key_id'] == "252") {
			echo "					<option value='252' selected='selected'>252</option>\n";
		}
		else {
			echo "					<option value='252'>252</option>\n";
		}
		if ($row['profile_key_id'] == "253") {
			echo "					<option value='253' selected='selected'>253</option>\n";
		}
		else {
			echo "					<option value='253'>253</option>\n";
		}
		if ($row['profile_key_id'] == "254") {
			echo "					<option value='254' selected='selected'>254</option>\n";
		}
		else {
			echo "					<option value='254'>254</option>\n";
		}
		if ($row['profile_key_id'] == "255") {
			echo "					<option value='255' selected='selected'>255</option>\n";
		}
		else {
			echo "					<option value='255'>255</option>\n";
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_vendor]' maxlength='255' value=\"".escape($row["profile_key_vendor"])."\">\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_type]' maxlength='255' value=\"".escape($row["profile_key_type"])."\">\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<select class='formfld' name='device_profile_keys[$x][profile_key_line]'>\n";
		echo "					<option value=''></option>\n";
		if ($row['profile_key_line'] == "0") {
			echo "					<option value='0' selected='selected'>0</option>\n";
		}
		else {
			echo "					<option value='0'>0</option>\n";
		}
		if ($row['profile_key_line'] == "1") {
			echo "					<option value='1' selected='selected'>1</option>\n";
		}
		else {
			echo "					<option value='1'>1</option>\n";
		}
		if ($row['profile_key_line'] == "2") {
			echo "					<option value='2' selected='selected'>2</option>\n";
		}
		else {
			echo "					<option value='2'>2</option>\n";
		}
		if ($row['profile_key_line'] == "3") {
			echo "					<option value='3' selected='selected'>3</option>\n";
		}
		else {
			echo "					<option value='3'>3</option>\n";
		}
		if ($row['profile_key_line'] == "4") {
			echo "					<option value='4' selected='selected'>4</option>\n";
		}
		else {
			echo "					<option value='4'>4</option>\n";
		}
		if ($row['profile_key_line'] == "5") {
			echo "					<option value='5' selected='selected'>5</option>\n";
		}
		else {
			echo "					<option value='5'>5</option>\n";
		}
		if ($row['profile_key_line'] == "6") {
			echo "					<option value='6' selected='selected'>6</option>\n";
		}
		else {
			echo "					<option value='6'>6</option>\n";
		}
		if ($row['profile_key_line'] == "7") {
			echo "					<option value='7' selected='selected'>7</option>\n";
		}
		else {
			echo "					<option value='7'>7</option>\n";
		}
		if ($row['profile_key_line'] == "8") {
			echo "					<option value='8' selected='selected'>8</option>\n";
		}
		else {
			echo "					<option value='8'>8</option>\n";
		}
		if ($row['profile_key_line'] == "9") {
			echo "					<option value='9' selected='selected'>9</option>\n";
		}
		else {
			echo "					<option value='9'>9</option>\n";
		}
		if ($row['profile_key_line'] == "10") {
			echo "					<option value='10' selected='selected'>10</option>\n";
		}
		else {
			echo "					<option value='10'>10</option>\n";
		}
		if ($row['profile_key_line'] == "11") {
			echo "					<option value='11' selected='selected'>11</option>\n";
		}
		else {
			echo "					<option value='11'>11</option>\n";
		}
		if ($row['profile_key_line'] == "12") {
			echo "					<option value='12' selected='selected'>12</option>\n";
		}
		else {
			echo "					<option value='12'>12</option>\n";
		}
		if ($row['profile_key_line'] == "13") {
			echo "					<option value='13' selected='selected'>13</option>\n";
		}
		else {
			echo "					<option value='13'>13</option>\n";
		}
		if ($row['profile_key_line'] == "14") {
			echo "					<option value='14' selected='selected'>14</option>\n";
		}
		else {
			echo "					<option value='14'>14</option>\n";
		}
		if ($row['profile_key_line'] == "15") {
			echo "					<option value='15' selected='selected'>15</option>\n";
		}
		else {
			echo "					<option value='15'>15</option>\n";
		}
		if ($row['profile_key_line'] == "16") {
			echo "					<option value='16' selected='selected'>16</option>\n";
		}
		else {
			echo "					<option value='16'>16</option>\n";
		}
		if ($row['profile_key_line'] == "17") {
			echo "					<option value='17' selected='selected'>17</option>\n";
		}
		else {
			echo "					<option value='17'>17</option>\n";
		}
		if ($row['profile_key_line'] == "18") {
			echo "					<option value='18' selected='selected'>18</option>\n";
		}
		else {
			echo "					<option value='18'>18</option>\n";
		}
		if ($row['profile_key_line'] == "19") {
			echo "					<option value='19' selected='selected'>19</option>\n";
		}
		else {
			echo "					<option value='19'>19</option>\n";
		}
		if ($row['profile_key_line'] == "20") {
			echo "					<option value='20' selected='selected'>20</option>\n";
		}
		else {
			echo "					<option value='20'>20</option>\n";
		}
		if ($row['profile_key_line'] == "21") {
			echo "					<option value='21' selected='selected'>21</option>\n";
		}
		else {
			echo "					<option value='21'>21</option>\n";
		}
		if ($row['profile_key_line'] == "22") {
			echo "					<option value='22' selected='selected'>22</option>\n";
		}
		else {
			echo "					<option value='22'>22</option>\n";
		}
		if ($row['profile_key_line'] == "23") {
			echo "					<option value='23' selected='selected'>23</option>\n";
		}
		else {
			echo "					<option value='23'>23</option>\n";
		}
		if ($row['profile_key_line'] == "24") {
			echo "					<option value='24' selected='selected'>24</option>\n";
		}
		else {
			echo "					<option value='24'>24</option>\n";
		}
		if ($row['profile_key_line'] == "25") {
			echo "					<option value='25' selected='selected'>25</option>\n";
		}
		else {
			echo "					<option value='25'>25</option>\n";
		}
		if ($row['profile_key_line'] == "26") {
			echo "					<option value='26' selected='selected'>26</option>\n";
		}
		else {
			echo "					<option value='26'>26</option>\n";
		}
		if ($row['profile_key_line'] == "27") {
			echo "					<option value='27' selected='selected'>27</option>\n";
		}
		else {
			echo "					<option value='27'>27</option>\n";
		}
		if ($row['profile_key_line'] == "28") {
			echo "					<option value='28' selected='selected'>28</option>\n";
		}
		else {
			echo "					<option value='28'>28</option>\n";
		}
		if ($row['profile_key_line'] == "29") {
			echo "					<option value='29' selected='selected'>29</option>\n";
		}
		else {
			echo "					<option value='29'>29</option>\n";
		}
		if ($row['profile_key_line'] == "30") {
			echo "					<option value='30' selected='selected'>30</option>\n";
		}
		else {
			echo "					<option value='30'>30</option>\n";
		}
		if ($row['profile_key_line'] == "31") {
			echo "					<option value='31' selected='selected'>31</option>\n";
		}
		else {
			echo "					<option value='31'>31</option>\n";
		}
		if ($row['profile_key_line'] == "32") {
			echo "					<option value='32' selected='selected'>32</option>\n";
		}
		else {
			echo "					<option value='32'>32</option>\n";
		}
		if ($row['profile_key_line'] == "33") {
			echo "					<option value='33' selected='selected'>33</option>\n";
		}
		else {
			echo "					<option value='33'>33</option>\n";
		}
		if ($row['profile_key_line'] == "34") {
			echo "					<option value='34' selected='selected'>34</option>\n";
		}
		else {
			echo "					<option value='34'>34</option>\n";
		}
		if ($row['profile_key_line'] == "35") {
			echo "					<option value='35' selected='selected'>35</option>\n";
		}
		else {
			echo "					<option value='35'>35</option>\n";
		}
		if ($row['profile_key_line'] == "36") {
			echo "					<option value='36' selected='selected'>36</option>\n";
		}
		else {
			echo "					<option value='36'>36</option>\n";
		}
		if ($row['profile_key_line'] == "37") {
			echo "					<option value='37' selected='selected'>37</option>\n";
		}
		else {
			echo "					<option value='37'>37</option>\n";
		}
		if ($row['profile_key_line'] == "38") {
			echo "					<option value='38' selected='selected'>38</option>\n";
		}
		else {
			echo "					<option value='38'>38</option>\n";
		}
		if ($row['profile_key_line'] == "39") {
			echo "					<option value='39' selected='selected'>39</option>\n";
		}
		else {
			echo "					<option value='39'>39</option>\n";
		}
		if ($row['profile_key_line'] == "40") {
			echo "					<option value='40' selected='selected'>40</option>\n";
		}
		else {
			echo "					<option value='40'>40</option>\n";
		}
		if ($row['profile_key_line'] == "41") {
			echo "					<option value='41' selected='selected'>41</option>\n";
		}
		else {
			echo "					<option value='41'>41</option>\n";
		}
		if ($row['profile_key_line'] == "42") {
			echo "					<option value='42' selected='selected'>42</option>\n";
		}
		else {
			echo "					<option value='42'>42</option>\n";
		}
		if ($row['profile_key_line'] == "43") {
			echo "					<option value='43' selected='selected'>43</option>\n";
		}
		else {
			echo "					<option value='43'>43</option>\n";
		}
		if ($row['profile_key_line'] == "44") {
			echo "					<option value='44' selected='selected'>44</option>\n";
		}
		else {
			echo "					<option value='44'>44</option>\n";
		}
		if ($row['profile_key_line'] == "45") {
			echo "					<option value='45' selected='selected'>45</option>\n";
		}
		else {
			echo "					<option value='45'>45</option>\n";
		}
		if ($row['profile_key_line'] == "46") {
			echo "					<option value='46' selected='selected'>46</option>\n";
		}
		else {
			echo "					<option value='46'>46</option>\n";
		}
		if ($row['profile_key_line'] == "47") {
			echo "					<option value='47' selected='selected'>47</option>\n";
		}
		else {
			echo "					<option value='47'>47</option>\n";
		}
		if ($row['profile_key_line'] == "48") {
			echo "					<option value='48' selected='selected'>48</option>\n";
		}
		else {
			echo "					<option value='48'>48</option>\n";
		}
		if ($row['profile_key_line'] == "49") {
			echo "					<option value='49' selected='selected'>49</option>\n";
		}
		else {
			echo "					<option value='49'>49</option>\n";
		}
		if ($row['profile_key_line'] == "50") {
			echo "					<option value='50' selected='selected'>50</option>\n";
		}
		else {
			echo "					<option value='50'>50</option>\n";
		}
		if ($row['profile_key_line'] == "51") {
			echo "					<option value='51' selected='selected'>51</option>\n";
		}
		else {
			echo "					<option value='51'>51</option>\n";
		}
		if ($row['profile_key_line'] == "52") {
			echo "					<option value='52' selected='selected'>52</option>\n";
		}
		else {
			echo "					<option value='52'>52</option>\n";
		}
		if ($row['profile_key_line'] == "53") {
			echo "					<option value='53' selected='selected'>53</option>\n";
		}
		else {
			echo "					<option value='53'>53</option>\n";
		}
		if ($row['profile_key_line'] == "54") {
			echo "					<option value='54' selected='selected'>54</option>\n";
		}
		else {
			echo "					<option value='54'>54</option>\n";
		}
		if ($row['profile_key_line'] == "55") {
			echo "					<option value='55' selected='selected'>55</option>\n";
		}
		else {
			echo "					<option value='55'>55</option>\n";
		}
		if ($row['profile_key_line'] == "56") {
			echo "					<option value='56' selected='selected'>56</option>\n";
		}
		else {
			echo "					<option value='56'>56</option>\n";
		}
		if ($row['profile_key_line'] == "57") {
			echo "					<option value='57' selected='selected'>57</option>\n";
		}
		else {
			echo "					<option value='57'>57</option>\n";
		}
		if ($row['profile_key_line'] == "58") {
			echo "					<option value='58' selected='selected'>58</option>\n";
		}
		else {
			echo "					<option value='58'>58</option>\n";
		}
		if ($row['profile_key_line'] == "59") {
			echo "					<option value='59' selected='selected'>59</option>\n";
		}
		else {
			echo "					<option value='59'>59</option>\n";
		}
		if ($row['profile_key_line'] == "60") {
			echo "					<option value='60' selected='selected'>60</option>\n";
		}
		else {
			echo "					<option value='60'>60</option>\n";
		}
		if ($row['profile_key_line'] == "61") {
			echo "					<option value='61' selected='selected'>61</option>\n";
		}
		else {
			echo "					<option value='61'>61</option>\n";
		}
		if ($row['profile_key_line'] == "62") {
			echo "					<option value='62' selected='selected'>62</option>\n";
		}
		else {
			echo "					<option value='62'>62</option>\n";
		}
		if ($row['profile_key_line'] == "63") {
			echo "					<option value='63' selected='selected'>63</option>\n";
		}
		else {
			echo "					<option value='63'>63</option>\n";
		}
		if ($row['profile_key_line'] == "64") {
			echo "					<option value='64' selected='selected'>64</option>\n";
		}
		else {
			echo "					<option value='64'>64</option>\n";
		}
		if ($row['profile_key_line'] == "65") {
			echo "					<option value='65' selected='selected'>65</option>\n";
		}
		else {
			echo "					<option value='65'>65</option>\n";
		}
		if ($row['profile_key_line'] == "66") {
			echo "					<option value='66' selected='selected'>66</option>\n";
		}
		else {
			echo "					<option value='66'>66</option>\n";
		}
		if ($row['profile_key_line'] == "67") {
			echo "					<option value='67' selected='selected'>67</option>\n";
		}
		else {
			echo "					<option value='67'>67</option>\n";
		}
		if ($row['profile_key_line'] == "68") {
			echo "					<option value='68' selected='selected'>68</option>\n";
		}
		else {
			echo "					<option value='68'>68</option>\n";
		}
		if ($row['profile_key_line'] == "69") {
			echo "					<option value='69' selected='selected'>69</option>\n";
		}
		else {
			echo "					<option value='69'>69</option>\n";
		}
		if ($row['profile_key_line'] == "70") {
			echo "					<option value='70' selected='selected'>70</option>\n";
		}
		else {
			echo "					<option value='70'>70</option>\n";
		}
		if ($row['profile_key_line'] == "71") {
			echo "					<option value='71' selected='selected'>71</option>\n";
		}
		else {
			echo "					<option value='71'>71</option>\n";
		}
		if ($row['profile_key_line'] == "72") {
			echo "					<option value='72' selected='selected'>72</option>\n";
		}
		else {
			echo "					<option value='72'>72</option>\n";
		}
		if ($row['profile_key_line'] == "73") {
			echo "					<option value='73' selected='selected'>73</option>\n";
		}
		else {
			echo "					<option value='73'>73</option>\n";
		}
		if ($row['profile_key_line'] == "74") {
			echo "					<option value='74' selected='selected'>74</option>\n";
		}
		else {
			echo "					<option value='74'>74</option>\n";
		}
		if ($row['profile_key_line'] == "75") {
			echo "					<option value='75' selected='selected'>75</option>\n";
		}
		else {
			echo "					<option value='75'>75</option>\n";
		}
		if ($row['profile_key_line'] == "76") {
			echo "					<option value='76' selected='selected'>76</option>\n";
		}
		else {
			echo "					<option value='76'>76</option>\n";
		}
		if ($row['profile_key_line'] == "77") {
			echo "					<option value='77' selected='selected'>77</option>\n";
		}
		else {
			echo "					<option value='77'>77</option>\n";
		}
		if ($row['profile_key_line'] == "78") {
			echo "					<option value='78' selected='selected'>78</option>\n";
		}
		else {
			echo "					<option value='78'>78</option>\n";
		}
		if ($row['profile_key_line'] == "79") {
			echo "					<option value='79' selected='selected'>79</option>\n";
		}
		else {
			echo "					<option value='79'>79</option>\n";
		}
		if ($row['profile_key_line'] == "80") {
			echo "					<option value='80' selected='selected'>80</option>\n";
		}
		else {
			echo "					<option value='80'>80</option>\n";
		}
		if ($row['profile_key_line'] == "81") {
			echo "					<option value='81' selected='selected'>81</option>\n";
		}
		else {
			echo "					<option value='81'>81</option>\n";
		}
		if ($row['profile_key_line'] == "82") {
			echo "					<option value='82' selected='selected'>82</option>\n";
		}
		else {
			echo "					<option value='82'>82</option>\n";
		}
		if ($row['profile_key_line'] == "83") {
			echo "					<option value='83' selected='selected'>83</option>\n";
		}
		else {
			echo "					<option value='83'>83</option>\n";
		}
		if ($row['profile_key_line'] == "84") {
			echo "					<option value='84' selected='selected'>84</option>\n";
		}
		else {
			echo "					<option value='84'>84</option>\n";
		}
		if ($row['profile_key_line'] == "85") {
			echo "					<option value='85' selected='selected'>85</option>\n";
		}
		else {
			echo "					<option value='85'>85</option>\n";
		}
		if ($row['profile_key_line'] == "86") {
			echo "					<option value='86' selected='selected'>86</option>\n";
		}
		else {
			echo "					<option value='86'>86</option>\n";
		}
		if ($row['profile_key_line'] == "87") {
			echo "					<option value='87' selected='selected'>87</option>\n";
		}
		else {
			echo "					<option value='87'>87</option>\n";
		}
		if ($row['profile_key_line'] == "88") {
			echo "					<option value='88' selected='selected'>88</option>\n";
		}
		else {
			echo "					<option value='88'>88</option>\n";
		}
		if ($row['profile_key_line'] == "89") {
			echo "					<option value='89' selected='selected'>89</option>\n";
		}
		else {
			echo "					<option value='89'>89</option>\n";
		}
		if ($row['profile_key_line'] == "90") {
			echo "					<option value='90' selected='selected'>90</option>\n";
		}
		else {
			echo "					<option value='90'>90</option>\n";
		}
		if ($row['profile_key_line'] == "91") {
			echo "					<option value='91' selected='selected'>91</option>\n";
		}
		else {
			echo "					<option value='91'>91</option>\n";
		}
		if ($row['profile_key_line'] == "92") {
			echo "					<option value='92' selected='selected'>92</option>\n";
		}
		else {
			echo "					<option value='92'>92</option>\n";
		}
		if ($row['profile_key_line'] == "93") {
			echo "					<option value='93' selected='selected'>93</option>\n";
		}
		else {
			echo "					<option value='93'>93</option>\n";
		}
		if ($row['profile_key_line'] == "94") {
			echo "					<option value='94' selected='selected'>94</option>\n";
		}
		else {
			echo "					<option value='94'>94</option>\n";
		}
		if ($row['profile_key_line'] == "95") {
			echo "					<option value='95' selected='selected'>95</option>\n";
		}
		else {
			echo "					<option value='95'>95</option>\n";
		}
		if ($row['profile_key_line'] == "96") {
			echo "					<option value='96' selected='selected'>96</option>\n";
		}
		else {
			echo "					<option value='96'>96</option>\n";
		}
		if ($row['profile_key_line'] == "97") {
			echo "					<option value='97' selected='selected'>97</option>\n";
		}
		else {
			echo "					<option value='97'>97</option>\n";
		}
		if ($row['profile_key_line'] == "98") {
			echo "					<option value='98' selected='selected'>98</option>\n";
		}
		else {
			echo "					<option value='98'>98</option>\n";
		}
		if ($row['profile_key_line'] == "99") {
			echo "					<option value='99' selected='selected'>99</option>\n";
		}
		else {
			echo "					<option value='99'>99</option>\n";
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_value]' maxlength='255' value=\"".escape($row["profile_key_value"])."\">\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_extension]' maxlength='255' value=\"".escape($row["profile_key_extension"])."\">\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<select class='formfld' name='device_profile_keys[$x][profile_key_protected]'>\n";
		echo "					<option value=''></option>\n";
		if ($row['profile_key_protected'] == "true") {
			echo "					<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "					<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($row['profile_key_protected'] == "false") {
			echo "					<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "					<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_label]' maxlength='255' value=\"".escape($row["profile_key_label"])."\">\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='device_profile_keys[$x][profile_key_icon]' maxlength='255' value=\"".escape($row["profile_key_icon"])."\">\n";
		echo "			</td>\n";
		echo "			<td class='list_control_icons' style='width: 25px;'>\n";
		echo "				<a href=\"device_profile_delete.php?device_profile_key_uuid=".escape($row['device_profile_key_uuid'])."&amp;a=delete\" alt='delete' onclick=\"return confirm('Do you really want to delete this?')\"><button type='button' class='btn btn-default list_control_icon'><span class='glyphicon glyphicon-remove'></span></button></a>\n";
		echo "			</td>\n";
		echo "		</tr>\n";
		$x++;
	}
	echo "	</table>\n";
	echo "<br />\n";
	echo $text['description-profile_key_icon']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_profile_settings']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<th class='vtablereq'>".$text['label-device_setting_name']."</th>\n";
	echo "			<td class='vtable'>".$text['label-device_setting_value']."</td>\n";
	echo "			<th class='vtablereq'>".$text['label-device_setting_enabled']."</th>\n";
	echo "			<td class='vtable'>".$text['label-device_setting_description']."</td>\n";
	echo "			<td class='vtable'></td>\n";
	echo "		</tr>\n";
	$x = 0;
	foreach($device_profile_settings as $row) {
		echo "		<tr>\n";
		echo "			<input type='hidden' name='device_profile_settings[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
		echo "			<input type='hidden' name='device_profile_settings[$x][device_profile_uuid]' value=\"".escape($row["device_profile_uuid"])."\">\n";
		echo "			<input type='hidden' name='device_profile_settings[$x][device_profile_setting_uuid]' value=\"".escape($row["device_profile_setting_uuid"])."\">\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='device_profile_settings[$x][profile_setting_name]' maxlength='255' value=\"".escape($row["profile_setting_name"])."\">\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='device_profile_settings[$x][profile_setting_value]' maxlength='255' value=\"".escape($row["profile_setting_value"])."\">\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<select class='formfld' name='device_profile_settings[$x][profile_setting_enabled]'>\n";
		echo "					<option value=''></option>\n";
		if ($row['profile_setting_enabled'] == "true") {
			echo "					<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "					<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($row['profile_setting_enabled'] == "false") {
			echo "					<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "					<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='device_profile_settings[$x][profile_setting_description]' maxlength='255' value=\"".escape($row["profile_setting_description"])."\">\n";
		echo "			</td>\n";
		echo "			<td class='list_control_icons' style='width: 25px;'>\n";
		echo "				<a href=\"device_profile_delete.php?device_profile_setting_uuid=".escape($row['device_profile_setting_uuid'])."&amp;a=delete\" alt='delete' onclick=\"return confirm('Do you really want to delete this?')\"><button type='button' class='btn btn-default list_control_icon'><span class='glyphicon glyphicon-remove'></span></button></a>\n";
		echo "			</td>\n";
		echo "		</tr>\n";
		$x++;
	}
	echo "	</table>\n";
	echo "<br />\n";
	echo $text['description-profile_setting_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-domain_uuid']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='domain_uuid'>\n";
	if (strlen($domain_uuid) == 0) {
		echo "		<option value='' selected='selected'>".$text['select-global']."</option>\n";
	}
	else {
		echo "		<option value=''>".$text['label-global']."</option>\n";
	}
	foreach ($_SESSION['domains'] as $row) {
		if ($row['domain_uuid'] == $domain_uuid) {
			echo "		<option value='".$row['domain_uuid']."' selected='selected'>".escape($row['domain_name'])."</option>\n";
		}
		else {
			echo "		<option value='".$row['domain_uuid']."'>".$row['domain_name']."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-domain_uuid']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_profile_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='device_profile_enabled'>\n";
	echo "		<option value=''></option>\n";
	if ($device_profile_enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($device_profile_enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-device_profile_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_profile_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='device_profile_description' maxlength='255' value='".escape($device_profile_description)."'>\n";
	echo "<br />\n";
	echo $text['description-device_profile_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='device_profile_uuid' value='".escape($device_profile_uuid)."'>\n";
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
