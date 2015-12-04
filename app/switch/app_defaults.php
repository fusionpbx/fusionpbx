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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.
	
	Contributor(s):
	Matthew Vale <github@mafoo.org>
*/

	if ($domains_processed == 1) {
		$dst_script_dir = $_SESSION['switch']['scripts_dir']['dir'];
		if (file_exists($dst_script_dir)) {
			if(!is_writable($dst_script_dir)){
				throw new Exception("'$dst_script_dir' is not writable");
			}
			$src_script_dirs = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/*/*/resources/scripts');
			foreach ($src_script_dirs as $src_script_dir) {
				if (!is_readable($src_script_dir)) {
					throw new Exception("Cannot read from '$src_dir' to get the scripts");
				}
				recursive_copy($src_script_dir, $dst_script_dir, $_SESSION['scripts']['options']['text']);
			}
			chmod($dst_script_dir, 0774);
		}
	}

?>