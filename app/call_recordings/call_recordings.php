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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('call_recording_view')) {
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
	if (is_array($_POST['call_recordings'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$call_recordings = $_POST['call_recordings'];
	}

//process the http post data by action
	if ($action != '' && is_array($call_recordings) && @sizeof($call_recordings) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('call_recording_delete')) {
					$obj = new call_recordings;
					$obj->delete($call_recordings);
				}
				break;
		}

		//redirect the user
		header('Location: call_recordings.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search string
	if (isset($_GET["search"])) {
		$search =  strtolower($_GET["search"]);
		$sql_search = "and (";
		$sql_search .= "	lower(call_recording_name) like :search ";
		$sql_search .= "	or lower(call_recording_path) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	$sql = "select count(call_recording_uuid) from v_call_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	if (isset($sql_search)) {
		$sql .= $sql_search;
	}
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(call_recording_uuid)', '*', $sql);
	$sql .= order_by($order_by, $order, 'call_recording_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$call_recordings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-call_recordings'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_recordings']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('call_recording_download_add') && $call_recordings) {
		echo button::create(['type'=>'button','label'=>$text['button-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'collapse'=>'hide-xs','onclick'=>"list_action_set('download'); list_form_submit('form_list');"]);
	}
	if (permission_exists('call_recording_delete') && $call_recordings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-left: 15px;','collapse'=>'hide-xs','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null),'collapse'=>'hide-xs']);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_recordings.php','style'=>($search == '' ? 'display: none;' : null),'collapse'=>'hide-xs']);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('call_recording_delete') && $call_recordings) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-call_recordings']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	$col_count = 4;
	if (permission_exists('call_recording_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($call_recordings ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
		$col_count++;
	}
	echo th_order_by('call_recording_name', $text['label-call_recording_name'], $order_by, $order, null, "class='pct-40'");
	if (permission_exists('call_recording_play') || permission_exists('call_recording_download')) {
		echo "<th class='shrink center'>".$text['label-recording']."</th>\n";
		$col_count++;
	}
	echo th_order_by('call_recording_length', $text['label-call_recording_length'], $order_by, $order, null, "class='right'");
	echo th_order_by('call_recording_date', $text['label-call_recording_date'], $order_by, $order);
	echo th_order_by('call_direction', $text['label-call_direction'], $order_by, $order);
	if (permission_exists('xml_cdr_details')) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($call_recordings) && @sizeof($call_recordings) != 0) {
		$x = 0;
		foreach ($call_recordings as $row) {
			//playback progress bar
			if (permission_exists('call_recording_play')) {
				echo "<tr class='list-row' id='recording_progress_bar_".escape($row['call_recording_uuid'])."' style='display: none;'><td class='playback_progress_bar_background' style='padding: 0; border: none;' colspan='".$col_count."'><span class='playback_progress_bar' id='recording_progress_".escape($row['call_recording_uuid'])."'></span></td>".(permission_exists('xml_cdr_details') ? "<td class='action-button' style='border-bottom: none !important;'></td>" : null)."</tr>\n";
				echo "<tr class='list-row' style='display: none;'><td></td></tr>\n"; // dummy row to maintain alternating background color
			}
			if (permission_exists('call_recording_play')) {
				$list_row_url = "javascript:recording_play('".escape($row['call_recording_uuid'])."');";
			}
			echo "<tr class='list-row' href=\"".$list_row_url."\">\n";
			if (permission_exists('call_recording_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='call_recordings[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='call_recordings[$x][uuid]' value='".escape($row['call_recording_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td class='overflow'>".escape($row['call_recording_name'])."</td>\n";
			if (permission_exists('call_recording_play') || permission_exists('call_recording_download')) {
				echo "	<td class='middle button center no-link no-wrap'>";
				if (file_exists($row['call_recording_path'].'/'.$row['call_recording_name'])) {
					if (permission_exists('call_recording_play')) {
						$recording_file_ext = pathinfo($row['call_recording_name'], PATHINFO_EXTENSION);
						switch ($recording_file_ext) {
							case "wav" : $recording_type = "audio/wav"; break;
							case "mp3" : $recording_type = "audio/mpeg"; break;
							case "ogg" : $recording_type = "audio/ogg"; break;
						}
						echo "<audio id='recording_audio_".escape($row['call_recording_uuid'])."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".escape($row['call_recording_uuid'])."')\" onended=\"recording_reset('".escape($row['call_recording_uuid'])."');\" src='download.php?id=".urlencode($row['call_recording_uuid'])."' type='".$recording_type."'></audio>";
						echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.escape($row['call_recording_uuid']),'onclick'=>"recording_play('".escape($row['call_recording_uuid'])."')"]);
					}
					if (permission_exists('call_recording_download')) {
						echo button::create(['type'=>'button','title'=>$text['label-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'link'=>'download.php?id='.urlencode($row['call_recording_uuid']).'&binary']);
					}
				}
				echo "	</td>\n";
			}
			echo "	<td class='right'>".($row['call_recording_length'] <= 59 ? '0:' : null).escape(str_pad($row['call_recording_length'], 2, '0', STR_PAD_LEFT))."</td>\n";
			$call_recording_date = explode(' ', $row['call_recording_date']);
			echo "	<td class='no-wrap'>".escape($call_recording_date['0'])." <span class='hide-sm-dn'>".escape($call_recording_date[1])."</span></td>\n";
			echo "	<td>".($row['call_direction'] != '' ? escape($text['label-'.$row['call_direction']]) : null)."</td>\n";
			if (permission_exists('xml_cdr_details')) {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-view'],'icon'=>$_SESSION['theme']['button_icon_view'],'link'=>PROJECT_PATH.'/app/xml_cdr/xml_cdr_details.php?id='.urlencode($row['call_recording_uuid'])]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($call_recordings);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
