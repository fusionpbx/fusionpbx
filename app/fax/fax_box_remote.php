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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('fax_inbox_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get fax server uuid, set connection parameters
	if (strlen($_GET['id']) > 0) {
		$fax_uuid = check_str($_GET["id"]);

		if (if_group("superadmin") || if_group("admin")) {
			//show all fax extensions
			$sql = "select * from v_fax ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and fax_uuid = '$fax_uuid' ";
		}
		else {
			//show only assigned fax extensions
			$sql = "select * from v_fax as f, v_fax_users as u ";
			$sql .= "where f.fax_uuid = u.fax_uuid ";
			$sql .= "and f.domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and f.fax_uuid = '$fax_uuid' ";
			$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
		}
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (count($result) == 0) {
			if (!if_group("superadmin") && !if_group("admin")) {
				echo "access denied";
				exit;
			}
		}
		foreach ($result as &$row) {
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
			break;
		}
		unset ($prep_statement);

		// make connection
		$fax_email_connection = "{".$fax_email_connection_host.":".$fax_email_connection_port."/".$fax_email_connection_type;
		$fax_email_connection .= ($fax_email_connection_security != '') ? "/".$fax_email_connection_security : "/notls";
		$fax_email_connection .= "/".(($fax_email_connection_validate == 'false') ? "no" : null)."validate-cert";
		$fax_email_connection .= "}".$fax_email_connection_mailbox;
		if (!$mailbox = imap_open($fax_email_connection, $fax_email_connection_username, $fax_email_connection_password)) {
			$_SESSION["message_mood"] = 'negative';
			$_SESSION["message"] = $text['message-cannot_connect']."(".imap_last_error().")";
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
		$email_id = check_str($_GET['email_id']);

		//download attachment
		if (isset($_GET['download'])) {
			$attachment = get_attachments($mailbox, $email_id, FT_UID);
			$file_type = pathinfo($attachment[0]['filename'], PATHINFO_EXTENSION);
			switch ($file_type) {
				case "pdf" : header("Content-Type: application/pdf"); break;
				case "tif" : header("Contet-Type: image/tiff"); break;
			}
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
			header("Content-Length: ".strlen($attachment[0]['attachment']));
			$browser = $_SERVER["HTTP_USER_AGENT"];
			if (preg_match("/MSIE 5.5/", $browser) || preg_match("/MSIE 6.0/", $browser)) {
				header("Content-Disposition: filename=\"".$attachment[0]['filename']."\"");
			}
			else {
				header("Content-Disposition: attachment; filename=\"".$attachment[0]['filename']."\"");
			}
			header("Content-Transfer-Encoding: binary");
			echo $attachment[0]['attachment'];
			exit;
		}

		//delete email
		if (isset($_GET['delete']) && permission_exists('fax_inbox_delete')) {
			$attachment = get_attachments($mailbox, $email_id, FT_UID);
			if (imap_delete($mailbox, $email_id, FT_UID)) {
				if (imap_expunge($mailbox)) {
					//clean up local inbox copy
					$fax_dir = $_SESSION['switch']['storage']['dir'].'/fax'.((count($_SESSION["domains"]) > 1) ? '/'.$_SESSION['domain_name'] : null);
					@unlink($fax_dir.'/'.$fax_extension.'/inbox/'.$attachment[0]['filename']);
					//redirect user
					$_SESSION["message"] = $text['message-delete'];
					header("Location: ?id=".$fax_uuid);
					exit;
				}
			}
			else {
				//redirect user
				$_SESSION["message_mood"] = "negative";
				$_SESSION["message"] = $text['message-delete_failed'];
				header("Location: ?id=".$fax_uuid);
				exit;
			}
		}
		else {
			//redirect user
			$_SESSION["message_mood"] = "negative";
			$_SESSION["message"] = $text['message-delete_failed'];
			header("Location: ?id=".$fax_uuid);
			exit;
		}

	}

//get emails
	$emails = imap_search($mailbox, 'SUBJECT "Fax"', SE_UID);

//show the header
	require_once "resources/header.php";

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
	echo "		<th width='30%'>".$text['label-email_received']."</th>\n";
	echo "		<th width='60%'>".$text['label-email-fax']."</th>\n";
	echo "		<th width='10%'>".$text['label-email_size']."</th>\n";
	if (permission_exists('fax_inbox_delete')) {
		echo "		<td class='list_control_icons'>&nbsp;</td>\n";
	}
	echo "	</tr>";

	if ($emails) {

		rsort($emails); // most recent on top

		foreach ($emails as $email_id) {
			$metadata = object_to_array(imap_fetch_overview($mailbox, $email_id, FT_UID));
			$attachment = get_attachments($mailbox, $email_id, FT_UID);

			echo "<tr ".(($metadata[0]['seen'] == 0) ? "style='font-weight: bold;'" : null).">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$metadata[0]['date']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'><a href='?id=".$fax_uuid."&email_id=".$email_id."&download'>".$attachment[0]['filename']."</a></td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".byte_convert(strlen($attachment[0]['attachment']))."</td>\n";
			if (permission_exists('fax_inbox_delete')) {
				echo "	<td class='list_control_icons'><a href='?id=".$fax_uuid."&email_id=".$email_id."&delete' onclick=\"return confirm('".$text['message-confirm-delete']."')\">$v_link_label_delete</a></td>\n";
			}
			echo "</tr>\n";
			//$message = imap_fetchbody($mailbox, $email_id, 2, FT_UID);
			//echo $message;
		}

	}
	else {
		echo "<tr valign='top'>\n";
		echo "	<td colspan='4' style='text-align: center;'><br><br>".$text['message-no_faxes_found']."<br><br></td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "<br><br>";

/* close the connection */
imap_close($inbox);


//show the footer
	require_once "resources/footer.php";



//functions used above
function object_to_array($obj) {
	if (!is_object($obj) && !is_array($obj)) { return $obj; }
	if (is_object($obj)) { $obj = get_object_vars($obj); }
	return array_map('object_to_array', $obj);
}

function get_attachments($connection, $message_number, $option = '') {
    $attachments = array();
    $structure = imap_fetchstructure($connection, $message_number, $option);

    if(isset($structure->parts) && count($structure->parts)) {

        for($i = 0; $i < count($structure->parts); $i++) {

            if($structure->parts[$i]->ifdparameters) {
                foreach($structure->parts[$i]->dparameters as $object) {
                    if(strtolower($object->attribute) == 'filename') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['filename'] = $object->value;
                    }
                }
            }

            if($structure->parts[$i]->ifparameters) {
                foreach($structure->parts[$i]->parameters as $object) {
                    if(strtolower($object->attribute) == 'name') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['name'] = $object->value;
                    }
                }
            }

            if($attachments[$i]['is_attachment']) {
                $attachments[$i]['attachment'] = imap_fetchbody($connection, $message_number, $i+1, $option);
                if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                    $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                }
                elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                    $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                }
            }

			unset($attachments[$i]['is_attachment']);
        }

    }
    return array_values($attachments); //reindex
}

?>