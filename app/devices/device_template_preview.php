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

header("Content-Type: text/plain");

// load required files
	require_once __DIR__.'/root.php';
	require_once __DIR__.'/../../resources/require.php';
	require_once __DIR__.'/../../resources/check_auth.php';
	require_once __DIR__.'/resources/classes/device_templates.class.php';

// check permissions
	if (!permission_exists('device_template_add') && !permission_exists('device_template_edit')) die("access denied");

//get vaiables from string
	$id = $_GET['id'];
    $mac = $_GET['mac'];
	$render = ($_GET['render']=="1")?true:false;

// check if is a proper id 
	if (!is_uuid($id)) die ("invalid");

// get template data
	$template = device_templates::get($db, $id);

// check if user is permitted to access template
	if (true) {
		if (isset($mac)&&$render) {
			// use provision mechanism to produce a template
			// load required files
			require_once __DIR__.'/../../provision/resources/classes/provision.php';
			// generate file
			$prov = new provision;
			$prov->db = $db;
			$prov->domain_uuid = $domain_uuid;
			$prov->mac = $mac;
			echo $prov->render();
		}
		else {
			// compaile template(s) and output
			echo device_templates::compile($db, $id);
		}
	}
?>