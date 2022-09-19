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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('clip_edit')) {
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

//show the content
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

	echo "</head>\n";
	echo "<body style='margin: 0; padding: 5px;' onfocus='blur();'>\n";

	echo "<div style='text-align: left;'>\n";

	$sql = "select * from v_clips order by clip_folder asc, clip_name asc";
	$database = new database;
	$result = $database->select($sql, null, 'all');
	unset($sql);

	if (is_array($result) && @sizeof($result) != 0) {
		$master_array = array();
		foreach ($result as $row) {
			$clip_folder = rtrim($row['clip_folder'], '/');
			$clip_folder .= '/'.$row['clip_name'];

			$parts = explode('/', $clip_folder);
			$folders = array();
			while ($bottom = array_pop($parts)) {
				if (sizeof($folders) > 0) {
					$folders = array($bottom => $folders);
				}
				else {
					$clip['uuid'] = $row['clip_uuid'];
					$clip['name'] = $row['clip_name'];
					$clip['before'] = $row['clip_text_start'];
					$clip['after'] = $row['clip_text_end'];
					$folders = array($bottom => $clip);
				}
			}

			$master_array = array_merge_recursive($master_array, $folders);
		}

		function parse_array($arr) {
			if (is_array($arr)) {
				//folder/clip
				foreach ($arr as $name => $sub_arr) {
					if ($name != $sub_arr['name']) {
						//folder
						echo "<a onclick='Toggle(this);' style='display: block; cursor: pointer; text-decoration: none;'><img src='resources/images/icon_folder.png' border='none' align='absmiddle' style='margin: 1px 2px 3px 0px;'>".escape($name)."</a>";
						echo "<div style='display: none; padding-left: 16px;'>\n";
						parse_array($sub_arr);
						echo "</div>\n";
					}
					else {
						//clip
						echo "<div style='white-space: nowrap;'>\n";
						echo "<a href='javascript:void(0);' onclick=\"parent.document.getElementById('clip_uuid').value='".$sub_arr['uuid']."'; parent.document.getElementById('clip_name').value='".$sub_arr['name']."';\">";
						echo "<img src='resources/images/icon_file.png' border='0' align='absmiddle' style='margin: 1px 2px 3px -1px;'>";
						echo escape($sub_arr['name']);
						echO "</a>\n";
						echo "<textarea style='display: none' id='before_".$sub_arr['uuid']."'>".$sub_arr['before']."</textarea>\n";
						echo "<textarea style='display: none' id='after_".$sub_arr['uuid']."'>".$sub_arr['after']."</textarea>\n";
						echo "</div>\n";
					}
				}
			}
		}
		parse_array($master_array);
	}
	unset($result, $row);
	echo "</div>\n";

//include the footer
	require_once "footer.php";

?>
