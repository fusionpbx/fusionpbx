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
if (permission_exists('script_editor_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the directory
	$_SESSION["app"]["edit"]["dir"] = $_GET["dir"];

echo "<html>\n";
echo "<head>\n";
echo "	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n";
echo "	<title></title>";

	echo "<script type=\"text/javascript\" language=\"javascript\">\n";
	echo "// Replaces all instances of the given substring.\n";
	echo "String.prototype.replaceall = function(\n";
	echo "strTarget, \n"; // The substring you want to replace
	echo "strSubString \n"; // The string you want to replace in
	echo ")\n";
	echo "{\n";
	echo "  var strText = this;\n";
	echo "  var intIndexOfMatch = strText.indexOf( strTarget );\n";
	echo "  \n";
	echo "  // Keep looping while an instance of the target string\n";
	echo "  // still exists in the string.\n";
	echo "  while (intIndexOfMatch != -1){\n";
	echo "  // Relace out the current instance.\n";
	echo "  strText = strText.replace( strTarget, strSubString )\n";
	echo "  \n";
	echo "  // Get the index of any next matching substring.\n";
	echo "  intIndexOfMatch = strText.indexOf( strTarget );\n";
	echo "}\n";
	echo "return( strText );\n";
	echo "}\n";

	echo "function urlencode(str) {\n";
	echo "  str=escape(str); \n"; //Escape does not encode '/' and '+' character
	echo "  str=str.replaceall(\"+\", \"%2B\");\n";
	echo "  str=str.replaceall(\"/\", \"%2F\");\n";
	echo "  return str;\n";
	echo "}";
	echo "</script>\n";

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
	?>
	<script language="Javascript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/edit_area/edit_area_full.js"></script>
	<script language="Javascript" type="text/javascript">
		// initialisation
		editAreaLoader.init({
			id: "edit1" // id of the textarea to transform
			,start_highlight: false
			,allow_toggle: false
			,word_wrap: false
			,language: "en"
			,syntax: "xml"
			,toolbar: "save, |, search, go_to_line,|, fullscreen, |, undo, redo, |, select_font, |, syntax_selection, |, change_smooth_selection, highlight, reset_highlight, word_wrap, |, help"
			,syntax_selection_allow: "css,html,js,php,xml,c,cpp,sql"
			,show_line_colors: true
			,load_callback: "my_load"
			,save_callback: "my_save"
		});

		// callback functions
		function my_save(id, content){
			makeRequest('filesave.php','file='+document.getElementById('file').value+'&content='+urlencode(content));
			parent.document.title=''+unescape(document.getElementById('file').value)+' :: Saved';
		}

		function my_load(elem){
			elem.value="The content is loaded from the load_callback function into EditArea";
		}

		function my_setSelectionRange(id){
			editAreaLoader.setSelectionRange(id, 0, 0);
		}

		function test_setSelectionRange(id){
			editAreaLoader.setSelectionRange(id, 0, 0);
		}

		function test_getSelectionRange(id){
			var sel =editAreaLoader.getSelectionRange(id);
			alert("start: "+sel["start"]+"\nend: "+sel["end"]);
		}

		function test_setSelectedText(id){
			text= "[REPLACED SELECTION]";
			editAreaLoader.setSelectedText(id, text);
		}

		function test_getSelectedText(id){
			alert(editAreaLoader.getSelectedText(id));
		}
  	</script>
</head>
<table border='0' style="height: 100%; width: 100%;">
	<tr>
		<td id='toolbar' valign='top' width='200' style="width: 200;" height='100%' nowrap>
			<IFRAME SRC='filelist.php' style='border: solid 1px #CCCCCC; height: 50%; width: 100%;' TITLE=''>
			<!-- File List: Requires IFRAME support -->
			</IFRAME>
			<IFRAME SRC='cliplist.php' style='border: solid 1px #CCCCCC; height: 50%; width: 100%;' TITLE=''>
			<!-- Clip List: Requires IFRAME support -->
			</IFRAME>
		</td>
		<td valign='top' width="100%" height='100%' style="height: 100%;">
			<?php
				if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
					//IE doesn't work with the 100% width with the IFRAME
					echo "<textarea id='edit1' style='height: 100%; width: 800px;' name=''>\n";
					echo "</textarea>\n";
				}
				else {
					echo "<textarea id='edit1' style='height: 100%; width: 100%;' name=''>\n";
					echo "</textarea>\n";
				}
			?>
			<input type='hidden' id='file' name='file' value='' />
		</td>
	</tr>
</table>
</body>
</html>