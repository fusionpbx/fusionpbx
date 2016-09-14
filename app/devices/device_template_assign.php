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
	require_once __DIR__.'/resources/classes/devices.class.php';
	require_once __DIR__.'/resources/classes/device_templates.class.php';
	require_once __DIR__.'/resources/classes/device_vendors.class.php';

// check permissions
	if (!permission_exists('device_template')) die("access denied");

// add multi-lingual support
	$language = new text;
	$text = $language->get();

// process action the action
	if ($_POST['__action']=="search") {
		$data = [];
		$filter = [];

		// search by domain
		if (isset($_POST['search_domain']) && $_POST['search_domain']!='all') {
			$filter[] = ['domain_uuid','=',$_POST['search_domain']];
		}

		// search by device vendor
		if (isset($_POST['search_vendor']) && $_POST['search_vendor']!='all') {
			if (count($filter)) $filter[] = ['AND'];
			$filter[] = ['device_vendor','=',$_POST['search_vendor']];
		}

		// search by device identifier
		if (!empty($_POST['search_identifier'])) {
			if (count($filter)) $filter[] = ['AND'];
			$filter[] = ['device_mac_address','LIKE',$_POST['search_identifier']];
		}
		
		// search by device label
		if (!empty($_POST['search_label'])) {
			if (count($filter)) $filter[] = ['AND'];
			$filter[] = ['device_label','LIKE',$_POST['search_label']];
		}

		// search by device model
		if (!empty($_POST['search_model'])) {
			if (count($filter)) $filter[] = ['AND'];
			$filter[] = ['device_model','LIKE',$_POST['search_model']];
		}

		// search by device template
		if (isset($_POST['search_template']) && $_POST['search_template']!='all') {
			if (count($filter)) $filter[] = ['AND'];
			$filter[] = ['device_template','=',$_POST['search_template']];
		}

	}
	// import list
	elseif ($_POST['__action']=="assign" && permission_exists('device_template')) {
		// process list
		$t = ['device_template'=>$_POST['assign_template']];
		foreach ($_POST['devices_selected'] as $k) {
				// save data
				devices::put($db, $k, $t);
		}
		$_SESSION["message"] = $text['message-updated'];
	}

// get lists and add blank element
	if (permission_exists('device_template_viewall')){
		$search_domains = ['all'=>$text['select-all']] + devices::list_linked_domains($db);
		$search_vendors = ['all'=>$text['select-all']] + devices::list_linked_vendors($db);
		$search_templates = ['all'=>$text['select-all']] + devices::list_linked_templates($db);

		$filter_templates = [['type=\'m\''],['AND'],['enabled=\'true\'']];
		$assign_templates = [null=>['name'=>'','collection'=>'']] + device_templates::find($db, $filter_templates, ['uuid','name','collection'], ['collection, name']);
	}
	else {
		if (count($filter)) $filter[] = ['AND'];
		$filter[] = ['domain_uuid','=',$_SESSION['domain_uuid']];

		$search_domains = [$_SESSION['domain_uuid']=>$_SESSION['domain_name']];
		$search_vendors = ['all'=>$text['select-all']] + devices::list_linked_vendors($db,$_SESSION['domain_uuid']);
		$search_templates = ['all'=>$text['select-all']] + devices::list_linked_templates($db,$_SESSION['domain_uuid']);

		$filter_templates = [['('],['domain_uuid IS NULL OR'],['domain_uuid','=',$_SESSION['domain_uuid']],[')'],['AND'],['type=\'m\''],['AND'],['enabled=\'true\'']];
		$assign_templates = [null=>['name'=>'','collection'=>'']] + device_templates::find($db, $filter_templates, ['uuid','name','collection'], ['collection, name']);
	}

	if ($_POST['__action'] == "search" && count($filter)) {
		$data = devices::find($db,$filter,['device_uuid','device_mac_address','device_label','device_vendor','device_template', 'device_model','device_enabled','domain_uuid']);
	}

// additional includes
	require_once "resources/header.php";

//show the content
	echo "<form method='post' id='fMain' action=''>\n";
	echo "<input type='hidden' id='__action' name='__action' />";
	echo "<input type='hidden' id='__data' name='__data' />";
	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "	<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-device_templates_assign']."</b></td>\n";
	echo "	<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	// back button
	echo "		<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"document.location='device_templates.php'\" value='".$text['button-back']."'>";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td align='left' colspan='2'>".$text['title_description-device_assign']."<br /><br /></td>\n";
	echo "</tr>\n";
	echo "</table>\n";


	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr><td width='50%' valign='bottom'>";
