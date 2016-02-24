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
if (permission_exists('php_editor_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include
	require_once "header.php";

echo "<script type=\"text/javascript\" language=\"javascript\">\n";
echo "    function makeRequest(url, strpost) {\n";
echo "        var http_request = false;\n";
echo "\n";
echo "        if (window.XMLHttpRequest) { // Mozilla, Safari, ...\n";
echo "            http_request = new XMLHttpRequest();\n";
echo "            if (http_request.overrideMimeType) {\n";
echo "                http_request.overrideMimeType('text/xml');\n";
echo "                // See note below about this line\n";
echo "            }\n";
echo "        } else if (window.ActiveXObject) { // IE\n";
echo "            try {\n";
echo "                http_request = new ActiveXObject(\"Msxml2.XMLHTTP\");\n";
echo "            } catch (e) {\n";
echo "                try {\n";
echo "                    http_request = new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
echo "                } catch (e) {}\n";
echo "            }\n";
echo "        }\n";
echo "\n";
echo "        if (!http_request) {\n";
echo "            alert('".$text['message-give-up']."');\n";
echo "            return false;\n";
echo "        }\n";
echo "        http_request.onreadystatechange = function() { returnContent(http_request); };\n";
echo "        http_request.overrideMimeType('text/html');\n";
echo "        http_request.open('POST', url, true);\n";
echo "\n";
echo "        if (strpost.length == 0) {\n";
echo "            //http_request.send(null);\n";
echo "            http_request.send('name=value&foo=bar');\n";
echo "        }\n";
echo "        else {\n";
echo "            http_request.setRequestHeader('Content-Type','application/x-www-form-urlencoded');\n";
echo "            http_request.send(strpost);\n";
echo "        }\n";
echo "    }\n";
echo "\n";
echo "    function returnContent(http_request) {\n";
echo "\n";
echo "        if (http_request.readyState == 4) {\n";
echo "            if (http_request.status == 200) {\n";
echo "                  parent.editAreaLoader.setValue('edit1', http_request.responseText); \n";
echo "            }\n";
echo "            else {\n";
echo "                alert('".$text['message-problem']."');\n";
echo "            }\n";
echo "        }\n";
echo "    }\n";
echo "</script>";

echo "<SCRIPT LANGUAGE=\"JavaScript\">\n";
//echo "// ---------------------------------------------\n";
//echo "// --- http://www.codeproject.com/jscript/dhtml_treeview.asp\n";
//echo "// --- Name:    Easy DHTML Treeview           --\n";
//echo "// --- Author:  D.D. de Kerf                  --\n";
//echo "// --- Version: 0.2          Date: 13-6-2001  --\n";
//echo "// ---------------------------------------------\n";
echo "function Toggle(node) {\n";
echo "	// Unfold the branch if it isn't visible\n";
echo "	if (node.nextSibling.style.display == 'none') {\n";
echo "  	node.nextSibling.style.display = 'block';\n";
echo "	}\n";
echo "	// Collapse the branch if it IS visible\n";
echo "	else {\n";
echo "  	node.nextSibling.style.display = 'none';\n";
echo "	}\n";
echo "\n";
echo "}\n";
echo "</SCRIPT>";

echo "<table  width='100%' height='100%' border='0' cellpadding='0' cellspacing='2'>\n";

echo "<tr class='border'>\n";
echo "	<td align=\"left\" valign='top' nowrap>\n";
echo "      <table border=0 cellpadding='0' cellspacing='0'><tr><td style='cursor: default;'><img src='resources/images/icon_folder.png' border='0' align='absmiddle' style='margin: 0px 2px 4px 0px;'> ".$text['label-clip-library']."</a><div>\n";

$sql = "select * from v_clips ";
$sql .= "order by clip_folder ";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
$result_count = count($result);

if ($result_count > 0) { //no results
	$last_folder = '';
	$tag_open = '';
	$x = 0;
	$current_depth = 0;
	$previous_depth = 0;
	foreach($result as $row) {
		$current_depth = count(explode("/", $row[clip_folder]));
		if ($current_depth < $previous_depth) {
			$count = ($previous_depth - $current_depth);
			$i=0;
			while($i < $count){
				echo "</div></td></tr></table>\n";
				$i++;
			}
			echo "</div></td></tr></table>\n";

		}

		if ($last_folder != $row['clip_folder']) {
			$clip_folder_name = str_replace ($previous_folder_name, "", $row['clip_folder']);
			$clip_folder_name = str_replace ("/", "", $clip_folder_name);
			echo "<table border=0 cellpadding='0' cellspacing='0'>";
			echo "	<tr>";
			echo "		<td nowrap style='padding-left: 16px;'>";
			echo "			<a onclick='Toggle(this);'><img src='resources/images/icon_folder.png' align='absmiddle' style='margin: 1px 2px 3px 0px;'>".$clip_folder_name."</a><div style='display:none'>";
			$tag_open = 1;
		}

		$previous_depth = $current_depth;
		$previous_folder_name = $row['clip_folder'];

		echo "<textarea style='display:none' id='clip_lib_start".$row['clip_uuid']."'>".$row['clip_text_start']."</textarea>\n";
		echo "<textarea style='display:none' id='clip_lib_end".$row['clip_uuid']."'>".$row['clip_text_end']."</textarea>\n";
		echo "\n";
		echo "<table border=0 cellpadding='0' cellspacing='0'>";
		echo "	<tr>";
		echo "		<td nowrap align='bottom' style='padding-left: 16px;'>";
		echo "			<a href='javascript:void(0);' onclick=\"parent.document.getElementById('clip_uuid').value='".$row['clip_uuid']."'; parent.document.getElementById('clip_name').value='".$row['clip_name']."';\"><img src='resources/images/icon_file.png' border='0' align='absmiddle' style='margin: 1px 2px 3px -1px;'>".$row['clip_name']."</a>";
		echo "		</td>";
		echo "	</tr>";
		echo "</table>\n";

		$last_folder = $row['clip_folder'];
		if ($c==0) { $c=1; } else { $c=0; }
	} //end foreach
	unset($sql, $result, $row_count);
} //end if results

echo "			</div>";
echo "		</td>";
echo "	</tr>";
echo "</table>\n";

echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";

require_once "footer.php";

unset ($result_count);
unset ($result);
unset ($key);
unset ($val);
unset ($c);
?>