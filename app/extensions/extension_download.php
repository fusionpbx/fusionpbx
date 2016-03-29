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
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";
if (if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

function array2csv(array &$array)
{
   if (count($array) == 0) {
     return null;
   }
   ob_start();
   $df = fopen("php://output", 'w');
   fputcsv($df, array_keys(reset($array)));
   foreach ($array as $row) {
      fputcsv($df, $row);
   }
   fclose($df);
   return ob_get_clean();
}

function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

if (isset($_REQUEST["column_group"])) {

	$columns = implode(",",$_REQUEST["column_group"]);
	$sql = "select " . $columns . " from v_extensions ";
	$sql .= " where domain_uuid = '".$domain_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$extensions = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	unset ($sql, $prep_statement);
//	print_r($extensions);

	download_send_headers("data_export_" . date("Y-m-d") . ".csv");
	echo array2csv($extensions);
	die();

}

$columns[] = 'extension_uuid';
$columns[] = 'domain_uuid';
$columns[] = 'extension';
$columns[] = 'number_alias';
$columns[] = 'password';
$columns[] = 'accountcode';
$columns[] = 'effective_caller_id_name';
$columns[] = 'effective_caller_id_number';
$columns[] = 'outbound_caller_id_name';
$columns[] = 'outbound_caller_id_number';
$columns[] = 'emergency_caller_id_name';
$columns[] = 'emergency_caller_id_number';
$columns[] = 'directory_full_name';
$columns[] = 'directory_visible';
$columns[] = 'directory_exten_visible';
$columns[] = 'limit_max';
$columns[] = 'limit_destination';
$columns[] = 'missed_call_app';
$columns[] = 'missed_call_data';
$columns[] = 'user_context';
$columns[] = 'toll_allow';
$columns[] = 'call_timeout';
$columns[] = 'call_group';
$columns[] = 'call_screen_enabled';
$columns[] = 'user_record';
$columns[] = 'hold_music';
$columns[] = 'auth_acl';
$columns[] = 'cidr';
$columns[] = 'sip_force_contact';
$columns[] = 'nibble_account';
$columns[] = 'sip_force_expires';
$columns[] = 'mwi_account';
$columns[] = 'sip_bypass_media';
$columns[] = 'unique_id';
$columns[] = 'dial_string';
$columns[] = 'dial_user';
$columns[] = 'dial_domain';
$columns[] = 'do_not_disturb';
$columns[] = 'forward_all_destination';
$columns[] = 'forward_all_enabled';
$columns[] = 'forward_busy_destination';
$columns[] = 'forward_busy_enabled';
$columns[] = 'forward_no_answer_destination';
$columns[] = 'forward_no_answer_enabled';
$columns[] = 'follow_me_uuid';
$columns[] = 'enabled';
$columns[] = 'description';
$columns[] = 'forward_caller_id_uuid';
$columns[] = 'absolute_codec_string';
$columns[] = 'forward_user_not_registered_destination';
$columns[] = 'forward_user_not_registered_enabled';

$c = 0;
$row_style["0"] = "row_style0";
$row_style["1"] = "row_style1";

//begin the page content
	require_once "resources/header.php";

	echo "<form method='post' name='frm' action='' autocomplete='off'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
		echo "<tr>\n";
		echo "<th><input type=\"checkbox\" id=\"selecctall\"/>";
		echo "</th>";
		echo "<th> Column Name";
		echo "</th>";
		echo "<th> Description";
		echo "</th>";
		echo "</tr>";
	echo "</tr>";
	foreach ($columns as $value) {
		echo "<tr>\n";
		echo "	<td width = '20px' valign='top' class='".$row_style[$c]."'><input class=\"checkbox1\" type=\"checkbox\" name=\"column_group[]\" value=\"$value\"/>";
		echo "</td>";
		echo "	<td valign='top' class='".$row_style[$c]."'> $value";
		echo "</td>";
		echo "	<td valign='top' class='".$row_style[$c]."'>";
		echo "</td>";
		echo "</tr>";
		if ($c==0) { $c=1; } else { $c=0; }
	}

	echo "	<tr>\n";
	echo "		<td colspan='3' align='right'>\n";
	echo "			<br>";
	echo "			<input type='submit' class='btn' value='".$text['button-export']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";
?>