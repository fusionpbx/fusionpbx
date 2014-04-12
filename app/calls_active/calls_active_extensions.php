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
include "app_languages.php";
if (permission_exists('extension_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];                
	}

//http get and set variables
	$event_type = $_GET['event_type']; //open_window //iframe
	if ($event_type=="iframe") {
		$iframe_width = $_GET['iframe_width'];
		$iframe_height = $_GET['iframe_height'];
		$iframe_postition = $_GET['iframe_postition'];
		if (strlen($iframe_postition) > 0) { $iframe_postition = 'right'; }
		if (strlen($iframe_width) > 0) { $iframe_width = '25%'; }
		if (strlen($iframe_height) > 0) { $iframe_height = '100%'; }
	}
	if (strlen($_GET['url']) > 0) {
		$url = $_GET['url'];
	}
	if (strlen($_GET['rows']) > 0) {
		$rows = $_GET['rows'];
	}
	else {
		$rows = 0;
	}

$conference_name = trim($_REQUEST["c"]);
$tmp_conference_name = str_replace("_", " ", $conference_name);

require_once "resources/header.php";
?><script type="text/javascript">
<!--

//declare variables
	var previous_uuid_1 = '';
	var previous_uuid_2 = '';
	var url = '<?php echo $url; ?>';

//define the ajax function
	function loadXmlHttp(url, id) {
		var f = this;
		f.xmlHttp = null;
		/*@cc_on @*/ // used here and below, limits try/catch to those IE browsers that both benefit from and support it
		/*@if(@_jscript_version >= 5) // prevents errors in old browsers that barf on try/catch & problems in IE if Active X disabled
		try {f.ie = window.ActiveXObject}catch(e){f.ie = false;}
		@end @*/
		if (window.XMLHttpRequest&&!f.ie||/^http/.test(window.location.href))
			f.xmlHttp = new XMLHttpRequest(); // Firefox, Opera 8.0+, Safari, others, IE 7+ when live - this is the standard method
		else if (/(object)|(function)/.test(typeof createRequest))
			f.xmlHttp = createRequest(); // ICEBrowser, perhaps others
		else {
			f.xmlHttp = null;
			 // Internet Explorer 5 to 6, includes IE 7+ when local //
			/*@cc_on @*/
			/*@if(@_jscript_version >= 5)
			try{f.xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");}
			catch (e){try{f.xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");}catch(e){f.xmlHttp=null;}}
			@end @*/
		}
		if(f.xmlHttp != null){
			f.el = document.getElementById(id);
			f.xmlHttp.open("GET",url,true);
			f.xmlHttp.onreadystatechange = function(){f.stateChanged();};
			f.xmlHttp.send(null);
		}
	}

loadXmlHttp.prototype.stateChanged=function () {
if (this.xmlHttp.readyState == 4 && (this.xmlHttp.status == 200 || !/^http/.test(window.location.href)))

	document.getElementById('ajax_reponse').innerHTML = this.xmlHttp.responseText;

	if (document.getElementById('uuid_1')) {
		uuid_1 = document.getElementById('uuid_1').innerHTML;
	}
	else {
		uuid_1 = "";
	}

	if (document.getElementById('direction_1')) {
		direction_1 = document.getElementById('direction_1').innerHTML;
	}
	else {
		direction_1 = "";
	}

	if (document.getElementById('cid_name_1')) {
		cid_name_1 = document.getElementById('cid_name_1').innerHTML;
	}
	else {
		cid_name_1 = "";
	}

	if (document.getElementById('cid_num_1')) {
		cid_num_1 = document.getElementById('cid_num_1').innerHTML;
	}
	else {
		cid_num_1 = "";
	}

	//get the user_status from the database
		if (document.getElementById('db_user_status')) {
			db_user_status = document.getElementById('db_user_status').innerHTML;
		}

	if (previous_uuid_1 != uuid_1) {
		if (cid_num_1.length > 6) {
				var new_url = url;
				new_url = new_url.replace("{cid_name}", cid_name_1);
				new_url = new_url.replace("{cid_num}", cid_num_1);
				new_url = new_url.replace("{uuid}", uuid_1);
				previous_uuid_1 = uuid_1;
<?php
				if ($event_type=="open_window") {
					echo "open_window = window.open(new_url,'width='+window.innerWidth+',height='+window.innerHeight+',left=0px;toolbar=yes,location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,copyhistory=yes,resizable=yes');";
					echo "if (window.focus) {open_window.focus()}\n";
				}
				if ($event_type=="iframe") {
					echo "document.getElementById('iframe1').src = new_url;\n";
					//iframe_postition
					//iframe_width
					//iframe_height
				}
?>
		}
		else {
			//hangup or initial page load detected
		}
		previous_uuid_1 = uuid_1;
	}
}

var requestTime = function() {
	<?php
	echo "var url = 'calls_active_extensions_inc.php?". $_SERVER["QUERY_STRING"]."';\n";
	echo "new loadXmlHttp(url, 'ajax_reponse');\n";
	if (strlen($_SESSION["ajax_refresh_rate"]) == 0) { $_SESSION["ajax_refresh_rate"] = "900"; }
	echo "setInterval(function(){new loadXmlHttp(url, 'ajax_reponse');}, ".$_SESSION["ajax_refresh_rate"].");";
	?>
}

if (window.addEventListener) {
	window.addEventListener('load', requestTime, false);
}
else if (window.attachEvent) {
	window.attachEvent('onload', requestTime);
}

function send_cmd(url) {
	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}
	else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.open("GET",url,false);
	xmlhttp.send(null);
	document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;
}

