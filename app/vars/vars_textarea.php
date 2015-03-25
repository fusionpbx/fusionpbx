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
if (permission_exists('var_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-variables_advanced'];

//restore the default vars.xml
if ($_GET['a'] == "default" && permission_exists('var_edit')) {
	//read default config file
	$fd = fopen($_SESSION['switch']['conf']['dir'].".orig/vars.xml", "r");
	$v_content = fread($fd, filesize($_SESSION['switch']['conf']['dir'].".orig/vars.xml"));
	fclose($fd);

	//write the default config fget
	$fd = fopen($_SESSION['switch']['conf']['dir']."/vars.xml", "w");
	fwrite($fd, $v_content);
	fclose($fd);
	$savemsg = "Default Restored";
}

//save the vars.xml
	if ($_POST['a'] == "save" && permission_exists('var_edit')) {
		$v_content = str_replace("\r","",$_POST['code']);
		$fd = fopen($_SESSION['switch']['conf']['dir']."/vars.xml", "w");
		fwrite($fd, $v_content);
		fclose($fd);
		$savemsg = "Saved";
	}

//get the contens of vars.xml
	$fd = fopen($_SESSION['switch']['conf']['dir']."/vars.xml", "r");
	$v_content = fread($fd, filesize($_SESSION['switch']['conf']['dir']."/vars.xml"));
	fclose($fd);

//edit area
	echo "	<script language=\"javascript\" type=\"text/javascript\" src=\"/resources/edit_area/edit_area_full.js\"></script>\n";
	echo "	<script language=\"Javascript\" type=\"text/javascript\">\n";
	echo "		// initialisation //load,\n";
	echo "		editAreaLoader.init({\n";
	echo "			id: \"code\"	// id of the textarea to transform //, |, help\n";
	echo "			,start_highlight: true\n";
	echo "			,font_size: \"9\"\n";
	echo "			,allow_toggle: false\n";
	echo "			,language: \"en\"\n";
	echo "			,syntax: \"xml\"\n";
	echo "			,toolbar: \"search, go_to_line,|, fullscreen, |, undo, redo, |, select_font, |, syntax_selection, |, change_smooth_selection, highlight, reset_highlight, |, help\" //new_document,\n";
	echo "			,plugins: \"charmap\"\n";
	echo "			,charmap_default: \"arrows\"\n";
	echo "		});\n";
	echo "	</script>";
	echo "\n";
	echo "\n";

?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<form action="vars.php" method="post" name="iform" id="iform">
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td width='100%'><span class="vexpl"><span class="red"><strong><?php echo $text['header-variables_advanced']?></strong></span>
					<br /><br />
					<?php echo $text['description-variables_advanced']?>
					<br /><br />
				</td>
				<td align='right' valign='top'>
					<?php if (permission_exists('var_edit')) { ?>
					<input type="submit" class='btn' value="<?php echo $text['button-save']?>" />
					<?php } ?>
				</td>
			</tr>

			<tr>
			<td colspan='2' class='' align='left'>
				<textarea name='code' style='width:100%' id='code' rows='35' wrap='off'><?php echo htmlentities($v_content); ?></textarea>
				<br />
				<br />
			</td>
			</tr>

			<tr>
				<td valign='top'>
				<?php
				if ($v_path_show) {
					echo "<b>location:</b> ".$_SESSION['switch']['conf']['dir']."/vars.xml\n";
				}
				?>
				</td>
				<td valign='top' align='right'>
					<input type="hidden" name="f" value="<?php echo $_GET['f']; ?>" />
					<input type="hidden" name="a" value="save" />
					<?php
					if (permission_exists('var_edit')) {
						echo "<input type='button' class='btn' value='".$text['button-restore']."' onclick=\"document.location.href='vars.php?a=default&f=vars.xml';\" />";
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
