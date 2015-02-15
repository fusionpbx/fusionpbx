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
require_once "config.php";
if (permission_exists('content_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

$rss_uuid = $_GET["rss_uuid"];

if (count($_POST)>0 && $_POST["persistform"] == "0") {
	$rss_sub_uuid = check_str($_POST["rss_sub_uuid"]);
	$rss_uuid = check_str($_POST["rss_uuid"]);
	$rss_sub_title = check_str($_POST["rss_sub_title"]);
	$rss_sub_link = check_str($_POST["rss_sub_link"]);
	$rss_sub_description = check_str($_POST["rss_sub_description"]);
	$rss_sub_optional_1 = check_str($_POST["rss_sub_optional_1"]);
	$rss_sub_optional_2 = check_str($_POST["rss_sub_optional_2"]);
	$rss_sub_optional_3 = check_str($_POST["rss_sub_optional_3"]);
	$rss_sub_optional_4 = check_str($_POST["rss_sub_optional_4"]);
	$rss_sub_optional_5 = check_str($_POST["rss_sub_optional_5"]);
	$rss_sub_add_date = check_str($_POST["rss_sub_add_date"]);
	$rss_sub_add_user = check_str($_POST["rss_sub_add_user"]);

	$msg = '';
	if (strlen($rss_uuid) == 0) { $msg .= $text['message-error-missing']." rss_uuid.<br>\n"; }
	if (strlen($rss_sub_uuid) == 0) { $msg .= $text['message-error-missing']." rss_sub_uuid.<br>\n"; }
	//if (strlen($rss_sub_title) == 0) { $msg .= "Please provide a title.<br>\n"; }
	if (strlen($rss_sub_description) == 0) { $msg .= $text['message-description']."<br>\n"; }

	if (strlen($msg) > 0) {
		require_once "resources/persist_form.php";
		require_once "resources/header.php";
		echo "<div align='center'>";
		echo "<table>";
		echo "<tr>";
		echo "<td>";
		echo "  <div class='borderlight' align='left' style='padding:10px;'>";
		echo "      $msg";
		echo "      <br>";
		echo "      <div align='center'>".persistform($_POST)."</div>";
		echo "  </div>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</div>";

		require_once "resources/footer.php";
		return;
	}

	//sql update
	$sql  = "update v_rss_sub set ";
	//$sql .= "rss_uuid = '$rss_uuid', ";
	$sql .= "rss_sub_title = '$rss_sub_title', ";
	$sql .= "rss_sub_link = '$rss_sub_link', ";
	$sql .= "rss_sub_description = '$rss_sub_description', ";
	$sql .= "rss_sub_optional_1 = '$rss_sub_optional_1', ";
	$sql .= "rss_sub_optional_2 = '$rss_sub_optional_2', ";
	$sql .= "rss_sub_optional_3 = '$rss_sub_optional_3', ";
	$sql .= "rss_sub_optional_4 = '$rss_sub_optional_4', ";
	$sql .= "rss_sub_optional_5 = '$rss_sub_optional_5' ";
	//$sql .= "rss_sub_add_date = now(), ";
	//$sql .= "rss_sub_add_user = '".$_SESSION["username"]."' ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and rss_sub_uuid = '$rss_sub_uuid' ";
	//$sql .= "and rss_uuid = '$rss_uuid' ";
	$count = $db->exec(check_sql($sql));
	//echo "Affected Rows: ".$count;

	$_SESSION["message"] = $text['message-update'];
	header("Location: rsssublist.php?rss_uuid=".$rss_uuid."&rss_sub_uuid=".$rss_sub_uuid);
	return;
}
else {
	//get data from the db
	$rss_sub_uuid = $_GET["rss_sub_uuid"];

	$sql = "";
	$sql .= "select * from v_rss_sub ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and rss_sub_uuid = '$rss_sub_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		//$rss_uuid = $row["rss_uuid"];
		$rss_sub_title = $row["rss_sub_title"];
		$rss_sub_link = $row["rss_sub_link"];
		$rss_sub_description = $row["rss_sub_description"];
		$rss_sub_optional_1 = $row["rss_sub_optional_1"];
		$rss_sub_optional_2 = $row["rss_sub_optional_2"];
		$rss_sub_optional_3 = $row["rss_sub_optional_3"];
		$rss_sub_optional_4 = $row["rss_sub_optional_4"];
		$rss_sub_optional_5 = $row["rss_sub_optional_5"];
		$rss_sub_add_date = $row["rss_sub_add_date"];
		$rss_sub_add_user = $row["rss_sub_add_user"];
		break; //limit to 1 row
	}
}

//show the header
	require_once "resources/header.php";
	require_once "resources/wysiwyg.php";

//show the content
	echo "<form method='post' action=''>";
	echo "<table cellpadding='0' cellspacing='0' width='100%'>";
	//echo "	<tr>";
	//echo "		<td>rss_uuid</td>";
	//echo "		<td><input type='text' name='rss_uuid' class='txt' value='$rss_uuid'></td>";
	//echo "	</tr>";
	echo "	<tr>";
	echo "		<td nowrap>".$text['label-sub-title']."</td>";
	echo "		<td width='100%'><input type='text' name='rss_sub_title' class='txt' value='$rss_sub_title'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td>".$text['label-sub-link']."</td>";
	echo "		<td><input type='text' name='rss_sub_link' class='txt' value='$rss_sub_link'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td valign='top'>".$text['label-description']."</td>";
	echo "		<td>";
	echo "            <textarea name='rss_sub_description' rows='12' class='txt'>$rss_sub_description</textarea>";
	echo "        </td>";
	echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_1</td>";
	//echo "		<td><input type='text' name='rss_sub_optional_1' value='$rss_sub_optional_1'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_2</td>";
	//echo "		<td><input type='text' name='rss_sub_optional_2' value='$rss_sub_optional_2'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_3</td>";
	//echo "		<td><input type='text' name='rss_sub_optional_3' value='$rss_sub_optional_3'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_4</td>";
	//echo "		<td><input type='text' name='rss_sub_optional_4' value='$rss_sub_optional_4'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td>rss_sub_optional_5</td>";
	//echo "		<td><input type='text' name='rss_sub_optional_5' value='$rss_sub_optional_5'></td>";
	//echo "	</tr>";

	echo "	<tr>";
	echo "		<td colspan='2' align='right'>";
	echo "		    <input type='hidden' name='rss_uuid' value='$rss_uuid'>";
	echo "		    <input type='hidden' name='persistform' value='0'>";
	echo "          <input type='hidden' name='rss_sub_uuid' value='$rss_sub_uuid'>";
	echo "			<br><br>";
	echo "          <input type='submit' name='submit' class='btn' value='".$text['button-update']."'>";
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//show the footer
  require_once "resources/footer.php";
?>
