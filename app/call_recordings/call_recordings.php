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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
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

//set additional variables
	$search = $_GET["search"] ?? '';
	$show = $_GET["show"] ?? '';

//get the http post data
	if (!empty($_POST['call_recordings']) && is_array($_POST['call_recordings'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$call_recordings = $_POST['call_recordings'];
	}

//process the http post data by action
	if (!empty($action) && is_array($call_recordings) && @sizeof($call_recordings) != 0) {
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
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//add the search string
	if (!empty($search)) {
		$search =  strtolower($_GET["search"]);
	}

//set the time zone
	if (!empty($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}
	$parameters['time_zone'] = $time_zone;

//get the count
	//$sql = "select count(*) ";
	//$sql .= "from view_call_recordings ";
	//$sql .= "where true ";
	//if ($_GET['show'] != "all" || !permission_exists('call_recording_all')) {
	//	$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
	//	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	//}
	//if (isset($search)) {
	//	$sql .= "and (";
	//	$sql .= "	lower(call_recording_name) like :search ";
	//	$sql .= "	or lower(call_recording_path) like :search ";
	//	$sql .= ") ";
	//	$parameters['search'] = '%'.$search.'%';
	//}
	//$database = new database;
	//$num_rows = $database->select($sql, $parameters, 'column');

//prepare some of the paging values
	$rows_per_page = (!empty($_SESSION['domain']['paging']['numeric'])) ? $_SESSION['domain']['paging']['numeric'] : 50;
	$page = $_GET['page'] ?? '';
	if (empty($page)) { $page = 0; $_GET['page'] = 0; }
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select r.domain_uuid, d.domain_name, r.call_recording_uuid, r.call_direction, ";
	$sql .= "r.call_recording_name, r.call_recording_path, r.call_recording_length, ";
	$sql .= "r.caller_id_name, r.caller_id_number, r.caller_destination, r.destination_number, ";
	$sql .= "to_char(timezone(:time_zone, r.call_recording_date), 'DD Mon YYYY') as call_recording_date_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, r.call_recording_date), 'HH12:MI:SS am') as call_recording_time_formatted \n";
	$sql .= "from view_call_recordings as r, v_domains as d ";
	//$sql .= "from v_call_recordings as r, v_domains as d ";
	$sql .= "where true ";
	if ($show != "all" || !permission_exists('call_recording_all')) {
		$sql .= "and (r.domain_uuid = :domain_uuid or r.domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	$sql .= "and r.domain_uuid = d.domain_uuid ";
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "	lower(r.call_direction) like :search ";
		$sql .= "	or lower(r.caller_id_name) like :search ";
		$sql .= "	or lower(r.caller_id_number) like :search ";
		$sql .= "	or lower(r.caller_destination) like :search ";
		$sql .= "	or lower(r.destination_number) like :search ";
		$sql .= "	or lower(r.call_recording_name) like :search ";
		$sql .= "	or lower(r.call_recording_path) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'r.call_recording_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$call_recordings = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//count the results
	$result_count = is_array($call_recordings) ? sizeof($call_recordings) : 0;

//limit the number of results
	if (!empty($_SESSION['cdr']['limit']['numeric']) && $_SESSION['cdr']['limit']['numeric'] > 0) {
		$num_rows = $_SESSION['cdr']['limit']['numeric'];
	}

//prepare to page the results
	$param = "&search=".urlencode($search);
	if ($show == "all" && permission_exists('call_recording_all')) {
		$param .= "&show=all";
	}
	list($paging_controls_mini, $rows_per_page) = paging($num_rows ?? null, $param, $rows_per_page, true, $result_count); //top
	list($paging_controls, $rows_per_page) = paging($num_rows ?? null, $param, $rows_per_page, false, $result_count); //bottom

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-call_recordings'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_recordings']." </b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('call_recording_download_add') && $call_recordings) {
		echo button::create(['type'=>'button','label'=>$text['button-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'collapse'=>'hide-xs','onclick'=>"list_action_set('download'); list_form_submit('form_list');"]);
	}
	if (permission_exists('call_recording_delete') && $call_recordings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-left: 15px;','collapse'=>'hide-xs','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('call_recording_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?type='.urlencode($destination_type ?? '').'&show=all'.(!empty($search) ? "&search=".urlencode($search) : null)]);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=\"$('#btn_reset').hide(); $('#btn_search').show();\">";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>(!empty($search) ? 'display: none;' : null),'collapse'=>'hide-xs']);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_recordings.php','style'=>(empty($search) ? 'display: none;' : null),'collapse'=>'hide-xs']);
	if (!empty($paging_controls_mini)) {
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
	$col_count = 8;
	if ($show == "all" && permission_exists('call_recording_all')) {
		$col_count++;
	}
	if (permission_exists('call_recording_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(empty($call_recordings) ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
		$col_count++;
	}
	if ($show == "all" && permission_exists('call_recording_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param, "class='shrink'");
	}
	echo th_order_by('caller_id_name', $text['label-caller_id_name'], $order_by, $order, null, "class='hide-sm-dn shrink'");
	echo th_order_by('caller_id_number', $text['label-caller_id_number'], $order_by, $order, null, "class='pct-10'");
	echo th_order_by('caller_destination', $text['label-caller_destination'], $order_by, $order, null, "class='hide-sm-dn shrink'");
	echo th_order_by('destination_number', $text['label-destination_number'], $order_by, $order, null, "class='hide-sm-dn shrink'");
	echo th_order_by('call_recording_name', $text['label-call_recording_name'], $order_by, $order, null, "class='pct-40 hide-sm-dn'");
	if (permission_exists('call_recording_play') || permission_exists('call_recording_download')) {
		echo "<th class='shrink center'>".$text['label-recording']."</th>\n";
		$col_count++;
	}
	echo th_order_by('call_recording_length', $text['label-call_recording_length'], $order_by, $order, null, "class='right hide-sm-dn shrink'");
	echo th_order_by('call_recording_date', $text['label-call_recording_date'], $order_by, $order, null, "class='center'");
	echo th_order_by('call_direction', $text['label-call_direction'], $order_by, $order, null, "class='hide-sm-dn shrink'");
	if (permission_exists('xml_cdr_details')) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($call_recordings) && @sizeof($call_recordings) != 0) {
		$x = 0;
		foreach ($call_recordings as $row) {
			//add padding to the call recording length
			$call_recording_length_padding = (!empty($row['call_recording_length'])) ? str_pad($row['call_recording_length'], 2, '0', STR_PAD_LEFT) : '';

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
				echo "		<input type='checkbox' name='call_recordings[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='call_recordings[$x][uuid]' value='".escape($row['call_recording_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == "all" && permission_exists('call_recording_all')) {
				echo "	<td class='overflow hide-sm-dn shrink'>".escape($row['domain_name'])."</td>\n";
			}
			echo "	<td class='hide-sm-dn shrink'>".escape($row['caller_id_name'])."</td>\n";
			echo "	<td class='overflow'>".escape($row['caller_id_number'])."</td>\n";
			echo "	<td class='overflow hide-sm-dn shrink'>".escape($row['caller_destination'])."</td>\n";
			echo "	<td class='overflow hide-sm-dn shrink'>".escape($row['destination_number'])."</td>\n";
			echo "	<td class='hide-sm-dn nowrap'>".escape($row['call_recording_name'])."</td>\n";
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
			echo "	<td class='right overflow hide-sm-dn shrink'>".($row['call_recording_length'] <= 59 ? '0:' : null).escape($call_recording_length_padding)."</td>\n";
			echo "	<td class='center no-wrap'>".escape($row['call_recording_date_formatted'])." <span class='hide-sm-dn'>".escape($row['call_recording_time_formatted'])."</span></td>\n";
			echo "	<td class='left hide-sm-dn shrink'>".($row['call_direction'] != '' ? escape($text['label-'.$row['call_direction']]) : null)."</td>\n";
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
