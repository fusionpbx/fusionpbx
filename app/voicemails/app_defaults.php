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

//process this only one time
if ($domains_processed == 1) {

	// process change from using macros to phrases
	$languages_path = $_SESSION['switch']['phrases']['dir'];
	if ($languages_path != '' && file_exists($languages_path)) {
		$folder_contents = scandir($languages_path);
		if (is_array($folder_contents) && @sizeof($folder_contents) != 0) {
			foreach ($folder_contents as $language_abbreviation) {
				if ($language_abbreviation == '.' || $language_abbreviation == '..') { continue; }
				// adjust language xml to include all xml phrase files in the vm folder
				$language_xml_path = $languages_path.'/'.$language_abbreviation.'/'.$language_abbreviation.'.xml';
				if (file_exists($language_xml_path)) {
					$language_xml_content = file_get_contents($language_xml_path);
					$language_xml_content = str_replace('data="vm/sounds.xml"', 'data="vm/*.xml"', $language_xml_content);
					@file_put_contents($language_xml_path, $language_xml_content);
				}
				// copy voicemail.xml to language/xx/vm folders
				$voicemail_xml_source = $_SERVER['PROJECT_ROOT'].'/app/voicemails/resources/switch/languages/'.$language_abbreviation.'/vm/voicemail.xml';
				$voicemail_xml_target = $languages_path.'/'.$language_abbreviation.'/vm/voicemail.xml';
				if (!file_exists($voicemail_xml_target)) {
					copy($voicemail_xml_source, $voicemail_xml_target);
				}
			}
		}
	}
	unset($languages_path, $folder_contents, $language_abbreviation, $language_xml_path, $language_xml_content, $voicemail_xml_source, $voicemail_xml_target);

}

?>