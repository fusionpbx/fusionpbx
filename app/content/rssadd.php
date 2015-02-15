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
if (permission_exists('content_add')) {
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

	//get the http values and set them as variables
		$rss_sub_category = check_str($_POST["rss_sub_category"]);
		$rss_title = check_str($_POST["rss_title"]);
		$rss_link = check_str($_POST["rss_link"]);
		$rss_description = check_str($_POST["rss_description"]);
		$rss_img = check_str($_POST["rss_img"]);
		$rss_optional_1 = check_str($_POST["rss_optional_1"]);
		$rss_optional_2 = check_str($_POST["rss_optional_2"]);
		$rss_optional_3 = check_str($_POST["rss_optional_3"]);
		$rss_optional_4 = check_str($_POST["rss_optional_4"]);
		$rss_optional_5 = check_str($_POST["rss_optional_5"]);
		$rss_group = check_str($_POST["rss_group"]);
		$rss_order = check_str($_POST["rss_order"]);

	//insert the data into the database
		$rss_uuid = uuid();
		$sql = "insert into v_rss ";
		$sql .= "(";
		$sql .= "domain_uuid, ";
		$sql .= "rss_uuid, ";
		$sql .= "rss_category, ";
		$sql .= "rss_sub_category, ";
		$sql .= "rss_title, ";
		$sql .= "rss_link, ";
		$sql .= "rss_description, ";
		$sql .= "rss_img, ";
		$sql .= "rss_optional_1, ";
		$sql .= "rss_optional_2, ";
		$sql .= "rss_optional_3, ";
		$sql .= "rss_optional_4, ";
		$sql .= "rss_optional_5, ";
		$sql .= "rss_group, ";
		$sql .= "rss_order, ";
		$sql .= "rss_add_date, ";
		$sql .= "rss_add_user ";
		$sql .= ")";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'$domain_uuid', ";
		$sql .= "'$rss_uuid', ";
		$sql .= "'$rss_category', ";
		$sql .= "'$rss_sub_category', ";
		$sql .= "'$rss_title', ";
		$sql .= "'$rss_link', ";
		$sql .= "'$rss_description', ";
		$sql .= "'$rss_img', ";
		$sql .= "'$rss_optional_1', ";
		$sql .= "'$rss_optional_2', ";
		$sql .= "'$rss_optional_3', ";
		$sql .= "'$rss_optional_4', ";
		$sql .= "'$rss_optional_5', ";
		$sql .= "'$rss_group', ";
		$sql .= "'$rss_order', ";
		$sql .= "now(), ";
		$sql .= "'".$_SESSION["username"]."' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);

	$_SESSION["message"] = $text['message-add'];
	header("Location: rsslist.php");
	return;
}

	require_once "resources/header.php";
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/tiny_mce')) {
		if ($rss_optional_1 == "text/html") {
			require_once "resources/wysiwyg.php";
		}
	}
	else {
		//--- Begin: Edit Area -----------------------------------------------------
			echo "    <script language=\"javascript\" type=\"text/javascript\" src=\"".PROJECT_PATH."/resources/edit_area/edit_area_full.js\"></script>\n";
			echo "    <!-- -->\n";

			echo "	<script language=\"Javascript\" type=\"text/javascript\">\n";
			echo "		editAreaLoader.init({\n";
			echo "			id: \"rss_description\" // id of the textarea to transform //, |, help\n";
			echo "			,start_highlight: true\n";
			echo "			,font_size: \"8\"\n";
			echo "			,allow_toggle: false\n";
			echo "			,language: \"en\"\n";
			echo "			,syntax: \"html\"\n";
			echo "			,toolbar: \"search, go_to_line,|, fullscreen, |, undo, redo, |, select_font, |, syntax_selection, |, change_smooth_selection, highlight, reset_highlight, |, help\" //new_document,\n";
			echo "			,plugins: \"charmap\"\n";
			echo "			,charmap_default: \"arrows\"\n";
			echo "    });\n";
			echo "    </script>";
		//--- End: Edit Area -------------------------------------------------------
	}

	echo "<form method='post' action=''>";
	echo "<table width='100%' cellpadding='0' cellspacing='0'>";

	echo "<tr>\n";
	echo "<td width='30%' nowrap valign='top'><b>Content Add</b></td>\n";
	echo "<td width='70%' align='right' valign='top'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='rsslist.php'\" value='".$text['button-back']."'><br /><br /></td>\n";
	echo "</tr>\n";
	//echo "	<tr>";
	//echo "		<td class='vncellreq'>Category</td>";
	//echo "		<td class='vtable'><input type='text' class='formfld' name='rss_category' value='$rss_category'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td class='vncellreq' nowrap>Sub Category</td>";
	//echo "		<td class='vtable'><input type='text' class='formfld' name='rss_sub_category' value='$rss_sub_category'></td>";
	//echo "	</tr>";
	echo "	<tr>";
	echo "		<td width='30%' class='vncellreq' nowrap>Title</td>";
	echo "		<td width='70%' class='vtable' width='100%'><input type='text' class='formfld' name='rss_title' value='$rss_title'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncellreq'>Link</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='rss_link' value='$rss_link'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncellreq'>Group</td>";
	echo "		<td class='vtable'>";
	//echo "            <input type='text' class='formfld' name='menuparentid' value='$menuparentid'>";

	//---- Begin Select List --------------------
	$sql = "SELECT * FROM v_groups ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "order by group_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();

	echo "<select name=\"rss_group\" class='formfld'>\n";
	echo "<option value=\"\">".$text['button-public']."</option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	//$count = count($result);
	foreach($result as $field) {
			if ($rss_group == $field[group_name]) {
				echo "<option value='".$field[group_name]."' selected>".$field[group_name]."</option>\n";
			}
			else {
				echo "<option value='".$field[group_name]."'>".$field[group_name]."</option>\n";
			}
	}

	echo "</select>";
	unset($sql, $result);
	//---- End Select List --------------------

	echo "        </td>";
	echo "	</tr>";

	/*
	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "		Template: \n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\">\n";
	echo "<select id='rss_sub_category' name='rss_sub_category' class='formfld' style=''>\n";
	echo "<option value=''></option>\n";
	$theme_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
	if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes')) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && $file != ".svn" && is_dir($theme_dir.'/'.$file)) {
				if ($file == $rss_sub_category) {
					echo "<option value='$file' selected='selected'>$file</option>\n";
				}
				else {
					echo "<option value='$file'>$file</option>\n";
				}
			}
		}
		closedir($handle);
	}
	echo "	</select>\n";
	echo "	<br />\n";
	echo "	Select a template to set as the default and then press save.<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	*/

	echo "	<tr>";
	echo "		<td class='vncellreq'>Type</td>";
	echo "		<td class='vtable'>";
	echo "            <select name=\"rss_optional_1\" class='formfld'>\n";
	if ($rss_optional_1 == "text/html") { echo "<option value=\"text/html\" selected>text/html</option>\n"; }
	else { echo "<option value=\"text/html\">text/html</option>\n"; }

	if ($rss_optional_1 == "text/javascript") { echo "<option value=\"text/javascript\" selected>text/javascript</option>\n"; }
	else { echo "<option value=\"text/javascript\">text/javascript</option>\n"; }
	echo "            </select>";
	echo "        </td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "              <select name='rss_order' class='formfld'>\n";
	if (strlen(htmlspecialchars($rss_order))> 0) {
		echo "              <option selected='yes' value='".htmlspecialchars($rss_order)."'>".htmlspecialchars($rss_order)."</option>\n";
	}
	$i=0;
	while($i<=999) {
		if (strlen($i) == 1) {
			echo "              <option value='00$i'>00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "              <option value='0$i'>0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "              <option value='$i'>$i</option>\n";
		}
		$i++;
	}
	echo "              </select>\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	//echo "		<td  class='vncellreq' valign='top'></td>";
	echo "		<td  class='' colspan='2' align='left'>";
	echo "            <strong>".$text['label-content'].":</strong> ";
	if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/tiny_mce')) {
		echo "            &nbsp; &nbsp; &nbsp; editor &nbsp; <a href='#' title='toggle' onclick=\"toogleEditorMode('rss_description'); return false;\">".$text['label-on-off']."</a><br>";
	}
	else {
		echo "            <textarea name='rss_description'  id='rss_description' class='formfld' cols='20' style='width: 100%' rows='12' ></textarea>";
	}
	echo "        </td>";
	echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td class='vncellreq'>Image</td>";
	//echo "		<td class='vtable'><input type='text' name='rss_img' value='$rss_img'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td class='vncellreq'>Priority</td>";
	//echo "		<td class='vtable'>";
	//echo "            <input type='text' name='rss_optional_1' value='$rss_optional_1'>";
	//echo "            <select name=\"rss_optional_1\" class='formfld'>\n";
	//echo "            <option value=\"$rss_optional_1\">$rss_optional_1</option>\n";
	//echo "            <option value=\"\"></option>\n";
	//echo "            <option value=\"low\">low</option>\n";
	//echo "            <option value=\"med\">med</option>\n";
	//echo "            <option value=\"high\">high</option>\n";
	//echo "            </select>";
	//echo "        </td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td class='vncellreq'>Status</td>";
	//echo "		<td class='vtable'>";
	//echo "            <input type='text' name='rss_optional_2' value='$rss_optional_2'>";
	//echo "            <select name=\"rss_optional_2\" class=\"formfld\">\n";
	//echo "            <option value=\"$rss_optional_2\">$rss_optional_2</option>\n";
	//echo "            <option value=\"\"></option>\n";
	//echo "            <option value=\"0\">0</option>\n";
	//echo "            <option value=\"10\">10</option>\n";
	//echo "            <option value=\"20\">20</option>\n";
	//echo "            <option value=\"30\">30</option>\n";
	//echo "            <option value=\"40\">40</option>\n";
	//echo "            <option value=\"50\">50</option>\n";
	//echo "            <option value=\"60\">60</option>\n";
	//echo "            <option value=\"70\">70</option>\n";
	//echo "            <option value=\"80\">80</option>\n";
	//echo "            <option value=\"90\">90</option>\n";
	//echo "            <option value=\"100\">100</option>\n";
	//echo "            </select>";
	//echo "        </td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td class='vncellreq'>Optional 3</td>";
	//echo "		<td class='vtable'><input type='text' class='formfld' name='rss_optional_3' value='$rss_optional_3'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td class='vncellreq'>Optional 4</td>";
	//echo "		<td class='vtable'><input type='text' class='formfld' name='rss_optional_4' value='$rss_optional_4'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td class='vncellreq'>rss_optional_5</td>";
	//echo "		<td class='vtable'><input type='text' class='formfld' name='rss_optional_5' value='$rss_optional_5'></td>";
	//echo "	</tr>";
	//echo "	<tr>";
	//echo "		<td class='vncellreq'>rss_add_date</td>";
	//echo "		<td class='vtable'><input type='text' class='formfld' name='rss_add_date' value='$rss_add_date'></td>";
	//echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='' colspan='2' align='right'>";
	echo "			<br><br>";
	echo "          <input type='submit' class='btn' name='submit' value='".$text['button-add-title']." $module_title'>\n";
	echo "      </td>";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";


require_once "resources/footer.php";
?>
