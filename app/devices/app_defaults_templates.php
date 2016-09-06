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
// load required files
require_once __DIR__.'/resources/classes/device_vendors.class.php';
require_once __DIR__.'/resources/classes/device_templates.class.php';

// read data from database
$vendors = device_vendors::find($db, null, ['name','device_vendor_uuid'], null, [namedvalue=>true]);
$data_database = device_templates::find($db, null, ['uuid','name'], null, [namedvalue=>true]);

// read default templates index file
$templates_file = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/devices/resources/defaults/device_templates.csv";
if (($handle = fopen("$templates_file", "r")) !== FALSE) {
    while (($line = fgetcsv($handle, 0, ",")) !== FALSE) {
        if (!is_null($line[0])) $templates_data[$line[0]]=$line;
    }
    fclose($handle);
}

// process defaults file and push diffrences to database
$templates_folder = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
foreach ($templates_data as $k => $v) {
		$t =[];
		$t['uuid']=$v[0];
		$t['name']=$v[1];
		$t['collection']=$v[2];
		$t['protected']="t";
		$t['vendor_uuid']=$vendors[$v[3]];
		$template_file = $templates_folder."/".trim($v[4]); 
		if (file_exists($template_file)) {
			$t['data']= file_get_contents($template_file);
		}
		// create
		if (!isset($data_database[$k])) {
			$t['enabled']="t";
			device_templates::put($db, null, $t);
		}
		// update
		else {
			device_templates::put($db, $v[0], $t);
		}
		//echo $k."<br />\n";
}

?>