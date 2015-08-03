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
*/

// make sure the PATH_SEPARATOR is defined
	if (!defined("PATH_SEPARATOR")) {
		if ( strpos( $_ENV[ "OS" ], "Win" ) !== false ) { define("PATH_SEPARATOR", ";"); } else { define("PATH_SEPARATOR", ":"); }
	}

// make sure the document_root is set
	$_SERVER["SCRIPT_FILENAME"] = str_replace("\\", "/", $_SERVER["SCRIPT_FILENAME"]);
	$_SERVER["DOCUMENT_ROOT"] = str_replace($_SERVER["PHP_SELF"], "", $_SERVER["SCRIPT_FILENAME"]);
	$_SERVER["DOCUMENT_ROOT"] = realpath($_SERVER["DOCUMENT_ROOT"]);
	//echo "DOCUMENT_ROOT: ".$_SERVER["DOCUMENT_ROOT"]."<br />\n";
	//echo "PHP_SELF: ".$_SERVER["PHP_SELF"]."<br />\n";
	//echo "SCRIPT_FILENAME: ".$_SERVER["SCRIPT_FILENAME"]."<br />\n";

// if the project directory exists then add it to the include path otherwise add the document root to the include path
	if (is_dir($_SERVER["DOCUMENT_ROOT"].'/fusionpbx')){
		if(!defined('PROJECT_PATH')) { define('PROJECT_PATH', '/fusionpbx'); }
		set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER["DOCUMENT_ROOT"].'/fusionpbx' );
	}
	else {
		if(!defined('PROJECT_PATH')) { define('PROJECT_PATH', ''); }
		set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] );
	}

?>