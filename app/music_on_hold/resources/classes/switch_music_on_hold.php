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
	Portions created by the Initial Developer are Copyright (C) 2010-2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
	Matthew Vale <github@mafoo.org>
*/
include "root.php";

//define the switch_music_on_hold class
	class switch_music_on_hold {

		public $domain_uuid;
		private $xml;

		public function __construct() {
			$this->domain_uuid = $_SESSION['domain_uuid'];
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function select($name, $selected, $options) {
			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//start the select
				$select = "<select class='formfld' name='".$name."' id='".$name."' style='width: auto;'>\n";

			//music on hold
				$music_list = $this->get();
				if (count($music_list) > 0) {
					$select .= "	<option value=''>\n";
					$select .= "	<optgroup label='".$text['label-music_on_hold']."'>\n";
					$previous_name = '';
					foreach($music_list as $row) {
						if ($previous_name != $row['music_on_hold_name']) {
							$name = '';
							if (strlen($row['domain_uuid']) > 0) {
								$name = $row['domain_name'].'/';	
							}
							$name .= $row['music_on_hold_name'];
							$select .= "		<option value='local_stream://".$name."' ".(($selected == "local_stream://".$name) ? 'selected="selected"' : null).">".$row['music_on_hold_name']."</option>\n";
						}
						$previous_name = $row['music_on_hold_name'];
					}
					$select .= "	</optgroup>\n";
				}
			//recordings
				if (is_dir($_SERVER["PROJECT_ROOT"].'/app/recordings')) {
					require_once "app/recordings/resources/classes/switch_recordings.php";
					$recordings_c = new switch_recordings;
					$recordings = $recordings_c->list_recordings();
					if (sizeof($recordings) > 0) {
						$select .= "	<optgroup label='".$text['label-recordings']."'>";
						foreach($recordings as $recording_value => $recording_name){
							$select .= "		<option value='".$recording_value."' ".(($selected == $recording_value) ? 'selected="selected"' : null).">".$recording_name."</option>\n";
						}
						$select .= "	</optgroup>\n";
					}
				}
			//streams
				if (is_dir($_SERVER["PROJECT_ROOT"].'/app/streams')) {
					$sql = "select * from v_streams ";
					$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
					$sql .= "and stream_enabled = 'true' ";
					$sql .= "order by stream_name asc ";
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$database = new database;
					$streams = $database->select($sql, $parameters, 'all');
					if (is_array($streams) && @sizeof($streams) != 0) {
						$select .= "	<optgroup label='".$text['label-streams']."'>";
						foreach($streams as $row){
							$select .= "		<option value='".$row['stream_location']."' ".(($selected == $row['stream_location']) ? 'selected="selected"' : null).">".$row['stream_name']."</option>\n";
						}
						$select .= "	</optgroup>\n";
					}
					unset($sql, $parameters, $streams, $row);
				}
			//add additional options
				if (sizeof($options) > 0) {
					$select .= "	<optgroup label='".$text['label-others']."'>";
					$select .= $options;
					$select .= "	</optgroup>\n";
				}
			//end the select and return it
				$select .= "</select>\n";
				return $select;
		}

		public function get() {
			//add multi-lingual support
				$language = new text;
				$text = $language->get(null, 'app/music_on_hold');

			//get moh records, build array
				$sql = "select ";
				$sql .= "d.domain_name, ";
				$sql .= "m.* ";
				$sql .= "from v_music_on_hold as m ";
				$sql .= "left join v_domains as d on d.domain_uuid = m.domain_uuid ";
				$sql .= "where (m.domain_uuid = :domain_uuid or m.domain_uuid is null) ";
				$sql .= "order by m.domain_uuid desc, music_on_hold_name asc, music_on_hold_rate asc ";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$database = new database;
				return $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
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
			//default category (note: GLOB_BRACE doesn't work on some systems)
				$array_1 = glob($music_on_hold_dir."/8000".$class_name.".php", GLOB_ONLYDIR);
				$array_2 = glob($music_on_hold_dir."/16000".$class_name.".php", GLOB_ONLYDIR);
				$array_3 = glob($music_on_hold_dir."/32000".$class_name.".php", GLOB_ONLYDIR);
				$array_4 = glob($music_on_hold_dir."/48000".$class_name.".php", GLOB_ONLYDIR);
				$array = array_merge((array)$array_1,(array)$array_2,(array)$array_3,(array)$array_4);
				unset($array_1,$array_2,$array_3,$array_4);
			//other categories
				if (count($_SESSION['domains']) > 1) {
					$array = array_merge($array, glob($music_on_hold_dir."/*/*/*", GLOB_ONLYDIR));
				}
				else {
					$array = array_merge($array, glob($music_on_hold_dir."/*/*", GLOB_ONLYDIR));
				}
			//list the categories
				$xml = "";
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
						$xml .= "	<directory name=\"$moh_name\" path=\"\$\${sounds_dir}/music/$moh_dir\">\n";
						$xml .= "		<param name=\"rate\" value=\"".$moh_rate."\"/>\n";
						$xml .= "		<param name=\"shuffle\" value=\"true\"/>\n";
						$xml .= "		<param name=\"channels\" value=\"1\"/>\n";
						$xml .= "		<param name=\"interval\" value=\"20\"/>\n";
						$xml .= "		<param name=\"timer-name\" value=\"soft\"/>\n";
						$xml .= "	</directory>\n";
						$this->xml = $xml;
				}
		}

		public function save() {
			//get the contents of the template
				if (file_exists('/usr/share/examples/fusionpbx')) {
					$file_contents = file_get_contents("/usr/share/examples/fusionpbx/resources/templates/conf/autoload_configs/local_stream.conf.xml");
				}
				else {
					$file_contents = file_get_contents($_SERVER["PROJECT_ROOT"]."/resources/templates/conf/autoload_configs/local_stream.conf.xml");
				}
			//check where the default music is stored
				$default_moh_prefix = 'music/default';
				if(file_exists($_SESSION['switch']['sounds']['dir'].'/music/8000')) {
					$default_moh_prefix = 'music';
				}
			//replace the variables
				$file_contents = preg_replace("/music\/default/", $default_moh_prefix, $file_contents);
				$file_contents = preg_replace("/[\t ]*(?:<!--)?{v_moh_categories}(?:-->)?/", $this->xml, $file_contents);

			//write the XML config file
				$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/local_stream.conf.xml","w");
				fwrite($fout, $file_contents);
				fclose($fout);

			//reload the XML
				$this->reload();
		}

	}

//build and save the XML
	//require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
	//$moh = new switch_music_on_hold;
	//$moh->xml();
	//$moh->save();

?>
