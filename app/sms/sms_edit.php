<?php
/* $Id$ */
/*
	call.php
	Copyright (C) 2008, 2009 Mark J Crane
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>

*/

include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('sms_add') || permission_exists('sms_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$sms_uuid = check_str($_REQUEST["id"]);
		$sql = "select * from v_sms_destinations ";
		$sql .= "where sms_destination_uuid = '" . $_REQUEST["id"] . "' ";
		$sql .= "and domain_uuid = '" . $_SESSION['domain_uuid'] . "' LIMIT 1";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$sms_destinations = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($sms_destinations as $row) {
			$destination = check_str($row["destination"]);
			$carrier = check_str($row["carrier"]);
			$description = check_str($row["description"]);
			$enabled = check_str($row["enabled"]);
			$sms_destination_uuid = $row['sms_destination_uuid'];
			$chatplan_detail_data = $row['chatplan_detail_data'];
			$email = $row['email'];
		}
		unset ($prep_statement);
	}
	else {
		$action = "add";
	}

//get the http values and set them as php variables
	if (count($_POST) > 0 && $action != "update") {
		//get the values from the HTTP POST and save them as PHP variables
			$destination = str_replace(' ','-',check_str($_POST["destination"]));
			$carrier = check_str($_POST["carrier"]);
			$description = check_str($_POST["description"]);
			$enabled = check_str($_POST["enabled"]);
			$sms_destination_uuid = uuid();
			$chatplan_detail_data = check_str($_POST["chatplan_detail_data"]);
			$email = check_str($_POST["email"]);
		if ($action == "add") {
			$sql_insert = "insert into v_sms_destinations ";
			$sql_insert .= "(";
			$sql_insert .= "sms_destination_uuid, ";
			$sql_insert .= "carrier, ";
			$sql_insert .= "domain_uuid, ";
			$sql_insert .= "destination, ";
			$sql_insert .= "enabled, ";
			$sql_insert .= "description, ";
			$sql_insert .= "chatplan_detail_data, ";
			$sql_insert .= "email ";
			$sql_insert .= ")";
			$sql_insert .= "values ";
			$sql_insert .= "(";
			$sql_insert .= ":sms_destination_uuid, ";
			$sql_insert .= ":carrier, ";
			$sql_insert .= ":domain_uuid, ";
			$sql_insert .= ":destination, ";
			$sql_insert .= ":enabled, ";
			$sql_insert .= ":description, ";
			$sql_insert .= ":chatplan_detail_data, ";
			$sql_insert .= ":email ";
			$sql_insert .= ")";

			$prep_statement = $db->prepare(check_sql($sql_insert));
			$prep_statement->execute(array(':sms_destination_uuid' => $sms_destination_uuid, ':carrier' => $carrier,
				'domain_uuid' => $_SESSION['domain_uuid'], ':destination' => $destination, ':enabled' => $enabled,
				':description' => $description, ':chatplan_detail_data' => $chatplan_detail_data, ':email' => $email));
			$prep_statement->execute();
			unset ($prep_statement);

			header( 'Location: sms.php') ;

		}
	} elseif (count($_POST) > 0 && $action == "update") {
			$destination = str_replace(' ','-',check_str($_POST["destination"]));
			$carrier = check_str($_POST["carrier"]);
			$description = check_str($_POST["description"]);
			$enabled = check_str($_POST["enabled"]);
			$chatplan_detail_data = check_str($_POST["chatplan_detail_data"]);
			$email = check_str($_POST["email"]);


			$sql_insert = "update v_sms_destinations set";
			$sql_insert .= " ";
			$sql_insert .= "carrier = :carrier, ";
			$sql_insert .= "destination = :destination, ";
			$sql_insert .= "enabled = :enabled, ";
			$sql_insert .= "description = :description, ";
			$sql_insert .= "chatplan_detail_data = :chatplan_detail_data, ";
			$sql_insert .= "email = :email ";
			$sql_insert .= "where sms_destination_uuid = :sms_destination_uuid and domain_uuid = :domain_uuid";


			$prep_statement = $db->prepare(check_sql($sql_insert));
			$prep_statement->execute(array(':carrier' => $carrier, ':destination' => $destination, ':enabled' => $enabled,
				':description' => $description, ':chatplan_detail_data' => $chatplan_detail_data, ':email' => $email,
				':sms_destination_uuid' => $sms_destination_uuid, ':domain_uuid' => $_SESSION['domain_uuid']));

			$prep_statement->execute();

			error_log($sql_insert);
			unset ($prep_statement);
			header( 'Location: sms.php') ;
	}

//include the header
	require_once "resources/header.php";
	require_once "resources/paging.php";

	echo "<form method='post' name='frm' id='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpdding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-sms-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-sms-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='sms.php'\" value='".$text['button-back']."'>\n";
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "	<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='destination' autocomplete='off' maxlength='255' value=\"$destination\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-carrier']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (count($_SESSION['sms']['carriers']) > 0) {
		echo "<select name='carrier' class='formfld'>\n";
		echo "    <option value=''></option>\n";
		sort($_SESSION['sms']['carriers']);
		foreach ($_SESSION['sms']['carriers'] as &$row) {
			echo "	<option value='$row'";
			if ($row == $carrier) {
				echo " selected='selected'";
			}
			echo ">$row</option>\n";
		}
		echo "</select><br />\n";
	}
	echo $text['description-carrier']."\n";
	echo "</td>\n";
	echo "</tr>\n";



	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-chatplan_detail_data']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='chatplan_detail_data' autocomplete='off' maxlength='255' value=\"$chatplan_detail_data\" >\n";
	echo "<br />\n";
	echo $text['description-chatplan_detail_data']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-sms_email']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='email' autocomplete='off' maxlength='255' value=\"$email\" >\n";
	echo "<br />\n";
	echo $text['description-sms_email']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('sms_enabled')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-enabled']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='enabled'>\n";
		if ($enabled == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
		}
		if ($enabled == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea class='formfld' name='description' rows='4'>$description</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == "update") {
		echo "		<input type='hidden' name='sms_destination_uuid' value='".$sms_destination_uuid."'>\n";
		echo "		<input type='hidden' name='id' id='id' value='".$sms_destination_uuid."'>";
	}

	echo "</table>\n";
	echo "</form>\n";


	echo "<script>\n";
//capture enter key to submit form
	echo "	$(window).keypress(function(event){\n";
	echo "		if (event.which == 13) { submit_form(); }\n";
	echo "	});\n";
// convert password fields to
	echo "	function submit_form() {\n";
	echo "		$('input:password').css('visibility','hidden');\n";
	echo "		$('input:password').attr({type:'text'});\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//show the footer
	require_once "resources/footer.php";
?>