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
if (permission_exists('script_editor_save')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (count($_POST)>0) {
	$clip_uuid = $_POST["id"];
	$clip_name = $_POST["clip_name"];
	$clip_folder = $_POST["clip_folder"];
	$clip_text_start = $_POST["clip_text_start"];
	$clip_text_end = $_POST["clip_text_end"];
	$clip_desc = $_POST["clip_desc"];
	$clip_order = $_POST["clip_order"];

	//no slashes
	$clip_name = str_replace('/', '|', $clip_name);
	$clip_name = str_replace('\\', '|', $clip_name);

	//sql update
	$array['clips'][0]['clip_uuid'] = $clip_uuid;
	$array['clips'][0]['clip_name'] = $clip_name;
	$array['clips'][0]['clip_folder'] = $clip_folder;
	$array['clips'][0]['clip_text_start'] = $clip_text_start;
	$array['clips'][0]['clip_text_end'] = $clip_text_end;
	$array['clips'][0]['clip_desc'] = $clip_desc;
	$array['clips'][0]['clip_order'] = $clip_order;

	$p = new permissions;
	$p->add('clip_edit', 'temp');

	$database = new database;
	$database->app_name = 'edit';
	$database->app_uuid = '17e628ee-ccfa-49c0-29ca-9894a0384b9b';
	$database->save($array);
	unset($array);

	$p->add('clip_edit', 'temp');

	//redirect the browser
	require_once "header.php";
	echo "<meta http-equiv=\"refresh\" content=\"1;url=clipoptions.php\">\n";
	echo $text['message-update'];
	require_once "footer.php";
	exit;
}
else {
	//get the uuid from http values
		$clip_uuid = $_GET["id"];

	//get the clip
		$sql = "select * from v_clips ";
		$sql .= "where clip_uuid = :clip_uuid ";
		$parameters['clip_uuid'] = $clip_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$clip_name = $row["clip_name"];
			$clip_folder = $row["clip_folder"];
			$clip_text_start = $row["clip_text_start"];
			$clip_text_end = $row["clip_text_end"];
			$clip_desc = $row["clip_desc"];
			$clip_order = $row["clip_order"];
		}
		unset($sql, $parameters, $row);
}

//show the content
	require_once "header.php";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr>\n";
	echo "	<td align=\"left\">\n";

	echo "<form method='post' action=''>";
	echo "<table border='0' width='100%'>";
	echo "	<tr>";
	echo "		<td>Name</td>";
	echo "		<td><input type='text' class='txt' name='clip_name' value='$clip_name'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td>Folder</td>";
	echo "		<td><input type='text' class='txt'  name='clip_folder' value='$clip_folder'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td colspan='2'>Before Selection<br>";
	echo "		  <textarea class='txt' style='resize: vertical;' name='clip_text_start'>$clip_text_start</textarea>";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td colspan='2'>After Selection<br>";
	echo "		  <textarea class='txt' style='resize: vertical;' name='clip_text_end'>$clip_text_end</textarea>";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td colspan='2'>Notes<br>";
	echo "		  <textarea class='txt' style='resize: vertical;' name='clip_desc'>$clip_desc</textarea>";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td align='left'><input type='button' value='".$text['button-back']."' onclick='history.back()'></td>";
	echo "		<td align='right'>";
	echo "			<input type='hidden' name='id' value='$clip_uuid'>";
	echo "			<input type='submit' name='submit' value='Update'>";
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";

	require_once "footer.php";
?>