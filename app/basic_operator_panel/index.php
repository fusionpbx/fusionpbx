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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('operator_panel_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set user status
	if (isset($_REQUEST['status']) && $_REQUEST['status'] != '') {

		//validate the user status
			$user_status = $_REQUEST['status'];
			switch ($user_status) {
				case "Available" :
					break;
				case "Available (On Demand)" :
					break;
				case "On Break" :
					break;
				case "Do Not Disturb" :
					break;
				case "Logged Out" :
					break;
				default :
					$user_status = '';
			}

		//update the status
			if (permission_exists("user_setting_edit")) {
				//add the user_edit permission
				$p = new permissions;
				$p->add("user_edit", "temp");

				//update the database user_status
				$array['users'][0]['user_uuid'] = $_SESSION['user']['user_uuid'];
				$array['users'][0]['domain_uuid'] = $_SESSION['user']['domain_uuid'];
				$array['users'][0]['user_status'] = $user_status;
				$database = new database;
				$database->app_name = 'operator_panel';
				$database->app_uuid = 'dd3d173a-5d51-4231-ab22-b18c5b712bb2';
				$database->save($array);

				//remove the temporary permission
				$p->delete("user_edit", "temp");

				unset($array);
			}

		//if call center app is installed then update the user_status
			if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/call_centers')) {
				//get the call center agent uuid
					$sql = "select call_center_agent_uuid from v_call_center_agents ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and user_uuid = :user_uuid ";
					$parameters['domain_uuid'] = $_SESSION['user']['domain_uuid'];
					$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
					$database = new database;
					$call_center_agent_uuid = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);

				//update the user_status
					if (is_uuid($call_center_agent_uuid)) {
						$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
						$switch_cmd .= "callcenter_config agent set status ".$call_center_agent_uuid." '".$user_status."'";
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}

				//update the user state
					if (is_uuid($call_center_agent_uuid)) {
						$cmd = "api callcenter_config agent set state ".$call_center_agent_uuid." Waiting";
						$response = event_socket_request($fp, $cmd);
					}

				//update do not disturb
					if ($user_status == "Do Not Disturb") {
						$x = 0;
						foreach($_SESSION['user']['extension'] as $row) {
							//build the array
							$array['extensions'][$x]['extension_uuid'] = $row['extension_uuid'];
							$array['extensions'][$x]['dial_string'] = '!USER_BUSY';
							$array['extensions'][$x]['do_not_disturb'] = 'true';

							//delete extension from the cache
							$cache = new cache;
							$cache->delete("directory:".$row['extension']."@".$_SESSION['user']['domain_name']);
							if(strlen($number_alias) > 0){
								$cache->delete("directory:".$row['number_alias']."@".$_SESSION['user']['domain_name']);
							}

							//incrment
							$x++;
						}
					}
					else {
						$x = 0;
						foreach($_SESSION['user']['extension'] as $row) {
							//build the array
							$array['extensions'][$x]['extension_uuid'] = $row['extension_uuid'];
							$array['extensions'][$x]['dial_string'] = null;
							$array['extensions'][$x]['do_not_disturb'] = 'false';

							//delete extension from the cache
							$cache = new cache;
							$cache->delete("directory:".$row['extension']."@".$_SESSION['user']['domain_name']);
							if(strlen($number_alias) > 0){
								$cache->delete("directory:".$row['number_alias']."@".$_SESSION['user']['domain_name']);
							}

							//incrment
							$x++;
						}
					}

				//grant temporary permissions
					$p = new permissions;
					$p->add('extension_edit', 'temp');

				//execute update
					$database = new database;
					$database->app_name = 'calls';
					$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
					$database->save($array);
					unset($array);

				//revoke temporary permissions
					$p->delete('extension_edit', 'temp');

				//delete extension from the cache
					$cache = new cache;
					$cache->delete("directory:".$extension."@".$this->domain_name);
					if(strlen($number_alias) > 0){
						$cache->delete("directory:".$number_alias."@".$this->domain_name);
					}
			}

		//stop execution
			exit;
	}

//set the title
	$document['title'] = $text['title-operator_panel'];

//include the header
	require_once "resources/header.php";

?>

<!-- virtual_drag function holding elements -->
<input type='hidden' class='formfld' id='vd_call_id' value=''>
<input type='hidden' class='formfld' id='vd_ext_from' value=''>
<input type='hidden' class='formfld' id='vd_ext_to' value=''>
<input type='hidden' class='formfld' id='sort1' value=''>

<!-- autocomplete for contact lookup -->
<link rel="stylesheet" type="text/css" href="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-ui.min.css">
<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-ui.min.js"></script>
<script type="text/javascript">

<?php
//determine refresh rate
$refresh_default = 1500; //milliseconds
$refresh = is_numeric($_SESSION['operator_panel']['refresh']['numeric']) ? $_SESSION['operator_panel']['refresh']['numeric'] : $refresh_default;
if ($refresh >= 0.5 && $refresh <= 120) { //convert seconds to milliseconds
	$refresh = $refresh * 1000;
}
else if ($refresh < 0.5 || ($refresh > 120 && $refresh < 500)) {
	$refresh = $refresh_default; //use default
}
else {
	//>= 500, must be milliseconds
}
unset($refresh_default);
?>

