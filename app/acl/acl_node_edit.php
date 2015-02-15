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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('acl_node_add') || permission_exists('acl_node_edit')) {
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

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$acl_node_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$node_type = check_str($_POST["node_type"]);
		$nod_cidr = check_str($_POST["nod_cidr"]);
		$nod_description = check_str($_POST["nod_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$acl_node_uuid = check_str($_POST["acl_node_uuid"]);
	}

	//check for all required data
		if (strlen($node_type) == 0) { $msg .= $text['message-required']." ".$text['label-node_type']."<br>\n"; }
		if (strlen($nod_cidr) == 0) { $msg .= $text['message-required']." ".$text['label-nod_cidr']."<br>\n"; }
		//if (strlen($nod_description) == 0) { $msg .= $text['message-required']." ".$text['label-nod_description']."<br>\n"; }
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

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('acl_node_add')) {
				$sql = "insert into v_acl_nodes ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "acl_node_uuid, ";
				$sql .= "node_type, ";
				$sql .= "nod_cidr, ";
				$sql .= "nod_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$node_type', ";
				$sql .= "'$nod_cidr', ";
				$sql .= "'$nod_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION['message'] = $text['message-add'];
				header('Location: acl_nodes.php');
				return;

			} //if ($action == "add")

			if ($action == "update" && permission_exists('acl_node_edit')) {
				$sql = "update v_acl_nodes set ";
				$sql .= "node_type = '$node_type', ";
				$sql .= "nod_cidr = '$nod_cidr', ";
				$sql .= "nod_description = '$nod_description' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and acl_node_uuid = '$acl_node_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION['message'] = $text['message-update'];
				header('Location: acl_nodes.php');
				return;

			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$acl_node_uuid = check_str($_GET["id"]);
		$sql = "select * from v_acl_nodes ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and acl_node_uuid = '$acl_node_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$node_type = $row["node_type"];
			$nod_cidr = $row["nod_cidr"];
			$nod_description = $row["nod_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-acl_node']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='acl_nodes.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-node_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='node_type'>\n";
	echo "	<option value=''></option>\n";
	if ($node_type == "allow") {
		echo "	<option value='allow' selected='selected'>".$text['label-allow']."</option>\n";
	}
	else {
		echo "	<option value='allow'>".$text['label-allow']."</option>\n";
	}
	if ($node_type == "deny") {
		echo "	<option value='deny' selected='selected'>".$text['label-deny']."</option>\n";
	}
	else {
		echo "	<option value='deny'>".$text['label-deny']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-node_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-nod_cidr']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='nod_cidr' maxlength='255' value=\"$nod_cidr\">\n";
	echo "<br />\n";
	echo $text['description-nod_cidr']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-nod_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='nod_description' maxlength='255' value=\"$nod_description\">\n";
	echo "<br />\n";
	echo $text['description-nod_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='acl_node_uuid' value='$acl_node_uuid'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>