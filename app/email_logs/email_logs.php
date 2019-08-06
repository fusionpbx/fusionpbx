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
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('email_log_view')) {
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
	if ($_REQUEST['a'] == 'download' && permission_exists('email_log_download')) {
		$email_log_uuid = check_str($_REQUEST["id"]);

		$msg_found = false;

		if ($email_log_uuid != '') {
			$sql = "select call_uuid, email from v_email_logs ";
			$sql .= "where email_log_uuid = '".$email_log_uuid."' ";
			$sql .= "and domain_uuid = '".$domain_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			if (is_array($result)) {
				foreach($result as $row) {
					$call_uuid = $row['call_uuid'];
					$email = $row['email'];
					$msg_found = true;
					break;
				}
			}
			unset ($prep_statement, $sql, $result);
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
	if ($_REQUEST['a'] == 'resend' && permission_exists('email_log_resend')) {
		$email_log_uuid = check_str($_REQUEST["id"]);
		$resend = true;

		$msg_found = false;

		if ($email_log_uuid != '') {
			$sql = "select email from v_email_logs ";
			$sql .= "where email_log_uuid = '".$email_log_uuid."' ";
			if (!permission_exists('email_log_all') || $_REQUEST['showall'] != 'true') {
				$sql .= "and domain_uuid = '".$domain_uuid."' ";
			}
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			if (is_array($result)) {
				foreach($result as $row) {
					$email = $row['email'];
					$msg_found = true;
					break;
				}
			}
			unset ($prep_statement, $sql, $result);
		}

		if ($msg_found) {
			$msg = $email;
			require_once "secure/v_mailto.php";
			if ($mailer_error == '') {
				message::add($text['message-message_resent']);
				if (permission_exists('email_log_all') && $_REQUEST['showall'] == 'true') {
					header("Location: email_log_delete.php?id=".$email_log_uuid."&showall=true");
				} else {
					header("Location: email_log_delete.php?id=".$email_log_uuid);
				}
			}
			else {
				message::add($text['message-resend_failed'].": ".$mailer_error, 'negative', 4000);
				if (permission_exists('email_log_all') && $_REQUEST['showall'] == 'true') {
					header("Location: email_logs.php?showall=true");
				} else {
					header("Location: email_logs.php");
				}
			}
		}

		exit;
	}

//prepare to page the results
	require_once "resources/paging.php";
	$sql = "select count(*) from v_email_logs ";
	if (permission_exists('email_log_all') && $_REQUEST['showall'] != 'true') {
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	if (permission_exists('email_log_all') && $_REQUEST['showall'] == 'true') {
		$param .= "&showall=true";
	} else {
		$param = "";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_email_logs ";
	if (permission_exists('email_log_all') && $_REQUEST['showall'] == 'true') {
		$sql .= "join v_domains on v_email_logs.domain_uuid = v_domains.domain_uuid ";
	}
	else {
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$sql .= order_by($order_by, $order, 'sent_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//additional includes
	$document['title'] = $text['title-emails'];
	require_once "resources/header.php";

//styles
	echo "<style>\n";

	echo "	#test_result_layer {\n";
	echo "		z-index: 999999;\n";
	echo "		position: absolute;\n";
	echo "		left: 0px;\n";
	echo "		top: 0px;\n";
	echo "		right: 0px;\n";
	echo "		bottom: 0px;\n";
	echo "		text-align: center;\n";
	echo "		vertical-align: middle;\n";
	echo "	}\n";

	echo "	#test_result_container {\n";
	echo "		display: block;\n";
	echo "		overflow: auto;\n";
	echo "		background-color: #fff;\n";
	echo "		padding: 20px 30px;\n";
	if (http_user_agent('mobile')) {
		echo "	margin: 0;\n";
	}
	else {
		echo "	margin: auto 10%;\n";
	}
	echo "		text-align: left;\n";
	echo "		-webkit-box-shadow: 0px 1px 20px #888;\n";
	echo "		-moz-box-shadow: 0px 1px 20px #888;\n";
	echo "		box-shadow: 0px 1px 20px #888;\n";
	echo "	}\n";

	echo "</style>\n";

//test result layer
	echo "<div id='test_result_layer' style='display: none;'>\n";
	echo "	<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>\n";
	echo "		<tr>\n";
	echo "			<td align='center' valign='middle'>\n";
	echo "				<span id='test_result_container'></span>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "</div>\n";

//show the content
	echo "<form id='test_form' method='post' action='email_test.php' target='_blank'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' valign='top' nowrap='nowrap'>";
	echo "			<b>".$text['header-emails']."</b>";
	echo "			<br /><br />";
	echo "			".$text['description-emails'];
	echo "		</td>\n";
	echo "		<td width='50%' align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' id='test_button' alt=\"".$text['button-test']."\" onclick=\"$(this).fadeOut(400, function(){ $('span#test_form').fadeIn(400); $('#to').focus(); });\" value='".$text['button-test']."'>\n";
	echo "			<span id='test_form' style='display: none;'>\n";
	echo "				<input type='text' class='formfld' style='min-width: 150px; width:150px; max-width: 150px;' name='to' id='to' placeholder='recipient@domain.com'>\n";
	echo "				<input type='submit' class='btn' id='send_button' alt=\"".$text['button-send']."\" value='".$text['button-send']."'>\n";
	echo "			</span>\n";
	if (permission_exists('email_log_all')) {
		if ($_REQUEST['showall'] != 'true') {
			echo "		<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='email_logs.php?showall=true';\">\n";
		}
	}
	echo "			<input type='button' class='btn' alt=\"".$text['button-refresh']."\" onclick=\"document.location.reload();\" value='".$text['button-refresh']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "</form>\n";
	echo "<br />\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($_REQUEST['showall'] == true && permission_exists('email_log_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, null, null, $param);
	}
	echo th_order_by('sent_date', $text['label-sent'], $order_by, $order, null, null, $param);
	echo th_order_by('type', $text['label-type'], $order_by, $order, null, null, $param);
	echo th_order_by('status', $text['label-status'], $order_by, $order, null, null, $param);
	echo "<th>".$text['label-message']."</th>\n";
	echo "<th>".$text['label-reference']."</th>\n";
	echo "<td class='list_control_icons'>&nbsp;</td>\n";
	echo "</tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		foreach($result as $row) {

			//get call details
			$sql = "select caller_id_name, caller_id_number, destination_number ";
			$sql .= "from v_xml_cdr ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and uuid = :uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['uuid'] = $row['call_uuid'];
			$database = new database;
			$result2 = $database->select($sql, $parameters, 'all');
			if (is_array($result2) && @sizeof($result2) != 0) {
				foreach($result2 as $row2) {
					$caller_id_name = ($row2['caller_id_name'] != '') ? $row2['caller_id_name'] : null;
					$caller_id_number = ($row2['caller_id_number'] != '') ? $row2['caller_id_number'] : null;
					$destination_number = ($row2['destination_number'] != '') ? $row2['destination_number'] : null;
				}
			}
			unset($sql, $parameters, $result2, $row2);

			$tr_link = "href='email_log_view.php?id=".$row['email_log_uuid']."'";
			echo "<tr ".$tr_link.">\n";
			if ($_REQUEST['showall'] == true && permission_exists('email_log_all')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['domain_name'])."</td>\n";
			}

			echo "	<td valign='top' class='".$row_style[$c]."'>";
			$sent_date = explode('.', $row['sent_date']);
			echo 		$sent_date[0];
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-type_'.escape($row['type'])]."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-status_'.escape($row['status'])]."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void'>";
			echo "		<a href='email_log_view.php?id=".escape($row['email_log_uuid'])."'>".$text['label-message_view']."</a>&nbsp;&nbsp;";
			if (permission_exists('email_log_download')) {
				echo "	<a href='?id=".escape($row['email_log_uuid'])."&a=download'>".$text['label-download']."</a>&nbsp;&nbsp;";
			}
			if (permission_exists('email_log_resend')) {
				echo "	<a href='?id=".$row['email_log_uuid']."&a=resend";
				if ($_REQUEST['showall'] == true && permission_exists('email_log_all')) {
					echo "&showall=true";
				}
				echo "'>" . $text['label-resend']."</a>";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg tr_link_void' style='white-space: nowrap; vertical-align: top;'>";
			echo "		<a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr_details.php?id=".escape($row['call_uuid'])."'>".$text['label-reference_cdr']."</a>";
			echo "		".($caller_id_name != '') ? "&nbsp;&nbsp;".$caller_id_name." (".format_phone($caller_id_number).")" : $caller_id_number;
			echo 		"&nbsp;&nbsp;<span style='font-size: 150%; line-height: 10px;'>&#8674;</span>&nbsp;&nbsp;".$destination_number;
			echo "	</td>\n";
			echo "	<td class='list_control_icons'>";
			echo 		"<a href='email_log_view.php?id=".escape($row['email_log_uuid'])."' alt='".$text['label-message_view']."'>$v_link_label_view</a>";
			if (permission_exists('email_log_delete')) {
				echo 	"<a href='email_log_delete.php?id=".escape($row['email_log_uuid']).($_REQUEST['showall'] == true ? '&showall=true' : null)."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }

		}
		unset($result, $row);
	}

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

//test script
	echo "<script>\n";

	echo "	$('#test_form').submit(function(event) {\n";
	echo "		event.preventDefault();\n";
	echo "		$.ajax({\n";
	echo "			url: $(this).attr('action'),\n";
	echo "			type: $(this).attr('method'),\n";
	echo "			data: new FormData(this),\n";
	echo "			processData: false,\n";
	echo "			contentType: false,\n";
	echo "			cache: false,\n";
	echo "			success: function(response){\n";
	echo "				$('#test_result_container').html(response);\n";
	echo "				$('#test_result_layer').fadeIn(400);\n";
	echo "				$('span#test_form').fadeOut(400);\n";
	echo "				$('#test_button').fadeIn(400);\n";
	echo "				$('#to').val('');\n";
	echo "			}\n";
	echo "		});\n";
	echo "	});\n";

	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
