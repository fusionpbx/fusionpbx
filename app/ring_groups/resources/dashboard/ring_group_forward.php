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
	Portions created by the Initial Developer are Copyright (C) 2013-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/ring_groups');

//get the list
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
		$domain_uuid = $_SESSION['domain_uuid'];
	}
	else {
		//show only assigned ring groups
		$domain_uuid = $_SESSION['user']['domain_uuid'];
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//connect to the database
	if (!isset($database)) {
		$database = new database;
	}

//find the path
	switch ($_SERVER['REQUEST_URI']) {
		case PROJECT_PATH."/core/dashboard/index.php":
			$validated_path = PROJECT_PATH."/core/dashboard/index.php";
			break;
		case PROJECT_PATH."/app/ring_groups/ring_group_forward.php":
			$validated_path = PROJECT_PATH."/app/ring_groups/ring_group_forward.php";
			break;
		default:
			$validated_path = PROJECT_PATH."/app/ring_groups/resources/dashboard/ring_group_forward.php";
	}

//update ring group forwarding
	if (is_array($_POST['ring_groups']) && @sizeof($_POST['ring_groups']) != 0 && permission_exists('ring_group_forward')) {

		//validate the token
			$token = new token;
			if (!$token->validate('/app/ring_groups/ring_group_forward.php')) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: '.$validated_path);
				exit;
			}

		$x = 0;
		foreach ($_POST['ring_groups'] as $row) {
			//build array
				if (is_uuid($row['ring_group_uuid'])) {
					$array['ring_groups'][$x]['ring_group_uuid'] = $row['ring_group_uuid'];
					$array['ring_groups'][$x]['ring_group_forward_enabled'] = $row['ring_group_forward_enabled'] == 'true' && $row['ring_group_forward_destination'] != '' ? 'true' : 'false';
					$array['ring_groups'][$x]['ring_group_forward_destination'] = $row['ring_group_forward_destination'];
				}
			//increment counter
				$x++;
		}

		if (is_array($array) && sizeof($array) != 0) {
			//update ring group
				$p = new permissions;
				$p->add('ring_group_edit', 'temp');

				$database->app_name = 'ring_groups';
				$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
				$database->save($array);
				unset($array);

				$p->delete('ring_group_edit', 'temp');

			//set message
				message::add($text['message-update']);
				$validated_path = PROJECT_PATH."/core/dashboard/index.php";

			//redirect the user
				header("Location: ".$validated_path);
				exit;
		}
	}

//prepare to page the results
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
		//show all ring groups
		$sql = "select count(*) from v_ring_groups ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	else {
		//show only assigned ring groups
		$sql = "select count(*) from v_ring_groups as r, v_ring_group_users as u ";
		$sql .= "where r.ring_group_uuid = u.ring_group_uuid ";
		$sql .= "and r.domain_uuid = :domain_uuid ";
		$sql .= "and u.user_uuid = :user_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
	}
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($parameters);

//prepare to page the results
	$rows_per_page = $is_included ? 10 : (is_numeric($_SESSION['domain']['paging']['numeric']) ? $_SESSION['domain']['paging']['numeric'] : 50);
	$param = "";
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;
	}

//get the list
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
		//show all ring groups
		$sql = "select * from v_ring_groups ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	else {
		//show only assigned ring groups
		$sql = "select r.ring_group_name, r.ring_group_uuid, r.ring_group_extension, r.ring_group_forward_destination, ";
		$sql .= "r.ring_group_forward_enabled, r.ring_group_description from v_ring_groups as r, v_ring_group_users as u ";
		$sql .= "where r.ring_group_uuid = u.ring_group_uuid ";
		$sql .= "and r.domain_uuid = :domain_uuid ";
		$sql .= "and u.user_uuid = :user_uuid ";
		$parameters['domain_uuid'] = $_SESSION['user']['domain_uuid'];
		$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
	}
	$sql .= order_by($order_by, $order, 'ring_group_extension', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/app/ring_groups/ring_group_forward.php');

//include header
	require_once "resources/header.php";

//show content
	echo "<div class='action_bar sub'>\n";
	echo "	<div class='heading'><b>".$text['header-ring-group-forward']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if ($is_included && $num_rows > 10) {
		echo button::create(['type'=>'button','label'=>$text['button-view_all'],'icon'=>'share-square','collapse'=>'hide-xs','link'=>PROJECT_PATH.'/app/ring_groups/ring_group_forward.php']);
	}
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>false,'onclick'=>"list_form_submit('form_list_ring_group_forward');"]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (!$is_included) {
		echo $text['description-ring-group-forward']."\n";
		echo "<br /><br />\n";
	}

	echo "<form id='form_list_ring_group_forward' method='post' action='".$validated_path."'>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo th_order_by('ring_group_name', $text['label-name'], $order_by, $order);
	echo th_order_by('ring_group_extension', $text['label-extension'], $order_by, $order);
	echo "<th class='shrink'>".$text['label-forwarding']."</th>";
	if (!$is_included) {
		echo th_order_by('ring_group_description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'");
	}
	echo "</tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		$x = 0;
		foreach ($result as $row) {
			$onclick = "onclick=\"document.getElementById('".escape($row['ring_group_uuid'])."').selectedIndex = (document.getElementById('".escape($row['ring_group_uuid'])."').selectedIndex) ? 0 : 1; if (document.getElementById('".escape($row['ring_group_uuid'])."').selectedIndex) { document.getElementById('destination').focus(); }\"";
			echo "<tr class='list-row'>\n";
			echo "	<td ".$onclick.">".escape($row['ring_group_name'])."&nbsp;</td>\n";
			echo "	<td ".$onclick.">".escape($row['ring_group_extension'])."&nbsp;</td>\n";
			echo "	<td class='input'>";
			echo "		<input type='hidden' name='ring_groups[".$x."][ring_group_uuid]' value=\"".escape($row["ring_group_uuid"])."\">";
			echo "		<select class='formfld' name='ring_groups[".$x."][ring_group_forward_enabled]' id='".escape($row['ring_group_uuid'])."' onchange=\"this.selectedIndex ? document.getElementById('destination').focus() : null;\">";
			echo "			<option value='false'>".$text['option-disabled']."</option>";
			echo "			<option value='true' ".($row["ring_group_forward_enabled"] == 'true' ? "selected='selected'" : null).">".$text['option-enabled']."</option>";
			echo "		</select>";
			echo "		<input class='formfld' style='width: 100px;' type='text' name='ring_groups[".$x."][ring_group_forward_destination]' id='destination' placeholder=\"".$text['label-forward_destination']."\" maxlength='255' value=\"".escape($row["ring_group_forward_destination"])."\">";
			echo "	</td>\n";
			if (!$is_included) {
				echo "	<td class='description overflow hide-sm-dn' ".$onclick.">".escape($row['ring_group_description'])."&nbsp;</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}
	unset($result);

	echo "</table>\n";
	echo "<br />\n";
	if (!$is_included) {
		echo "<div align='center'>".$paging_controls."</div>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

?>
