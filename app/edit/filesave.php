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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('script_editor_save')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

	$filepath = $_POST["filepath"];
	if ($filepath != '') {

		try {
			//save file content
				$filepath = realpath($filepath); //filepath
				$filepath = str_replace ('//', '/', $filepath);
				$filepath = str_replace ("\\", "/", $filepath);
				$content = $_POST["content"];

				$handle = fopen($filepath, 'wb');
				if (!$handle) {
					throw new Exception('Write Failed - Check File Owner & Permissions');
				}
				fwrite($handle, $content);
				fclose($handle);

			//set the reload_xml value to true
				$_SESSION["reload_xml"] = true;

			//alert user of success
				echo "<script>alert('Changes Saved'); parent.focus_editor();</script>";
		}
		catch(Exception $e) {
		  //alert error
		  echo "<script>alert('".$e->getMessage()."'); parent.focus_editor();</script>";
		}

	}

?>