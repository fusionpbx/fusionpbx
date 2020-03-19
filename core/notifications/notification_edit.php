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
Portions created by the Initial Developer are Copyright (C) 2008-2012
the Initial Developer. All Rights Reserved.

Contributor(s):
Mark J Crane <markjcrane@fusionpbx.com>
*/

require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (if_group('superadmin')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

// retrieve software uuid
	$sql = "select software_uuid, software_url, software_version from v_software";
	$database = new database;
	$row = $database->select($sql, null, 'row');
	if (is_array($row) && sizeof($row) != 0) {
		$software_uuid = $row["software_uuid"];
		$software_url = $row["software_url"];
		$software_version = $row["software_version"];
	}
	unset($sql, $row);

	if (count($_REQUEST) > 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: notification_edit.php');
				exit;
			}

		// prepare demographic information **********************************************

			// fusionpbx version
			$software_ver = $software_version;

			// php version
			$php_ver = phpversion();

			// webserver name & version
			$web_server = $_SERVER['SERVER_SOFTWARE'];

			// switch version
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$switch_result = event_socket_request($fp, 'api version');
			}
			$switch_ver = trim($switch_result);

			// database name & version
			switch ($db_type) {
				case "pgsql" :	$sql = "select version();";			break;
				case "mysql" :	$sql = "select version();";			break;
				case "sqlite" :	$sql = "select sqlite_version();";	break;
			}
			$database = new database;
			$db_ver = $database->select($sql, null, 'column');
			unset($sql);

			// operating system name & version
			$os_platform = PHP_OS;
			$os_info_1 = php_uname("a");
			if ($os_platform == "Linux") {
				$os_info_2 = shell_exec("cat /etc/*{release,version}");
				$os_info_2 .= shell_exec("lsb_release -d -s");
			}
			else if (substr(strtoupper($os_platform), 0, 3) == "WIN") {
				$os_info_2 = trim(shell_exec("ver"));
			}

		// **************************************************************************

		// check for demographic only submit
		if (isset($_GET["demo"])) {

			// update remote server record with new values
			$url = "https://".$software_url."/app/notifications/notifications_manage.php";
			$url .= "?demo";
			$url .= "&id=".$software_uuid;
			$url .= "&software_ver=".urlencode($software_ver);
			$url .= "&php_ver=".urlencode($php_ver);
			$url .= "&web_server=".urlencode($web_server);
			$url .= "&switch_ver=".urlencode($switch_ver);
			$url .= "&db_type=".urlencode($db_type);
			$url .= "&db_ver=".urlencode($db_ver);
			$url .= "&os_platform=".urlencode($os_platform);
			$url .= "&os_info_1=".urlencode($os_info_1);
			$url .= "&os_info_2=".urlencode($os_info_2);

			if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {
				$response = file_get_contents($url);
			}
			else if (function_exists('curl_version')) {
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($curl);
				curl_close($curl);
			}

			// parse response
			$response = json_decode($response, true);

			if ($response['result'] == 'submitted') {
				// set message
				message::add($text['message-demographics_submitted']);
			}

			header("Location: notification_edit.php");
			exit;

		}

		// retrieve submitted values
		$project_notifications = check_str($_POST["project_notifications"]);
		$project_security = check_str($_POST["project_security"]);
		$project_releases = check_str($_POST["project_releases"]);
		$project_events = check_str($_POST["project_events"]);
		$project_news = check_str($_POST["project_news"]);
		$project_notification_method = check_str($_POST["project_notification_method"]);
		$project_notification_recipient = check_str($_POST["project_notification_recipient"]);

		// get local project notification participation flag
		$sql = "select project_notifications from v_notifications";
		$database = new database;
		$current_project_notifications = $database->select($sql, null, 'row');
		unset($sql);

		// check if remote record should be removed
		if ($project_notifications == 'false') {

			if ($current_project_notifications == 'true') {
				// remove remote server record
				$url = "https://".$software_url."/app/notifications/notifications_manage.php?id=".$software_uuid."&action=delete";
				if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {
					$response = file_get_contents($url);
				}
				else if (function_exists('curl_version')) {
					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					$response = curl_exec($curl);
					curl_close($curl);
				}

				// parse response
				$response = json_decode($response, true);

				if ($response['result'] == 'deleted') {
					// set local project notification participation flag to false
					$sql = "update v_notifications set project_notifications = 'false'";
					$database = new database;
					$database->execute($sql);
					unset($sql);
				}
			}
			// redirect
			message::add($text['message-update']);
			header("Location: notification_edit.php");
			exit;
		}

		// check for invalid values
		if ($project_notifications == 'true') {
			if (
				($project_notification_method == 'email' && !valid_email($project_notification_recipient)) ||
				($project_notification_method == 'email' && $project_notification_recipient == '')
				) {
					$_SESSION["postback"] = $_POST;
					message::add($text['message-invalid_recipient'], 'negative');
					header("Location: notification_edit.php");
					exit;
			}
		}

		// update remote server record with new values
		$url = "https://".$software_url."/app/notifications/notifications_manage.php";
		$url .= "?id=".$software_uuid;
		$url .= "&security=".$project_security;
		$url .= "&releases=".$project_releases;
		$url .= "&events=".$project_events;
		$url .= "&news=".$project_news;
		$url .= "&method=".$project_notification_method;
		$url .= "&recipient=".urlencode($project_notification_recipient);
		$url .= "&software_ver=".urlencode($software_ver);
		$url .= "&php_ver=".urlencode($php_ver);
		$url .= "&web_server=".urlencode($web_server);
		$url .= "&switch_ver=".urlencode($switch_ver);
		$url .= "&db_type=".urlencode($db_type);
		$url .= "&db_ver=".urlencode($db_ver);
		$url .= "&os_platform=".urlencode($os_platform);
		$url .= "&os_info_1=".urlencode($os_info_1);
		$url .= "&os_info_2=".urlencode($os_info_2);

		if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {
			$response = file_get_contents($url);
		}
		else if (function_exists('curl_version')) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($curl);
			curl_close($curl);
		}

		// parse response
		$response = json_decode($response, true);

		if ($response['result'] == 'updated' || $response['result'] == 'inserted') {
			// set local project notification participation flag to true
			$sql = "update v_notifications set project_notifications = 'true'";
			$database = new database;
			$database->execute($sql);
			unset($sql);
			// set message
			if (
				$project_security == 'false' &&
				$project_releases == 'false' &&
				$project_events == 'false' &&
				$project_news == 'false'
				) {
				message::add($text['message-update']." - ".$text['message-no_channels'], 'alert');
			}
			else {
				message::add($text['message-update']);
			}
			// redirect
			header("Location: notification_edit.php");
			exit;
		}

	}