//ajax refresh
	var refresh = <?php echo $refresh; ?>;
	var source_url = 'resources/content.php?' <?php if (isset($_GET['debug'])) { echo " + '&debug'"; } ?>;
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
		var url = new URL(this.xmlHttp.responseURL);
		if (/login\.php$/.test(url.pathname)) {
			// You are logged out. Stop refresh!
			refresh_stop();
			url.searchParams.set('path', '<?php echo $_SERVER['REQUEST_URI']; ?>');
			window.location.href = url.href;
			return;
		}

		if (this.xmlHttp.readyState == 4 && (this.xmlHttp.status == 200 || !/^http/.test(window.location.href)))
			//this.el.innerHTML = this.xmlHttp.responseText;
			document.getElementById('ajax_reponse').innerHTML = this.xmlHttp.responseText;
		if (document.getElementById('sort')) {
			if (document.getElementById('sort').value != "")
				document.getElementById('sort1').value=document.getElementById('sort').value;
		}
	}

	var requestTime = function() {
		var url = source_url;
		url += '&vd_ext_from=' + document.getElementById('vd_ext_from').value;
		url += '&vd_ext_to=' + document.getElementById('vd_ext_to').value;
		url += '&group=' + ((document.getElementById('group')) ? document.getElementById('group').value : '');
		url += '&extension_filter=' + ((document.getElementById('extension_filter')) ? document.getElementById('extension_filter').value : '');
		url += '&name_filter=' + ((document.getElementById('name_filter')) ? document.getElementById('name_filter').value : '');
		url += '&eavesdrop_dest=' + ((document.getElementById('eavesdrop_dest')) ? document.getElementById('eavesdrop_dest').value : '');
		if (document.getElementById('sort1'))
			if (document.getElementById('sort1').value == '1') url += '&sort';
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
	var ie_workaround = false;

	function drag(ev, from_ext) {
		refresh_stop();
		try {
			ev.dataTransfer.setData("Call", ev.target.id);
			ev.dataTransfer.setData("From", from_ext);
			virtual_drag_reset();
		}
		catch (err) {
			// likely internet explorer being used, do workaround
			virtual_drag(ev.target.id, from_ext);
			ie_workaround = true;
		}
	}

	function allowDrop(ev, target_id) {
		ev.preventDefault();
	}

	function discardDrop(ev, target_id) {
		ev.preventDefault();
	}

	function drop(ev, to_ext) {
		ev.preventDefault();
		if (ie_workaround) { // potentially set on drag() function above
			var call_id = document.getElementById('vd_call_id').value;
			var from_ext = document.getElementById('vd_ext_from').value;
			virtual_drag_reset();
		}
		else {
			var call_id = ev.dataTransfer.getData("Call");
			var from_ext = ev.dataTransfer.getData("From");
		}
		var to_ext = to_ext;
		var cmd;

		if (call_id != '') {
			cmd = get_transfer_cmd(call_id, to_ext); //transfer a call
		}
		else {
			if (from_ext != to_ext) { // prevent user from dragging extention onto self
				cmd = get_originate_cmd(from_ext, to_ext); //make a call
			}
		}

		if (cmd != '') { send_cmd(cmd) }

		refresh_start();
	}

//refresh controls
	function refresh_stop() {
		clearInterval(interval_timer_id);
		if (document.getElementById('refresh_state')) { document.getElementById('refresh_state').innerHTML = "<img src='resources/images/refresh_paused.png' style='width: 16px; height: 16px; border: none; margin-top: 1px; cursor: pointer;' onclick='refresh_start();' alt=\"<?php echo $text['label-refresh_enable']?>\" title=\"<?php echo $text['label-refresh_enable']?>\">"; }
	}

	function refresh_start() {
		if (document.getElementById('refresh_state')) { document.getElementById('refresh_state').innerHTML = "<img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' alt=\"<?php echo $text['label-refresh_pause']?>\" title=\"<?php echo $text['label-refresh_pause']?>\">"; }
		refresh_stop();
		interval_timer_id = setInterval( function() {
			url = source_url;
			url += '&vd_ext_from=' + document.getElementById('vd_ext_from').value;
			url += '&vd_ext_to=' + document.getElementById('vd_ext_to').value;
			url += '&group=' + ((document.getElementById('group')) ? document.getElementById('group').value : '');
			url += '&extension_filter=' + ((document.getElementById('extension_filter')) ? document.getElementById('extension_filter').value : '');
			url += '&name_filter=' + ((document.getElementById('name_filter')) ? document.getElementById('name_filter').value : '');
			url += '&eavesdrop_dest=' + ((document.getElementById('eavesdrop_dest')) ? document.getElementById('eavesdrop_dest').value : '');
			if (document.getElementById('sort1'))
				if (document.getElementById('sort1').value == '1') url += '&sort';
			<?php
			if (isset($_GET['debug'])) {
				echo "url += '&debug';";
			}
			?>
			new loadXmlHttp(url, 'ajax_reponse');
		}, refresh);
	}

//call or transfer to destination
	function go_destination(from_ext, destination, which, call_id) {
		call_id = typeof call_id !== 'undefined' ? call_id : '';
		if (destination != '') {
			if (!isNaN(parseFloat(destination)) && isFinite(destination)) {
				if (call_id == '') {
					cmd = get_originate_cmd(from_ext, destination); //make a call
				}
				else {
					cmd = get_transfer_cmd(call_id, destination);
				}
				if (cmd != '') {
					send_cmd(cmd);
					$('#destination_'+from_ext+'_'+which).removeAttr('onblur');
					toggle_destination(from_ext, which);
				}
			}
		}
	}

//hangup call
	function hangup_call(call_id) {
		if (call_id != '') {
			send_cmd('exec.php?cmd=uuid_kill&call_id=' + call_id)
		}
	}

//eavesdrop call
	function eavesdrop_call(ext, chan_uuid) {
		if (ext != '' && chan_uuid != '') {
			cmd = get_eavesdrop_cmd(ext, chan_uuid, document.getElementById('eavesdrop_dest').value);
			if (cmd != '') {
				send_cmd(cmd)
			}
		}
	}

//record call
	function record_call(chan_uuid) {
		if (chan_uuid != '') {
			cmd = get_record_cmd(chan_uuid);
			if (cmd != '') {
				send_cmd(cmd);
			}
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

//hide/show destination input field
	function toggle_destination(ext, which) {
		refresh_stop();
		if (which == 'call') {
			if ($('#destination_'+ext+'_call').is(':visible')) {
				$('#destination_'+ext+'_call').val('');
				$('#destination_'+ext+'_call').autocomplete('destroy');
				$('#destination_'+ext+'_call').hide(0, function() {
					$('.call_control').children().attr('onmouseout', "refresh_start();");
					$('.destination_control').attr('onmouseout', "refresh_start();");
					refresh_start();
				});
			}
			else {
				$('#destination_'+ext+'_call').show(0, function() {
					$('#destination_'+ext+'_call').trigger('focus');
					$('#destination_'+ext+'_call').autocomplete({
						source: "autocomplete.php",
						minLength: 3,
						select: function(event, ui) {
							$('#destination_'+ext+'_call').val(ui.item.value);
							$('#frm_destination_'+ext+'_call').submit();
						}
					});
					$('.call_control').children().removeAttr('onmouseout');
					$('.destination_control').removeAttr('onmouseout');
				});
			}
		}
		else if (which == 'transfer') {
			if ($('#destination_'+ext+'_transfer').is(':visible')) {
				$('#destination_'+ext+'_transfer').val('');
				$('#destination_'+ext+'_transfer').autocomplete('destroy');
				$('#destination_'+ext+'_transfer').hide(0, function() {
					$('#op_caller_details_'+ext).show();
					$('.call_control').children().attr('onmouseout', "refresh_start();");
					$('.destination_control').attr('onmouseout', "refresh_start();");
					refresh_start();
				});
			}
			else {
				$('#op_caller_details_'+ext).hide(0, function() {
					$('#destination_'+ext+'_transfer').show(0, function() {
						$('#destination_'+ext+'_transfer').trigger('focus');
						$('#destination_'+ext+'_transfer').autocomplete({
							source: "autocomplete.php",
							minLength: 3,
							select: function(event, ui) {
								$('#destination_'+ext+'_transfer').val(ui.item.value);
								$('#frm_destination_'+ext+'_transfer').submit();
							}
						});
						$('.call_control').children().removeAttr('onmouseout');
						$('.destination_control').removeAttr('onmouseout');
					});
				});
			}
		}
	}

	function get_transfer_cmd(uuid, destination) {
		url = "exec.php?cmd=uuid_transfer&uuid=" + uuid + "&destination=" + destination
		return url;
	}

	function get_originate_cmd(source, destination) {
		url = "exec.php?cmd=originate&source=" + source + "&destination=" + destination
		return url;
	}

	function get_eavesdrop_cmd(ext, chan_uuid, destination) {
		url = "exec.php?cmd=uuid_eavesdrop&ext=" + ext + "&chan_uuid=" + chan_uuid + "&destination=" + destination;
		return url;
	}

	function get_record_cmd(uuid) {
		url = "exec.php?cmd=uuid_record&uuid=" + uuid;
		return url;
	}

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
						cmd = get_originate_cmd(document.getElementById('vd_ext_from').value, document.getElementById('vd_ext_to').value); //originate a call
					}
					if (cmd != '') {
						//alert(cmd);
						send_cmd(cmd);
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
	TABLE {
		border-spacing: 0px;
		border-collapse: collapse;
		border: none;
		}
</style>

<?php

//create simple array of users own extensions
unset($_SESSION['user']['extensions']);
if (is_array($_SESSION['user']['extension'])) {
	foreach ($_SESSION['user']['extension'] as $assigned_extensions) {
		$_SESSION['user']['extensions'][] = $assigned_extensions['user'];
	}
}

?>

<div id='ajax_reponse'></div>
<div id='cmd_reponse' style='display: none;'></div>
<br><br>

<?php

//include the footer
	require_once "resources/footer.php";

?>
