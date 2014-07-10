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
if (permission_exists('var_add') || permission_exists('var_edit')) {
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

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$var_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//set http values as php variables
	if (count($_POST)>0) {
		$var_name = check_str($_POST["var_name"]);
		$var_value = check_str($_POST["var_value"]);
		$var_cat = check_str($_POST["var_cat"]);
		if (strlen($_POST["var_cat_other"]) > 0) {
			$var_cat = check_str($_POST["var_cat_other"]);
		}
		$var_enabled = check_str($_POST["var_enabled"]);
		$var_order = check_str($_POST["var_order"]);
		$var_description = $_POST["var_description"];
		$var_description = str_replace("''", "'", $var_description);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$var_uuid = check_str($_POST["var_uuid"]);
	}

	//check for all required data
		if (strlen($var_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		//if (strlen($var_value) == 0) { $msg .= $text['message-required'].$text['label-value']."<br>\n"; }
		//if (strlen($var_cat) == 0) { $msg .= $text['message-required'].$text['label-category']."<br>\n"; }
		if (strlen($var_enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
		if (strlen($var_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
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
			if ($action == "add" && permission_exists('var_add')) {
				$var_uuid = uuid();
				$sql = "insert into v_vars ";
				$sql .= "(";
				$sql .= "var_uuid, ";
				$sql .= "var_name, ";
				$sql .= "var_value, ";
				$sql .= "var_cat, ";
				$sql .= "var_enabled, ";
				$sql .= "var_order, ";
				$sql .= "var_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$var_uuid', ";
				$sql .= "'$var_name', ";
				$sql .= "'$var_value', ";
				$sql .= "'$var_cat', ";
				$sql .= "'$var_enabled', ";
				$sql .= "'$var_order', ";
				$sql .= "'".base64_encode($var_description)."' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				//unset the user defined variables
					$_SESSION["user_defined_variables"] = "";

				//synchronize the configuration
					save_var_xml();

				$_SESSION["message"] = $text['message-add'];
				header("Location: vars.php");
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('var_edit')) {
				//update the variables
					$sql = "update v_vars set ";
					$sql .= "var_name = '$var_name', ";
					$sql .= "var_value = '$var_value', ";
					$sql .= "var_cat = '$var_cat', ";
					$sql .= "var_enabled = '$var_enabled', ";
					$sql .= "var_order = '$var_order', ";
					$sql .= "var_description = '".base64_encode($var_description)."' ";
					$sql .= "where var_uuid = '$var_uuid' ";
					$db->exec(check_sql($sql));
					unset($sql);

				//unset the user defined variables
					$_SESSION["user_defined_variables"] = "";

				//synchronize the configuration
					save_var_xml();

				$_SESSION["message"] = $text['message-update'];
				header("Location: vars.php");
				return;
			} //if ($action == "update")
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$var_uuid = $_GET["id"];
		$sql = "select * from v_vars ";
		$sql .= "where var_uuid = '$var_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$var_name = $row["var_name"];
			$var_value = $row["var_value"];
			$var_cat = $row["var_cat"];
			$var_enabled = $row["var_enabled"];
			$var_order = $row["var_order"];
			$var_description = base64_decode($row["var_description"]);
		}
		unset ($prep_statement);
	}

//include header
	require_once "resources/header.php";
	if ($action == "add") {
		$document['title'] = $text['title-var_add'];
	}
	if ($action == "update") {
		$document['title'] = $text['title-var_edit'];
	}

//show contents
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($action == "add") {
		echo "<td width='30%' align='left'nowrap><b>".$text['header-variable_add']."</b><br><br></td>\n";
	}
	if ($action == "update") {
		echo "<td width='30%' align='left' nowrap><b>".$text['header-variable_edit']."</b><br><br></td>\n";
	}
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='vars.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='var_name' maxlength='255' value=\"$var_name\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-value'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='var_value' maxlength='255' value=\"$var_value\">\n";
	echo "<br />\n";
	echo $text['description-value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-category'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$table_name = 'v_vars';$field_name = 'var_cat';$sql_where_optional = "";$field_current_value = $var_cat;
	echo html_select_other($db, $table_name, $field_name, $sql_where_optional, $field_current_value);
	echo "<br />\n";
	echo $text['description-category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-enabled'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='var_enabled'>\n";
	if ($var_enabled == "true") {
		echo "    <option value='true' SELECTED >".$text['option-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['option-true']."</option>\n";
	}
	if ($var_enabled == "false") {
		echo "    <option value='false' SELECTED >".$text['option-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['option-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-order'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='var_order' class='formfld'>\n";
	$i=0;
	while($i<=999) {
		$selected = ($var_order == $i) ? "selected" : null;
		if (strlen($i) == 1) {
			echo "	<option value='00$i' ".$selected.">00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "	<option value='0$i' ".$selected.">0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "	<option value='$i' ".$selected.">$i</option>\n";
		}
		$i++;
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' name='var_description' rows='17'>$var_description</textarea>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='var_uuid' value='$var_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";

	//if variable is a code then show the codec info
	if ($var_name == "global_codec_prefs" || $var_name == "outbound_codec_prefs") {
		echo "<tr>\n";
		echo "<td align='left' colspan='2'>\n";
		echo "<br />\n";
		echo "<b>".$text['label-codec_information']."</b><br><br>\n";
		echo "Module must be compiled and loaded. &nbsp; &nbsp; codecname[@8000h|16000h|32000h[@XXi]]<br />\n";
		echo "<br />\n";
		echo "XX is the frame size must be multples allowed for the codec<br />\n";
		echo "10-120ms is supported on some codecs.<br />\n";
		echo "We do not support exceeding the MTU of the RTP packet.<br />\n";
		echo "<br />\n";

		echo "	<table>\n";
		echo "	<tr>\n";
		echo "	<tr><td width='200'>iLBC@30i</td><td>iLBC using mode=30 which will win in all cases.</td></tr>\n";
		echo "	<tr><td>DVI4@8000h@20i</td><td>IMA ADPCM 8kHz using 20ms ptime. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>DVI4@16000h@40i</td><td>IMA ADPCM 16kHz using 40ms ptime. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>speex@8000h@20i</td><td>Speex 8kHz using 20ms ptime.</td></tr>\n";
		echo "	<tr><td>speex@16000h@20i</td><td>Speex 16kHz using 20ms ptime.</td></tr>\n";
		echo "	<tr><td>speex@32000h@20i</td><td>Speex 32kHz using 20ms ptime.</td></tr>\n";
		echo "	<tr><td>G7221@16000h</td><td>G722.1 16kHz (aka Siren 7)</td></tr>\n";
		echo "	<tr><td>G7221@32000h</td><td>G722.1C 32kHz (aka Siren 14)</td></tr>\n";
		echo "	<tr><td>CELT@32000h</td><td>CELT 32kHz, only 10ms supported</td></tr>\n";
		echo "	<tr><td>CELT@48000h</td><td>CELT 48kHz, only 10ms supported</td></tr>\n";
		echo "	<tr><td>GSM@40i</td><td>GSM 8kHz using 40ms ptime. (GSM is done in multiples of 20, Default is 20ms)</td></tr>\n";
		echo "	<tr><td>G722</td><td>G722 16kHz using default 20ms ptime. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>PCMU</td><td>G711 8kHz ulaw using default 20ms ptime. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>PCMA</td><td>G711 8kHz alaw using default 20ms ptime. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>G726-16</td><td>G726 16kbit adpcm using default 20ms ptime. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>G726-24</td><td>G726 24kbit adpcm using default 20ms ptime. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>G726-32</td><td>G726 32kbit adpcm using default 20ms ptime. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>G726-40</td><td>G726 40kbit adpcm using default 20ms ptime. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>AAL2-G726-16</td><td>Same as G726-16 but using AAL2 packing. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>AAL2-G726-24</td><td>Same as G726-24 but using AAL2 packing. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>AAL2-G726-32</td><td>Same as G726-32 but using AAL2 packing. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>AAL2-G726-40</td><td>Same as G726-40 but using AAL2 packing. (multiples of 10)</td></tr>\n";
		echo "	<tr><td>LPC</td><td>LPC10 using 90ms ptime (only supports 90ms at this time)</td></tr>\n";
		echo "	<tr><td>L16</td><td>L16 isn't recommended for VoIP but you can do it. L16 can exceed the MTU rather quickly.</td></tr>\n";
		echo "	<tr><td colspan='2'><br /></td></tr>\n";

		echo "	<tr><td colspan='2'>These are the passthru audio codecs:</td></tr>\n";
		echo "	<tr><td>G729</td><td>G729 in passthru mode. (mod_g729)</td></tr>\n";
		echo "	<tr><td>G723</td><td>G723.1 in passthru mode. (mod_g723_1)</td></tr>\n";
		echo "	<tr><td>AMR</td><td>AMR in passthru mode. (mod_amr)</td></tr>\n";
		echo "	<tr><td colspan='2'><br /></td></tr>\n";

		echo "	<tr><td colspan='2'>These are the passthru video codecs: (mod_h26x)</td></tr>\n";
		echo "	<tr><td>H261</td><td>H.261 Video</td></tr>\n";
		echo "	<tr><td>H263</td><td>H.263 Video</td></tr>\n";
		echo "	<tr><td>H263-1998</td><td>H.263-1998 Video</td></tr>\n";
		echo "	<tr><td>H263-2000</td><td>H.263-2000 Video</td></tr>\n";
		echo "	<tr><td>H264</td><td>H.264 Video</td></tr>";
		echo "	</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "</td>";
		echo "</tr>";
	}

	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include header
	require_once "resources/footer.php";
?>