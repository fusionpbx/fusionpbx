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
	Portions created by the Initial Developer are Copyright (C) 2016-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('message_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the action
	if (is_array($_POST["messages"])) {
		$messages = $_POST["messages"];
		foreach($messages as $row) {
			if ($row['action'] == 'delete') {
				$action = 'delete';
				break;
			}
		}
	}

//delete the messages
	if (permission_exists('message_delete')) {
		if ($action == "delete") {
			//download
				$obj = new messages;
				$obj->delete($messages);
			//delete message
				message::add($text['message-delete']);
		}
	}

//get variables used to control the order
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//add the search term
	$search = strtolower(check_str($_GET["search"]));
	if (strlen($search) > 0) {
		$sql_search = " (";
		$sql_search .= "lower(message_type) like '%".$search."%' ";
		$sql_search .= "or lower(message_direction) like '%".$search."%' ";
		$sql_search .= "or lower(message_date) like '%".$search."%' ";
		$sql_search .= "or lower(message_from) like '%".$search."%' ";
		$sql_search .= "or lower(message_to) like '%".$search."%' ";
		$sql_search .= "or lower(message_text) like '%".$search."%' ";
		$sql_search .= "or lower(message_media_type) like '%".$search."%' ";
		$sql_search .= ") ";
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(message_uuid) as num_rows from v_messages ";
	if ($_GET['show'] == "all" && permission_exists('message_all')) {
		if (isset($sql_search)) {
			$sql .= "where ".$sql_search;
		}
	} else {
		$sql .= "where user_uuid = '".$_SESSION['user_uuid']."' ";
		$sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
		if (isset($sql_search)) {
			$sql .= "and ".$sql_search;
		}
	}
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		if ($row['num_rows'] > 0) {
			$num_rows = $row['num_rows'];
		}
		else {
			$num_rows = '0';
		}
	}

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if ($_GET['show'] == "all" && permission_exists('message_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_messages ";
	if ($_GET['show'] == "all" && permission_exists('message_all')) {
		if (isset($sql_search)) {
			$sql .= "where ".$sql_search;
		}
	} else {
		$sql .= "where user_uuid = '".$_SESSION['user_uuid']."' ";
		$sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
		if (isset($sql_search)) {
				$sql .= "and ".$sql_search;
		}
	}
	$sql .= "order by message_date desc ";
	$sql .= "limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$messages = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//define the checkbox_toggle function
	echo "<script type=\"text/javascript\">\n";
	echo "	function checkbox_toggle(item) {\n";
	echo "		var inputs = document.getElementsByTagName(\"input\");\n";
	echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
	echo "		    if (inputs[i].type === 'checkbox') {\n";
	echo "		       	if (document.getElementById('checkbox_all').checked == true) {\n";
	echo "				inputs[i].checked = true;\n";
	echo "			}\n";
	echo "				else {\n";
	echo "					inputs[i].checked = false;\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-messages']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "				<input type='button' class='btn' value='".$text['button-send']."' onclick=\"window.location='message_send.php';\">\n";

	if (permission_exists('message_all')) {
		if ($_GET['show'] == 'all') {
			echo "				<input type='hidden' name='show' value='all'>";
		}
		else {
			echo "				<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='messages.php?show=all';\">\n";
		}
	}

	echo "				<input type='text' class='txt' style='width: 150px; margin-left: 15px;' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<form method='post' action=''>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	if (is_array($messages)) {
		$x = 0;
		foreach($messages as $row) {
			if ($x == 0) {
				echo "	<th style='width:30px;'>\n";
				echo "		<input type='checkbox' name='checkbox_all' id='checkbox_all' value='' onclick=\"checkbox_toggle();\">\n";
				echo "	</th>\n";
				echo th_order_by('message_type', $text['label-message_type'], $order_by, $order);
				echo th_order_by('message_direction', $text['label-message_direction'], $order_by, $order);
				echo th_order_by('message_date', $text['label-message_date'], $order_by, $order);
				echo th_order_by('message_from', $text['label-message_from'], $order_by, $order);
				echo th_order_by('message_to', $text['label-message_to'], $order_by, $order);
				echo th_order_by('message_text', $text['label-message_text'], $order_by, $order);
				echo "	<td class='list_control_icons'>";
				echo "		&nbsp;\n";
				echo "	</td>\n";
				echo "</tr>\n";

			}
			if (permission_exists('message_edit')) {
				$tr_link = "href='message_edit.php?id=".escape($row['message_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			//echo "	<td valign='top' class=''>".escape($row['user_uuid'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='align: center; padding: 3px 3px 0px 8px;'>\n";
			echo "		<input type='checkbox' name=\"messages[$x][checked]\" id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('chk_all_".$x."').checked = false; }\">\n";
			echo "		<input type='hidden' name=\"messages[$x][message_uuid]\" value='".escape($row['message_uuid'])."' />\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style=''>".escape($row['message_type'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style=''>".escape($row['message_direction'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style=''>".escape($row['message_date'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style=''>".escape($row['message_from'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style=''>".escape($row['message_to'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style=''>".escape($row['message_text'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['message_media_type'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['message_media_url'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['message_media_content'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['message_json'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('message_edit')) {
				echo "<a href='message_edit.php?id=".escape($row['message_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('message_delete')) {
				echo "<button type='submit' class='btn btn-default list_control_icon' name=\"messages[$x][action]\" alt='".$text['button-delete']."' value='delete'><span class='glyphicon glyphicon-remove'></span></button>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $messages);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='8' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	echo "			&nbsp;";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "</form>\n";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
