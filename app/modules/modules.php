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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
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
	if (permission_exists('module_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted data
	if (is_array($_POST['modules'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$modules = $_POST['modules'];
	}

//process the http post data by action
	if ($action != '' && is_array($modules) && @sizeof($modules) != 0) {
		switch ($action) {
			case 'start':
				$obj = new modules;
				$obj->start($modules);
				break;
			case 'stop':
				$obj = new modules;
				$obj->stop($modules);
				break;
			case 'toggle':
				if (permission_exists('module_edit')) {
					$obj = new modules;
					$obj->toggle($modules);
				}
				break;
			case 'delete':
				if (permission_exists('module_delete')) {
					$obj = new modules;
					$obj->delete($modules);
				}
				break;
		}

		header('Location: modules.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//connect to event socket
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//check connection status
	$esl_alive = false;
	if ($fp) {
		$esl_alive = true;
		fclose($fp);
	}

//warn if switch not running
	if (!$fp) {
		message::add($text['error-event-socket'], 'negative', 5000);
	}

//use the module class to get the list of modules from the db and add any missing modules
	$module = new modules;
	$module->db = $db;
	$module->dir = $_SESSION['switch']['mod']['dir'];
	$module->get_modules();
	$modules = $module->modules;
	$module_count = count($modules);
	$module->synch();
	$module->xml();
	$msg = $module->msg;

//show the msg
	if ($msg) {
		message::add($msg, 'negative', 5000);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//get includes and the title
	$document['title'] = $text['title-modules'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-modules']." (".$module_count.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('module_edit') && $modules && $fp) {
		echo button::create(['type'=>'button','label'=>$text['button-stop'],'icon'=>$_SESSION['theme']['button_icon_stop'],'onclick'=>"modal_open('modal-stop','btn_stop');"]);
		echo button::create(['type'=>'button','label'=>$text['button-start'],'icon'=>$_SESSION['theme']['button_icon_start'],'onclick'=>"modal_open('modal-start','btn_start');"]);
	}
	echo button::create(['type'=>'button','label'=>$text['button-refresh'],'icon'=>$_SESSION['theme']['button_icon_refresh'],'style'=>'margin-right: 15px;','link'=>'modules.php']);
	if (permission_exists('module_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'module_edit.php']);
	}
	if (permission_exists('module_edit') && $modules) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('module_delete') && $modules) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('module_edit') && $modules && $fp) {
		echo modal::create(['id'=>'modal-stop','type'=>'general','message'=>$text['confirm-stop_modules'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_stop','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('stop'); list_form_submit('form_list');"])]);
		echo modal::create(['id'=>'modal-start','type'=>'general','message'=>$text['confirm-start_modules'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_start','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('start'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('module_edit') && $modules) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('module_delete') && $modules) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-modules']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	function write_header($modifier) {
		global $fp, $text, $modules;
		$modifier = str_replace('/', '', $modifier);
		$modifier = str_replace('  ', ' ', $modifier);
		$modifier = str_replace(' ', '_', $modifier);
		$modifier = strtolower(trim($modifier));
		echo "\n";
		echo "<tr class='list-header'>\n";
		if (permission_exists('module_edit') || permission_exists('module_delete')) {
			echo "	<th class='checkbox'>\n";
			echo "		<input type='checkbox' id='checkbox_all_".$modifier."' name='checkbox_all' onclick=\"list_all_toggle('".$modifier."'); checkbox_on_change(this);\" ".($modules ?: "style='visibility: hidden;'").">\n";
			echo "	</th>\n";
		}
		echo "<th>".$text['label-label']."</th>\n";
		echo "<th class='hide-xs'>".$text['label-status']."</th>\n";
		if ($fp) {
			echo "<th class='center'>".$text['label-action']."</th>\n";
		}
		echo "<th class='center'>".$text['label-enabled']."</th>\n";
		echo "<th class='hide-sm-dn' style='min-width: 40%;'>".$text['label-description']."</th>\n";
		if (permission_exists('module_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
			echo "<td class='action-button'>&nbsp;</td>\n";
		}
		echo "</tr>\n";
	}
	if (is_array($modules) && @sizeof($modules) != 0) {
		$previous_category = '';
		foreach ($modules as $x => $row) {
			//write category and column headings
				if ($previous_category != $row["module_category"]) {
					echo "<tr>\n";
					echo "<td colspan='7' class='no-link'>\n";
					echo ($previous_category != '' ? '<br />' : null)."<b>".$row["module_category"]."</b>";
					echo "</td>\n";
					echo "</tr>\n";
					write_header($row["module_category"]);
				}
			if (permission_exists('module_edit')) {
				$list_row_url = "module_edit.php?id=".urlencode($row['module_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('module_edit') || permission_exists('module_delete')) {
				$modifier = strtolower(trim($row["module_category"]));
				$modifier = str_replace('/', '', $modifier);
				$modifier = str_replace('  ', ' ', $modifier);
				$modifier = str_replace(' ', '_', $modifier);
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='modules[$x][checked]' id='checkbox_".$x."' class='checkbox_".$modifier."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all_".$modifier."').checked = false; }\">\n";
				echo "		<input type='hidden' name='modules[$x][uuid]' value='".escape($row['module_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "   <td>";
			if (permission_exists('module_edit')) {
				echo "<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['module_label'])."</a>";
			}
			else {
				echo escape($row['module_label']);
			}
			echo "	</td>\n";
			if ($fp) {
				if ($module->active($row["module_name"])) {
					echo "	<td class='hide-xs'>".$text['label-running']."</td>\n";
					if (permission_exists('module_edit')) {
						echo "	<td class='no-link center'>";
						echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-stop'],'title'=>$text['button-stop'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('stop'); list_form_submit('form_list')"]);
						echo "	</td>\n";
					}
				}
				else {
					echo "	<td class='hide-xs'>\n";
					echo $row['module_enabled'] == 'true' ? "<strong style='color: red;'>".$text['label-stopped']."</strong>" : $text['label-stopped']." ".escape($notice);
					echo "	</td>\n";
					if (permission_exists('module_edit')) {
						echo "	<td class='no-link center'>";
						echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-start'],'title'=>$text['button-start'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('start'); list_form_submit('form_list')"]);
						echo "	</td>\n";
					}
				}
			}
			else{
				echo "   <td class='hide-xs'>".$text['label-unknown']."</td>\n";
			}
			if (permission_exists('module_edit')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['module_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo $text['label-'.$row['module_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row["module_description"])."&nbsp;</td>\n";
			if (permission_exists('module_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";

			$previous_category = $row["module_category"];

			$x++;
		}
	}
	unset($modules);

	echo "</table>\n";
	echo "<br />\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
