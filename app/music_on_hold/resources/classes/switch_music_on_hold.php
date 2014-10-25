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
	Copyright (C) 2010
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";

//define the directory class
	class switch_music_on_hold {

		public $domain_uuid;
		public $domain_name;
		public $select_name;
		public $select_value;
		public $select_options;
		private $xml;

		public function __construct() {
			require_once "resources/classes/database.php";
			$this->app_uuid = '';
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function select() {
			//build the list of categories
				$music_on_hold_dir = $_SESSION["switch"]["sounds"]["dir"]."/music";
				if (count($_SESSION['domains']) > 1) {
					$music_on_hold_dir = $music_on_hold_dir."/".$_SESSION['domain_name'];
				}

			//add multi-lingual support
				require_once "app/music_on_hold/app_languages.php";
				foreach($text as $key => $value) {
					$text[$key] = $value[$_SESSION['domain']['language']['code']];
				}

			//start the select
				$select = "	<select class='formfld' name='".$this->select_name."' id='".$this->select_name."' style='width: auto;'>\n";
				$select .= "		<option value='' style='font-style: italic;'>".$text['opt-default']."</option>\n";

			//categories
				$array = glob($music_on_hold_dir."/*/*", GLOB_ONLYDIR);
			//list the categories
				$moh_xml = "";
				foreach($array as $moh_dir) {
					//set the directory
						$moh_dir = substr($moh_dir, strlen($music_on_hold_dir."/"));
					//get and set the rate
						$sub_array = explode("/", $moh_dir);
						$moh_rate = end($sub_array);
					//set the name
						$moh_name = $moh_dir;
						$moh_name = substr($moh_dir, 0, strlen($moh_name)-(strlen($moh_rate)));
						$moh_name = rtrim($moh_name, "/");
						if (count($_SESSION['domains']) > 1) {
							$moh_value = "local_stream://".$_SESSION['domain_name']."/".$moh_name;
						}
						else {
							$moh_value = "local_stream://".$moh_name;
						}
						$select .= "		<option value='".$moh_value."' ".(($this->select_value == $moh_value)?'selected="selected"':null).">".(str_replace('_', ' ', $moh_name))."</option>\n";
				}
			//recordings
				if($dh = opendir($_SESSION['switch']['recordings']['dir']."/")) {
					$tmp_selected = false;
					$files = Array();
					//$select .= "<optgroup label='recordings'>\n";
					while($file = readdir($dh)) {
						if($file != "." && $file != ".." && $file[0] != '.') {
							if(is_dir($_SESSION['switch']['recordings']['dir'] . "/" . $file)) {
								//this is a directory
							}
							else {
								if ($this->select_value == $_SESSION['switch']['recordings']['dir']."/".$file && strlen($this->select_value) > 0) {
									$tmp_selected = true;
									$select .= "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$file."' selected='selected'>".$file."</option>\n";
								}
								else {
									$select .= "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$file."'>".$file."</option>\n";
								}
							}
						}
					}
					closedir($dh);
					//$select .= "</optgroup>\n";
				}
			//add additional options
				$select .= $this->select_options;
			//end the select and return it
				$select .= "	</select>\n";
				return $select;
		}

		public function reload() {
			//if the handle does not exist create it
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			//if the handle still does not exist show an error message
				if (!$fp) {
					$msg = "<div align='center'>".$text['message-event-socket']."<br /></div>";
				}
			//send the api command to check if the module exists
				if ($fp) {
					$cmd = "reload mod_local_stream";
					$switch_result = event_socket_request($fp, 'api '.$cmd);
					unset($cmd);
				}
		}

		public function xml() {
			//build the list of categories
				$music_on_hold_dir = $_SESSION['switch']['sounds']['dir'].'/music';
			//default category
				if (defined("GLOB_BRACE")) {
					$array = glob($music_on_hold_dir."/{8000,16000,32000,48000}", GLOB_ONLYDIR|GLOB_BRACE);
				}
				else {
					$array_1 = glob($music_on_hold_dir."/8000".$class_name.".php", GLOB_ONLYDIR);
					$array_2 = glob($music_on_hold_dir."/16000".$class_name.".php", GLOB_ONLYDIR);
					$array_3 = glob($music_on_hold_dir."/32000".$class_name.".php", GLOB_ONLYDIR);
					$array_4 = glob($music_on_hold_dir."/48000".$class_name.".php", GLOB_ONLYDIR);
					$array = array_merge((array)$array_1,(array)$array_2,(array)$array_3,(array)$array_4);
					unset($array_1,$array_2,$array_3,$array_4);
				}
			//other categories
				if (count($_SESSION['domains']) > 1) {
					$array = array_merge($array, glob($music_on_hold_dir."/*/*/*", GLOB_ONLYDIR));
				}
				else {
					$array = array_merge($array, glob($music_on_hold_dir."/*/*", GLOB_ONLYDIR));
				}
			//list the categories
				$moh_xml = "";
				foreach($array as $moh_dir) {
					//set the directory
						$moh_dir = substr($moh_dir, strlen($music_on_hold_dir."/"));
					//get and set the rate
						$sub_array = explode("/", $moh_dir);
						$moh_rate = end($sub_array);
					//set the name
						$moh_name = $moh_dir;
						if ($moh_dir == $moh_rate) {
							$moh_name = "default/$moh_rate";
						}
					//build the xml
						$moh_xml .= "	<directory name=\"$moh_name\" path=\"\$\${sounds_dir}/music/$moh_dir\">\n";
						$moh_xml .= "		<param name=\"rate\" value=\"".$moh_rate."\"/>\n";
						$moh_xml .= "		<param name=\"shuffle\" value=\"true\"/>\n";
						$moh_xml .= "		<param name=\"channels\" value=\"1\"/>\n";
						$moh_xml .= "		<param name=\"interval\" value=\"20\"/>\n";
						$moh_xml .= "		<param name=\"timer-name\" value=\"soft\"/>\n";
						$moh_xml .= "	</directory>\n";
						$this->xml = $moh_xml;
				}
		}

		public function save() {
			//get the contents of the template
				if (file_exists('/usr/share/examples/fusionpbx')) {
					$file_contents = file_get_contents("/usr/share/examples/fusionpbx/resources/templates/conf/autoload_configs/local_stream.conf.xml");
				}
				else {
					$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf/autoload_configs/local_stream.conf.xml");
				}

			//replace the variable
				$file_contents = str_replace("{v_moh_categories}", $this->xml, $file_contents);

			//write the XML config file
				$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/local_stream.conf.xml","w");
				fwrite($fout, $file_contents);
				fclose($fout);

			//reload the XML
				$this->reload();
		}
	}

//require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
//$moh= new switch_music_on_hold;
//$moh->select_name = "hold_music";
//$moh->select_value = $hold_music;
//echo $moh->select();

?>