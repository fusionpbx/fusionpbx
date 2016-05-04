<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('email_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = ($_GET["order_by"] != '') ? $_GET["order_by"] : 'sent_date';
	$order = ($_GET["order"] != '') ? $_GET["order"] : 'desc';

//download email
	if ($_REQUEST['a'] == 'download' && permission_exists('email_download')) {
		$email_uuid = check_str($_REQUEST["id"]);

		$msg_found = false;

		if ($email_uuid != '') {
			$sql = "select call_uuid, email from v_emails ";
			$sql .= "where email_uuid = '".$email_uuid."' ";
			$sql .= "and domain_uuid = '".$domain_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			if ($result_count > 0) {
				foreach($result as $row) {
					$call_uuid = $row['call_uuid'];
					$email = $row['email'];
					$msg_found = true;
					break;
				}
			}
			unset ($prep_statement, $sql, $result, $result_count);
		}

		if ($msg_found) {
			header("Content-Type: message/rfc822");
			header('Content-Disposition: attachment; filename="'.$call_uuid.'.eml"');
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			header("Content-Length: ".strlen($email));
			echo $email;
			exit;
		}
	}

//resend email
	if ($_REQUEST['a'] == 'resend' && permission_exists('email_resend')) {
		$email_uuid = check_str($_REQUEST["id"]);
		$resend = true;

		$msg_found = false;

		if ($email_uuid != '') {
			$sql = "select email from v_emails ";
			$sql .= "where email_uuid = '".$email_uuid."' ";
			if (!permission_exists('emails_all') || $_REQUEST['showall'] != 'true') {
				$sql .= "and domain_uuid = '".$domain_uuid."' ";
			}
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			if ($result_count > 0) {
				foreach($result as $row) {
					$email = $row['email'];
					$msg_found = true;
					break;
				}
			}
			unset ($prep_statement, $sql, $result, $result_count);
		}

		if ($msg_found) {
			$msg = $email;
			require_once "secure/v_mailto.php";
			if ($mailer_error == '') {
				$_SESSION["message"] = $text['message-message_resent'];
				if (permission_exists('emails_all') && $_REQUEST['showall'] == 'true') {
					header("Location: email_delete.php?id=".$email_uuid."&showall=true");
				} else {
					header("Location: email_delete.php?id=".$email_uuid);
				}
			}
			else {
				$_SESSION["message_mood"] = 'negative';
				$_SESSION["message_delay"] = '4'; //sec
				$_SESSION["message"] = $text['message-resend_failed'].": ".$mailer_error;
				if (permission_exists('emails_all') && $_REQUEST['showall'] == 'true') {
					header("Location: emails.php?showall=true");
				} else {
					header("Location: emails.php");
				}
			}
		}

		exit;
	}

//additional includes
	$document['title'] = $text['title-emails'];
	require_once "resources/header.php";
	require_once "resources/paging.php";

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' valign='top' nowrap='nowrap'>";
	echo "			<b>".$text['header-emails']."</b>";
	echo "			<br /><br />";
	echo "			".$text['description-emails'];
	echo "		</td>\n";
	echo "		<td width='50%' align='right' valign='top'>\n";
	if (permission_exists('emails_all')) {
		if ($_REQUEST['showall'] != 'true') {
			echo "		<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='emails.php?showall=true';\">\n";
		}
	}
	echo "			<input type='button' class='btn' alt=\"".$text['button-refresh']."\" onclick=\"document.location.reload();\" value='".$text['button-refresh']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_emails ";
		if (permission_exists('emails_all')) {
			if ($_REQUEST['showall'] != 'true') {
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
			}
		}
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
		$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			$num_rows = ($row['num_rows'] > 0) ? $row['num_rows'] : 0;
		}

	//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		if (permission_exists('emails_all') && $_REQUEST['showall'] == 'true') {
				$param .= "&showall=true";
		} else {
			$param = "";
		}
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the list
		$sql = "select * from v_emails ";
		if (permission_exists('emails_all') && $_REQUEST['showall'] == 'true') {
				$sql .= " join v_domains on v_emails.domain_uuid = v_domains.domain_uuid ";
		} else {
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
		}
		if (strlen($order_by)> 0) { $sql .= "order by ".$order_by." ".$order." "; }
		$sql .= "limit ".$rows_per_page." offset ".$offset." ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($_REQUEST['showall'] == true && permission_exists('emails_all')) {
		echo th_order_by('domain_name', $text['label-domain-name'], $order_by, $order, null, null, $param);
	}
	echo th_order_by('sent_date', $text['label-sent'], $order_by, $order, null, null, $param);
	echo th_order_by('type', $text['label-type'], $order_by, $order, null, null, $param);
	echo th_order_by('status', $text['label-status'], $order_by, $order, null, null, $param);
	echo "<th>".$text['label-message']."</th>\n";
	echo "<th>".$text['label-reference']."</th>\n";
	echo "<td class='list_control_icons'>&nbsp;</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {

			//get call details
				$sql = "select caller_id_name, caller_id_number, destination_number from v_xml_cdr ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and uuid = '".$row['call_uuid']."' ";
				//echo "<tr><td colspan='40'>".$sql."</td></tr>";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result2 = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach($result2 as $row2) {
					$caller_id_name = ($row2['caller_id_name'] != '') ? $row2['caller_id_name'] : null;
					$caller_id_number = ($row2['caller_id_number'] != '') ? $row2['caller_id_number'] : null;
					$destination_number = ($row2['destination_number'] != '') ? $row2['destination_number'] : null;
				}
				unset($prep_statement, $sql);

			$tr_link = "href='email_view.php?id=".$row['email_uuid']."'";
			echo "<tr ".$tr_link.">\n";
			if ($_REQUEST['showall'] == true && permission_exists('emails_all')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['domain_name']."</td>\n";
			}

			echo "	<td valign='top' class='".$row_style[$c]."'>";
			$sent_date = explode('.', $row['sent_date']);
			echo 		$sent_date[0];
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-type_'.$row['type']]."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-status_'.$row['status']]."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void'>";
			echo "		<a href='email_view.php?id=".$row['email_uuid']."'>".$text['label-message_view']."</a>&nbsp;&nbsp;";
			if (permission_exists('email_download')) {
				echo "	<a href='?id=".$row['email_uuid']."&a=download'>".$text['label-download']."</a>&nbsp;&nbsp;";
			}
			if (permission_exists('email_resend')) {
				echo "	<a href='?id=".$row['email_uuid']."&a=resend";
				if ($_REQUEST['showall'] == true && permission_exists('emails_all')) {
					echo "&showall=true";
				}
				echo "'>" . $text['label-resend']."</a>";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg tr_link_void' style='white-space: nowrap; vertical-align: top;'>";
			echo "		<a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr_details.php?uuid=".$row['call_uuid']."'>".$text['label-reference_cdr']."</a>";
			echo "		".($caller_id_name != '') ? "&nbsp;&nbsp;".$caller_id_name." (".format_phone($caller_id_number).")" : $caller_id_number;
			echo 		"&nbsp;&nbsp;<span style='font-size: 150%; line-height: 10px;'>&#8674;</span>&nbsp;&nbsp;".$destination_number;
			echo "	</td>\n";
			echo "	<td class='list_control_icons'>";
			echo 		"<a href='email_view.php?id=".$row['email_uuid']."' alt='".$text['label-message_view']."'>$v_link_label_view</a>";
			if (permission_exists('email_delete')) {
				echo 	"<a href='email_delete.php?id=".$row['email_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='21' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>
