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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('contact_view')) {
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
	if (is_array($_POST['contacts'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$contacts = $_POST['contacts'];
	}

//process the http post data by action
	if ($action != '' && is_array($contacts) && @sizeof($contacts) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('contact_delete')) {
					$obj = new contacts;
					$obj->delete($contacts);
				}
				break;
		}

		header('Location: contacts.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//retrieve current user's assigned groups (uuids)
	foreach ($_SESSION['groups'] as $group_data) {
		$user_group_uuids[] = $group_data['group_uuid'];
	}

//add user's uuid to group uuid list to include private (non-shared) contacts
	$user_group_uuids[] = $_SESSION["user_uuid"];

//get contact settings - sync sources
	$sql = "select contact_uuid, contact_setting_value ";
	$sql .= "from v_contact_settings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_setting_category = 'sync' ";
	$sql .= "and contact_setting_subcategory = 'source' ";
	$sql .= "and contact_setting_name = 'array' ";
	$sql .= "and contact_setting_value <> '' ";
	$sql .= "and contact_setting_value is not null ";
	if (!permission_exists('contact_domain_view')) {
		$sql .= "and ( "; //only contacts assigned to current user's group(s) and those not assigned to any group
		$sql .= "	contact_uuid in ( ";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where ";
		if (is_array($user_group_uuids) && @sizeof($user_group_uuids) != 0) {
			foreach ($user_group_uuids as $index => $user_group_uuid) {
				if (is_uuid($user_group_uuid)) {
					$sql_where_or[] = "group_uuid = :group_uuid_".$index;
					$parameters['group_uuid_'.$index] = $user_group_uuid;
				}
			}
			if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
				$sql .= " ( ".implode(' or ', $sql_where_or)." ) ";
			}
			unset($sql_where_or, $index, $user_group_uuid);
		}
		$sql .= "		and domain_uuid = :domain_uuid ";
		$sql .= "	) ";
		$sql .= "	or ";
		$sql .= "	contact_uuid not in ( ";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where group_uuid = :group_uuid ";
		$sql .= "		and domain_uuid = :domain_uuid ";
		$sql .= "	) ";
		$sql .= ") ";
	}
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['group_uuid'] = $_SESSION['group_uuid'];
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	if (is_array($result) && @sizeof($result) != 0) {
		foreach($result as $row) {
			$contact_sync_sources[$row['contact_uuid']][] = $row['contact_setting_value'];
		}
	}
	unset($sql, $parameters, $result);

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		if (is_numeric($search)) {
			$sql_search .= "and contact_uuid in ( ";
			$sql_search .= "	select contact_uuid from v_contact_phones ";
			$sql_search .= "	where phone_number like :search ";
			$sql_search .= ") ";
		}
		else {
			//open container
				$sql_search .= "and ( ";
			//search contact
				$sql_search .= "contact_uuid in ( ";
				$sql_search .= "	select contact_uuid from v_contacts ";
				$sql_search .= "	where domain_uuid = :domain_uuid ";
				$sql_search .= "	and ( ";
				$sql_search .= "		lower(contact_organization) like :search or ";
				$sql_search .= "		lower(contact_name_given) like :search or ";
				$sql_search .= "		lower(contact_name_family) like :search or ";
				$sql_search .= "		lower(contact_nickname) like :search or ";
				$sql_search .= "		lower(contact_title) like :search or ";
				$sql_search .= "		lower(contact_category) like :search or ";
				$sql_search .= "		lower(contact_role) like :search or ";
				$sql_search .= "		lower(contact_url) like :search or ";
				$sql_search .= "		lower(contact_time_zone) like :search or ";
				$sql_search .= "		lower(contact_note) like :search or ";
				$sql_search .= "		lower(contact_type) like :search ";
				$sql_search .= "	) ";
				$sql_search .= ") ";
			//search contact emails
				if (permission_exists('contact_email_view')) {
					$sql_search .= "or contact_uuid in ( ";
					$sql_search .= "	select contact_uuid from v_contact_emails ";
					$sql_search .= "	where domain_uuid = :domain_uuid ";
					$sql_search .= "	and ( ";
					$sql_search .= "		lower(email_address) like :search or ";
					$sql_search .= "		lower(email_description) like :search ";
					$sql_search .= "	) ";
					$sql_search .= ") ";
				}
			//search contact notes
				if (permission_exists('contact_note_view')) {
					$sql_search .= "or contact_uuid in ( ";
					$sql_search .= "	select contact_uuid from v_contact_notes ";
					$sql_search .= "	where domain_uuid = :domain_uuid ";
					$sql_search .= "	and lower(contact_note) like :search ";
					$sql_search .= ") ";
				}
			//close container
				$sql_search .= ") ";
		}
		$parameters['search'] = '%'.$search.'%';
	}

//build query for paging and list
	$sql = "select count(*) ";
	$sql .= "from v_contacts as c ";
	$sql .= "where true ";
	if ($_GET['show'] != "all" || !permission_exists('contact_all')) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!permission_exists('contact_domain_view')) {
		$sql .= "and ( "; //only contacts assigned to current user's group(s) and those not assigned to any group
		$sql .= "	contact_uuid in ( ";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where ";
		if (is_array($user_group_uuids) && @sizeof($user_group_uuids) != 0) {
			foreach ($user_group_uuids as $index => $user_group_uuid) {
				if (is_uuid($user_group_uuid)) {
					$sql_where_or[] = "group_uuid = :group_uuid_".$index;
					$parameters['group_uuid_'.$index] = $user_group_uuid;
				}
			}
			if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
				$sql .= " ( ".implode(' or ', $sql_where_or)." ) ";
			}
			unset($sql_where_or, $index, $user_group_uuid);
		}
		$sql .= "		and domain_uuid = :domain_uuid ";
		$sql .= "	) ";
		$sql .= "	or contact_uuid in ( ";
		$sql .= "		select contact_uuid from v_contact_users ";
		$sql .= "		where user_uuid = :user_uuid ";
		$sql .= "		and domain_uuid = :domain_uuid ";
		$sql .= "";
		$sql .= "	) ";
		$sql .= ") ";
		$parameters['user_uuid'] = $_SESSION['user_uuid'];
	}
	$sql .= $sql_search;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".urlencode($search);
	if ($_GET['show'] == "all" && permission_exists('contact_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page); //bottom
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true); //top
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select *, ";
	$sql .= "(select a.contact_attachment_uuid from v_contact_attachments as a where a.contact_uuid = c.contact_uuid and a.attachment_primary = 1) as contact_attachment_uuid ";
	$sql .= "from v_contacts as c ";
	$sql .= "where true ";
	if ($_GET['show'] != "all" || !permission_exists('contact_all')) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!permission_exists('contact_domain_view')) {
		$sql .= "and ( "; //only contacts assigned to current user's group(s) and those not assigned to any group
		$sql .= "	contact_uuid in ( ";
		$sql .= "		select contact_uuid from v_contact_groups ";
		$sql .= "		where ";
		if (is_array($user_group_uuids) && @sizeof($user_group_uuids) != 0) {
			foreach ($user_group_uuids as $index => $user_group_uuid) {
				if (is_uuid($user_group_uuid)) {
					$sql_where_or[] = "group_uuid = :group_uuid_".$index;
					$parameters['group_uuid_'.$index] = $user_group_uuid;
				}
			}
			if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
				$sql .= " ( ".implode(' or ', $sql_where_or)." ) ";
			}
			unset($sql_where_or, $index, $user_group_uuid);
		}
		$sql .= "		and domain_uuid = :domain_uuid ";
		$sql .= "	) ";
		$sql .= "	or contact_uuid in ( ";
		$sql .= "		select contact_uuid from v_contact_users ";
		$sql .= "		where user_uuid = :user_uuid ";
		$sql .= "		and domain_uuid = :domain_uuid ";
		$sql .= "";
		$sql .= "	) ";
		$sql .= ") ";
		$parameters['user_uuid'] = $_SESSION['user_uuid'];
	}
	$sql .= $sql_search;
	$database = new database;
	if ($order_by != '') {
		$sql .= order_by($order_by, $order);
		$sql .= ", contact_organization asc ";
	}
	else {
		$contact_default_sort_column = $_SESSION['contacts']['default_sort_column']['text'] != '' ? $_SESSION['contacts']['default_sort_column']['text'] : "last_mod_date";
		$contact_default_sort_order = $_SESSION['contacts']['default_sort_order']['text'] != '' ? $_SESSION['contacts']['default_sort_order']['text'] : "desc";

		$sql .= order_by($contact_default_sort_column, $contact_default_sort_order);
		if ($db_type == "pgsql") {
			$sql .= " nulls last ";
		}
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$contacts = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//includes and title
	$document['title'] = $text['title-contacts'];
	require_once "resources/header.php";

//contact attachment layer
	echo "<style>\n";
	echo "	#contact_attachment_layer {\n";
	echo "		z-index: 999999;\n";
	echo "		position: absolute;\n";
	echo "		left: 0px;\n";
	echo "		top: 0px;\n";
	echo "		right: 0px;\n";
	echo "		bottom: 0px;\n";
	echo "		text-align: center;\n";
	echo "		vertical-align: middle;\n";
	echo "	}\n";
	echo "</style>\n";
	echo "<div id='contact_attachment_layer' style='display: none;'></div>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-contacts']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('contact_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'collapse'=>'hide-sm-dn','style'=>'margin-right: 15px;','link'=>'contact_import.php']);
	}
	if (permission_exists('contact_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','collapse'=>'hide-sm-dn','link'=>'contact_edit.php']);
	}
	if (permission_exists('contact_delete') && $contacts) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','collapse'=>'hide-sm-dn','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('contact_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?type=&show=all'.($search != '' ? "&search=".urlencode($search) : null)]);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','collapse'=>'hide-sm-dn']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','collapse'=>'hide-sm-dn','link'=>'contacts.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('contact_delete') && $contacts) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-contacts']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('contact_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($contacts ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == "all" && permission_exists('contact_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param, "class='shrink'");
	}
	echo th_order_by('contact_type', $text['label-contact_type'], $order_by, $order);
	echo th_order_by('contact_organization', $text['label-contact_organization'], $order_by, $order);
	echo "<th class='shrink hide-xs'>&nbsp;</th>\n";
	echo th_order_by('contact_name_given', $text['label-contact_name_given'], $order_by, $order);
	echo th_order_by('contact_name_family', $text['label-contact_name_family'], $order_by, $order);
	echo th_order_by('contact_nickname', $text['label-contact_nickname'], $order_by, $order, null, "class='hide-xs'");
	echo th_order_by('contact_title', $text['label-contact_title'], $order_by, $order, null, "class='hide-sm-dn'");
	echo th_order_by('contact_role', $text['label-contact_role'], $order_by, $order, null, "class='hide-sm-dn'");
	echo "<th class='shrink hide-sm-dn'>&nbsp;</th>\n";
	if ($_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($contacts) && @sizeof($contacts) != 0) {
		$x = 0;
		foreach($contacts as $row) {
			$list_row_url = "contact_view.php?id=".urlencode($row['contact_uuid'])."&query_string=".urlencode($_SERVER["QUERY_STRING"]);
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('contact_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='contacts[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='contacts[$x][uuid]' value='".escape($row['contact_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == "all" && permission_exists('contact_all')) {
				if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td>".escape($domain)."</td>\n";
			}
			echo "	<td>".ucwords(escape($row['contact_type']))."&nbsp;</td>\n";
			echo "	<td class='overflow'><a href='".$list_row_url."'>".escape($row['contact_organization'])."</a>&nbsp;</td>\n";
			echo "	<td class='shrink no-link hide-xs center'>";
			if (is_uuid($row['contact_attachment_uuid'])) {
				echo "<i class='fas fa-portrait' style='cursor: pointer;' onclick=\"display_attachment('".escape($row['contact_attachment_uuid'])."');\"></i>";
			}
			echo "	</td>\n";
			echo "	<td class='no-wrap'><a href='".$list_row_url."'>".escape($row['contact_name_given'])."</a>&nbsp;</td>\n";
			echo "	<td class='no-wrap'><a href='".$list_row_url."'>".escape($row['contact_name_family'])."</a>&nbsp;</td>\n";
			echo "	<td class='no-wrap hide-xs'>".escape($row['contact_nickname'])."&nbsp;</td>\n";
			echo "	<td class='overflow hide-sm-dn'>".escape($row['contact_title'])."&nbsp;</td>\n";
			echo "	<td class='overflow hide-sm-dn'>".escape($row['contact_role'])."&nbsp;</td>\n";
			echo "	<td class='hide-sm-dn'>";
			if (is_array($contact_sync_sources[$row['contact_uuid']]) && @sizeof($contact_sync_sources[$row['contact_uuid']]) != 0) {
				foreach ($contact_sync_sources[$row['contact_uuid']] as $contact_sync_source) {
					switch ($contact_sync_source) {
						case 'google': echo "<img src='resources/images/icon_gcontacts.png' style='width: 21px; height: 21px; border: none; padding-left: 2px;' alt='".$text['label-contact_google']."'>"; break;
					}
				}
			}
			else {
				echo "&nbsp;";
			}
			echo "	</td>\n";
			if ($_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-view'],'icon'=>$_SESSION['theme']['button_icon_view'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($contacts);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//javascript
	echo "<script>\n";
	echo "	function display_attachment(id) {\n";
	echo "		$('#contact_attachment_layer').load('contact_attachment.php?id=' + id + '&action=display', function(){\n";
	echo "			$('#contact_attachment_layer').fadeIn(200);\n";
	echo "		});\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
