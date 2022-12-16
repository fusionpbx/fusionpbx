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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes fileshp";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists("registration_domain") || permission_exists("registration_all") || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get common submitted data
	$show = $_REQUEST['show'];
	$search = $_REQUEST['search'];
	$profile = $_REQUEST['profile'];

//define query string array
	if ($show) { $qs['show'] = "&show=".urlencode($show); }
	if ($search) { $qs['search'] = "&search=".urlencode($search); }
	if ($profile) { $qs['profile'] = "&profile=".urlencode($profile); }

//get posted data
	if (is_array($_POST['registrations'])) {
		$action = $_POST['action'];
		$registrations = $_POST['registrations'];
	}

//process posted data
	if ($action != '' && is_array($registrations) && @sizeof($registrations) != 0) {
		$obj = new registrations;

		switch ($action) {
			case 'unregister':
				$obj->unregister($registrations);
				break;

			case 'provision':
				$obj->provision($registrations);
				break;

			case 'reboot':
				$obj->reboot($registrations);
				break;
		}

		header('Location: registrations.php'.($show || $search || $profile ? '?' : null).$qs['show'].$qs['search'].$qs['profile']);
		exit;
	}

//get the registrations
	$obj = new registrations;
	$obj->show = $show;
	$registrations = $obj->get($profile);

//order the array
	require_once "resources/classes/array_order.php";
	$order = new array_order();
	$registrations = $order->sort($registrations, 'sip-auth-realm', 'user');

