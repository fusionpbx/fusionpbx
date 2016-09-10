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
	require_once __DIR__.'/resources/classes/device_vendors.class.php';

// check permissions
	if (!permission_exists('device_template_add')) die("access denied");

// add multi-lingual support
	$language = new text;
	$text = $language->get();

// collect post data 
	if (is_array($_POST['templates'])) {
		$data= $_POST['templates'];
	}
	else {
		$data= [];
	}

// process action the action
	// add to list
	if ($_POST["__action"]=="add" && isset($_POST['import_uri'])) {
		if (strpos($_POST['import_uri'],'http://')!==false || strpos($_POST['import_uri'],'https://')!==false) {
			$data[] = ['location'=>$_POST['import_uri']];
		}
		else {
			foreach(glob($_POST['import_uri']) as $k => $v) {
				$data[] = ['location'=>$v];
			}
		}
	}
	// drop from list
	elseif ($_POST["__action"]=="drop" && is_numeric($_POST['__data'])) {
		unset($data[$_POST['__data']]); 
	}
	// clear list
	elseif ($_POST["__action"]=="drop-all") {
		$data = [];
	}
	// import list
	elseif ($_POST["__action"]=="import" && permission_exists('device_template_add')) {
		// process list
		foreach ($data as $k => $v) {
			// only process if there is a location and name
			if (!empty($v['location']) && !empty($v['name'])) {
				// compile data
				$t = []; 
				$t['domain_uuid']=$v['domain'];
				$t['vendor_uuid']=$v['vendor'];
				$t['name']=$v['name'];
				$t['collection']=$v['collection'];
				$t['type']='m';
				$t['enabled']='true';
				$t['protected']='false'; 
				$t['data']=file_get_contents($v['location']);
				// save data
				device_templates::put($db, null, $t);
			}
		}
		// clear list
		$data = [];
		$_SESSION["message"] = $text['message-add'];
	}

// get vendors
	$vendors = device_vendors::find($db,['enabled','=','true'], ['device_vendor_uuid','name'], 'name', [numbered=>true]);

// additional includes
	require_once "resources/header.php";

//show the content
	echo "<form method='post' id='fMain' action=''>\n";
	echo "<input type='hidden' id='__action' name='__action' />";
	echo "<input type='hidden' id='__data' name='__data' />";
	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "	<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-device_templates_import']."</b></td>\n";
	echo "	<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	// back button
	echo "		<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"document.location='device_templates.php'\" value='".$text['button-back']."'>";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td align='left' colspan='2'>".$text['title_description-device_templates']."<br /><br /></td>\n";
	echo "</tr>\n";
	echo "</table>\n";

// import search
	echo "<table width='75%' border='0' cellpadding='0' cellspacing='0'>\n";
	// import uri
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "	".$text['label-import-uri']."\n";
	echo "</td>\n";
	echo "<td class='vtable' width='70%' align='left'>\n";
	echo "	<input class='formfld' type='text' name='import_uri' value='".((isset($_POST['import_uri'])) ? htmlspecialchars($_POST['import_uri']) : $_SERVER['DOCUMENT_ROOT']."/resources/templates/provision/*/*/*")."' required='required'>\n";
	echo "	<input type='button' class='btn' id='import_add' value='".$text['button-add']."'>\n";
	echo "  <br />".$text['description-import-uri']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table><br/><br/>";

	if (count($data) > 0) {

	// import list
		echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr><td colspan='6'>Only values with a name will be added to the database on import.</td></tr>\n";
		echo "<tr>\n";
		echo th_order_by('location', $text['label-location'], $order_by, $order);
		echo th_order_by('name', $text['label-name'], $order_by, $order);
		echo th_order_by('domain', $text['label-domain'], $order_by, $order);
		echo th_order_by('vendor', $text['label-vendor'], $order_by, $order);
		echo th_order_by('collection', $text['label-collection'], $order_by, $order);
		echo "<td class='list_control_icons'>";
		
		echo "  <a href='javascript:void(0);' alt='".$text['button-delete']."' onclick='action(\"drop-all\",\"\");'>$v_link_label_delete</a>";
		echo "</td>\n";
		echo "<tr>\n";

		$c=-1;
		foreach($data as $k => $v) {
			// set row color
			$c = (($c++)<1) ? $c : 0;
			// add rows
			echo "<tr>\n";
			// template location
			echo "	<td valign='middle' class='row_style$c'><input type='hidden' name='templates[$k][location]' value='".$v['location']."'> ".$v['location']."</td>\n";
			// template name
			echo "	<td valign='top' class='row_style$c'><input type='text' class='formfld' name='templates[$k][name]' value='".$v['name']."' size='32'></td>\n";
			// template domain
			echo "	<td valign='top' class='row_style$c'>";
			if (permission_exists('device_template_domain')) {
			echo "    <select class='formfld' name='templates[$k][domain]'>\n";
			echo "    <option value=''".((strlen($k['domain']) == 0) ?" Selected":'').">".$text['select-global']."</option>\n";
			foreach ($_SESSION['domains'] as $i) {
			echo "    <option value='".$i['domain_uuid']."'".(($i['domain_uuid']==$v['domain']) ?" Selected":'').">".$i['domain_name']."</option>\n";
			}
			echo "    </select>\n";
			}
			else {
			echo $_SESSION["domain_name"];
			}
			echo "	</td>\n";
			// template vendor
			echo "	<td valign='top' class='row_style$c'>";
			echo "	<select class='formfld' name='templates[$k][vendor]'>\n";
			foreach ($vendors as $i) {
			echo "	<option value='".$i['device_vendor_uuid']."'".(($i['device_vendor_uuid']==$v['vendor']) ?" Selected":'').">".$i['name']."</option>\n";
			}
			echo "	</select>\n";
			echo "	</td>";
			// template collection
			echo "	<td valign='top' class='row_style$c'><input type='text' class='formfld' name='templates[$k][collection]' value='".$v['collection']."' size='32'></td>\n";
			// side controls
			echo "	<td class='list_control_icons' style='vertical-align: middle; text-align: left;'>";
			echo "  <a href='javascript:void(0);' alt='".$text['button-delete']."' onclick='action(\"drop\",\"$k\");'>$v_link_label_delete</a>";
			echo "	</td>\n";
			echo "</tr>\n";

		}
	
		echo "<tr>";
		echo "<td colspan='5' nowrap='nowrap' style='vertical-align: middle; text-align: center;'>&nbsp</td>\n";
		echo "<td class='list_control_icons' nowrap='nowrap' style='vertical-align: middle; text-align: right;'>";
		echo "  <a href='javascript:void(0);' alt='".$text['button-delete']."' onclick='action(\"drop-all\",\"\");'>$v_link_label_delete</a>";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>";
		echo "<td colspan='6' style='vertical-align: middle; text-align: right;'><br/>\n";
		echo "	<input type='button' class='btn' id='import_save' value='".$text['button-import']."'>\n";
		echo "</td>\n";
		echo "</tr>\n";
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

	$('#import_add').click(function() {action('add','')} );
	$('#import_save').click(function() {action('import','')} );
</script>

<?php
	echo "</form>\n";
	echo "<br /><br />";
//include the footer
	require_once "resources/footer.php";

?>