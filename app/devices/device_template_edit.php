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
	require_once __DIR__.'/root.php';
	require_once __DIR__.'/../../resources/require.php';
	require_once __DIR__.'/../../resources/check_auth.php';
	require_once __DIR__.'/resources/classes/device_vendors.class.php';
	require_once __DIR__.'/resources/classes/device_templates.class.php';

// check permissions
	if (!permission_exists('device_template_add') && !permission_exists('device_template_edit')) die("access denied");

//declare variables
	$template = new device_template();

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (is_uuid($_POST["__uuid"])&&$_POST["__uuid"]==$_REQUEST["id"]) {
		$template->uuid = $_POST["__uuid"];
		$operation = "update";
	}
	elseif (is_uuid($_REQUEST["id"])) {
		$template->uuid = $_REQUEST["id"];
		$operation = "edit";
	}
	else {
		$operation = "add";
	}

//get the http post values and set them as php variables
	if (count($_POST) > 0) {
		// pull post data
		$template->domain_uuid = check_str($_POST["template_domain_uuid"]);
		$template->name = check_str($_POST["template_name"]);
		$template->description = check_str($_POST["template_description"]);
		$template->collection = check_str($_POST["template_collection"]);
		$template->enabled = check_str($_POST["template_enabled"]);
		$template->type = check_str($_POST["template_type"]);
		$template->include = $_POST["template_include"];
		$template->data = check_str($_POST["template_data"]);
		// process or format data
		$template->domain_uuid = (empty($template->domain_uuid))? null : $template->domain_uuid;
		$template->name = (empty($template->name))? null : $template->name;
		$template->description = (empty($template->description))? null : $template->description;
		$template->collection = (empty($template->collection))? null : $template->collection;
		$template->enabled = (empty($template->enabled))? "false" : $template->enabled;
		$template->type = (empty($template->type))? null : $template->type;
		$template->include = (empty($template->include)||$template->type=="s")? null : implode(",", array_filter($template->include));
		$template->data = (empty($template->data))? null : $template->data;
	}