var record_count = 0;
var cmd;
var destination;
// -->
</script>

<?php

echo "<div align='center'>";

echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
echo "	<tr>\n";
echo "	<td align='left' colspan='2' nowrap='nowrap'>\n";
echo "		<b>".$text['title-2']."</b><br>\n";
echo "	</td>\n";

//get the user status when the page loads
	$sql = "";
	$sql .= "select * from v_users ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and username = '".$_SESSION['username']."' ";
	$sql .= "and user_enabled = 'true' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$user_status = $row["user_status"];
		break; //limit to 1 row
	}

if ($_SESSION['user_status_display'] == "false") {
	//hide the user_status when it is set to false
}
else {
	echo "		<td class='' width='40%'>\n";
	echo "			&nbsp;";
	echo "		</td>\n";
	echo "		<td class='' valign='bottom' align='right' style='width:200px' nowrap='nowrap'>\n";
	//status list
	echo "			&nbsp;";
	echo "			<strong>".$text['label-status']."</strong>&nbsp;\n";
	$cmd = "'calls_exec.php?action=user_status&data='+this.value+'";
	$cmd .= "&cmd=callcenter_config+agent+set+status+".$_SESSION['username']."@".$_SESSION['domain_name']."+'+this.value";
	echo "			<select id='agent_status' name='agent_status' class='formfld' style='width:125px' nowrap='nowrap' onchange=\"send_cmd($cmd);\">\n";
	echo "				<option value='                '></option>\n";
	if ($user_status == "Available") {
		echo "		<option value='Available' selected='selected'>".$text['check-available-status']."</option>\n";
	}
	else {
		echo "		<option value='Available'>".$text['check-available-status']."</option>\n";
	}
	if ($user_status == "Available (On Demand)") {
		echo "		<option value='Available_On_Demand' selected='selected'>".$text['check-available-on-demand-status']."</option>\n";
	}
	else {
		echo "		<option value='Available_On_Demand'>".$text['check-available-on-demand-status']."</option>\n";
	}
	if ($user_status == "Logged Out") {
		echo "		<option value='Logged_Out' selected='selected'>".$text['check-loggedout-status']."</option>\n";
	}
	else {
		echo "		<option value='Logged_Out'>".$text['check-loggedout-status']."</option>\n";
	}
	if ($user_status == "On Break") {
		echo "		<option value='On_Break' selected='selected'>".$text['check-onbreak-status']."</option>\n";
	}
	else {
		echo "		<option value='On_Break'>".$text['check-onbreak-status']."</option>\n";
	}
	if ($user_status == "Do Not Disturb") {
		echo "		<option value='Do_Not_Disturb' selected='selected'>".$text['check-do-not-disturb-status']."</option>\n";
	}
	else {
		echo "		<option value='Do_Not_Disturb'>".$text['check-do-not-disturb-status']."</option>\n";
	}
	echo "			</select>\n";
	echo "		</td>\n";
}

echo "	<td align='right' nowrap='nowrap'>\n";
echo "			&nbsp;";
echo "			<strong>".$text['label-transfer']."</strong>\n";
echo "			<input type=\"text\" id=\"form_value\" name=\"form_value\" class='formfld' style='width:125px'/>\n";
echo "	</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td align='left' colspan='99'>\n";
echo "			".$text['description-2']."\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "</table>\n";

echo "<div id=\"url\"></div>\n";
echo "<br />\n";

echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
echo "	<tr class='border'>\n";
if ($event_type=="iframe") {
	echo "	<td align=\"left\" width='".$iframe_width."'>\n";
}
else {
	echo "	<td align=\"left\" width='100%'>\n";
}
echo "		<div id=\"ajax_reponse\"></div>\n";
echo "		<div id=\"time_stamp\" style=\"visibility:hidden\">".date('Y-m-d-s')."</div>\n";
echo "	</td>\n";

if ($event_type=="iframe") {
	echo "</td>\n";
	echo "<td width='".$iframe_width."' height='".$iframe_height."'>\n";
	echo "	<iframe src ='$url' width='100%' id='iframe1' height='100%' frameborder=0>\n";
	echo "		<p>Your browser does not support iframes.</p>\n";
	echo "	</iframe>\n";
	echo "</td>\n";
}

echo "	</tr>";
echo "</table>";
echo "</div>\n";

echo "<script type=\"text/javascript\">\n";
echo "<!--\n";
echo "function get_transfer_cmd(uuid) {\n";
echo "	destination = document.getElementById('form_value').value;\n";
echo "	if (destination.length > 1) { \n";
echo "		cmd = \"uuid_transfer \"+uuid+\" -bleg \"+destination+\" xml ".trim($_SESSION['user_context'])."\";\n";
echo "	}\n";
echo "	else {\n";
echo "		cmd = '';\n";
echo "		alert(\"Please provide a number to transfer the call to.\");\n";
echo "	}\n";
echo "	return escape(cmd);\n";
echo "}\n";
echo "\n";
echo "function get_park_cmd(uuid) {\n";
echo "	cmd = \"uuid_transfer \"+uuid+\" -bleg *6000 xml ".trim($_SESSION['user_context'])."\";\n";
echo "	return escape(cmd);\n";
echo "}\n";
echo "\n";
echo "function get_record_cmd(uuid, prefix, name) {\n";
echo "	cmd = \"uuid_record \"+uuid+\" start ".$_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d")."/\"+uuid+\".wav\";\n";
echo "	return escape(cmd);\n";
echo "}\n";
echo "-->\n";
echo "</script>\n";

require_once "resources/footer.php";
?>