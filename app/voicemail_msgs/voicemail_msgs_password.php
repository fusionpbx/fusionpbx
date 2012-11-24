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
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('voicemail_edit')) {
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

//set the action as an add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$extension_uuid = check_str($_REQUEST["id"]);
	}

//deny the user if not assigned to this mailboxes
	$sql = "";
	$sql .= " select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and extension_uuid = '$extension_uuid'";
	if (!(if_group("admin") || if_group("superadmin"))) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$v_mailboxes = '';
	$x = 0;
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$x++;
	}
	unset ($prep_statement);
	if ($x == 0) {
		//user has not been assigned to this account
		echo "access denied";
		exit;
	}

//get the http post variables
	if (count($_POST)>0) {
		//$domain_uuid = check_str($_POST["domain_uuid"]);
		$extension = check_str($_POST["extension"]);
		$password = check_str($_POST["password"]);

		$mailbox = check_str($_POST["mailbox"]);
		$vm_password = check_str($_POST["vm_password"]);
		$accountcode = check_str($_POST["accountcode"]);
		$effective_caller_id_name = check_str($_POST["effective_caller_id_name"]);
		$effective_caller_id_number = check_str($_POST["effective_caller_id_number"]);
		$outbound_caller_id_name = check_str($_POST["outbound_caller_id_name"]);
		$outbound_caller_id_number = check_str($_POST["outbound_caller_id_number"]);
		$vm_enabled = check_str($_POST["vm_enabled"]);
		$vm_mailto = check_str($_POST["vm_mailto"]);
		$vm_attach_file = check_str($_POST["vm_attach_file"]);
		$vm_keep_local_after_email = check_str($_POST["vm_keep_local_after_email"]);
		$user_context = check_str($_POST["user_context"]);
		$call_group = check_str($_POST["call_group"]);
		$auth_acl = check_str($_POST["auth_acl"]);
		$cidr = check_str($_POST["cidr"]);
		$sip_force_contact = check_str($_POST["sip_force_contact"]);
		$enabled = check_str($_POST["enabled"]);
		$description = check_str($_POST["description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$extension_uuid = check_str($_POST["extension_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		//if (strlen($password) == 0) { $msg .= "Please provide: Password<br>\n"; }
		//if (strlen($mailbox) == 0) { $msg .= "Please provide: Mailbox<br>\n"; }
		if (strlen($vm_password) == 0) { $msg .= "".$text['confirm-password']."<br>\n"; }
		//if (strlen($accountcode) == 0) { $msg .= "Please provide: Account Code<br>\n"; }
		//if (strlen($effective_caller_id_name) == 0) { $msg .= "Please provide: Effective Caller ID Name<br>\n"; }
		//if (strlen($effective_caller_id_number) == 0) { $msg .= "Please provide: Effective Caller ID Number<br>\n"; }
		//if (strlen($outbound_caller_id_name) == 0) { $msg .= "Please provide: Outbound Caller ID Name<br>\n"; }
		//if (strlen($outbound_caller_id_number) == 0) { $msg .= "Please provide: Outbound Caller ID Number<br>\n"; }
		//if (strlen($vm_enabled) == 0) { $msg .= "Please provide: Voicemail Enabled<br>\n"; }
		//if (strlen($vm_mailto) == 0) { $msg .= "Please provide: Voicemail Mail To<br>\n"; }
		//if (strlen($vm_attach_file) == 0) { $msg .= "Please provide: Voicemail Attach File<br>\n"; }
		//if (strlen($vm_keep_local_after_email) == 0) { $msg .= "Please provide: VM Keep Local After Email<br>\n"; }
		//if (strlen($user_context) == 0) { $msg .= "Please provide: User Context<br>\n"; }
		//if (strlen($call_group) == 0) { $msg .= "Please provide: Call Group<br>\n"; }
		//if (strlen($auth_acl) == 0) { $msg .= "Please provide: Auth ACL<br>\n"; }
		//if (strlen($cidr) == 0) { $msg .= "Please provide: CIDR<br>\n"; }
		//if (strlen($sip_force_contact) == 0) { $msg .= "Please provide: SIP Force Contact<br>\n"; }
		//if (strlen($enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		}

	//add or update the database
	if ($_POST["persistformvar"] != "true") {

		if ($action == "update") {
			$sql = "update v_extensions set ";
			//$sql .= "extension = '$extension', ";
			//$sql .= "password = '$password', ";
			//$sql .= "mailbox = '$mailbox', ";
			$sql .= "vm_password = '#$vm_password', ";
			//$sql .= "accountcode = '$accountcode', ";
			//$sql .= "effective_caller_id_name = '$effective_caller_id_name', ";
			//$sql .= "effective_caller_id_number = '$effective_caller_id_number', ";
			//$sql .= "outbound_caller_id_name = '$outbound_caller_id_name', ";
			//$sql .= "outbound_caller_id_number = '$outbound_caller_id_number', ";
			$sql .= "vm_enabled = '$vm_enabled', ";
			$sql .= "vm_mailto = '$vm_mailto', ";
			$sql .= "vm_attach_file = '$vm_attach_file', ";
			$sql .= "vm_keep_local_after_email = '$vm_keep_local_after_email' ";
			//$sql .= "user_context = '$user_context', ";
			//$sql .= "call_group = '$call_group', ";
			//$sql .= "auth_acl = '$auth_acl', ";
			//$sql .= "cidr = '$cidr', ";
			//$sql .= "sip_force_contact = '$sip_force_contact', ";
			//$sql .= "enabled = '$enabled', ";
			//$sql .= "description = '$description' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and extension_uuid = '$extension_uuid'";
			$db->exec(check_sql($sql));
			unset($sql);

			//syncrhonize configuration
				save_extension_xml();

			//apply settings reminder
				$_SESSION["reload_xml"] = true;

			//redirect the user
				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=voicemail_msgs.php\">\n";
				echo "<div align='center'>\n";
				echo "".$text['confirm-update']."\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
	   } //if ($action == "update")
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$extension_uuid = $_GET["id"];
		$sql = "";
		$sql .= "select * from v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and extension_uuid = '$extension_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$extension = $row["extension"];
			$password = $row["password"];
			$mailbox = $row["mailbox"];
			$vm_password = $row["vm_password"];
			$vm_password = str_replace("#", "", $vm_password); //preserves leading zeros
			$accountcode = $row["accountcode"];
			$effective_caller_id_name = $row["effective_caller_id_name"];
			$effective_caller_id_number = $row["effective_caller_id_number"];
			$outbound_caller_id_name = $row["outbound_caller_id_name"];
			$outbound_caller_id_number = $row["outbound_caller_id_number"];
			$vm_enabled = $row["vm_enabled"];
			$vm_mailto = $row["vm_mailto"];
			$vm_attach_file = $row["vm_attach_file"];
			$vm_keep_local_after_email = $row["vm_keep_local_after_email"];
			$user_context = $row["user_context"];
			$call_group = $row["call_group"];
			$auth_acl = $row["auth_acl"];
			$cidr = $row["cidr"];
			$sip_force_contact = $row["sip_force_contact"];
			$enabled = $row["enabled"];
			$description = $row["description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "includes/header.php";

//show the content
	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "\n";
	echo "function enable_change(enable_over) {\n";
	echo "	var endis;\n";
	echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	echo "	document.iform.range_from.disabled = endis;\n";
	echo "	document.iform.range_to.disabled = endis;\n";
	echo "}\n";
	echo "\n";
	echo "function show_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"block\";\n";
	echo "}\n";
	echo "\n";
	echo "function hide_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"block\";\n";
	echo "}\n";
	echo "</script>";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class=''>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' nowrap valign='top' align='left'><b>".$text['label-voicemail']." $extension</b></td>\n";
	echo "	<td width='70%' align='right' valign='top'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='voicemail_msgs.php'\" value='".$text['button-back']."'><br /><br /></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-password'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='password' name='vm_password' id='password' onfocus=\"document.getElementById('show_password').innerHTML = 'Password: '+document.getElementById('password').value;\" autocomplete='off' maxlength='50' value=\"$vm_password\">\n";
	echo "<br />\n";
	echo "<span onclick=\"document.getElementById('show_password').innerHTML = ''\">".$text['description-password']." </span><span id='show_password'></span>\n";
	echo "</td>\n";
	echo "</tr>\n";

	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	//echo "    Effective Caller ID Name:\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "    <input class='formfld' type='text' name='effective_caller_id_name' maxlength='255' value=\"$effective_caller_id_name\">\n";
	//echo "<br />\n";
	//echo "Enter the effective caller id name here.\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	//echo "    Effective Caller ID Number:\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "    <input class='formfld' type='text' name='effective_caller_id_number' maxlength='255' value=\"$effective_caller_id_number\">\n";
	//echo "<br />\n";
	//echo "Enter the effective caller id number here.\n";
	//echo "</td>\n";
	//echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='vm_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($vm_enabled == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($vm_enabled == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo " ".$text['description-voicemail-enable']."\n";
	echo "</td>\n";
	echo "</tr>\n";	

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-mail'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='vm_mailto' maxlength='255' value=\"$vm_mailto\">\n";
	echo "<br />\n";
	echo "".$text['description-email']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-attach'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='vm_attach_file'>\n";
	echo "    <option value=''></option>\n";
	if ($vm_attach_file == "true") { 
		echo "    <option value='true' selected='selected' >true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($vm_attach_file == "false") { 
		echo "    <option value='false' selected='selected' >false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "".$text['description-email-attachment']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-vm'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='vm_keep_local_after_email'>\n";
	echo "    <option value=''></option>\n";
	if ($vm_keep_local_after_email == "true") { 
		echo "    <option value='true' selected='selected' >true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($vm_keep_local_after_email == "false") { 
		echo "    <option value='false' selected='selected' >false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "".$text['description-keep-local']." \n";
	echo "</td>\n";
	echo "</tr>\n";

	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	//echo "    Description:\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "    <textarea class='formfld' name='description' rows='4'>$description</textarea>\n";
	//echo "<br />\n";
	//echo "\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='extension_uuid' value='$extension_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "includes/footer.php";

?>
