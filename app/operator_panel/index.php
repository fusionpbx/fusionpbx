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

if (permission_exists('operator_panel_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	include "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

require_once "resources/header.php";
?>

<!-- virtual_drag function holding elements -->
<input type='hidden' class='formfld' id='vd_call_id' value=''>
<input type='hidden' class='formfld' id='vd_ext_from' value=''>
<input type='hidden' class='formfld' id='vd_ext_to' value=''>

<script type="text/javascript">
//ajax refresh
	var refresh = 1950;
	var source_url = 'index_inc.php?' <?php if (isset($_GET['debug'])) { echo " + '&debug'"; } ?>;
	var interval_timer_id;

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
		//this.el.innerHTML = this.xmlHttp.responseText;
		document.getElementById('ajax_reponse').innerHTML = this.xmlHttp.responseText;
	}

	var requestTime = function() {
		var url = source_url;
		url += '&vd_ext_from=' + document.getElementById('vd_ext_from').value;
		url += '&vd_ext_to=' + document.getElementById('vd_ext_to').value;
		url += '&group=' + ((document.getElementById('group')) ? document.getElementById('group').options[document.getElementById('group').selectedIndex].value : '');
		<?php
		if (isset($_GET['debug'])) {
			echo "url += '&debug';";
		}
		?>
		new loadXmlHttp(url, 'ajax_reponse');
		refresh_start();
	}

	if (window.addEventListener) {
		window.addEventListener('load', requestTime, false);
	}
	else if (window.attachEvent) {
		window.attachEvent('onload', requestTime);
	}


//drag/drop functionality
	function drag(ev, from_ext) {
		refresh_stop();
		ev.dataTransfer.setData("Call", ev.target.id);
		ev.dataTransfer.setData("From", from_ext);
		virtual_drag_reset();
	}

	function allowDrop(ev, target_id) {
		ev.preventDefault();
	}

	function discardDrop(ev, target_id) {
		ev.preventDefault();
	}

	function drop(ev, to_ext) {
		ev.preventDefault();

		var call_id = ev.dataTransfer.getData("Call");
		var from_ext = ev.dataTransfer.getData("From");
		var to_ext = to_ext;
		var cmd;

		if (call_id != '') {
			cmd = get_transfer_cmd(call_id, to_ext); //transfer a call
		}
		else {
			if (from_ext != to_ext) { // prevent user from dragging extention onto self
				cmd = get_originate_cmd(from_ext+'@<?=$_SESSION["domain_name"]?>', to_ext); //make a call
			}
		}

		if (cmd != '') { send_cmd('exec.php?cmd='+escape(cmd)); }

		refresh_start();
	}

//refresh controls
	function refresh_stop() {
		clearInterval(interval_timer_id);
	}

	function refresh_start() {
		interval_timer_id = setInterval( function() {
			url = source_url;
			url += '&vd_ext_from=' + document.getElementById('vd_ext_from').value;
			url += '&vd_ext_to=' + document.getElementById('vd_ext_to').value;
			url += '&group=' + ((document.getElementById('group')) ? document.getElementById('group').options[document.getElementById('group').selectedIndex].value : '');
			<?php
			if (isset($_GET['debug'])) {
				echo "url += '&debug';";
			}
			?>
			new loadXmlHttp(url, 'ajax_reponse');
		}, refresh);
	}

//call destination
	function call_destination(from_ext, destination) {
		if (destination != '') {
			cmd = get_originate_cmd(from_ext+'@<?=$_SESSION["domain_name"]?>', destination); //make a call
		}
		if (cmd != '') {
			send_cmd('exec.php?cmd='+escape(cmd));
		}
		refresh_start();
	}

//kill call
	function kill_call(call_id) {
		if (call_id != '') {
			cmd = 'uuid_kill ' + call_id;
			send_cmd('exec.php?cmd='+escape(cmd));
		}
	}

//used by call control and ajax refresh functions
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

<?php
//hide/show destination input field
	echo "function toggle_destination(ext) {\n";
	echo "	refresh_stop();\n";
	echo "	$('#destination_'+ext).fadeToggle(200, function(){\n";
	echo "		if ($('#destination_'+ext).is(':visible')) {\n";
	echo "			$('#destination_'+ext).focus();\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			$('#destination_'+ext).val('');\n";
	echo "			refresh_start();\n";
	echo "		}\n";
	echo "	});\n";
	echo "}\n";

	echo "function get_transfer_cmd(uuid, destination) {\n";
	echo "	cmd = \"uuid_transfer \"+uuid+\" \"+destination+\" XML ".trim($_SESSION['user_context'])."\";\n";
	echo "	return cmd;\n";
	echo "}\n";

	echo "function get_originate_cmd(source, destination) {\n";
	echo "	cmd = \"bgapi originate {sip_auto_answer=true,origination_caller_id_number=\"+destination+\",sip_h_Call-Info=_undef_}user/\"+source+\" \"+destination+\" XML ".trim($_SESSION['user_context'])."\";\n";
	echo "	return cmd;\n";
	echo "}\n";

	echo "function get_record_cmd(uuid) {\n";
	echo "	cmd = \"uuid_record \"+uuid+\" start ".$_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d")."/\"+uuid+\".wav\";\n";
	echo "	return cmd;\n";
	echo "}\n";
?>

//virtual functions
	function virtual_drag(call_id, ext) {
		if (document.getElementById('vd_ext_from').value != '' && document.getElementById('vd_ext_to').value != '') {
			virtual_drag_reset();
		}

		if (call_id != '') {
			document.getElementById('vd_call_id').value = call_id;
		}

		if (ext != '') {
			if (document.getElementById('vd_ext_from').value == '') {
				document.getElementById('vd_ext_from').value = ext;
				document.getElementById(ext).style.borderStyle = 'dotted';
				if (document.getElementById('vd_ext_to').value != '') {
					document.getElementById(document.getElementById('vd_ext_to').value).style.borderStyle = '';
					document.getElementById('vd_ext_to').value = '';
				}
			}
			else {
				document.getElementById('vd_ext_to').value = ext;
				if (document.getElementById('vd_ext_from').value != document.getElementById('vd_ext_to').value) {
					if (document.getElementById('vd_call_id').value != '') {
						cmd = get_transfer_cmd(document.getElementById('vd_call_id').value, document.getElementById('vd_ext_to').value); //transfer a call
					}
					else {
						cmd = get_originate_cmd(document.getElementById('vd_ext_from').value + '@<?=$_SESSION["domain_name"]?>', document.getElementById('vd_ext_to').value); //originate a call
					}
					if (cmd != '') {
						//alert(cmd);
						send_cmd('exec.php?cmd='+escape(cmd));
					}
				}
				virtual_drag_reset();
			}
		}
	}

	function virtual_drag_reset(vd_var) {
		if (!(vd_var === undefined)) {
			document.getElementById(vd_var).value = '';
		}
		else {
			document.getElementById('vd_call_id').value = '';
			if (document.getElementById('vd_ext_from').value != '') {
				document.getElementById(document.getElementById('vd_ext_from').value).style.borderStyle = '';
				document.getElementById('vd_ext_from').value = '';
			}
			if (document.getElementById('vd_ext_to').value != '') {
				document.getElementById(document.getElementById('vd_ext_to').value).style.borderStyle = '';
				document.getElementById('vd_ext_to').value = '';
			}
		}
	}

</script>

<style type="text/css">
	DIV.ext {
		float: left;
		width: 235px;
		margin: 0px 10px 10px 0px;
		padding: 0px;
		border-style: solid;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		-webkit-box-shadow: 0 0 3px #e5e9f0;
		-moz-box-shadow: 0 0 3px #e5e9f0;
		box-shadow: 0 0 3px #e5e9f0;
		border-width: 1px 3px;
		border-color: #b9c5d8 #c5d1e5;
		background-color: #e5eaf5;
		cursor: default;
		}

	DIV.state_active {
		background-color: #baf4bb;
		border-width: 1px 3px;
		border-color: #77d779;
		}

	DIV.state_ringing {
		background-color: #a8dbf0;
		border-width: 1px 3px;
		border-color: #41b9eb;
		}

	TABLE {
		border-spacing: 0px;
		border-collapse: collapse;
		border: none;
		}

	TABLE.ext {
		width: 100%;
		height: 60px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		background-color: #e5eaf5;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		}

	TD.ext_icon {
		vertical-align: middle;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		}

	IMG.ext_icon {
		cursor: move;
		width: 39px;
		height: 42px;
		border: none;
		}

	TD.ext_info {
		text-align: left;
		vertical-align: top;
		font-family: arial;
		font-size: 10px;
		overflow: auto;
		width: 100%;
		padding: 3px 5px 3px 7px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		background-color: #f0f2f6;
		}

	TD.state_ringing {
		background-color: #d1f1ff;
		}

	TD.state_active {
		background-color: #e1ffe2;
		}

	TABLE.state_ringing {
		background-color: #a8dbf0;
		}

	TABLE.state_active {
		background-color: #baf4bb;
		}

	.user_info {
		font-family: arial;
		font-size: 10px;
		display: inline-block;
		}

	.user_info strong {
		color: #3164AD;
		}

	.caller_info {
		display: block;
		margin-top: 7px;
		font-family: arial;
		font-size: 10px;
		}

	.call_info {
		display: inline-block;
		padding: 0px;
		font-family: arial;
		font-size: 10px;
		}

</style>

<?php

//create simple array of users own extensions
foreach ($_SESSION['user']['extension'] as $assigned_extensions) {
	$_SESSION['user']['extensions'][] = $assigned_extensions['user'];
}

echo "<div id='ajax_reponse'>";
//	include("index_inc.php");
echo "</div>\n";
echo "<div id='cmd_reponse' style='display: none;'></div>";
echo "<br><br>";


require_once "resources/footer.php";
?>