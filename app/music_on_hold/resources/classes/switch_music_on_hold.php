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
if (!class_exists('switch_music_on_hold')) {
	class switch_music_on_hold {

		/**
		 * declare private variables
		 */
		private $xml;
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
			$this->app_name = 'music_on_hold';
			$this->app_uuid = '1dafe0f8-c08a-289b-0312-15baf4f20f81';
			$this->permission_prefix = 'music_on_hold_';
			$this->list_page = 'music_on_hold.php';
			$this->table = 'music_on_hold';
			$this->uuid_prefix = 'music_on_hold_';

		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
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
					if (is_array($recordings) && sizeof($recordings) > 0) {
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
				if (is_array($options) && sizeof($options) > 0) {
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

		/**
		 * read the music files to add the music on hold into the database
		 */
		public function import() {
			//get the domains
				$sql = "select * from v_domains ";
				$database = new database;
				$domains = $database->select($sql, null, 'all');
				unset($sql);

			//get the music_on_hold array
				$sql = "select * from v_music_on_hold ";
				$sql .= "order by domain_uuid desc, music_on_hold_name asc, music_on_hold_rate asc";
				$database = new database;
				$music_on_hold = $database->select($sql, null, 'all');
				unset($sql);

			//build an array of the sound files
				$music_directory =  $_SESSION['switch']['sounds']['dir'].'/music';
				if (file_exists($music_directory)) {
					$files = array_merge(glob($music_directory.'/*/*/*.wav'), glob($music_directory.'/*/*/*/*.wav'), glob($stream_path.'/*/*/*/*.mp3'), glob($stream_path.'/*/*/*/*.ogg'));
				}

			//build a new file array
				foreach($files as $file) {
					$path = substr($file, strlen($music_directory.'/'));
					$path = str_replace("\\", "/", $path);
					$path_array = explode("/", $path);
					$file_array[$path_array[0]][$path_array[1]][$path_array[2]] = dirname($file);
					//echo "domain_name ".$path_array[0]."<br />\n";
					//echo "category_name ".$path_array[1]."<br />\n";
				}
				//view_array($file_array);

			//prepare the data
				$i = 0;
				foreach($file_array as $domain_name => $a1) {
					foreach($a1 as $category_name => $a2) {
						foreach($a2 as $sample_rate => $file_path) {
							//echo "domain_name ".$domain_name."<br />\n";
							//echo "category_name ".$category_name."<br />\n";
							foreach($domains as $domain) {
								//view_array($field, false);
								if ($field['domain_name'] === $domain_name) {
									$domain_uuid = $domain['domain_uuid'];
									//echo "domain_uuid ".$domain_uuid."<br />\n";
								}
							}

							if ($domain_name == 'global' || $domain_name == 'default') {
								$domain_uuid = null;
							}
							//view_array($row, false);

							$array['music_on_hold'][$i]['music_on_hold_uuid'] = uuid();
							$array['music_on_hold'][$i]['domain_uuid'] = $domain_uuid;
							$array['music_on_hold'][$i]['music_on_hold_name'] = $category_name;
							$array['music_on_hold'][$i]['music_on_hold_path'] = $file_path;
							$array['music_on_hold'][$i]['music_on_hold_rate'] = strlen($sample_rate) != 0 ? $sample_rate : null;
							$array['music_on_hold'][$i]['music_on_hold_shuffle'] = 'false';
							$array['music_on_hold'][$i]['music_on_hold_channels'] = 1;
							$array['music_on_hold'][$i]['music_on_hold_interval'] = 20;
							$array['music_on_hold'][$i]['music_on_hold_timer_name'] = 'soft';
							$array['music_on_hold'][$i]['music_on_hold_chime_list'] = null;
							$array['music_on_hold'][$i]['music_on_hold_chime_freq'] = null;
							$array['music_on_hold'][$i]['music_on_hold_chime_max'] = null;
							$i++;
						}
					}
				}
				//view_array($array, false);

			//save the data
				$p = new permissions;
				$p->add('music_on_hold_add', 'temp');

				$database = new database;
				$database->app_name = 'music_on_hold';
				$database->app_uuid = '1dafe0f8-c08a-289b-0312-15baf4f20f81';
				$database->save($array);
				//echo $database->message;
				unset($array);

				$p->delete('music_on_hold_add', 'temp');	
		}

		/**
		 * delete records/files
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter checked records
							foreach ($records as $music_on_hold_uuid => $record) {
								if (is_uuid($music_on_hold_uuid)) {
									if ($record['checked'] == 'true') {
										$moh[$music_on_hold_uuid]['delete'] = true;
									}
									foreach ($record as $key => $array) {
										if (is_numeric($key) && is_array($array) && @sizeof($array) != 0 && $array['checked'] == 'true') {
											$moh[$music_on_hold_uuid][] = $array['file_name'];
										}
									}
								}
							}
							unset($array);

						//loop checked records
							$files_deleted = 0;
							if (is_array($moh) && @sizeof($moh) != 0) {

								//get music on hold details
									$sql = "select * from v_music_on_hold ";
									$sql .= "where (domain_uuid = :domain_uuid ".(!permission_exists('music_on_hold_domain') ? "": "or domain_uuid is null ").") ";
									$sql .= "and music_on_hold_uuid in ('".implode("','", array_keys($moh))."') ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										foreach ($rows as $row) {
											$streams[$row['music_on_hold_uuid']] = $row;
										}
									}
									unset($sql, $parameters, $rows, $row);

								//delete files, folders, build delete array
									$x = 0;
									foreach ($moh as $music_on_hold_uuid => $row) {

										//prepare path
											$stream_path = $streams[$music_on_hold_uuid]['music_on_hold_path'];
											$stream_path = str_replace('$${sounds_dir}', $_SESSION['switch']['sounds']['dir'], $stream_path);

										//delete checked files
											foreach ($row as $key => $stream_file) {
												if (is_numeric($key)) {
													$stream_file_path = str_replace('../', '', path_join($stream_path, $stream_file));
													if (@unlink($stream_file_path)) {
														$files_deleted++;
													}
												}
											}

										//delete name rate
											if ($row['delete']) {

												//build delete array
													$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $music_on_hold_uuid;
													if (!permission_exists('music_on_hold_domain')) {
														$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
													}
													$x++;

												//delete rate folder
													@rmdir($stream_path);

												//delete name (category) folder, if empty
													$name_path = dirname($stream_path);
													if (@sizeof(scandir($name_path)) == 2) { //empty (only /.. and /. remaining)
 														@rmdir($name_path);
													}
											}

									}

							}

						//delete the moh records
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//set flag
									$moh_deleted = true;

							}
							unset($records, $moh);

						//post delete
							if ($moh_deleted || $files_deleted) {
								//clear the cache
									$cache = new cache;
									$cache->delete("configuration:local_stream.conf");

								//reload moh
									$this->reload();

								//set message
									message::add($text['message-delete']);
							}

					}

			}
		} //method

	} //class
}

//build and save the XML
	//require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
	//$moh = new switch_music_on_hold;
	//$moh->xml();
	//$moh->save();

?>
