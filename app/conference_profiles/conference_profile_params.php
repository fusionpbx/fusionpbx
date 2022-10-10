<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('conference_profile_param_view')) {
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
	if (is_array($_POST['conference_profile_params'])) {
		$action = $_POST['action'];
		$conference_profile_uuid = $_POST['conference_profile_uuid'];
		$conference_profile_params = $_POST['conference_profile_params'];
	}

//process the http post data by action
	if ($action != '' && is_array($conference_profile_params) && @sizeof($conference_profile_params) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('conference_profile_param_edit')) {
					$obj = new conference_profiles;
					$obj->conference_profile_uuid = $conference_profile_uuid;
					$obj->toggle_params($conference_profile_params);
				}
				break;
			case 'delete':
				if (permission_exists('conference_profile_param_delete')) {
					$obj = new conference_profiles;
					$obj->conference_profile_uuid = $conference_profile_uuid;
					$obj->delete_params($conference_profile_params);
				}
				break;
		}

		header('Location: conference_profile_edit.php?id='.urlencode($conference_profile_uuid));
		exit;
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//prepare to page the results
	$sql = "select count(conference_profile_param_uuid) ";
	$sql .= "from v_conference_profile_params ";
	$sql .= "where conference_profile_uuid = :conference_profile_uuid ";
	$parameters['conference_profile_uuid'] = $conference_profile_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&id=".$conference_profile_uuid;
	if (isset($_GET['page'])) {
		$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;
	}

//get the list
	$sql = str_replace('count(conference_profile_param_uuid)', '*', $sql);
	$sql .= order_by($order_by, $order, 'profile_param_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/app/conference_profiles/conference_profile_params.php');

//show the content
	echo "<div class='action_bar' id='action_bar_sub'>\n";
	echo "	<div class='heading'><b id='heading_sub'>".$text['title-conference_profile_params']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','id'=>'action_bar_sub_button_back','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'collapse'=>'hide-xs','style'=>'margin-right: 15px; display: none;','link'=>'conference_profiles.php']);
	if (permission_exists('conference_profile_param_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','collapse'=>'hide-xs','link'=>'conference_profile_param_edit.php?conference_profile_uuid='.escape($_GET['id'])]);
	}
	if (permission_exists('conference_profile_param_edit') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','collapse'=>'hide-xs','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('conference_profile_param_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','collapse'=>'hide-xs','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 	"</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('conference_profile_param_edit') && $result) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('conference_profile_param_delete') && $result) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-conference_profile_param']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post' action='conference_profile_params.php'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='conference_profile_uuid' value=\"".escape($conference_profile_uuid)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('conference_profile_param_edit') || permission_exists('conference_profile_param_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($result ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('profile_param_name', $text['label-profile_param_name'], $order_by, $order, null, null, $param);
	echo th_order_by('profile_param_value', $text['label-profile_param_value'], $order_by, $order, null, "class='pct-40'", $param);
	echo th_order_by('profile_param_enabled', $text['label-profile_param_enabled'], $order_by, $order, null, "class='center'", $param);
	echo th_order_by('profile_param_description', $text['label-profile_param_description'], $order_by, $order, null, "class='hide-sm-dn'", $param);
	if (permission_exists('conference_profile_param_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		$x = 0;
		foreach ($result as $row) {
			if (permission_exists('conference_profile_param_edit')) {
				$list_row_url = 'conference_profile_param_edit.php?conference_profile_uuid='.urlencode($row['conference_profile_uuid']).'&id='.urlencode($row['conference_profile_param_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('conference_profile_param_edit') || permission_exists('conference_profile_param_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='conference_profile_params[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='conference_profile_params[$x][uuid]' value='".escape($row['conference_profile_param_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('conference_profile_param_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['profile_param_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['profile_param_name']);
			}
			echo "	</td>\n";
			echo "	<td class='overflow'>".escape($row['profile_param_value'])."&nbsp;</td>\n";
			if (permission_exists('conference_profile_param_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['profile_param_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['profile_param_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['profile_param_description'])."&nbsp;</td>\n";
			if (permission_exists('conference_profile_param_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($result);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//make sub action bar sticky
	echo "<script>\n";
	echo "	window.addEventListener('scroll', function(){\n";
	echo "		action_bar_scroll('action_bar_sub', 255, heading_modify, heading_restore);\n";
	echo "	}, false);\n";

	echo "	function heading_modify() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'inline-block';\n";
	echo "	}\n";

	echo "	function heading_restore() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'none';\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>