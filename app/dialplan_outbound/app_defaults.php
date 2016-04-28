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

//make sure that enum uses sofia internal in the enum.conf.xml file
	if ($domains_processed == 1) {
		$switch_conf_dir = $_SESSION['switch']['conf']['dir'];
		$file_contents = file_get_contents($switch_conf_dir."/autoload_configs/enum.conf.xml");
		$file_contents_new = str_replace("service=\"E2U+SIP\" regex=\"sip:(.*)\" replace=\"sofia/\${use_profile}/\$1", "service=\"E2U+SIP\" regex=\"sip:(.*)\" replace=\"sofia/internal/\$1", $file_contents);
		if ($file_contents != $file_contents_new) {
			$fout = fopen($switch_conf_dir."/autoload_configs/enum.conf.xml","w");
			fwrite($fout, $file_contents_new);
			fclose($fout);
			if ($display_type == "text") {
				echo "	enum.conf.xml: 	updated\n";
			}
		}
	}

?>