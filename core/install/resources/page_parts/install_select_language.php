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

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' align='left' nowrap><b>".$text['header-select_language']."</b><br><br></td>\n";
	echo "<td width='70%' align='right'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-select']."'/>\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-select_language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
		echo "<fieldset class='container'>";
	foreach($_SESSION['app']['languages'] as $lang_code){
		echo "<fieldset class='container'>";
		echo "	<label class='radio' style='width:200px;'>";
		echo "<input type='radio' name='install_language' value='$lang_code' id='lang_$lang_code' onchange='JavaScript:disable_next()'";
		if($lang_code == $_SESSION['domain']['language']['code'])
		{
			echo " checked='checked'";
		}
		echo "/>";
		echo "<img src='<!--{project_path}-->/themes/flags/$lang_code.png' alt='$lang_code'/>&nbsp;".$text["language-$lang_code"];
		echo "</label>\n";
		echo "</fieldset>";
	}
	echo "</fieldset>";
	echo "<br />\n";
	echo $text['description-select_language']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";
	echo "</form>";
?><script type='text/javascript'>
function disable_next() {
	document.getElementById("next").style.display = 'none';
}
</script>