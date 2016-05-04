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
	Matthew Vale <github@mafoo.org>
*/
	//fetch the values
	require_once "core/install/resources/classes/detect_switch.php";
	$switch_detect = new detect_switch($event_host, $event_port, $event_password);
	//$switch_detect->event_port = 2021;
	$detect_ok = true;
	try {
		$switch_detect->detect();
	} catch(Exception $e){
		//echo "<p><b>Failed to detect configuration</b> detect_switch reported: " . $e->getMessage() ."</p>\n";
		//$detect_ok = false;
	}
	echo "<input type='hidden' name='install_language' value='".$_SESSION['domain']['language']['code']."'/>\n";
	echo "<input type='hidden' name='install_step' value='detect_config'/>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' align='left' nowrap><b>".$text['header-event_socket']."</b><br><br></td>\n";
	echo "<td width='70%' align='right'>";
	//echo "	<input type='button' name='detect' class='btn' onclick=\"location.reload();\" value='".$text['button-detect']."'/>\n";
	echo "	<input type='button' name='back' class='btn' onclick=\"history.go(-1);\" value='".$text['button-back']."'/>\n";
	echo "	<input type='submit' name='next' class='btn' value='".$text['button-next']."'/>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-event_host']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='event_host' maxlength='255' value=\"".$switch_detect->event_host."\" />\n";
	echo "<br />\n";
	echo $text['description-event_host']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-event_port']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='event_port' maxlength='255' value=\"".$switch_detect->event_port."\"/>\n";
	echo "<br />\n";
	echo $text['description-event_port']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-event_password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='password' name='event_password' maxlength='255' value=\"".$switch_detect->event_password."\"/>\n";
	echo "<br />\n";
	echo $text['description-event_password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<br>";
	echo "		</td>\n";
	echo "	</tr>";

	echo "</table>";
	if($detect_ok){
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

		echo "<tr>\n";
		echo "<td colspan='4' align='left' nowrap><b>".$text['title-detected_configuration']."</b></td>\n";
		echo "</tr>\n";

		$id = 1;
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='15%'>\n";
		echo "Switch version\n";
		echo "</td>\n";
		echo "<td class='vtable' width='35%' align='left'>\n";
		echo "    ".$switch_detect->version()."\n";
		echo "</td>\n";

		foreach ($switch_detect->get_dirs() as $folder)
		{
			if($id % 2 == 0){ echo "<tr>\n"; }
			echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='15%'>\n";
			echo $folder."\n";
			echo "</td>\n";
			echo "<td class='vtable' width='35%' align='left'>\n";
			echo "    ".$switch_detect->$folder()."\n";
			echo "</td>\n";
			if($id % 2 == 1){ echo "</tr>\n"; }
			$id++;
		}
		if($id % 2 == 1){ echo "</tr>\n"; }
		echo "<tr>\n";
		echo "<td colspan='4' align='left' nowrap><br/><b>".$text['title-assumed_configuration']."</b></td>\n";
		echo "</tr>\n";
		$id=0;
		foreach ($switch_detect->get_vdirs() as $folder) {
			if($id % 2 == 0){ echo "<tr>\n"; }
			echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='15%'>\n";
			echo $folder."\n";
			echo "</td>\n";
			echo "<td class='vtable' width='35%' align='left'>\n";
			echo "    ".$switch_detect->$folder()."\n";
			echo "</td>\n";
			if($id % 2 == 1){ echo "</tr>\n"; }
			$id++;
		}
		echo "</table>";
	}

?>