// check postback session
	if (!isset($_SESSION["postback"])) {

		// check local project notification participation flag
		$sql = "select project_notifications from v_notifications";
		$database = new database;
		$row = $database->select($sql, null, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$setting["project_notifications"] = $row["project_notifications"];
		}
		unset($sql, $row);

		// if participation enabled
		if ($setting["project_notifications"] == 'true') {

			// get current project notification preferences
			$url = "https://".$software_url."/app/notifications/notifications_manage.php?id=".$software_uuid;
			if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {
				$response = file_get_contents($url);
			}
			else if (function_exists('curl_version')) {
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($curl);
				curl_close($curl);
			}

			// parse response
			$setting = json_decode($response, true);
			$setting["project_notifications"] = 'true';
		}

	}
	else {

		// load postback variables
		$setting = fix_postback($_SESSION["postback"]);
		unset($_SESSION["postback"]);

	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

require_once "resources/header.php";
$document['title'] = $text['title-notifications'];

// show the content
	echo "<form method='post' name='frm' id='frm'>\n";
	echo "<table cellpadding='0' cellspacing='0' width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' nowrap='nowrap'><b>".$text['header-notifications']."</b><br><br></td>\n";
	echo "		<td align='right'>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "			<br><br>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-notifications']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "		<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo 			$text['label-project_notifications']."\n";
	echo "		</td>\n";
	echo "		<td width='70%' class='vtable' align='left'>\n";
	echo "			<select name='project_notifications' class='formfld' style='width: auto;' onchange=\"$('#notification_channels').slideToggle();\">\n";
	echo "				<option value='false' ".(($setting["project_notifications"] == 'false') ? "selected='selected'" : null).">".$text['option-disabled']."</option>\n";
	echo "				<option value='true' ".(($setting["project_notifications"] == 'true') ? "selected='selected'" : null).">".$text['option-enabled']."</option>\n";
	echo "			</select><br />\n";
	echo 			$text['description-project_notifications']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";

	echo "</table>\n";

	echo "<div id='notification_channels' ".(($setting["project_notifications"] != 'true') ? "style='display: none;'" : null).">\n";
		echo "<table cellpadding='0' cellspacing='0' width='100%' border='0'>\n";

		echo "	<tr>\n";
		echo "		<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
		echo 			$text['label-project_security']."\n";
		echo "		</td>\n";
		echo "		<td width='70%' class='vtable' align='left'>\n";
		echo "			<select name='project_security' class='formfld' style='width: auto;'>\n";
		echo "				<option value='false' ".(($setting["project_security"] == 'false') ? "selected='selected'" : null).">".$text['option-disabled']."</option>\n";
		echo "				<option value='true' ".(($setting["project_security"] == 'true') ? "selected='selected'" : null).">".$text['option-enabled']."</option>\n";
		echo "			</select><br />\n";
		echo 			$text['description-project_security']."\n";
		echo "		</td>\n";
		echo "	</tr>\n";

		echo "	<tr>\n";
		echo "		<td class='vncell' valign='top' align='left' nowrap>\n";
		echo 			$text['label-project_releases']."\n";
		echo "		</td>\n";
		echo "		<td class='vtable' align='left'>\n";
		echo "			<select name='project_releases' class='formfld' style='width: auto;'>\n";
		echo "				<option value='false' ".(($setting["project_releases"] == 'false') ? "selected='selected'" : null).">".$text['option-disabled']."</option>\n";
		echo "				<option value='true' ".(($setting["project_releases"] == 'true') ? "selected='selected'" : null).">".$text['option-enabled']."</option>\n";
		echo "			</select><br />\n";
		echo 			$text['description-project_releases']."\n";
		echo "		</td>\n";
		echo "	</tr>\n";

		echo "	<tr>\n";
		echo "		<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
		echo 			$text['label-project_events']."\n";
		echo "		</td>\n";
		echo "		<td width='70%' class='vtable' align='left'>\n";
		echo "			<select name='project_events' class='formfld' style='width: auto;'>\n";
		echo "				<option value='false' ".(($setting["project_events"] == 'false') ? "selected='selected'" : null).">".$text['option-disabled']."</option>\n";
		echo "				<option value='true' ".(($setting["project_events"] == 'true') ? "selected='selected'" : null).">".$text['option-enabled']."</option>\n";
		echo "			</select><br />\n";
		echo 			$text['description-project_events']."\n";
		echo "		</td>\n";
		echo "	</tr>\n";

		echo "	<tr>\n";
		echo "		<td class='vncell' valign='top' align='left' nowrap>\n";
		echo 			$text['label-project_news']."\n";
		echo "		</td>\n";
		echo "		<td class='vtable' align='left'>\n";
		echo "			<select name='project_news' class='formfld' style='width: auto;'>\n";
		echo "				<option value='false' ".(($setting["project_news"] == 'false') ? "selected='selected'" : null).">".$text['option-disabled']."</option>\n";
		echo "				<option value='true' ".(($setting["project_news"] == 'true') ? "selected='selected'" : null).">".$text['option-enabled']."</option>\n";
		echo "			</select><br />\n";
		echo 			$text['description-project_news']."\n";
		echo "		</td>\n";
		echo "	</tr>\n";

		echo "	<input type='hidden' name='project_notification_method' value='email'>\n";
		/*
		echo "	<tr>\n";
		echo "		<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
		echo 			$text['label-project_notification_method']."\n";
		echo "		</td>\n";
		echo "		<td width='70%' class='vtable' align='left'>\n";
		echo "			<select name='project_notification_method' class='formfld' style='width: auto;'>\n";
		echo "				<option value='email' ".(($setting["project_notification_method"] == 'email') ? "selected='selected'" : null).">".$text['option-email']."</option>\n";
		echo "			</select><br />\n";
		echo 			$text['description-project_notification_method']."\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		*/

		echo "	<tr>\n";
		echo "		<td class='vncellreq' valign='top' align='left' nowrap>\n";
		echo 			$text['label-project_notification_recipient']."\n";
		echo "		</td>\n";
		echo "		<td class='vtable' align='left'>\n";
		echo "			<input class='formfld' type='text' name='project_notification_recipient' maxlength='50' value='".$setting["project_notification_recipient"]."'><br />\n";
		echo 			$text['description-project_notification_recipient']."\n";
		echo "		</td>\n";
		echo "	</tr>\n";

		echo "</table>\n";
	echo "</div>\n";

	echo "<table cellpadding='0' cellspacing='0' width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' class='vtable' style='padding: 15px;' align='right'>\n";
	echo "			".$text['message-disclaimer']."\n";
	echo "			<br /><br />\n";
	echo "			".$text['message-demographics']." <a href='?demo'>".$text['message-demographics_click_here']."</a>.\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table cellpadding='0' cellspacing='0' width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='right'>\n";
	echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "</table>\n";

	echo "</form>\n";

// include the footer
	require_once "resources/footer.php";
?>