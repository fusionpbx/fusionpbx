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
if (permission_exists('call_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the HTTP values and set as variables
	$show = trim($_REQUEST["show"]);
	if ($show != "all") { $show = ''; }

//show the header
	$document['title'] = $text['title'];
	require_once "resources/header.php";

//ajax for refresh
	?>
	<script type="text/javascript">
		var refresh = 1500;
		var source_url = 'calls_active_inc.php?';
		<?php
		if ($show == 'all') {
			echo "source_url = source_url + '&show=all';";
		}
		if (isset($_REQUEST["debug"])) {
			echo "source_url = source_url + '&debug';";
		}
		?>
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
			new loadXmlHttp(url, 'ajax_reponse');
			refresh_start();
		}

		if (window.addEventListener) {
			window.addEventListener('load', requestTime, false);
		}
		else if (window.attachEvent) {
			window.attachEvent('onload', requestTime);
		}

	//refresh controls
		function refresh_stop() {
			clearInterval(interval_timer_id);
			document.getElementById('refresh_state').innerHTML = "<img src='resources/images/refresh_paused.png' style='width: 16px; height: 16px; border: none; margin-top: 1px; cursor: pointer;' onclick='refresh_start();' alt=\"<?php echo $text['label-refresh_enable']?>\" title=\"<?php echo $text['label-refresh_enable']?>\">";
		}

		function refresh_start() {
			if (document.getElementById('refresh_state')) { document.getElementById('refresh_state').innerHTML = "<img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' alt=\"<?php echo $text['label-refresh_pause']?>\" title=\"<?php echo $text['label-refresh_pause']?>\">"; }
			interval_timer_id = setInterval( function() {
				url = source_url;
				new loadXmlHttp(url, 'ajax_reponse');
			}, refresh);
		}

	//call controls
		function hangup(uuid) {
			if (confirm("<?php echo $text['confirm-hangup']?>")) {
				send_cmd('calls_exec.php?cmd=uuid_kill%20'+uuid);
			}
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

	</script>

<?php
echo "<div id='ajax_reponse'></div>\n";
echo "<div id='time_stamp' style='visibility:hidden'>".date('Y-m-d-s')."</div>\n";
echo "<br><br><br>";

require_once "resources/footer.php";

/*
// deprecated functions for this page

	function get_park_cmd(uuid, context) {
		cmd = \"uuid_transfer \"+uuid+\" -bleg *6000 xml \"+context;
		return escape(cmd);
	}

	function get_record_cmd(uuid, prefix, name) {
		cmd = \"uuid_record \"+uuid+\" start ".$_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d")."/\"+uuid+\".wav\";
		return escape(cmd);
	}
*/
?>