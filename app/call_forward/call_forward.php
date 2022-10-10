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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
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
	$text = $language->get($_SESSION['domain']['language']['code'], 'app/call_forward');

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

		header('Location: call_forward.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the search
	$search = strtolower($_GET["search"]);

//define select count query
	$sql = "select count(*) from v_extensions ";
	if ($_GET['show'] == "all" && permission_exists('call_forward_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (strlen($search) > 0) {
		$sql .= "and ( ";
		$sql .= "extension like :search ";
		$sql .= "or lower(description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
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
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($parameters);

//prepare the paging
	if ($is_included) {
		$rows_per_page = 10;
	}
	else {
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	}
	$params[] = "app_uuid=".$app_uuid;
	if ($search) { $params[] = "search=".$search; }
	if ($order_by) { $params[] = "order_by=".$order_by; }
	if ($order) { $params[] = "order=".$order; }
	if ($_GET['show'] == "all" && permission_exists('call_forward_all')) {
		$params[] .= "show=all";
	}
	$param = $params ? implode('&', $params) : null;
	unset($params);
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_extensions ";
	if ($_GET['show'] == "all" && permission_exists('call_forward_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (strlen($search) > 0) {
		$sql .= "and ( ";
		$sql .= "extension like :search ";
		$sql .= "or lower(description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
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
		$document['title'] = $text['title-call_forward'];
	}
	require_once "resources/header.php";

//show the content
	if ($is_included) {
		echo "<div class='action_bar sub'>\n";
		echo "	<div class='heading'><b>".$text['header-call_forward']."</b></div>\n";
		echo "	<div class='actions'>\n";
		if ($num_rows > 10) {
			echo button::create(['type'=>'button','label'=>$text['button-view_all'],'icon'=>'project-diagram','collapse'=>false,'link'=>PROJECT_PATH.'/app/call_forward/call_forward.php']);
		}
		echo "	</div>\n";
		echo "	<div style='clear: both;'></div>\n";
		echo "</div>\n";
	}
	else {
		echo "<div class='action_bar' id='action_bar'>\n";
		echo "	<div class='heading'><b>".$text['header-call_forward']." (".$num_rows.")</b></div>\n";
		echo "	<div class='actions'>\n";

		if ($extensions) {
			if (permission_exists('call_forward')) {
				echo button::create(['type' => 'button', 'label' => $text['label-call_forward'], 'icon' => $_SESSION['theme']['button_icon_toggle'], 'collapse' => false, 'name' => 'btn_toggle_cfwd', 'onclick' => "list_action_set('toggle_call_forward'); modal_open('modal-toggle','btn_toggle');"]);
			}
			if (permission_exists('follow_me')) {
				echo button::create(['type' => 'button', 'label' => $text['label-follow_me'], 'icon' => $_SESSION['theme']['button_icon_toggle'], 'collapse' => false, 'name' => 'btn_toggle_follow', 'onclick' => "list_action_set('toggle_follow_me'); modal_open('modal-toggle','btn_toggle');"]);
			}
			if (permission_exists('do_not_disturb')) {
				echo button::create(['type' => 'button', 'label' => $text['label-dnd'], 'icon' => $_SESSION['theme']['button_icon_toggle'], 'collapse' => false, 'name' => 'btn_toggle_dnd', 'onclick' => "list_action_set('toggle_do_not_disturb'); modal_open('modal-toggle','btn_toggle');"]);
			}
		}
		if ($_GET['show'] !== 'all' && permission_exists('call_forward_all')) {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all'.($params ? '&'.implode('&', $params) : null)]);
		}
		echo 		"<form id='form_search' class='inline' method='get'>\n";
		if ($_GET['show'] == 'all' && permission_exists('call_forward_all')) {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
		echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
		//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_forward.php','style'=>($search == '' ? 'display: none;' : null)]);
		if ($paging_controls_mini != '') {
			echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
		}
		echo "		</form>\n";
		echo "	</div>\n";
		echo "	<div style='clear: both;'></div>\n";
		echo "</div>\n";

		if ($extensions) {
			echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_form_submit('form_list');"])]);
		}

		echo $text['description-call_routing']."\n";
		echo "<br /><br />\n";

		echo "<form id='form_list' method='post'>\n";
		if ($_GET['show'] == 'all' && permission_exists('call_forward_all')) {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		echo "<input type='hidden' id='action' name='action' value=''>\n";
		echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";
	}

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (!$is_included) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($extensions ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
		if ($_GET['show'] == "all" && permission_exists('call_forward_all')) {
			echo "<th>".$text['label-domain']."</th>\n";
		}
	}
	echo "	<th>".$text['label-extension']."</th>\n";
	if (permission_exists('call_forward')) {
		echo "	<th>".$text['label-call_forward']."</th>\n";
	}
	if (permission_exists('follow_me')) {
		echo "	<th>".$text['label-follow_me']."</th>\n";
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
			$list_row_url = PROJECT_PATH."/app/call_forward/call_forward_edit.php?id=".$row['extension_uuid']."&return_url=".urlencode($_SERVER['REQUEST_URI']);
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (!$is_included && $extensions) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='extensions[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='extensions[$x][uuid]' value='".escape($row['extension_uuid'])."' />\n";
				echo "	</td>\n";

				if ($_GET['show'] == "all" && permission_exists('call_forward_all')) {
					if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
						$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
					}
					else {
						$domain = $text['label-global'];
					}
					echo "	<td>".escape($domain)."</td>\n";
				}
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
