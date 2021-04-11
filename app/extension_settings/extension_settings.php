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
	Portions created by the Initial Developer are Copyright (C) 2021
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('extension_setting_view')) {
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
	if (is_array($_POST['extension_settings'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$extension_settings = $_POST['extension_settings'];
	}

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$extension_uuid = $_REQUEST["id"];
	}

//process the http post data by action
	if ($action != '' && is_array($extension_settings) && @sizeof($extension_settings) != 0) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: extension_settings.php');
			exit;
		}

		//prepare the database object
		$obj = new extension_settings;

		//send the array to the database class
		switch ($action) {
			case 'copy':
				if (permission_exists('extension_setting_add')) {
					$obj->copy($extension_settings);
				}
				break;
			case 'toggle':
				if (permission_exists('extension_setting_edit')) {
					$obj->toggle($extension_settings);
				}
				break;
			case 'delete':
				if (permission_exists('extension_setting_delete')) {
					$obj->extension_uuid = $extension_uuid;
					$obj->delete($extension_settings);
				}
				break;
		}

		//redirect the user
		header('Location: extension_settings.php?id='.urlencode($extension_uuid).'&'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get the count
	$sql = "select count(extension_setting_uuid) ";
	$sql .= "from v_extension_settings ";
	$sql .= "where extension_uuid = :extension_uuid ";
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "	lower(extension_setting_type) like :search ";
		$sql .= "	or lower(extension_setting_name) like :search ";
		$sql .= "	or lower(extension_setting_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	else {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		if (isset($sql_search)) {
			$sql .= "and ".$sql_search;
		}
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$parameters['extension_uuid'] = $extension_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//get the list
	$sql = "select ";
	//$sql .= "d.domain_name, ";
	$sql .= "extension_setting_uuid, ";
	$sql .= "extension_setting_type, ";
	$sql .= "extension_setting_name, ";
	$sql .= "extension_setting_value, ";
	$sql .= "cast(extension_setting_enabled as text), ";
	$sql .= "extension_setting_description ";
	$sql .= "from v_extension_settings as e ";
	//$sql .= ",v_domains as d ";
	$sql .= "where extension_uuid = :extension_uuid ";
	$sql .= "and (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
	//$sql .= "and d.domain_uuid = e.domain_uuid ";
	if (isset($_GET["search"])) {
		$sql .= "and (";
		$sql .= "	lower(extension_setting_type) like :search ";
		$sql .= "	or lower(extension_setting_name) like :search ";
		$sql .= "	or lower(extension_setting_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

	$sql .= order_by($order_by, $order, 'extension_setting_type', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$parameters['extension_uuid'] = $extension_uuid;
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$extension_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-extension_settings'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-extension_settings']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";

	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_add','name'=>'btn_add','link'=>'/app/extensions/extension_edit.php?id='.$extension_uuid]);

	if (permission_exists('extension_setting_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'extension_setting_edit.php?extension_uuid='.$extension_uuid]);
	}
	if (permission_exists('extension_setting_add') && $extension_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('extension_setting_edit') && $extension_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('extension_setting_delete') && $extension_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	//if (permission_exists('extension_setting_all')) {
	//	if ($_GET['show'] == 'all') {
	//		echo "		<input type='hidden' name='show' value='all'>\n";
	//	}
	//	else {
	//		echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all&id='.$extension_uuid]);
	//	}
	//}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'extension_settings.php?id='.$extension_uuid,'style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		<input type='hidden' name='id' value='".$extension_uuid."'>\n";
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('extension_setting_add') && $extension_settings) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('extension_setting_edit') && $extension_settings) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('extension_setting_delete') && $extension_settings) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-extension_settings']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	if (is_array($extension_settings) && @sizeof($extension_settings) != 0) {
		$x = 0;
		foreach ($extension_settings as $row) {
			$extension_setting_type = $row['extension_setting_type'];
			$extension_setting_type = strtolower($extension_setting_type);

			$label_extension_setting_type = $row['extension_setting_type'];
			$label_extension_setting_type = str_replace("_", " ", $label_extension_setting_type);
			$label_extension_setting_type = str_replace("-", " ", $label_extension_setting_type);
			$label_extension_setting_type = ucwords($label_extension_setting_type);

			if ($previous_extension_setting_type !== $row['extension_setting_type']) {
				echo "		<tr>";
				echo "			<td align='left' colspan='999'>&nbsp;</td>\n";
				echo "		</tr>";
				echo "		<tr>";
				echo "			<td align='left' colspan='999' nowrap='nowrap'><b>".escape($label_extension_setting_type)."</b></td>\n";
				echo "		</tr>";
				echo "<tr class='list-header'>\n";
				if (permission_exists('extension_setting_add') || permission_exists('extension_setting_edit') || permission_exists('extension_setting_delete')) {
					echo "	<th class='checkbox'>\n";
					echo "		<input type='checkbox' id='checkbox_all_".$extension_setting_type."' name='checkbox_all' onclick=\"list_all_toggle('".$extension_setting_type."'); checkbox_on_change(this);\">\n";
					echo "	</th>\n";
				}
				//if ($_GET['show'] == 'all' && permission_exists('extension_setting_all')) {
				//	echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
				//}

				//echo th_order_by('extension_setting_type', $text['label-extension_setting_type'], $order_by, $order);
				//echo th_order_by('extension_setting_name', $text['label-extension_setting_name'], $order_by, $order);
				//echo th_order_by('extension_setting_value', $text['label-extension_setting_value'], $order_by, $order);
				//echo th_order_by('extension_setting_enabled', $text['label-extension_setting_enabled'], $order_by, $order, null, "class='center'");
				echo "	<th>".$text['label-extension_setting_type']."</th>\n";
				echo "	<th>".$text['label-extension_setting_name']."</th>\n";
				echo "	<th>".$text['label-extension_setting_value']."</th>\n";
				echo "	<th class='center'>".$text['label-extension_setting_enabled']."</th>\n";

				echo "	<th class='hide-sm-dn'>".$text['label-extension_setting_description']."</th>\n";
				if (permission_exists('extension_setting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
					echo "	<td class='action-button'>&nbsp;</td>\n";
				}
				echo "</tr>\n";

			}
			if (permission_exists('extension_setting_edit')) {
				$list_row_url = "extension_setting_edit.php?id=".urlencode($row['extension_setting_uuid'])."&extension_uuid=".urlencode($extension_uuid);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('extension_setting_add') || permission_exists('extension_setting_edit') || permission_exists('extension_setting_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='extension_settings[$x][checked]' id='checkbox_".$x."' class='checkbox_".$extension_setting_type."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all_".$extension_setting_type."').checked = false; }\">\n";
				echo "		<input type='hidden' name='extension_settings[$x][uuid]' value='".escape($row['extension_setting_uuid'])."' />\n";
				echo "	</td>\n";
			}
			//if ($_GET['show'] == 'all' && permission_exists('extension_setting_all')) {
			//	echo "	<td>".escape($row['domain_name'])."</td>\n";
			//}
			echo "	<td>".escape($row['extension_setting_type'])."</td>\n";
			echo "	<td>".escape($row['extension_setting_name'])."</td>\n";
			echo "	<td>".escape($row['extension_setting_value'])."</td>\n";
			if (permission_exists('extension_setting_edit')) {
				echo "	<td class='no-link center'>\n";
				echo "		<input type='hidden' name='number_translations[$x][extension_setting_enabled]' value='".escape($row['extension_setting_enabled'])."' />\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['extension_setting_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['extension_setting_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['extension_setting_description'])."</td>\n";
			if (permission_exists('extension_setting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";

			//set the previous category
			$previous_extension_setting_type = $row['extension_setting_type'];
			$x++;
		}
		unset($extension_settings);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$id."' value='".$extension_uuid."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
