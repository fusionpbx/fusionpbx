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

// read default templates data file
$templates_file = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/devices/resources/defaults/device_templates.csv";
if (($file = fopen($templates_file, "r")) !== FALSE) {
    while (($line = fgetcsv($file, 0, ",")) !== FALSE) {
        if (!is_null($line[0])) $default_templates_details[$line[0]]=$line;
    }
    fclose($file);
}

// process defaults file and push diffrences to database
$templates_folder = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/";
foreach ($default_templates_details as $k => $v) {
		$t =[];
		$t['uuid']=$v[0];
		$t['name']=$v[1];
		$t['collection']=$v[2];
		$t['protected']="true";
		$t['vendor_uuid']=$vendors[$v[3]];
		// load data from file system
		$template_file = $templates_folder."/".trim($v[4]); 
		if (file_exists($template_file)) {
			$t['data']= file_get_contents($template_file);
		}
		// create
		if (!isset($data_database[$k])) {
			$t['enabled']="true";
			device_templates::put($db, null, $t);
		}
		// update
		else {
			device_templates::put($db, $v[0], $t);
		}
		//echo $k."<br />\n";
}


// alternet linux location
if (file_exists("/etc/fusionpbx/resources/templates/provision")) {
	$templates_folder = "/etc/fusionpbx/resources/templates/provision";
}
// alternet FreeBSD location
elseif (file_exists("/usr/local/etc/fusionpbx/resources/templates/provision")) {
	$templates_folder = "/usr/local/etc/fusionpbx/resources/templates/provision";
}
else {
	$templates_folder = null;
}

if (isset($templates_folder)) { 
	// read user custom templates data file
	$templates_file = $templates_folder."/custom_templates.csv";
	if (file_exists($templates_file)) {
		if (($file = fopen($templates_file, "r")) !== FALSE) {
			while (($line = fgetcsv($file, 0, ",")) !== FALSE) {
				if (!is_null($line[0])) $custom_templates_details[$line[0]]=$line;
			}
			fclose($file);
		}
	}

	// import file templates
	$files_data = str_replace($templates_folder."/","",glob("$templates_folder/*/*/{\$mac}.*"));

	foreach ($files_data as $k => $v) {
		//check if file exists in the defaults data already
		$r1=null;
		foreach ($default_templates_details as $key => $val) {
			if ($val[4] === " ".$v) { $r1 = $key; break; }
		}
		// check if file exists in the custom data already
		$r2=null;
		foreach ($custom_templates_details as $key => $val) {
			if ($val[4] === $v) { $r2 = $key; break; }
		}

		// process only if the file is not a duplicate
		if ($r1==null && $r2==null) {
			// looks like we have a unique file
			// split file path
			$p = explode("/", $v, 3);
			
			// create array of template info
			$t =[];
			$t["uuid"] = uuid();
			// check to see if the folder name is a domain that exists
			// pattern '192.168.0.10/675xi/{$mac.cfg}'
			// or 'voice.test.com/675xi/{$mac.cfg}'
			$d=null;
			foreach ($_SESSION['domains'] as $key => $val) {
				if ($val['domain_name'] === $p[0]) { $d = $key; break; }
			}
			// take appropriate action
			if($d!=null){
				$t['domain_uuid']=$d;
				$t['collection']=$p[1];
				$t['name']=ucfirst($p[1])." Template";
			}
			else {
				if(isset($vendors[$p[0]])) {
					$t['vendor_uuid']=$vendors[$p[0]];
				}
				$t['name']=ucfirst($p[0])." Template";
			}
			$t['description']=$v;
			$t['protected']="false";
			// load data from file system
			$template_file = $templates_folder."/".trim($v); 
			if (file_exists($template_file)) {
				$t['data']= file_get_contents($template_file);
			}

			// save to database
			if (!isset($data_database[$k])) {
				$t['enabled']="true";
				device_templates::put($db, null, $t);
			}
			
			// add template details to custom templates data
			$custom_templates_details[] = [$t["uuid"],$t["domain_uuid"], $t["name"], $t["collection"],$v];
			
		}
	}

	// save custom template details
	if (($file = fopen($templates_file,"w"))!==false) {
		foreach ($custom_templates_details as $line)
		{
			fputcsv($file, $line, ",");
		}
		fclose($file);
	}

}

?>