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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/functions/object_to_array.php";
	require_once "resources/functions/parse_message.php";

//check permissions
	if (permission_exists('fax_inbox_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get submitted id
	$fax_uuid = $_GET["id"];

//get fax server uuid, set connection parameters
	if (is_uuid($fax_uuid)) {

		if (permission_exists('fax_extension_view')) {
			//show all fax extensions
			$sql = "select * from v_fax ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and fax_uuid = :fax_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['fax_uuid'] = $fax_uuid;
		}
		else {
			//show only assigned fax extensions
			$sql = "select * from v_fax as f, v_fax_users as u ";
			$sql .= "where f.fax_uuid = u.fax_uuid ";
			$sql .= "and f.domain_uuid = :domain_uuid ";
			$sql .= "and f.fax_uuid = :fax_uuid ";
			$sql .= "and u.user_uuid = :user_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['fax_uuid'] = $fax_uuid;
			$parameters['user_uuid'] = $_SESSION['user_uuid'];
		}
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$fax_name = $row["fax_name"];
			$fax_extension = $row["fax_extension"];
			$fax_email_connection_type = $row["fax_email_connection_type"];
			$fax_email_connection_host = $row["fax_email_connection_host"];
			$fax_email_connection_port = $row["fax_email_connection_port"];
			$fax_email_connection_security = $row["fax_email_connection_security"];
			$fax_email_connection_validate = $row["fax_email_connection_validate"];
			$fax_email_connection_username = $row["fax_email_connection_username"];
			$fax_email_connection_password = $row["fax_email_connection_password"];
			$fax_email_connection_mailbox = $row["fax_email_connection_mailbox"];
			$fax_email_inbound_subject_tag = $row["fax_email_inbound_subject_tag"];
		}
		else {
			if (!permission_exists('fax_extension_view')) {
				echo "access denied";
				exit;
			}
		}
		unset($sql, $parameters, $row);

		// make connection
		$fax_email_connection = "{".$fax_email_connection_host.":".$fax_email_connection_port."/".$fax_email_connection_type;
		$fax_email_connection .= ($fax_email_connection_security != '') ? "/".$fax_email_connection_security : "/notls";
		$fax_email_connection .= "/".(($fax_email_connection_validate == 'false') ? "no" : null)."validate-cert";
		$fax_email_connection .= "}".$fax_email_connection_mailbox;
		if (!$connection = imap_open($fax_email_connection, $fax_email_connection_username, $fax_email_connection_password)) {
			message::add($text['message-cannot_connect']."(".imap_last_error().")", 'neative');
			header("Location: fax.php");
			exit;
		}

	}
	else {
		header("Location: fax.php");
		exit;
	}

//message action
	if ($_GET['email_id'] != '') {
		$email_id = $_GET['email_id'];

		//download attachment
		if (isset($_GET['download'])) {
			$message = parse_message($connection, $email_id, FT_UID);
			$attachment = $message['attachments'][0];
			if ($attachment) {
				$file_type = pathinfo($attachment['name'], PATHINFO_EXTENSION);
				switch ($file_type) {
					case "pdf" : header("Content-Type: application/pdf"); break;
					case "tif" : header("Contet-Type: image/tiff"); break;
				}
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
				header("Content-Length: ".$attachment['size']);
				$browser = $_SERVER["HTTP_USER_AGENT"];
				if (preg_match("/MSIE 5.5/", $browser) || preg_match("/MSIE 6.0/", $browser)) {
					header("Content-Disposition: filename=\"".$attachment['name']."\"");
				}
				else {
					header("Content-Disposition: attachment; filename=\"".$attachment['name']."\"");
				}
				header("Content-Transfer-Encoding: binary");
				echo $attachment['data'];
				exit;
			}
			else{
				//redirect user
				message::add($text['message-download_failed'], 'negative');
				header("Location: ?id=".$fax_uuid);
				exit;
			}

		}

		//delete email
		if (isset($_GET['delete']) && permission_exists('fax_inbox_delete')) {
			$message = parse_message($connection, $email_id, FT_UID);
			$attachment = $message['attachments'][0];
			if (imap_delete($connection, $email_id, FT_UID)) {
				if (imap_expunge($connection)) {
					//clean up local inbox copy
					$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'];
					@unlink($fax_dir.'/'.$fax_extension.'/inbox/'.$attachment['name']);
					//redirect user
					message::add($text['message-delete']);
					header("Location: ?id=".$fax_uuid);
					exit;
				}
			}
			else {
				//redirect user
				message::add($text['message-delete_failed'], 'negative');
				header("Location: ?id=".$fax_uuid);
				exit;
			}
		}
		else {
			//redirect user
			message::add($text['message-delete_failed'], 'negative');
			header("Location: ?id=".$fax_uuid);
			exit;
		}

	}

//get emails
	$emails = imap_search($connection, "SUBJECT \"".$fax_email_inbound_subject_tag."\"", SE_UID);

//show the header
	require_once "resources/header.php";

//set the row styles
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the inbox
	$c = 0;
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top'>\n";
	echo "			<b>".$text['header-inbox'].": <span style='color: #000;'>".$fax_name." (".$fax_extension.")</span></b>\n";
	echo "		</td>\n";
	echo "		<td width='70%' align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='fax.php';\" value='".$text['button-back']."'>\n";
	echo "			<input type='button' class='btn' alt='".$text['button-refresh']."' onclick=\"document.location.reload();\" value='".$text['button-refresh']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br><br>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<th>".$text['label-fax_caller_id_name']."</th>\n";
	echo "		<th>".$text['label-fax_caller_id_number']."</th>\n";
	echo "		<th>".$text['table-file']."</th>\n";
	echo "		<th>".$text['label-email_size']."</th>\n";
	echo "		<th>".$text['label-email_received']."</th>\n";
	if (permission_exists('fax_inbox_delete')) {
		echo "		<td style='width: 25px;' class='list_control_icons'>&nbsp;</td>\n";
	}
	echo "	</tr>";

	if (is_array($emails) && @sizeof($emails) != 0) {
		rsort($emails); // most recent on top
		foreach ($emails as $email_id) {
			$metadata = object_to_array(imap_fetch_overview($connection, $email_id, FT_UID));
			$message = parse_message($connection, $email_id, FT_UID);
			$attachment = $message['attachments'][0];
			$file_name = $attachment['name'];
			$caller_id_name = substr($file_name, 0, strpos($file_name, '-'));
			$caller_id_number = (is_numeric($caller_id_name)) ? format_phone((int) $caller_id_name) : null;
			echo "	<tr ".(($metadata[0]['seen'] == 0) ? "style='font-weight: bold;'" : null).">\n";
			echo "		<td valign='top' class='".$row_style[$c]."'>".$caller_id_name."</td>\n";
			echo "		<td valign='top' class='".$row_style[$c]."'>".$caller_id_number."</td>\n";
			echo "		<td valign='top' class='".$row_style[$c]."'><a href='?id=".$fax_uuid."&email_id=".$email_id."&download'>".$file_name."</a></td>\n";
			echo "		<td valign='top' class='".$row_style[$c]."'>".byte_convert($attachment['size'])."</td>\n";
			echo "		<td valign='top' class='".$row_style[$c]."'>".$metadata[0]['date']."</td>\n";
			if (permission_exists('fax_inbox_delete')) {
				echo "		<td style='width: 25px;' class='list_control_icons'><a href='?id=".$fax_uuid."&email_id=".$email_id."&delete' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a></td>\n";
			}
			echo "	</tr>\n";
			$c = ($c) ? 0 : 1;
		}
	}
	else {
		echo "<tr valign='top'>\n";
		echo "	<td colspan='4' style='text-align: center;'><br><br>".$text['message-no_faxes_found']."<br><br></td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "<br><br>";

//close the connection
	imap_close($connection);

//show the footer
	require_once "resources/footer.php";

?>
