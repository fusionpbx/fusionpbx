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
	Portions created by the Initial Developer are Copyright (C) 2016-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

// Disable the PHP SESSION
$no_session = true;

// Includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";

// Set global variable(s)
global $settings;

// Check the domain cidr range 
if (!empty($settings->get('cdr', 'cidr')) && !defined('STDIN')) {
	$found = false;

	if (check_cidr($settings->get('cdr', 'cidr'), $_SERVER['REMOTE_ADDR'])) {
		echo "access denied";
		exit;
	}
}

// Set ini settings
set_time_limit(3600);
ini_set('memory_limit', '256M');
ini_set("precision", 6);

// Import the call detail records from HTTP POST or file system
$xml_cdr = new xml_cdr(["database" => $database, "settings" => $settings, "domain_uuid" => $domain_uuid]);
$xml_cdr->post();
$xml_cdr->read_files();

