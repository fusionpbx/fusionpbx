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
	Matthew Vale <github@mafoo.org>
*/
require_once "root.php";
require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('languages_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get http post variables and set them to php variables
	$reference_language = $_SESSION['domain']['language']['code'];
	$target_language = check_str($_GET["target_language"]);
	$app_target = 'resources';
	if (count($_POST) > 0) {
		//set the variables
			$reference_language = check_str($_POST["reference_language"]);
			$target_language = check_str($_POST["target_language"]);
			$app_target = check_str($_POST["app_target"]);
	}

//collect languages
	$language = new text;
	
	$language_text = $language->get('all', $app_target, true);
	foreach ($language_text as $lang_label => $lang_codes) {
		$language_labels[] = $lang_label;
		$reference_text[$lang_label] = $lang_codes[$reference_language];
		$target_text[$lang_label] = $lang_codes[$target_language];
	}
	sort($language_labels);
	
	if($app_target != 'resources'){
		$global_text = $language->get($reference_language, 'resources', true);
	}
	unset($language_text);

//add multi-lingual support
	$text = $language->get();

//get the list of installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
	$app_list;
	$x=0;
	foreach ($config_list as $config_path) {
		include($config_path);
		$dirs = explode("/", $config_path);
		$app_path = $dirs[(sizeof($dirs)-3)] . "/" . $dirs[(sizeof($dirs)-2)];
		$app_name = $apps[$x]['name'];
		if( strlen($app_name) == 0) { $app_name = $app_path; }
		$app_list[$app_name] = $app_path;
		$x++;
	}
	$theme_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/themes/*/app_languages.php");
	foreach ($theme_list as $config_path) {
		$dirs = explode("/", $config_path);
		$app_path = $dirs[(sizeof($dirs)-3)] . "/" . $dirs[(sizeof($dirs)-2)];
		$app_name = 'Theme - ' . $dirs[(sizeof($dirs)-2)];
		$app_list[$app_name] = $app_path;
	}
	unset($apps);
	ksort($app_list);

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<p>\n";
	echo "<b>".$text['title-compare_languages']."</b><br/>\n";
	echo $text['description-compare_languages']."\n";
	echo "</p>\n";

//select comparison
	echo "<span><b>".$text['header-compare_languages']."</b><br/></span>\n";
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-reference_language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<fieldset class='container'>";
	foreach($_SESSION['app']['languages'] as $lang_code){
		echo "<fieldset class='container'>";
		echo "	<label class='radio' style='width:200px;'>";
		echo "<input type='radio' name='reference_language' value='$lang_code' id='reference_language_$lang_code'";
		if($lang_code == $reference_language)
		{
			echo " checked='checked'";
		}
		echo "/>";
		echo "<img src='$project_path/themes/flags/$lang_code.png' alt='$lang_code'/>&nbsp;".$text["language-$lang_code"];
		echo "</label>\n";
		echo "</fieldset>";
	}
	echo "</fieldset>";
	echo "<br />".$text['description-reference_language']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-target_language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<fieldset class='container'>";
	foreach($_SESSION['app']['languages'] as $lang_code){
		echo "<fieldset class='container'>";
		echo "	<label class='radio' style='width:200px;'>";
		echo "<input type='radio' name='target_language' value='$lang_code' id='target_language_$lang_code'";
		if($lang_code == $target_language)
		{
			echo " checked='checked'";
		}
		echo "/>";
		echo "<img src='$project_path/themes/flags/$lang_code.png' alt='$lang_code'/>&nbsp;".$text["language-$lang_code"];
		echo "</label>\n";
		echo "</fieldset>";
	}
	echo "</fieldset>";
	echo "<br />".$text['description-app_target']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-application']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='app_target' id='app_target' class='formfld'>\n";
	echo "		<option value='resources'";
	if($app_target == 'resources') { echo " selected='selected'"; }
	echo ">Global</option>\n";
	echo "		<option value=''>==========</option>";
	foreach($app_list as $app => $app_path ) {
		echo "		<option value='$app_path'";
		if($app_target == $app_path) { echo " selected='selected'"; }
		echo ">".$app."</option>\n";
	}
	echo "	</select>\n";
	echo "	<br />".$text['description-app_target']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "	<div style='text-align:right'>\n";
	echo "    <button type='button' onclick=\"history.go(-1);\">".$text['button-back']."</button>\n";
	echo "    <button type='submit' id='next'>".$text['button-search']."</button>\n";
	echo "	</div>\n";
	echo "</form>\n";

	echo "<br/>\n";

//render the texts
	echo "<span><b>".$text['header-language_results']."</b> for '$app_target/app_languages.php'<br/></span>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>".$text['label-tag']."</th>";
	echo "<th><img src='$project_path/themes/flags/$reference_language.png' alt='$reference_language'/>&nbsp;".$text["language-$reference_language"]."</th>\n";
	if(strlen($target_language) > 0 ) { echo "<th><img src='$project_path/themes/flags/$target_language.png' alt='$target_language'/>&nbsp;".$text["language-$target_language"]."</th>\n"; }
	echo "</tr>\n";
	$language_count = 0;
	foreach ($language_labels as $lang_label){
		if( preg_match( '/\Alanguage-\w{2}(?:-\w{2})?\z/', $lang_label) ) { break; }
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap>$lang_label";
		if(isset($global_text[$lang_label])){
			echo "&nbsp;<img src='$project_path/themes/enhanced/images/warning.png' alt='!' title=\"".$text['warning-global_already_defined']."'".$global_text[$lang_label]."'\"/>";
		}
		echo "</td>\n";
		echo "<td class='vtable' align='left'>";
		if(strlen($reference_text[$lang_label]) == 0) {
			echo "<b>Missing!</b>";
		}else{
			echo $reference_text[$lang_label];
		}
		echo "</td>\n";
		if(strlen($target_language) > 0 ) {
			echo "<td class='vtable' align='left'>";
			if(strlen($target_text[$lang_label]) == 0) {
				echo "<b>Missing!</b>";
			}else{
				echo $target_text[$lang_label];
			}
			echo "</td>\n";
		}
		echo "</tr>\n";
		$language_count++;
	}
	if($language_count == 0){
		echo "<tr><td colspan='3'>Sorry, this app hasn't defined any text</td></tr>\n";
	}
	echo "</table>\n";
	
//include the footer
	require_once "resources/footer.php";

?>