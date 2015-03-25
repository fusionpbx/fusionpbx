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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('dialplan_advanced_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

require_once "resources/header.php";
$document['title'] = $text['title-default_dialplan'];

if ($_GET['a'] == "default" && permission_exists('dialplan_advanced_edit')) {
	//create the dialplan/default.xml for single tenant or dialplan/domain.xml
	require_once "app/dialplan/resources/classes/dialplan.php";
	$dialplan = new dialplan;
	$dialplan->domain_uuid = $_SESSION['domain_uuid'];
	$dialplan->switch_dialplan_dir = $_SESSION['switch']['dialplan']['dir'];
	$dialplan->restore_advanced_xml();
	//print_r($dialplan->result);
}

if ($_POST['a'] == "save" && permission_exists('dialplan_advanced_edit')) {
	$v_content = str_replace("\r","",$_POST['code']);
	if (file_exists($_SESSION['switch']['dialplan']['dir']."/".$_SESSION['domain_name'].".xml")) {
		$fd = fopen($_SESSION['switch']['dialplan']['dir']."/".$_SESSION['domain_name'].".xml", "w");
	}
	else {
		$fd = fopen($_SESSION['switch']['dialplan']['dir']."/default.xml", "w");
	}
	fwrite($fd, $v_content);
	fclose($fd);
	$savemsg = $text['message-update'];
}

if (file_exists($_SESSION['switch']['dialplan']['dir']."/".$_SESSION['domain_name'].".xml")) {
	$fd = fopen($_SESSION['switch']['dialplan']['dir']."/".$_SESSION['domain_name'].".xml", "r");
	$v_content = fread($fd, filesize($_SESSION['switch']['dialplan']['dir']."/".$_SESSION['domain_name'].".xml"));
}
else {
	$fd = fopen($_SESSION['switch']['dialplan']['dir']."/default.xml", "r");
	$v_content = fread($fd, filesize($_SESSION['switch']['dialplan']['dir']."/default.xml"));
}
fclose($fd);

?>

<script language="Javascript">
function sf() { document.forms[0].savetopath.focus(); }
</script>
<script language="Javascript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/edit_area/edit_area_full.js"></script>
<script language="Javascript" type="text/javascript">
	// initialisation
	editAreaLoader.init({
		id: "code"	// id of the textarea to transform
		,start_highlight: true
		,allow_toggle: false
		,language: "en"
		,syntax: "html"
		,toolbar: "search, go_to_line,|, fullscreen, |, undo, redo, |, select_font, |, syntax_selection, |, change_smooth_selection, highlight, reset_highlight, |, help"
		,syntax_selection_allow: "css,html,js,php,xml,c,cpp,sql"
		,show_line_colors: true
	});
</script>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
   <tr>
     <td class="" >
     	<br>
		<form action="dialplan_advanced.php" method="post" name="iform" id="iform">
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
			  <tr>
				<td align='left' width='100%'>
					<span class="title"><?php echo $text['header-default_dialplan']?></span><br><br>
					<?php echo $text['description-default_dialplan']?>
					<br />
					<br />
				</td>
				<td width='10%' align='right' valign='top'>
					<input type='button' class='btn' name='' alt='<?php echo $text['button-back']?>' onclick="window.location='dialplans.php'" value='<?php echo $text['button-back']?>'>
					<input type='submit' class='btn' value='<?php echo $text['button-save']?>' />
				</td>
			  </tr>
			<tr>
			<td colspan='2' class='' valign='top' align='left' nowrap>
				<textarea style="width:100%" id="code" name="code" rows="31"><?php echo htmlentities($v_content); ?></textarea>
				<br />
				<br />
			</td>
			</tr>
			<tr>
				<td align='left'>
				<?php
				if ($v_path_show) {
					echo "<b>location:</b> ".$_SESSION['switch']['conf']['dir']."/dialplan/default.xml\n";
				}
				?>
				</td>
				<td align='right'>
					<input type='hidden' name='f' value='<?php echo $_GET['f']; ?>' />
					<input type='hidden' name='a' value='save' />
					<?php
					if (permission_exists('dialplan_advanced_edit')) {
						echo "<input type='button' class='btn' value='".$text['button-restore']."' onclick=\"document.location.href='dialplan_advanced.php?a=default&f=default.xml';\" />";
					}
					?>
				</td>
			</tr>
			<tr>
			<td colspan='2'>
				<br /><br /><br />
				<br /><br /><br />
				<br /><br /><br />
				<br /><br /><br />
				<br /><br /><br />
				<br /><br /><br />
				<br /><br /><br />
				<br /><br /><br />
				<br /><br /><br />
				<br /><br /><br />
			</td>
			</tr>
			</table>
		</form>
</td>
</tr>
</table>

<?php
	require_once "resources/footer.php";
?>
