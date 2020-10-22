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

	if (!function_exists('save_ivr_menu_xml')) {
		function save_ivr_menu_xml() {
			global $domain_uuid;

			//prepare for dialplan .xml files to be written. delete all dialplan files that are prefixed with dialplan_ and have a file extension of .xml
			if (count($_SESSION["domains"]) > 1) {
				$v_needle = 'v_'.$_SESSION['domain_name'].'_';
			}
			else {
				$v_needle = 'v_';
			}
			if($dh = opendir($_SESSION['switch']['conf']['dir']."/ivr_menus/")) {
				$files = Array();
				while($file = readdir($dh)) {
					if($file != "." && $file != ".." && $file[0] != '.') {
						if(is_dir($dir . "/" . $file)) {
							//this is a directory
						} else {
							if (strpos($file, $v_needle) !== false && substr($file,-4) == '.xml') {
								//echo "file: $file<br />\n";
								unlink($_SESSION['switch']['conf']['dir']."/ivr_menus/".$file);
							}
						}
					}
				}
				closedir($dh);
			}

			$sql = "select * from v_ivr_menus ";
			$sql .= " where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
			$result = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters);

			if (is_array($result) && @sizeof($result) != 0) {
				foreach($result as $row) {
					$dialplan_uuid = $row["dialplan_uuid"];
					$ivr_menu_uuid = $row["ivr_menu_uuid"];
					$ivr_menu_name = $row["ivr_menu_name"];
					$ivr_menu_extension = $row["ivr_menu_extension"];
					$ivr_menu_greet_long = $row["ivr_menu_greet_long"];
					$ivr_menu_greet_short = $row["ivr_menu_greet_short"];
					$ivr_menu_invalid_sound = $row["ivr_menu_invalid_sound"];
					$ivr_menu_exit_sound = $row["ivr_menu_exit_sound"];
					$ivr_menu_confirm_macro = $row["ivr_menu_confirm_macro"];
					$ivr_menu_confirm_key = $row["ivr_menu_confirm_key"];
					$ivr_menu_tts_engine = $row["ivr_menu_tts_engine"];
					$ivr_menu_tts_voice = $row["ivr_menu_tts_voice"];
					$ivr_menu_confirm_attempts = $row["ivr_menu_confirm_attempts"];
					$ivr_menu_timeout = $row["ivr_menu_timeout"];
					$ivr_menu_exit_app = $row["ivr_menu_exit_app"];
					$ivr_menu_exit_data = $row["ivr_menu_exit_data"];
					$ivr_menu_inter_digit_timeout = $row["ivr_menu_inter_digit_timeout"];
					$ivr_menu_max_failures = $row["ivr_menu_max_failures"];
					$ivr_menu_max_timeouts = $row["ivr_menu_max_timeouts"];
					$ivr_menu_digit_len = $row["ivr_menu_digit_len"];
					$ivr_menu_direct_dial = $row["ivr_menu_direct_dial"];
					$ivr_menu_context = $row["ivr_menu_context"];
					$ivr_menu_enabled = $row["ivr_menu_enabled"];
					$ivr_menu_description = $row["ivr_menu_description"];

					//replace space with an underscore
						$ivr_menu_name = str_replace(" ", "_", $ivr_menu_name);

					//add each IVR menu to the XML config
						$tmp = "<include>\n";
						if (strlen($ivr_menu_description) > 0) {
							$tmp .= "	<!-- $ivr_menu_description -->\n";
						}
						if (count($_SESSION["domains"]) > 1) {
							$tmp .= "	<menu name=\"".$_SESSION['domains'][$domain_uuid]['domain_name']."-".$ivr_menu_name."\"\n";
						}
						else {
							$tmp .= "	<menu name=\"$ivr_menu_name\"\n";
						}
						if (stripos($ivr_menu_greet_long, 'mp3') !== false || stripos($ivr_menu_greet_long, 'wav') !== false) {
							//found wav or mp3
							$tmp .= "		greet-long=\"".$ivr_menu_greet_long."\"\n";
						}
						else {
							//not found
							$tmp .= "		greet-long=\"".$ivr_menu_greet_long."\"\n";
						}
						if (stripos($ivr_menu_greet_short, 'mp3') !== false || stripos($ivr_menu_greet_short, 'wav') !== false) {
							if (strlen($ivr_menu_greet_short) > 0) {
								$tmp .= "		greet-short=\"".$ivr_menu_greet_short."\"\n";
							}
						}
						else {
							//not found
							if (strlen($ivr_menu_greet_short) > 0) {
								$tmp .= "		greet-short=\"".$ivr_menu_greet_short."\"\n";
							}
						}
						$tmp .= "		invalid-sound=\"$ivr_menu_invalid_sound\"\n";
						$tmp .= "		exit-sound=\"$ivr_menu_exit_sound\"\n";
						$tmp .= "		confirm-macro=\"$ivr_menu_confirm_macro\"\n";
						$tmp .= "		confirm-key=\"$ivr_menu_confirm_key\"\n";
						$tmp .= "		tts-engine=\"$ivr_menu_tts_engine\"\n";
						$tmp .= "		tts-voice=\"$ivr_menu_tts_voice\"\n";
						$tmp .= "		confirm-attempts=\"$ivr_menu_confirm_attempts\"\n";
						$tmp .= "		timeout=\"$ivr_menu_timeout\"\n";
						$tmp .= "		inter-digit-timeout=\"$ivr_menu_inter_digit_timeout\"\n";
						$tmp .= "		max-failures=\"$ivr_menu_max_failures\"\n";
						$tmp .= "		max-timeouts=\"$ivr_menu_max_timeouts\"\n";
						$tmp .= "		digit-len=\"$ivr_menu_digit_len\">\n";

						$sub_sql = "select * from v_ivr_menu_options ";
						$sub_sql .= "where ivr_menu_uuid = :ivr_menu_uuid ";
						$sub_sql .= "and domain_uuid = :domain_uuid ";
						$sub_sql .= "order by ivr_menu_option_order asc ";
						$parameters['ivr_menu_uuid'] = $ivr_menu_uuid;
						$parameters['domain_uuid'] = $domain_uuid;
						$database = new database;
						$sub_result = $database->select($sub_sql, $parameters, 'all');
						if (is_array($sub_result) && @sizeof($sub_result) != 0) {
							foreach ($sub_result as &$sub_row) {
								//$ivr_menu_uuid = $sub_row["ivr_menu_uuid"];
								$ivr_menu_option_digits = $sub_row["ivr_menu_option_digits"];
								$ivr_menu_option_action = $sub_row["ivr_menu_option_action"];
								$ivr_menu_option_param = $sub_row["ivr_menu_option_param"];
								$ivr_menu_option_description = $sub_row["ivr_menu_option_description"];

								$tmp .= "		<entry action=\"$ivr_menu_option_action\" digits=\"$ivr_menu_option_digits\" param=\"$ivr_menu_option_param\"/>";
								if (strlen($ivr_menu_option_description) == 0) {
									$tmp .= "\n";
								}
								else {
									$tmp .= "	<!-- $ivr_menu_option_description -->\n";
								}
							}
						}
						unset($sub_sql, $sub_result, $sub_row);

						if ($ivr_menu_direct_dial == "true") {
							$tmp .= "		<entry action=\"menu-exec-app\" digits=\"/(^\d{3,6}$)/\" param=\"transfer $1 XML ".$ivr_menu_context."\"/>\n";
						}
						$tmp .= "	</menu>\n";
						$tmp .= "</include>\n";

						//remove invalid characters from the file names
							$ivr_menu_name = str_replace(" ", "_", $ivr_menu_name);
							$ivr_menu_name = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $ivr_menu_name);

						//write the file
							if (count($_SESSION["domains"]) > 1) {
								$fout = fopen($_SESSION['switch']['conf']['dir']."/ivr_menus/v_".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."_".$ivr_menu_name.".xml","w");
							}
							else {
								$fout = fopen($_SESSION['switch']['conf']['dir']."/ivr_menus/v_".$ivr_menu_name.".xml","w");
							}
							fwrite($fout, $tmp);
							fclose($fout);
				}
			}
			unset($result, $row);

			//apply settings
			$_SESSION["reload_xml"] = true;
		}
	}

?>
