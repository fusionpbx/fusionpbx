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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

function persistformvar($form_array) {
	// Remember Form Input Values
	if (is_array($form_array)) {
		$content .= "<form method='post' action='".escape($_SERVER["HTTP_REFERER"])."' target='_self'>\n";
		foreach($form_array as $key => $val) {
			if ($key == "XID" || $key == "ACT" || $key == "RET") continue;
			if ($key != "persistform") { //clears the persistform value
				$content .= "	<input type='hidden' name='".escape($key)."' value='".escape($val)."' />\n";
			}
		}
		$content .= "	<input type='hidden' name='persistformvar' value='true' />\n"; //sets persistform to yes
		$content .= "	<input class='btn' type='submit' value='Back' />\n";
		$content .= "</form>\n";
	}
	echo $content;
	//return $content;
}
//persistformvar($_POST);
//persistformvar($_GET);

?>