//get registration count
	$num_rows = 0;
	if (is_array($registrations)) {
		foreach ($registrations as $row) {
			$matches = preg_grep("/".$search."/i", $row);
			if ($matches != false) {
				$num_rows++;
			}
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//detect page reload via ajax
	$reload = isset($_GET['reload']) && permission_exists('registration_reload') ? true : false;

//define location url
	$location = ($reload ? 'registration_reload.php' : 'registrations.php');

//include the header
	if (!$reload) {
		$document['title'] = $text['header-registrations'];
		require_once "resources/header.php";
	}

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-registrations']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (!$reload) {
		echo button::create(['type'=>'button','label'=>$text['button-refresh'],'icon'=>$_SESSION['theme']['button_icon_refresh'],'link'=>$location.($qs ? '?' : null).$qs['show'].$qs['search'].$qs['profile']]);
	}
	if ($registrations) {
		echo button::create(['type'=>'button','label'=>$text['button-unregister'],'title'=>$text['button-unregister'],'icon'=>'user-slash','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-unregister','btn_unregister');"]);
		echo button::create(['type'=>'button','label'=>$text['button-provision'],'title'=>$text['button-provision'],'icon'=>'fax','onclick'=>"modal_open('modal-provision','btn_provision');"]);
		echo button::create(['type'=>'button','label'=>$text['button-reboot'],'title'=>$text['button-reboot'],'icon'=>'power-off','onclick'=>"modal_open('modal-reboot','btn_reboot');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('registration_all')) {
		if ($show == 'all') {
			echo 	"<input type='hidden' name='show' value='".escape($show)."'>";
			echo button::create(['type'=>'button','label'=>$text['button-show_local'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>$location.($qs['search'] || $qs['profile'] ? '?' : null).$qs['search'].$qs['profile']]);
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>$location.'?show=all'.$qs['search'].$qs['profile']]);
		}
		if ($profile != '') {
			echo 	"<input type='hidden' name='profile' value='".escape($profile)."'>";
			echo button::create(['type'=>'button','label'=>$text['button-all_profiles'],'icon'=>'network-wired','style'=>'margin-left: 15px;','link'=>$location.($qs['show'] || $qs['search'] ? '?' : null).$qs['show'].$qs['search']]);
		}
	}
	if (!$reload) {
		echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
		echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
		//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>$location.($qs['show'] || $qs['profile'] ? '?' : null).$qs['show'].$qs['profile'],'style'=>($search == '' ? 'display: none;' : null)]);
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($registrations) {
		echo modal::create(['id'=>'modal-unregister','type'=>'general','message'=>$text['confirm-unregister'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_unregister','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('unregister'); list_form_submit('form_list');"])]);
		echo modal::create(['id'=>'modal-provision','type'=>'general','message'=>$text['confirm-provision'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_provision','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('provision'); list_form_submit('form_list');"])]);
		echo modal::create(['id'=>'modal-reboot','type'=>'general','message'=>$text['confirm-reboot'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_reboot','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('reboot'); list_form_submit('form_list');"])]);
	}

	echo $text['description-registrations']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";
	echo "<input type='hidden' name='profile' value='".escape($profile)."'>";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th class='checkbox'>\n";
	echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($registrations ?: "style='visibility: hidden;'").">\n";
	echo "	</th>\n";
	echo "	<th>".$text['label-user']."</th>\n";
	echo "	<th class='pct-25'>".$text['label-agent']."</th>\n";
	echo "	<th class='hide-md-dn'>".$text['label-contact']."</th>\n";
	echo "	<th class='hide-sm-dn'>".$text['label-lan_ip']."</th>\n";
	echo "	<th class='hide-sm-dn'>".$text['label-ip']."</th>\n";
	echo "	<th class='hide-sm-dn'>".$text['label-port']."</th>\n";
	echo "	<th class='hide-md-dn'>".$text['label-hostname']."</th>\n";
	echo "	<th class='pct-35' style='width: 35%;'>".$text['label-status']."</th>\n";
	echo "	<th class='hide-md-dn'>".$text['label-ping']."</th>\n";
	echo "	<th class='hide-md-dn'>".$text['label-sip_profile_name']."</th>\n";
	echo "	<td class='action-button'>&nbsp;</td>\n";
	echo "</tr>\n";

	if (is_array($registrations) && @sizeof($registrations) != 0) {
		$x = 0;
		foreach ($registrations as $row) {
			$matches = preg_grep('/'.$search.'/i', $row);
			if ($matches != false) {

				//prepare the user variable
				$user = explode('@', $row['user']);
				if ($user[1] == $_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name']) {
					$user = "<span class='hide-sm-dn'>".escape($row['user'])."</span><span class='hide-md-up cursor-help' title='".escape($row['user'])."'>".escape($user[0])."</span>";
				}
				else {
					$user = escape($row['user']);
				}

				//reformat the status
				$patterns = array();
				$patterns[] = '/(\d{4})-(\d{2})-(\d{2})/';
				$patterns[] = '/(\d{2}):(\d{2}):(\d{2})/';
				$patterns[] = '/unknown/';
				$patterns[] = '/exp\(/';
				$patterns[] = '/\(/';
				$patterns[] = '/\)/';
				$patterns[] = '/\s+/';
				$status = preg_replace($patterns, ' ', $row['status']);

				//show the content
				echo "<tr class='list-row' href='#'>\n";
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='registrations[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='registrations[$x][user]' value='".escape($row['user'])."' />\n";
				echo "		<input type='hidden' name='registrations[$x][profile]' value='".escape($row['sip_profile_name'])."' />\n";
				echo "		<input type='hidden' name='registrations[$x][agent]' value='".escape($row['agent'])."' />\n";
				echo "		<input type='hidden' name='registrations[$x][host]' value='".escape($row['host'])."' />\n";
				echo "		<input type='hidden' name='registrations[$x][domain]' value='".escape($row['sip-auth-realm'])."' />\n";
				echo "	</td>\n";
				echo "	<td class=''>".$user."</td>\n";
				echo "	<td class='' title=\"".escape($row['agent'])."\"><span class='cursor-help'>".escape($row['agent'])."</span></td>\n";
				echo "	<td class='hide-md-dn'>".escape(explode('"',$row['contact'])[1])."</td>\n";
				echo "	<td class='hide-sm-dn no-link'><a href='https://".urlencode($row['lan-ip'])."' target='_blank'>".escape($row['lan-ip'])."</a></td>\n";
				echo "	<td class='hide-sm-dn no-link'><a href='https://".urlencode($row['network-ip'])."' target='_blank'>".escape($row['network-ip'])."</a></td>\n";
				echo "	<td class='hide-sm-dn'>".escape($row['network-port'])."</td>\n";
				echo "	<td class='hide-md-dn'>".escape($row['host'])."</td>\n";
				echo "	<td class='' title=\"".escape($row['status'])."\"><span class='cursor-help'>".escape($status)."</span></td>\n";
				echo "	<td class='hide-md-dn'>".escape($row['ping-time'])."</td>\n";
				echo "	<td class='hide-md-dn'>".escape($row['sip_profile_name'])."</td>\n";
				echo "	<td class='action-button'>\n";
				if ($_SESSION['registrations']['list_row_button_unregister']['boolean'] == 'true') {
					echo button::create(['type'=>'submit','title'=>$text['button-unregister'],'icon'=>'user-slash fa-fw','style'=>'margin-left: 2px; margin-right: 0;','onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('unregister'); list_form_submit('form_list')"]);
				}
				if ($_SESSION['registrations']['list_row_button_provision']['boolean'] == 'true') {
					echo button::create(['type'=>'submit','title'=>$text['button-provision'],'icon'=>'fax fa-fw','style'=>'margin-left: 2px; margin-right: 0;','onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('provision'); list_form_submit('form_list')"]);
				}
				if ($_SESSION['registrations']['list_row_button_reboot']['boolean'] == 'true') {
					echo button::create(['type'=>'submit','title'=>$text['button-reboot'],'icon'=>'power-off fa-fw','style'=>'margin-left: 2px; margin-right: 0;','onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('reboot'); list_form_submit('form_list')"]);
				}
				echo 	"</td>\n";
				echo "</tr>\n";
				$x++;
			}
		}
	}
	unset($registrations);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//get the footer
	if (!$reload) {
		require_once "resources/footer.php";
	}

?>
