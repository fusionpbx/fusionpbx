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
	if (permission_exists('edit_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add css and javascript
	require_once "header.php";

//define function recure_dir
	function recur_dir($dir) {
		clearstatcache();
		$htmldirlist = '';
		$htmlfilelist = '';
		$dirlist = opendir($dir);
		$dir_array = array();
		if($dirlist !== false) while (false !== ($file = readdir($dirlist))) {
			if ($file != "." AND $file != ".."){
				$newpath = $dir.'/'.$file;
				$level = explode('/',$newpath);
				if (
					substr(strtolower($newpath), -4) == ".svn" ||
					substr(strtolower($newpath), -4) == ".git" ||
					substr(strtolower($newpath), -3) == ".db" ||
					substr(strtolower($newpath), -4) == ".jpg" ||
					substr(strtolower($newpath), -4) == ".gif" ||
					substr(strtolower($newpath), -4) == ".png" ||
					substr(strtolower($newpath), -4) == ".ico" ||
					substr(strtolower($newpath), -4) == ".ttf"
					) {
					//ignore certain files (and folders)
				}
				else {
					$dir_array[] = $newpath;
				}
				if ($x > 1000) { break; };
				$x++;
			}
		}

		asort($dir_array);
		foreach ($dir_array as $newpath){
			$level = explode('/',$newpath);

			if (is_dir($newpath)) {
				$dirname = end($level);
				$htmldirlist .= "<div style='white-space: nowrap; padding-left: 16px;'>\n";
				$htmldirlist .= "<a onclick='Toggle(this);' style='display: block; cursor: pointer;'><img src='resources/images/icon_folder.png' border='0' align='absmiddle' style='margin: 1px 2px 3px 0px;'>".$dirname."</a>";
				$htmldirlist .= "<div style='display: none;'>".recur_dir($newpath)."</div>\n";
				$htmldirlist .= "</div>\n";
			}
			else {
				$filename = end($level);
				$filesize = round(filesize($newpath)/1024, 2);
				$newpath = str_replace ('//', '/', $newpath);
				$newpath = str_replace ("\\", "/", $newpath);
				$htmlfilelist .= "<div style='white-space: nowrap; padding-left: 16px;'>\n";
				$htmlfilelist .= "<a href='javascript:void(0);' onclick=\"parent.document.getElementById('filepath').value='".$newpath."'; parent.document.getElementById('current_file').value = '".$newpath."'; makeRequest('file_read.php','file=".urlencode($newpath)."');\" title='".$newpath." &#10; ".$filesize." KB'>";
				$htmlfilelist .= "<img src='resources/images/icon_file.png' border='0' align='absmiddle' style='margin: 1px 2px 3px -1px;'>".$filename."</a>\n";
				$htmlfilelist .= "</div>\n";
			}
		}

		closedir($dirlist);
		return $htmldirlist ."\n". $htmlfilelist;
	}

//get the directory
	if (!isset($_SESSION)) { session_start(); }
	switch ($_SESSION["app"]["edit"]["dir"]) {
		case 'scripts':
			$edit_directory = $_SESSION['switch']['scripts']['dir'];
			break;
		case 'php':
			$edit_directory = $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH;
			break;
		case 'grammar':
			$edit_directory = $_SESSION['switch']['grammar']['dir'];
			break;
		case 'provision':
			switch (PHP_OS) {
				case "Linux":
					if (file_exists('/etc/fusionpbx/resources/templates/provision')) {
						$edit_directory = '/etc/fusionpbx/resources/templates/provision';
					}
					else {
						$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					}
					break;
				case "FreeBSD":
					if (file_exists('/usr/local/etc/fusionpbx/resources/templates/provision')) {
						$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					}
					else {
						$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					}
					break;
				case "NetBSD":
					$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					break;
				case "OpenBSD":
					$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
					break;
				default:
					$edit_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
			}
			break;
		case 'xml':
			$edit_directory = $_SESSION['switch']['conf']['dir'];
			break;
	}
	if (!isset($edit_directory)) {
		foreach ($_SESSION['editor']['path'] as $path) {
			if ($_SESSION["app"]["edit"]["dir"] == $path) {
				$edit_directory = $path;
				break;
			}
		}
	}

//define ajax functions
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
echo "        if (http_request.overrideMimeType) {\n";
echo "              http_request.overrideMimeType('text/html');\n";
echo "        }\n";
echo "        http_request.open('POST', url, true);\n";
echo "\n";
echo "\n";
echo "        if (strpost.length == 0) {\n";
echo "            //http_request.send(null);\n";
echo "            http_request.send('name=value&foo=bar');\n";
echo "        }\n";
echo "        else {\n";
echo "            http_request.setRequestHeader('Content-Type','application/x-www-form-urlencoded');\n";
echo "            http_request.send(strpost);\n";
echo "        }\n";
echo "\n";
echo "    }\n";
echo "\n";
echo "    function returnContent(http_request) {\n";
echo "\n";
echo "        if (http_request.readyState == 4) {\n";
echo "            if (http_request.status == 200) {\n";
echo "			parent.document.getElementById('editor_source').value=http_request.responseText;";
echo "			parent.editor.getSession().setValue(parent.document.getElementById('editor_source').value);";
echo "			parent.editor.gotoLine(1);";
echo "			parent.editor.scrollToLine(1, true, true, function() {});";
echo "			parent.editor.focus();";
echo "\n";
echo "            }\n";
echo "            else {\n";
echo "                alert('".$text['message-problem']."');\n";
echo "            }\n";
echo "        }\n";
echo "\n";
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

// keyboard shortcut bindings
echo "<script language='JavaScript' type='text/javascript' src='".PROJECT_PATH."/resources/jquery/jquery-3.4.1.min.js'></script>\n";
echo "<script src='https://code.jquery.com/jquery-migrate-3.1.0.js'></script>\n";

//save file
key_press('ctrl+s', 'down', 'window', null, null, "parent.$('form#frm_edit').submit(); return false;", true);

//open file manager/clip library pane
key_press('ctrl+q', 'down', 'window', null, null, 'parent.toggle_sidebar(); parent.focus_editor(); return false;', true);

//prevent backspace (browser history back)
key_press('backspace', 'down', 'window', null, null, 'return false;', true);

echo "</head>\n";
echo "<body style='margin: 0px; padding: 5px;'>\n";

echo "<div style='text-align: left; padding-top: 3px; padding-bottom: 3px;'><a href='javascript:void(0);' onclick=\"window.open('file_options.php','filewin','left=20,top=20,width=310,height=350,toolbar=0,resizable=0');\" style='text-decoration:none;' title='".$text['label-files']."'><img src='resources/images/icon_gear.png' border='0' align='absmiddle' style='margin: 0px 2px 4px -1px;'>".$text['label-files']."</a></div>\n";
echo "<div style='text-align: left; margin-left: -16px;'>\n";
if (file_exists($edit_directory)) {
	echo recur_dir($edit_directory);
}
echo "</div>\n";

require_once "footer.php";

unset ($result_count);
unset ($result);
unset ($key);
unset ($val);
unset ($c);

?>
