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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process one time
	if ($domains_processed == 1) {

		//add missing switch directories in default settings
			$obj = new switch_settings;
			$obj->settings();
			unset($obj);

		//use the module class to get the list of modules from the db and add any missing modules
			if (isset($_SESSION['switch']['mod']['dir'])) {
				$mod = new modules;
				$mod->db = $db;
				$mod->dir = $_SESSION['switch']['mod']['dir'];
				$mod->get_modules();
				$mod->synch();
				$msg = $mod->msg;

				//save the modules.conf
				save_module_xml();
			}
	}

?>