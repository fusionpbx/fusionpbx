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
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/calls');

//get posted data
	if (is_array($_POST['extensions'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$extensions = $_POST['extensions'];
	}

//process the http post data by action
	if ($action != '' && is_array($extensions) && @sizeof($extensions) != 0) {
		switch ($action) {
			case 'toggle_call_forward':
				if (permission_exists('call_forward')) {
					$obj = new call_forward;
					$obj->toggle($extensions);
				}
				break;
			case 'toggle_follow_me':
				if (permission_exists('follow_me')) {
					$obj = new follow_me;
					$obj->toggle($extensions);
				}
				break;
			case 'toggle_do_not_disturb':
				if (permission_exists('do_not_disturb')) {
					$obj = new do_not_disturb;
					$obj->toggle($extensions);
				}
				break;
		}

		header('Location: calls.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//handle search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and ( ";
		$sql_search .= "extension like :search ";
		$sql_search .= "or lower(description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//define select count query
	$sql = "select count(*) from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and enabled = 'true' ";
	if (!permission_exists('extension_edit')) {
		if (is_array($_SESSION['user']['extension']) && count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//used to hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	if ($is_included) {
		$rows_per_page = 10;
	}
	else {
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	}
	$param = "&search=".$search;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and enabled = 'true' ";
	if (!permission_exists('extension_edit')) {
		if (is_array($_SESSION['user']['extension']) && count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//used to hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$sql .= $sql_search;
	$sql .= order_by($order_by, $order, 'extension', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include header
	if (!$is_included) {
		$document['title'] = $text['title-call_routing'];
	}
	require_once "resources/header.php";

//javascript for toggle select box
	echo "<script language='javascript' type='text/javascript'>\n";
	echo "	function toggle_select() {\n";
	echo "		$('#call_control_feature').fadeToggle(400, function() {\n";
	echo "			document.getElementById('call_control_feature').selectedIndex = 0;\n";
	echo "			document.getElementById('call_control_feature').focus();\n";
	echo "		});\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	if ($is_included) {
		echo "<div class='action_bar sub'>\n";
		echo "	<div class='heading'><b>".$text['header-call_routing']."</b></div>\n";
		echo "	<div class='actions'>\n";
		if ($num_rows > 10) {
			echo button::create(['type'=>'button','label'=>$text['button-view_all'],'icon'=>'project-diagram','collapse'=>false,'link'=>PROJECT_PATH.'/app/calls/calls.php']);
		}
		echo "	</div>\n";
		echo "	<div style='clear: both;'></div>\n";
		echo "</div>\n";
	}
	else {
		echo "<div class='action_bar' id='action_bar'>\n";
		echo "	<div class='heading'><b>".$text['header-call_routing']." (".$num_rows.")</b></div>\n";
		echo "	<div class='actions'>\n";
		if ($extensions) {
			echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','onclick'=>"toggle_select(); this.blur();"]);
		}
		echo 		"<select class='formfld' style='display: none; width: auto;' id='call_control_feature' onchange=\"if (this.selectedIndex != 0) { modal_open('modal-toggle','btn_toggle'); }\">";
		echo "			<option value='' selected='selected'>".$text['label-select']."</option>";
		if (permission_exists('call_forward')) {
			echo "		<option value='call_forward'>".$text['label-call-forward']."</option>";
		}
		if (permission_exists('follow_me')) {
			echo "		<option value='follow_me'>".$text['label-follow-me']."</option>";
		}
		if (permission_exists('do_not_disturb')) {
			echo "		<option value='do_not_disturb'>".$text['label-dnd']."</option>";
		}
		echo "		</select>";
		echo 		"<form id='form_search' class='inline' method='get'>\n";
		echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
		echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
		echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'calls.php','style'=>($search == '' ? 'display: none;' : null)]);
		if ($paging_controls_mini != '') {
			echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
		}
		echo "		</form>\n";
		echo "	</div>\n";
		echo "	<div style='clear: both;'></div>\n";
		echo "</div>\n";

		if ($extensions) {
			echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle_' + document.getElementById('call_control_feature').options[document.getElementById('call_control_feature').selectedIndex].value); list_form_submit('form_list');"])]);
		}

		echo $text['description-call_routing']."\n";
		echo "<br /><br />\n";

		echo "<form id='form_list' method='post'>\n";
		echo "<input type='hidden' id='action' name='action' value=''>\n";
		echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";
	}

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (!$is_included) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($extensions ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo "	<th>".$text['label-extension']."</th>\n";
	if (permission_exists('call_forward')) {
		echo "	<th>".$text['label-call-forward']."</th>\n";
	}
	if (permission_exists('follow_me')) {
		echo "	<th>".$text['label-follow-me']."</th>\n";
	}
	if (permission_exists('do_not_disturb')) {
		echo "	<th>".$text['label-dnd']."</th>\n";
	}
	echo "	<th class='".($is_included ? 'hide-md-dn' : 'hide-sm-dn')."'>".$text['label-description']."</th>\n";
	if ($_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($extensions)) {
		$x = 0;
		foreach($extensions as $row) {
			$list_row_url = PROJECT_PATH."/app/calls/call_edit.php?id=".$row['extension_uuid']."&return_url=".urlencode($_SERVER['REQUEST_URI']);
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (!$is_included && $extensions) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='extensions[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='extensions[$x][uuid]' value='".escape($row['extension_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td><a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['extension'])."</a></td>\n";
			if (permission_exists('call_forward')) {
				//-- inline toggle -----------------
				//$button_label = $row['forward_all_enabled'] == 'true' ? ($row['forward_all_destination'] != '' ? escape(format_phone($row['forward_all_destination'])) : '('.$text['label-invalid'].')') : null;
				//if (!$is_included) {
				//	echo "	<td class='no-link'>";
				//	echo button::create(['type'=>'submit','class'=>'link','label'=>($button_label != '' ? $button_label : $text['label-disabled']),'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle_call_forward'); list_form_submit('form_list')"]);
				//	echo "	</td>\n";
				//}
				//else {
				//	echo "	<td>".$button_label."</td>";
				//}
				//unset($button_label);
				//----------------------------------

				echo "	<td>\n";
				echo $row['forward_all_enabled'] == 'true' ? escape(format_phone($row['forward_all_destination'])) : '&nbsp;';
				echo "	</td>\n";
			}
			if (permission_exists('follow_me')) {
				//-- inline toggle -----------------
				//get destination count
				//if ($row['follow_me_enabled'] == 'true' && is_uuid($row['follow_me_uuid'])) {
				//	$sql = "select count(*) from v_follow_me_destinations ";
				//	$sql .= "where follow_me_uuid = :follow_me_uuid ";
				//	$sql .= "and domain_uuid = :domain_uuid ";
				//	$parameters['follow_me_uuid'] = $row['follow_me_uuid'];
				//	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				//	$database = new database;
				//	$follow_me_destination_count = $database->select($sql, $parameters, 'column');
				//	$button_label = $follow_me_destination_count ? $text['label-enabled'].' ('.$follow_me_destination_count.')' : $text['label-invalid'];
				//	unset($sql, $parameters);
				//}
				//if (!$is_included) {
				//	echo "	<td class='no-link'>\n";
				//	echo button::create(['type'=>'submit','class'=>'link','label'=>($button_label != '' ? $button_label : $text['label-disabled']),'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle_follow_me'); list_form_submit('form_list')"]);
				//	echo "	</td>\n";
				//}
				//else {
				//	echo "	<td>".$button_label."</td>";
				//}
				//unset($button_label);
				//----------------------------------

				//get destination count
				$follow_me_destination_count = 0;
				if ($row['follow_me_enabled'] == 'true' && is_uuid($row['follow_me_uuid'])) {
					$sql = "select count(*) from v_follow_me_destinations ";
					$sql .= "where follow_me_uuid = :follow_me_uuid ";
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['follow_me_uuid'] = $row['follow_me_uuid'];
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$database = new database;
					$follow_me_destination_count = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);
				}
				echo "	<td>\n";
				echo $follow_me_destination_count ? $text['label-enabled'].' ('.$follow_me_destination_count.')' : '&nbsp;';
				echo "	</td>\n";
			}
			if (permission_exists('do_not_disturb')) {
				//-- inline toggle -----------------
				//$button_label = $row['do_not_disturb'] == 'true' ? $text['label-enabled'] : null;
				//if (!$is_included) {
				//	echo "	<td class='no-link'>";
				//	echo button::create(['type'=>'submit','class'=>'link','label'=>($button_label != '' ? $button_label : $text['label-disabled']),'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle_do_not_disturb'); list_form_submit('form_list')"]);
				//	echo "	</td>\n";
				//}
				//else {
				//	echo "	<td>".$button_label."</td>";
				//}
				//----------------------------------

				echo "	<td>\n";
				echo $row['do_not_disturb'] == 'true' ? $text['label-enabled'] : '&nbsp;';
				echo "	</td>\n";
			}
			echo "	<td class='description overflow ".($is_included ? 'hide-md-dn' : 'hide-sm-dn')."'>".escape($row['description'])."&nbsp;</td>\n";
			if ($_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($extensions);
	}

	echo "</table>\n";

	if (!$is_included) {
		echo "<br />\n";
		echo "<div align='center'>".$paging_controls."</div>\n";

		echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

		echo "</form>\n";

		require_once "resources/footer.php";
	}

?>
