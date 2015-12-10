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

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-languages']."</b></td>\n";
	echo "		<td width='50%' align='right'>&nbsp;</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-languages']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";


//table headers
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th nowrap='' style='width:2em'>".$text['label-flag']."</th>\n";
	echo th_order_by('language_code', $text['label-culture_code'], $order_by, $order, '', "style='width:4em;'");
	echo th_order_by('language_name', $text['label-name'], $order_by, $order);
	echo "</tr>\n";

	$c= 0;
	$warn_about_wrong_culture = false;
	foreach($_SESSION['app']['languages'] as $lang_code){ //TODO add sorting
		$tr_link = "href='languages_compare.php?target_language=$lang_code'";
		echo "<tr $tr_link>\n";
		echo "<td class='row_style".($c%2)."'>";
		echo "<img src='$project_path/themes/flags/$lang_code.png' alt='$lang_code'/></td>";
		echo "<td class='row_style".($c%2)."'>$lang_code";
		if(strlen($lang_code) < 5){
			$warn_about_wrong_culture = true;
			echo "$nbsp;<sup>*1</sup>";
		}
		echo "</td>";
		echo "<td class='row_style".($c%2)."'>".$text["language-$lang_code"]."</td>";
		echo "</tr>\n";
		$c++;
	}
//complete the content
	echo "</table>";
	if($warn_about_wrong_culture){
		$lang_code = $_SESSION['domain']['language']['code'];
		echo "<p><sup>*1</sup>&nbsp;".$text['warning-incorrect_language_culture_code']."<a href='https://msdn.microsoft.com/$lang_code/library/ee825488%28v=cs.20%29.aspx'>https://msdn.microsoft.com/$lang_code/library/ee825488%28v=cs.20%29.aspx</a></p>";
	}

//include the footer
	require_once "resources/footer.php";

?>