//process actions
	if (count($_POST) > 0 && strlen($_POST["_persistformvar"]) == 0) {
		//check for all required data
			$msg = '';
			if (strlen($template->name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
			if (strlen($template->enabled) == 0) { $msg .= $text['message-required']." ".$text['label-enabled']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//add or update the database and set message
			if ($_POST["persistformvar"] != "true") {

				if ($_POST["__action"]=="save" || $_POST["__action"]=="savenew" || $_POST["__action"]=="saveclose") {
					if ($operation == "add" && permission_exists('device_template_add')) {
						device_templates::put($db, null, (array) $template);
						$_SESSION["message"] = $text['message-add'];
					}

					if ($operation == "update" && permission_exists('device_template_edit')) {
						device_templates::put($db, $template->uuid, (array) $template);
						$_SESSION["message"] = $text['message-update'];
					}

					if ($_POST["__action"]=="savenew") {
						header("Location: device_template_edit.php");
						return;
					}
					if ($_POST["__action"]=="saveclose") {
						header("Location: device_templates.php");
						return;
					}
				}
				elseif ($_POST["__action"]=="clone" && permission_exists('device_template_add')) {
					$n = device_templates::duplicate($db,$template->uuid, ['domain_uuid'=>$_SESSION['domain_uuid']]);
					$_SESSION["message"] = $text['message-add'];
					header("Location: device_template_edit.php?id=".$n);
					return;
				}
				elseif ($_POST["__action"]=="delete" && permission_exists('device_template_delete')) {
					device_templates::drop($db, $template->uuid);
					$_SESSION["message"] = $text['message-delete'];
					header("Location: device_templates.php");
					return;
				}
		}

	}

//load the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$template = device_templates::get($db, $template->uuid);
	}

//load includable device templates
	$filter = [['('],['domain_uuid IS NULL OR'],['domain_uuid','=',$domain_uuid],[')'], ['AND'],['type','=','s']];
	//if (strlen($device_vendor)>0) { $filter[] = ['AND']; $filter[]=['vendor_name','=',$domain_uuid];} 
	$device_templates = device_templates::find($db, $filter, ['uuid','name','collection'], ['collection, name']);

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-device_template'];

//render content
	echo "<form method='post' id='fMain' name='fMain' action=''>\n";
	echo "<input type='hidden' id='__action' name='__action' value=''>\n";
	echo "<input type='hidden' id='__uuid' name='__uuid' value='$template->uuid'>\n";
//content header
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n";
	echo "<tr>\n";
	echo "	<td align='left' width='30%'><span class=\"title\">".$text['title-device_template']."</span><br /></td>\n";
	echo "	<td width='70%' align='right'>\n";
	echo "		<input type='button' class='btn' value='".$text['button-back']."' onclick=\"window.location='device_templates.php".((strlen($app_uuid) > 0) ? "?app_uuid=".$app_uuid : null)."';\">\n";
	echo "		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	if (permission_exists('device_template_edit')) {
	echo "		<input type='button' class='btn' value='Preview' onclick='window.open(\"device_template_preview.php?id=$template->uuid\", \"_blank\");'>\n";
	echo "		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "		<input type='button' class='btn' value='".$text['button-save']."' onclick='$(\"#__action\").val(\"save\");$(\"#fMain\").submit();'>\n";
	echo "		<input type='button' class='btn' value='".$text['button-savenew']."' onclick='$(\"#__action\").val(\"savenew\");$(\"#fMain\").submit();'>\n";
	echo "		<input type='button' class='btn' value='".$text['button-saveclose']."' onclick='$(\"#__action\").val(\"saveclose\");$(\"#fMain\").submit();'>\n";
	}
	if (permission_exists('device_template_add')) {
	echo "		<input type='button' class='btn' value='".$text['button-copy']."' onclick='if (confirm(\"".$text['confirm-copy']."\")){ $(\"#__action\").val(\"clone\");$(\"#fMain\").submit(); }'>\n";
	}
	if (permission_exists('device_template_delete') && !$template->protected=="false") {
	echo "		<input type='button' class='btn' value='".$text['button-delete']."' onclick='if (confirm(\"".$text['confirm-delete']."\")){ $(\"#__action\").val(\"delete\");$(\"#fMain\").submit(); }'>\n";
	}
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td align='left' colspan='2'>".$text['description-template-edit']."</td>\n";
	echo "</tr>\n";
	echo "</table><br />\n";
// content input section
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
// content input left section
	echo "<td width='50%' style='vertical-align: top;'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	// template name
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' width='70%' align='left'>\n";
	echo "	<input class='formfld' type='text' name='template_name' maxlength='255' placeholder='' value=\"".htmlspecialchars($template->name)."\" required='required'>\n";
	echo "  <br />".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// template description
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "  ".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left' width='70%'>\n";
	echo "  <textarea class='formfld' style='width: 300px; height: 100px;' name='template_description'>".htmlspecialchars($template->description)."</textarea>\n";
	echo "  <br />".$text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// template vendor
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_vendor']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='vendor'>\n";
	//$verndors = device_vendors::find($db,['enabled','=','true'], ['device_vendor_uuid','name'], 'name', [numbered=>true]);
	echo "	<option value=''".(($template->vendor_uuid=="") ?" Selected":'')."></option>\n";
	foreach (device_vendors::find($db,['enabled','=','true'], ['device_vendor_uuid','name'], 'name', [numbered=>true]) as $vendor) {
	echo "	<option value='".$vendor['device_vendor_uuid']."'".(($vendor['device_vendor_uuid']==$template->vendor_uuid) ?" Selected":'').">".$vendor['name']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n".$text['description-device_vendor']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// template type
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='template_type'>\n";
	echo "	<option value='m'".(($template->type=="m") ?" Selected":'').">Master</option>\n";
	echo "	<option value='s'".(($template->type=="s") ?" Selected":'').">Slave</option>\n";
	echo "	</select>\n";
	echo "  <br />".$text['description-device_template_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";
// content input left section
	echo "</table>";
	echo "</td>";
// content input right section
	echo "<td width='50%' style='vertical-align: top;'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	// template enabled
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='template_enabled'>\n";
	echo "	<option value='false'".(($template->enabled=="false") ?" Selected":'').">".$text['label-false']."</option>\n";
	echo "	<option value='true'".(($template->enabled=="true") ?" Selected":'').">".$text['label-true']."</option>\n";
	echo "	</select>\n";
	echo "  <br />".$text['description-device_template_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// template domain
	if (permission_exists('device_template_domain')) {
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-domain']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='template_domain_uuid'>\n";
	echo "    <option value=''".((strlen($template->domain_uuid) == 0) ?" Selected":'').">".$text['select-global']."</option>\n";
	foreach ($_SESSION['domains'] as $row) {
	echo "    <option value='".$row['domain_uuid']."'".(($row['domain_uuid']==$template->domain_uuid) ?" Selected":'').">".$row['domain_name']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n".$text['description-domain_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	}
	// template collection
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap' width='30%'>\n";
	echo "  ".$text['label-collection']."\n";
	echo "</td>\n";
	echo "<td class='vtable' width='70%' align='left'>\n";
	echo "  <input class='formfld' type='text' name='template_collection' maxlength='255' placeholder='' value=\"".htmlspecialchars($template->collection)."\" required='required'>\n";
	echo "  <br />".$text['description-collection']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	// template include
	if ($template->type!="s") {
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-device_template']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	// create new select for each template
	$template->include .= ", ";
	foreach (explode(",",$template->include) as $k1 => $v1) {
	echo "<select name='template_include[$k1]' class='formfld'>\n";
	echo "<option value=''></option>\n";
		$g = -1;
		foreach($device_templates as $k2 => $v2) {
		if ($g!=$v2->collection) {
			if ($g!=-1) echo "</optgroup>";
			echo "<optgroup label='$v2->collection'>";
			$g=$v2->collection;
		}
		echo "<option value='$k2' ".(($k2==$v1) ?" Selected":'').">$v2->name</option>\n";
		}
	echo "</optgroup>";
	echo "</select><br/>\n";
	}
	echo $text['description-device_template']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	}
// content input right section
	echo "</table>";
	echo "</td>";
	echo "</tr>";
// content editor section
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	<br/>";
?>

	<table cellpadding='0' cellspacing='0' border='0' style='float:right;'>
	<tr>
		<td valign='middle' style='padding-left: 6px;'><img src='resources/ace/icon_invisibles.png' title='Toggle Invisibles' class='control' onclick="toggle_option('invisibles');"></td>
		<td valign='middle' style='padding-left: 6px;'><img src='resources/ace/icon_indenting.png' title='Toggle Indent Guides' class='control' onclick="toggle_option('indenting');"></td>
		<td valign='middle' style='padding-left: 6px;'><img src='resources/ace/icon_replace.png' title='Show Find/Replace [Ctrl+H]' class='control' onclick="editor.execCommand('replace');"></td>
		<td valign='middle' style='padding-left: 6px;'><img src='resources/ace/icon_goto.png' title='Show Go To Line' class='control' onclick="editor.execCommand('gotoline');"></td>
		<td valign='middle' style='padding-left: 10px;'>
			<select id='editor_mode' style='height: 23px;'>
			<option value='ini'>Conf</option><option value='text'>Text</option><option value='xml'>XML</option><option value='json'>JSON</option><option value='php'>PHP</option>
			</select>
		</td>
		<td valign='middle' style='padding-left: 4px;'>
			<select id='editor_font_size' style='height: 23px;'>
				</option><option value='8px'>8px</option><option value='10px'>10px</option><option value='12px' selected>12px</option><option value='14px'>14px</option><option value='16px'>16px</option><option value='18px'>18px</option><option value='20px'>20px</option>
			</select>
		</td>
	</tr>
	</table>

<?php
	echo "	<textarea id='template_data' name='template_data' style='display:none;'>";
	echo htmlspecialchars($template->data);
	echo "	</textarea>";
	echo "	<div id='template_editor'></div>";
	echo "</td>\n";
	echo "</tr>\n";
// content editor section
	echo "</table>";
	echo "<br><br>";

// content footer
	echo "<br>\n";
	echo "<div align='right'>\n";
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='$(\"#__action\").val(\"save\");$(\"#fMain\").submit();'>\n";
	echo "</div>\n";
	echo "<br><br>\n";
	echo "</form>";
?>

	<script language="javascript">
	// javascript to change select to input and back again
		var objs;

		function change_to_input(obj){
			tb=document.createElement('INPUT');
			tb.type='text';
			tb.name=obj.name;
			tb.className='formfld';
			//tb.setAttribute('id', 'ivr_menu_option_param');
			tb.setAttribute('style', 'width:175px;');
			tb.value=obj.options[obj.selectedIndex].value;
			tbb=document.createElement('INPUT');
			tbb.setAttribute('class', 'btn');
			tbb.setAttribute('style', 'margin-left: 4px;');
			tbb.type='button';
			tbb.value=$("<div />").html('&#9665;').text();
			tbb.objs=[obj,tb,tbb];
			tbb.onclick=function(){ replace_param(this.objs); }
			obj.parentNode.insertBefore(tb,obj);
			obj.parentNode.insertBefore(tbb,obj);
			obj.parentNode.removeChild(obj);
			replace_param(this.objs);
		}

		function replace_param(obj){
			obj[2].parentNode.insertBefore(obj[0],obj[2]);
			obj[0].parentNode.removeChild(obj[1]);
			obj[0].parentNode.removeChild(obj[2]);
		}
	</script>

	<style type="text/css" media="screen">
		#template_editor {
			display: block;
			margin: auto;
			width: 100%;
			min-height: 400px;
			border: 1px solid #888;
		}
	</style>

	<script type="text/javascript" src="/resources/ace/ace.js" charset="utf-8"></script>
	<script type="text/javascript">
		// ace editor initilization
		var template_data = $('#template_data');

		//load ace editor
			var editor = ace.edit("template_editor");
			editor.setOptions({
				mode: 'ace/mode/ini',
				theme: 'ace/theme/Tomorrow',
				maxLines: 'Infinity',
				selectionStyle: 'text',
				cursorStyle: 'smooth',
				showGutter: true,
				showPrintMargin: false,
				highlightGutterLine: false,
				useSoftTabs: false
				});
			
			//editor.$blockScrolling = Infinity;

			editor.getSession().setValue(template_data.val());
			editor.getSession().on('change', function () {
       			template_data.val(editor.getSession().getValue());
   			});
			editor.setAutoScrollEditorIntoView();
			editor.setFontSize('12px');

		//remove certain keyboard shortcuts
			editor.commands.bindKey("Ctrl-T", null); //disable transpose letters - prefer new browser tab
			editor.commands.bindKey("Ctrl-F", null); //disable find - control broken with bootstrap
			editor.commands.bindKey("Ctrl-H", null); //disable replace - control broken with bootstrap

			$('#editor_mode').on('change', function() {
				editor.getSession().setMode('ace/mode/' + this.options[this.selectedIndex].value); 
				
			});

			$('#editor_font_size').on('change', function() {
				editor.setFontSize(this.options[this.selectedIndex].value); 
				
			});
	</script>
<?php
//show the footer
	require_once "resources/footer.php";

?>