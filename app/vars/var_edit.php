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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('var_add') || permission_exists('var_edit')) {
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
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$var_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set http values as php variables
	if (count($_POST) > 0) {
		$var_category = trim($_POST["var_category"]);
		$var_name = trim($_POST["var_name"]);
		$var_value = trim($_POST["var_value"]);
		$var_command = trim($_POST["var_command"]);
		$var_hostname = trim($_POST["var_hostname"]);
		$var_enabled = trim($_POST["var_enabled"] ?: 'false');
		$var_order = trim($_POST["var_order"]);
		$var_description = trim($_POST["var_description"]);
		$var_description = str_replace("''", "'", $var_description);

		if (strlen($_POST["var_category_other"]) > 0) {
			$var_category = trim($_POST["var_category_other"]);
		}
	}

//process the post
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid
			if ($action == "update") {
				$var_uuid = $_POST["var_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: vars.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (strlen($var_category) == 0) { $msg .= $text['message-required'].$text['label-category']."<br>\n"; }
			if (strlen($var_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
			//if (strlen($var_value) == 0) { $msg .= $text['message-required'].$text['label-value']."<br>\n"; }
			//if (strlen($var_command) == 0) { $msg .= $text['message-required'].$text['label-command']."<br>\n"; }
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
					//begin insert array
						$var_uuid = uuid();
						$array['vars'][0]['var_uuid'] = $var_uuid;
					//set message
						message::add($text['message-add']);
				}

				if ($action == "update" && permission_exists('var_edit')) {
					//begin update array
						$array['vars'][0]['var_uuid'] = $var_uuid;
					//set message
						message::add($text['message-update']);
				}

				if (is_array($array) && @sizeof($array) != 0) {
					//add common fields to array
						$array['vars'][0]['var_category'] = $var_category;
						$array['vars'][0]['var_name'] = $var_name;
						$array['vars'][0]['var_value'] = $var_value;
						$array['vars'][0]['var_command'] = $var_command;
						$array['vars'][0]['var_hostname'] = $var_hostname != '' ? $var_hostname : null;
						$array['vars'][0]['var_enabled'] = $var_enabled;
						$array['vars'][0]['var_order'] = $var_order;
						$array['vars'][0]['var_description'] = base64_encode($var_description);

					//execute insert/update
						$database = new database;
						$database->app_name = 'vars';
						$database->app_uuid = '54e08402-c1b8-0a9d-a30a-f569fc174dd8';
						$database->save($array);
						unset($array);

					//unset the user defined variables
						$_SESSION["user_defined_variables"] = "";

					//synchronize the configuration
						save_var_xml();

					//redirect
						header("Location: vars.php");
						exit;
				}
			}

	}

//pre-populate the form
	if (is_array($_GET) && is_uuid($_GET["id"]) && $_POST["persistformvar"] != "true") {
		$var_uuid = $_GET["id"];
		$sql = "select * from v_vars ";
		$sql .= "where var_uuid = :var_uuid ";
		$parameters['var_uuid'] = $var_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$var_category = $row["var_category"];
			$var_name = $row["var_name"];
			$var_value = $row["var_value"];
			$var_command = $row["var_command"];
			$var_hostname = $row["var_hostname"];
			$var_enabled = $row["var_enabled"];
			$var_order = $row["var_order"];
			$var_description = base64_decode($row["var_description"]);
		}
		unset($sql, $parameters);
	}

//set the defaults
	if (strlen($var_enabled) == 0) { $var_enabled = 'true'; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include header
	$document['title'] = $text['title-variable'];
	require_once "resources/header.php";

//show contents
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-variable']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'vars.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-category']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	$table_name = 'v_vars';
	$field_name = 'var_category';
	$sql_where_optional = "";
	$field_current_value = $var_category;
	echo html_select_other($table_name, $field_name, $sql_where_optional, $field_current_value);
	echo $text['description-category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='var_name' maxlength='255' value=\"".escape($var_name)."\">\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='var_value' maxlength='255' value=\"".escape($var_value)."\">\n";
	echo "<br />\n";
	echo $text['description-value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-command']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='var_command'>\n";
	if ($var_command == "set") {
		echo "    <option value='set' selected='selected'>".$text['option-set']."</option>\n";
	}
	else {
		echo "    <option value='set'>".$text['option-set']."</option>\n";
	}
	if ($var_command == "exec-set") {
		echo "    <option value='exec-set' selected='selected'>".$text['option-exec-set']."</option>\n";
	}
	else {
		echo "    <option value='exec-set'>".$text['option-exec-set']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-command']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-hostname']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='var_hostname' maxlength='255' value=\"".escape($var_hostname)."\">\n";
	echo "<br />\n";
	echo $text['description-hostname']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='var_enabled' name='var_enabled' value='true' ".($var_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='var_enabled' name='var_enabled'>\n";
		echo "		<option value='true' ".($var_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($var_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='var_order' class='formfld'>\n";
	$i = 0;
	while ($i <= 999) {
		$selected = ($var_order == $i) ? "selected='selected'" : null;
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
	echo $text['description-order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' name='var_description' rows='17'>".$var_description."</textarea>\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

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
		echo "	<tr><td width='200'>opus@48000h@10i</td><td>Opus 48khz using 10 ms ptime (mono and stereo)</td></tr>\n";
		echo "	<tr><td>opus@48000h@20i</td><td>Opus 48khz using 20 ms ptime (mono and stereo)</td></tr>\n";
		echo "	<tr><td>opus@48000h@40i</td><td>Opus 48khz using 40 ms ptime</td></tr>\n";
		echo "	<tr><td>opus@16000h@10i</td><td>Opus 16khz using 10 ms ptime (mono and stereo)</td></tr>\n";
		echo "	<tr><td>opus@16000h@20i</td><td>Opus 16khz using 20 ms ptime (mono and stereo)</td></tr>\n";
		echo "	<tr><td>opus@16000h@40i</td><td>Opus 16khz using 40 ms ptime</td></tr>\n";
		echo "	<tr><td>opus@8000h@10i</td><td>Opus 8khz using 10 ms ptime (mono and stereo)</td></tr>\n";
		echo "	<tr><td>opus@8000h@20i</td><td>Opus 8khz using 20 ms ptime (mono and stereo)</td></tr>\n";
		echo "	<tr><td>opus@8000h@40i</td><td>Opus 8khz using 40 ms ptime</td></tr>\n";
		echo "	<tr><td>opus@8000h@60i</td><td>Opus 8khz using 60 ms ptime</td></tr>\n";
		echo "	<tr><td>opus@8000h@80i</td><td>Opus 8khz using 80 ms ptime</td></tr>\n";
		echo "	<tr><td>opus@8000h@100i</td><td>Opus 8khz using 100 ms ptime</td></tr>\n";
		echo "	<tr><td>opus@8000h@120i</td><td>Opus 8khz using 120 ms ptime</td></tr>\n";
		echo "	<tr><td>iLBC@30i</td><td>iLBC using mode=30 which will win in all cases.</td></tr>\n";
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
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='var_uuid' value='".escape($var_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>";

//include header
	require_once "resources/footer.php";

?>
