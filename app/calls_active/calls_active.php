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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	$show = trim($_REQUEST["show"] ?? '');
	if ($show != "all") { $show = ''; }

//show the header
	$document['title'] = $text['title'];
	require_once "resources/header.php";

//load gateways into a session variable
	$sql = "select gateway_uuid, domain_uuid, gateway from v_gateways where enabled = 'true' ";
	$database = new database;
	$gateways = $database->select($sql, $parameters ?? null, 'all');
	foreach ($gateways as $row) {
		$_SESSION['gateways'][$row['gateway_uuid']] = $row['gateway'];
	}

//ajax for refresh
	?>
	<script type="text/javascript">
	//define refresh function, initial start
 		var refresh = 1980;
		var source_url = 'calls_active_inc.php?';
		var timer_id;

		<?php
		if ($show == 'all') {
			echo "source_url = source_url + '&show=all';";
		}
		if (isset($_REQUEST["debug"])) {
			echo "source_url = source_url + '&debug';";
		}
		?>
		function ajax_get() {
			url = source_url + '&eavesdrop_dest=' + ((document.getElementById('eavesdrop_dest')) ? document.getElementById('eavesdrop_dest').value : '');
			$.ajax({
				url: url,
				success: function(response){
					$("#ajax_response").html(response);
					const table = document.getElementById('calls_active');
					var row_count = table.rows.length;
					if (row_count > 0) { row_count = row_count - 1; }
					const calls_active_count = document.getElementById('calls_active_count');
					calls_active_count.innerHTML = row_count;
				}
			});
			timer_id = setTimeout(ajax_get, refresh);
		};

		refresh_start();

	//refresh controls
		function refresh_stop() {
			clearTimeout(timer_id);
			//document.getElementById('refresh_state').innerHTML = "<img src='resources/images/refresh_paused.png' style='width: 16px; height: 16px; border: none; margin-top: 1px; margin-right: 20px; cursor: pointer;' onclick='refresh_start();' alt=\"<?php echo $text['label-refresh_enable']?>\" title=\"<?php echo $text['label-refresh_enable']?>\">";
			document.getElementById('refresh_state').innerHTML = "<?php echo button::create(['type'=>'button','title'=>$text['label-refresh_enable'],'icon'=>'pause','onclick'=>'refresh_start()']); ?>";
		}

		function refresh_start() {
			//if (document.getElementById('refresh_state')) { document.getElementById('refresh_state').innerHTML = "<img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 2px; margin-right: 20px; cursor: pointer;' alt=\"<?php echo $text['label-refresh_pause']?>\" title=\"<?php echo $text['label-refresh_pause']?>\">"; }
			if (document.getElementById('refresh_state')) { document.getElementById('refresh_state').innerHTML = "<?php echo button::create(['type'=>'button','title'=>$text['label-refresh_pause'],'icon'=>'sync-alt fa-spin','onclick'=>'refresh_stop()']); ?>"; }
			ajax_get();
		}

	//eavesdrop call
		function eavesdrop_call(ext, chan_uuid) {
			if (ext != '' && chan_uuid != '') {
				cmd = get_eavesdrop_cmd(ext, chan_uuid, document.getElementById('eavesdrop_dest').value);
				if (cmd != '') {
					send_cmd(cmd);
				}
			}
		}

		function get_eavesdrop_cmd(ext, chan_uuid, destination) {
			url = "calls_exec.php?action=eavesdrop&ext=" + ext + "&chan_uuid=" + chan_uuid + "&destination=" + destination;
			return url;
		}

	//used by eavesdrop function
		function send_cmd(url) {
			if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp=new XMLHttpRequest();
			}
			else {// code for IE6, IE5
				xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp.open("GET",url,false);
			xmlhttp.send(null);
			document.getElementById('cmd_response').innerHTML=xmlhttp.responseText;
		}

	</script>

<?php

