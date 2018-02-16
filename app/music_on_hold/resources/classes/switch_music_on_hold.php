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
	Portions created by the Initial Developer are Copyright (C) 2010-2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
	Matthew Vale <github@mafoo.org>
*/
include "root.php";

//define the switch_music_on_hold class
	class switch_music_on_hold {

		private $music_list;
		private $domain_uuid;
		private $xml;
		private $db;

		public function __construct($domain_uuid) {
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
			$this->domain_uuid = ( strlen($domain_uuid) > 0 ? $domain_uuid : $_SESSION['domain_uuid'] );
			$this->music_list = $this->list_music();
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function select_options($selected) {
			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//music on hold
				if (count($this->music_list) > 0) {
					$select .= "	<option value=''>\n";					
					$options .= "	<optgroup label='".$text['label-music_on_hold']." - Global'>\n";
					$previous_name = '';
					foreach($this->music_list as $row) {
						if ($previous_domain != $row['domain_name']) {
							$options .= "	</optgroup>\n";
							$options .= "	<optgroup label='".$text['label-music_on_hold']." - ".$row['domain_name']."'>\n";
						}
						if ($previous_name != $row['music_on_hold_name']) {
							$name = $row['music_on_hold_name'];	
							if (strlen($row['domain_uuid']) > 0) {
								$name = $row['domain_name'].'/'.$name;
							}
							$options .= "		<option value='local_stream://".$name."' ".(($optionsed == "local_stream://".$name) ? 'selected="selected"' : null).">".$row['music_on_hold_name']."</option>\n";
						}
						$previous_name = $row['music_on_hold_name'];
						$previous_domain = $row['domain_name'];
					}
					$options .= "	</optgroup>\n";
				}

			//return the options
				return $options;
		}

		public function select($name, $selected, $options) {
			//add multi-lingual support
				$language = new text;
				$text = $language->get();

			//start the select
				$select = "<select class='formfld' name='".$name."' id='".$name."' style='width: auto;'>\n";

			//music on hold
				$select .= $this->select_options($selected);

			//recordings
				if (is_dir($_SERVER["PROJECT_ROOT"].'/app/recordings')) {
					require_once "app/recordings/resources/classes/switch_recordings.php";
					$recordings = new switch_recordings;
					$select .= $recordings->select_options($selected);
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
			return $this->list_music;
		}

		public function list_music() {
			$sql = "select ";
			$sql .= "d.domain_name, m.* ";
			$sql .= "from v_music_on_hold as m ";
			$sql .= "left join v_domains as d ON d.domain_uuid = m.domain_uuid ";
			$sql .= "where (m.domain_uuid = '".$this->domain_uuid."' or m.domain_uuid is null) ";
			$sql .= "order by m.domain_uuid desc, music_on_hold_rate asc, music_on_hold_name asc";
			$prep_statement = $this->db->prepare(check_sql($sql));
			$prep_statement->execute();
			return $prep_statement->fetchAll(PDO::FETCH_NAMED);
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
			//get the list of moh
				$xml = "";
				if (count($this->music_list) > 0) {
					foreach($this->music_list as $row) {
						$name = $row['music_on_hold_name'];
						if (strlen($row['domain_uuid']) > 0) {
							$name = $row['domain_name'].'/'.$name;	
						}
						$xml .= "	<directory name=\"$name/".$row['music_on_hold_rate']."\" path=\"\$\${sounds_dir}/music/$name/".$row['music_on_hold_rate']."\">\n";
						$xml .= "		<param name=\"rate\" value=\"".$row['music_on_hold_rate']."\"/>\n";
						$xml .= "		<param name=\"shuffle\" value=\"".$row['music_on_hold_shuffle']."\"/>\n";
						$xml .= "		<param name=\"channels\" value=\"".( strlen($row['music_on_hold_channels']) > 0 ? $row['music_on_hold_channels'] : 1 )."\"/>\n";
						$xml .= "		<param name=\"interval\" value=\"".( strlen($row['music_on_hold_interval']) > 0 ? $row['music_on_hold_interval'] : 20 )."\"/>\n";
						$xml .= "		<param name=\"timer-name\" value=\"".( strlen($row['music_on_hold_timer_name']) > 0 ? $row['music_on_hold_timer_name'] : 'soft' )."\"/>\n";
						( strlen($row['music_on_hold_chime_list']) > 0 ? $xml .= "		<param name=\"chime-list\" value=\"".$row['music_on_hold_chime_list']."\"/>\n" : null);
						( strlen($row['music_on_hold_chime_freq']) > 0 ? $xml .= "		<param name=\"chime-freq\" value=\"".$row['music_on_hold_chime_freq']."\"/>\n" : null);
						( strlen($row['music_on_hold_chime_max']) > 0 ? $xml .= "		<param name=\"chime-max\" value=\"".$row['music_on_hold_chime_max']."\"/>\n" : null);
						$xml .= "	</directory>\n";
					}
				}
			// store the xml
				$this->xml = $xml;
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
