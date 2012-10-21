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
*/
include "root.php";

//define the directory class
	class switch_music_on_hold {

		public $domain_uuid;
		public $domain_name;
		public $select_name;
		public $select_value;

		public function __construct() {
			require_once "includes/classes/database.php";
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

			//start the select
				$select = "";
				$select .= "	<select class='formfld' name='hold_music' id='hold_music' style='width: auto;'>\n";
				$select .= "		<option value='' style='font-style: italic;'>Default</option>\n";

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

			//end the select and return it
				$select .= "	</select>\n";
				return $select;
		}

	}

//require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
//$moh= new switch_music_on_hold;
//$moh->select_name = "hold_music";
//$moh->select_value = $hold_music;
//echo $moh->select();

?>