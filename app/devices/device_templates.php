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
	Sebastian Krupinski <sebastian@ksacorp.com>
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Sebastian Krupinski <sebastian@ksacorp.com>
*/

// load required files
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once __DIR__.'/resources/classes/device_templates.class.php';

// check permissions
	if (!permission_exists('device_template_view')) die("access denied");

// add multi-lingual support
	$language = new text;
	$text = $language->get();

// process action the action
	if ($_POST["__action"]=="copy" && permission_exists('device_template_add')) {
		if (is_uuid($_POST["__data"])) {
			device_templates::duplicate($db, $_POST["__data"],['domain_uuid'=>$_SESSION['domain_uuid']]);
			$_SESSION["message"] = $text['message-add'];
		}
	}
	elseif ($_POST["__action"]=="drop" && permission_exists('device_template_delete')) {
		if (is_uuid($_POST["__data"]) && !device_templates::get($db, $_POST["__data"], ['protected'])->protected) {
			device_templates::drop($db, $_POST["__data"]);
			$_SESSION["message"] = $text['message-delete'];
		}
	}
	elseif ($_POST["__action"]=="enable" && permission_exists('device_template_edit')) {
		if (is_uuid($_POST["__data"])) {
			if (device_templates::get($db, $_POST["__data"], ['enabled'])->enabled) {
				device_templates::put($db, $_POST["__data"], ['enabled'=>"f"]);
				//$_SESSION["message"] = $text['message-disabled'];
			}
			else {
				device_templates::put($db, $_POST["__data"], ['enabled'=>"t"]);
				//$_SESSION["message"] = $text['message-enabled'];
			}
		}
	}


// show table
// set the filter
	if ($_POST['search_domain']=="all") {
		$search_domain = "all";
		$filter = [];
	}
	elseif ($_POST['search_domain']=="global") {
		$search_domain = "global";
		$filter = [['domain_uuid IS NULL'], ['AND']];
	}
	elseif (is_uuid($_POST['search_domain'])) {
		$search_domain = $_POST['search_domain'];
		$filter = [['domain_uuid','=',$search_domain, 'AND']];
	}
	else {
		$search_domain = $_SESSION['domain_uuid'];
		$filter = [['domain_uuid','=',$search_domain, 'AND']];
	}
// set the search filter
	$search_text = strtolower(check_str($_POST['search_text']));
	if (strlen($search_text) > 0) {
		$filter[] = ['(']; 
		$filter[] = ['LOWER(name)','LIKE',"%$search_text%",'OR'];
		$filter[] = ['LOWER(description)','LIKE',"%$search_text%",'OR'];
		$filter[] = ['LOWER(collection)','LIKE',"%$search_text%"];
		$filter[] = [')'];
	}
	else {
		$filter[] = ['enabled',"=", "t"];
	}

// set the order
	$orderby = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);
	$sort = (!empty($orderby)) ? $orderby : 'collection, name';

// get data from database
	$columns = ['uuid','name', 'collection', 'enabled', 'protected'];
	$data = device_templates::find($db, $filter, $columns, $sort);
	$num_rows = device_templates::count($db, $filter);

// additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results	 
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<form method='post' id='fMain' action=''>\n";
	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "	<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-device_templates']."</b></td>\n";
	echo "	<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	// back button
	echo "		<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"document.location='devices.php'\" value='".$text['button-back']."'>";
	// domain selection
	echo "    <select class='formfld' id='search_domain' name='search_domain'>\n";
	if (permission_exists('device_template_domain')){
	echo "    <option value='all'".(($search_domain == 'all') ?" Selected":'').">".$text['select-all']."</option>\n";
	}
	echo "    <option value='global'".(($search_domain == 'global') ?" Selected":'').">".$text['select-global']."</option>\n";
	foreach ($_SESSION['domains'] as $row) {
	echo "    <option value='".$row['domain_uuid']."'".(($row['domain_uuid']==$search_domain) ?" Selected":'').">".$row['domain_name']."</option>\n";
	}
	echo "    </select>\n";
	// seach input
	echo "		<input type='text' class='txt' style='width: 150px' name='search_text' value='".$search_text."'>\n";
	echo "		<input type='submit' class='btn' value='".$text['button-search']."' onclick=''>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td align='left' colspan='2'>".$text['title_description-device_templates']."<br /><br /></td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('name', $text['label-name'], $order_by, $order);
	echo th_order_by('collection', $text['label-collection'], $order_by, $order);
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('device_template_add')) {
		echo "<a href='device_template_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if (is_array($data)) {
		foreach($data as $k => $v) {
			if (permission_exists('device_template_edit')) {
				$tr_link = "href='device_template_edit.php?id=$k'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>$v->name&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>$v->collection&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('device_template_edit')) {
				echo "	<a href='javascript:void(0);' onclick='action(\"enable\",\"$k\");'>".(($v->enabled)?'True':'False')."</a>&nbsp;";
			}
			else {
				echo "	".(($v->enabled)?'True':'False')."&nbsp;";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>$v->description&nbsp;</td>\n";
			echo "	<td class='list_control_icons' style='text-align:left;'>";
			if (permission_exists('device_template_edit')) {
				echo "<a href='device_template_edit.php?id=$k' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('device_template_add')) {
				echo "<a href='javascript:void(0);' alt='".$text['button-copy']."' onclick='if (confirm(\"".$text['confirm-copy']."\")) {action(\"copy\",\"$k\")}'>$v_link_label_add</a>";
			}
			if (permission_exists('device_template_delete')&&!$v->protected) {
				echo "<a href='javascript:void(0);' alt='".$text['button-delete']."' onclick='if (confirm(\"".$text['confirm-delete']."\")) {action(\"drop\",\"$k\")};'>$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
	}

	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('device_template_add')) {
		echo 		"<a href='device_template_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
?>

<script type="text/javascript">
	function action(a,d) {
		//alert('Action: '+a+' '+d);
		StopPropagation();
		
		if (a) { 
			$('<input>').attr({type: 'hidden', id: "__action", name: "__action", value: a}).appendTo('#fMain');
			$('<input>').attr({type: 'hidden', id: "__data", name: "__data", value: d}).appendTo('#fMain');
		}
		$('#fMain').submit();

		return false;
	}

	function StopPropagation(e)
	{
		e = e || window.event;
        e.cancelBubble = true;
        if (e.stopPropagation) e.stopPropagation();
		if (e.preventDefault) e.preventDefault();
	}

	$('#search_domain').change(function(){
    	action();
	});
</script>

<?php
	echo "</form>\n";
	echo "<br /><br />";
//include the footer
	require_once "resources/footer.php";

?>