//create simple array of users own extensions
	unset($_SESSION['user']['extensions']);
	if (is_array($_SESSION['user']['extension'])) {
		foreach ($_SESSION['user']['extension'] as $assigned_extensions) {
			$_SESSION['user']['extensions'][] = $assigned_extensions['user'];
		}
	}

//create token
	$object = new token;
	$token = $object->create('/app/calls_active/calls_active_inc.php');
	$_SESSION['app']['calls_active']['token']['name'] = $token['name'];
	$_SESSION['app']['calls_active']['token']['hash'] = $token['hash'];

//show the content header
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title']."</b><div id='calls_active_count' class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	echo "		<span id='refresh_state'>".button::create(['type'=>'button','title'=>$text['label-refresh_pause'],'icon'=>'sync-alt fa-spin','onclick'=>'refresh_stop()'])."</span>";
	if (permission_exists('call_active_eavesdrop') && !empty($user['extensions'])) {
		if (sizeof($user['extensions']) > 1) {
			echo "	<input type='hidden' id='eavesdrop_dest' value=\"".(($_REQUEST['eavesdrop_dest'] == '') ? $user['extension'][0]['destination'] : escape($_REQUEST['eavesdrop_dest']))."\">\n";
			echo "	<i class='fas fa-headphones' style='margin-left: 15px; cursor: help;' title='".$text['description-eavesdrop_destination']."' align='absmiddle'></i>\n";
			echo "	<select class='formfld' style='margin-right: 5px;' align='absmiddle' onchange=\"document.getElementById('eavesdrop_dest').value = this.options[this.selectedIndex].value; refresh_start();\" onfocus='refresh_stop();'>\n";
			if (is_array($user['extensions'])) {
				foreach ($user['extensions'] as $user_extension) {
					echo "	<option value='".escape($user_extension)."' ".(($_REQUEST['eavesdrop_dest'] == $user_extension) ? "selected" : null).">".escape($user_extension)."</option>\n";
				}
			}
			echo "	</select>\n";
		}
		else if (sizeof($user['extensions']) == 1) {
			echo "	<input type='hidden' id='eavesdrop_dest' value=\"".escape($user['extension'][0]['destination'])."\">\n";
		}
	}
	if (permission_exists('call_active_hangup') && $rows) {
		echo button::create(['type'=>'button','label'=>$text['label-hangup'],'icon'=>'phone-slash','id'=>'btn_delete','onclick'=>"refresh_stop(); modal_open('modal-hangup','btn_hangup');"]);
	}
	if (permission_exists('call_active_all')) {
		if ($show == "all") {
			echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$theme_button_icon_back,'link'=>'calls_active.php','onmouseover'=>'refresh_stop()','onmouseout'=>'refresh_start()']);
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$theme_button_icon_all,'link'=>'calls_active.php?show=all','onmouseover'=>'refresh_stop()','onmouseout'=>'refresh_start()']);
		}
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('call_active_hangup') && $rows) {
		echo modal::create(['id'=>'modal-hangup','type'=>'general','message'=>$text['confirm-hangups'],'actions'=>button::create(['type'=>'button','label'=>$text['label-hangup'],'icon'=>'check','id'=>'btn_hangup','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('hangup'); list_form_submit('form_list');"])]);
	}

	echo $text['description']."\n";
	echo "<br /><br />\n";

//show the content body
	echo "<div id='ajax_response'></div>\n";
	echo "<div id='cmd_response' style='display: none;'></div>\n";
	echo "<div id='time_stamp' style='visibility:hidden'>".date('Y-m-d-s')."</div>\n";
	echo "<br><br><br>";

//show the footer
	require_once "resources/footer.php";

/*
// deprecated functions for this page

	function get_park_cmd(uuid, context) {
		cmd = \"uuid_transfer \"+uuid+\" -bleg *6000 xml \"+context;
		return escape(cmd);
	}

	function get_record_cmd(uuid, prefix, name) {
		cmd = \"uuid_record \"+uuid+\" start ".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/archive/".date("Y")."/".date("M")."/".date("d")."/\"+uuid+\".wav\";
		return escape(cmd);
	}
*/

?>