// start search panel
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	// search by device domain
	if (permission_exists('device_template_viewall')) {
	echo "<tr>\n";
	echo "<td class='vncell' width='30%' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-domain']."\n";
	echo "</td>\n";
	echo "<td class='vtable' width='70%' align='left'>\n";
	echo "    <select class='formfld' name='search_domain'>\n";
	foreach ($search_domains as $k=> $v) {
	echo "	<option value='$k'".(($k==$_POST['search_domain']) ?" Selected":'').">".(!empty($v)?$v:$k)."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n".$text['description-domain_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	}
	// search by device vendor
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_vendor']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='search_vendor'>\n";
	foreach ($search_vendors as $k => $v) {
	echo "	<option value='$k'".(($k==$_POST['search_vendor']) ?" Selected":'').">".(!empty($v)?$v:$k)."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n".$text['description-device_vendor']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// search by device identifier
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_identifier']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='search_identifier' maxlength='255' value='".$_POST['search_identifier']."'/><br />\n";
	echo $text['description-device_identifier']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// search by device label
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_label']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='search_label' maxlength='255' value='".$_POST['search_label']."'/><br />\n";
	echo $text['description-device_label']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// search by device model
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_model']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='search_model' maxlength='255' value='".$_POST['search_model']."'/><br />\n";
	echo $text['description-device_model']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// search by device template
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_template']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='search_template'>\n";
	foreach ($search_templates as $k => $v) {
	echo "	<option value='$k'".(($k==$_POST['search_template']) ?" Selected":'').">".(!empty($v)?$v:$k)."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n".$text['description-device_template']."\n";
	echo "</td>\n";
	echo "</tr>";
	// search controls
	echo "<tr>";
	echo "<td colspan='2' style='vertical-align: middle; text-align: right;'><br/>\n";
	echo "	<input type='button' class='btn' id='search' value='".$text['button-search']."'>\n";
	echo "</td>\n";
	echo "</tr></table>";
// end search panel
	echo "</td><td width='50%' valign='bottom'>";

// start assign panel
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	// assign template
	echo "<tr>";
	echo "<td class='vncell' width='30%' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_template']."\n";
	echo "</td>\n";
	echo "<td class='vtable' width='70%' align='left'>\n";
	echo "	<select class='formfld' name='assign_template'>\n";
		$g = -1;
		foreach($assign_templates as $k => $v) {
			if ($g!=$v->collection) {
				if ($g!=-1) echo "</optgroup>";
				echo "<optgroup label='$v->collection'>";
				$g=$v->collection;
			}
			echo "<option value='$k' ".(($k==$assign_templates) ?" Selected":'').">$v->name</option>\n";
		}
		echo "</optgroup>";
	echo "	</select>\n";
	echo "<br />\n".$text['description-device_template']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// assign controls
	echo "<tr>";
	echo "<td colspan='2' style='vertical-align: middle; text-align: right;'><br/>\n";
	echo "	<input type='button' class='btn' id='assign' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
// end assign panel
	echo "</table>\n";
	echo "</td></tr></table><br/><br/>";


	if (count($data) > 0) {

	// import list
		echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo th_order_by('domain', $text['label-domain'], $order_by, $order);
		echo th_order_by('identifier', $text['label-device_mac_address'], $order_by, $order);
		echo th_order_by('label', $text['label-device_label'], $order_by, $order);
		echo th_order_by('vendor', $text['label-device_vendor'], $order_by, $order);
		echo th_order_by('model', $text['label-device_model'], $order_by, $order);
		echo th_order_by('template', $text['label-device_template'], $order_by, $order);
		echo th_order_by('enabled', $text['label-device_enabled'], $order_by, $order);
		echo "<td></td>\n";
		echo "<tr>\n";

		$c=-1;
		foreach($data as $k => $v) {
			// set row color
			$c = (($c++)<1) ? $c : 0;
			// add rows
			echo "<tr>\n";
			// device domain
			echo "	<td valign='top' class='row_style$c'>".(!empty($search_domains[$v->domain_uuid])?$search_domains[$v->domain_uuid]:$v->domain_uuid)."</td>\n";
			// device identifier
			echo "	<td valign='middle' class='row_style$c'>$v->device_mac_address</td>\n";
			// device label
			echo "	<td valign='top' class='row_style$c'>$v->device_label</td>\n";
			// device vendor
			echo "	<td valign='top' class='row_style$c'>".(!empty($search_vendors[$v->device_vendor])?$search_vendors[$v->device_vendor]:$v->device_vendor)."</td>\n";
			// device model
			echo "	<td valign='top' class='row_style$c'>$v->device_model</td>";
			// device template
			echo "	<td valign='top' class='row_style$c'>".(!empty($search_templates[$v->device_template])?$search_templates[$v->device_template]:$v->device_template)."</td>\n";
			// device enabled
			echo "	<td valign='top' class='row_style$c'>$v->device_enabled</td>\n";
			// side controls
			echo "	<td style='vertical-align: middle; text-align:center;'>";
			echo "  <input type='checkbox' name='devices_selected[]' value='$k'>";
			echo "	</td>\n";
			echo "</tr>\n";

		}

		echo "</table>";

	}
?>

<script type="text/javascript">
	function action(a,d) {
		if (a) { 
			$('#__action').val(a);
			$('#__data').val(d);
		}
		$('#fMain').submit();
	}

	$('input.editable[type="text"]').val(function(i, v) {
        return this.id.replace(/_/g, " ");
 	});

	$('#search').click(function() {action('search','')} );
	$('#assign').click(function() {action('assign','')} );
</script>

<?php
	echo "</form>\n";
	echo "<br /><br />";
//include the footer
	require_once "resources/footer.php";

?>