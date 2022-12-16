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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('access_control_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (is_array($_POST['access_controls'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$access_controls = $_POST['access_controls'];
	}

//process the http post data by action
	if ($action != '' && is_array($access_controls) && @sizeof($access_controls) != 0) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: access_controls.php');
			exit;
		}

		//prepare the array
		foreach($access_controls as $row) {
			$array['access_controls'][$x]['checked'] = $row['checked'];
			$array['access_controls'][$x]['access_control_uuid'] = $row['access_control_uuid'];
			$x++;
		}

		//prepare the database object
		$database = new database;
		$database->app_name = 'access_controls';
		$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';

		//send the array to the database class
		switch ($action) {
			case 'copy':
				if (permission_exists('access_control_add')) {
					$database->copy($array);
				}
				break;
			case 'toggle':
				if (permission_exists('access_control_edit')) {
					$database->toggle($array);
				}
				break;
			case 'delete':
				if (permission_exists('access_control_delete')) {
					$database->delete($array);
				}
				break;
		}

		//redirect the user
		header('Location: access_controls.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	$sql = "select count(access_control_uuid) ";
	$sql .= "from v_access_controls ";
	if (isset($_GET["search"])) {
		$sql .= "where (";
		$sql .= "	lower(access_control_name) like :search ";
		$sql .= "	or lower(access_control_default) like :search ";
		$sql .= "	or lower(access_control_description) like :search ";
		$sql .= ") ";
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//get the list
	$sql = "select ";
	$sql .= "access_control_uuid, ";
	$sql .= "access_control_name, ";
	$sql .= "access_control_default, ";
	$sql .= "access_control_description ";
	$sql .= "from v_access_controls ";
	if (isset($_GET["search"])) {
		$sql .= "where (";
		$sql .= "	lower(access_control_name) like :search ";
		$sql .= "	or lower(access_control_default) like :search ";
		$sql .= "	or lower(access_control_description) like :search ";
		$sql .= ") ";
	}
	$sql .= order_by($order_by, $order, 'access_control_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$access_controls = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-access_controls'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-access_controls']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('access_control_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'access_control_edit.php']);
	}
	if (permission_exists('access_control_add') && $access_controls) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('access_control_delete') && $access_controls) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'access_controls.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('access_control_add') && $access_controls) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('access_control_delete') && $access_controls) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-access_controls']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('access_control_add') || permission_exists('access_control_edit') || permission_exists('access_control_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($access_controls ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('access_control_name', $text['label-access_control_name'], $order_by, $order);
	echo th_order_by('access_control_default', $text['label-access_control_default'], $order_by, $order);
	echo "	<th class='hide-sm-dn'>".$text['label-access_control_description']."</th>\n";
	if (permission_exists('access_control_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($access_controls) && @sizeof($access_controls) != 0) {
		$x = 0;
		foreach ($access_controls as $row) {
			if (permission_exists('access_control_edit')) {
				$list_row_url = "access_control_edit.php?id=".urlencode($row['access_control_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('access_control_add') || permission_exists('access_control_edit') || permission_exists('access_control_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='access_controls[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='access_controls[$x][access_control_uuid]' value='".escape($row['access_control_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('access_control_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['access_control_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['access_control_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['access_control_default'])."</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['access_control_description'])."</td>\n";
			if (permission_exists('access_control_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($access_controls);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
