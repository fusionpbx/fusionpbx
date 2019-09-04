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
	Copyright (C) 2015-2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * cache class provides an abstracted cache
 *
 * @method string dialplan - builds the dialplan for the fax servers
 */
//define the fax class
	if (!class_exists('fax')) {
		class fax {
			/**
			 * define the variables
			 */
			public $domain_uuid;
			public $fax_uuid;
			public $dialplan_uuid;
			public $fax_name;
			public $fax_description;
			public $fax_extension;
			public $fax_forward_number;
			public $destination_number;
			private $forward_prefix;

			/**
			 * Called when the object is created
			 */
			public function __construct() {
				//place holder
			}

			/**
			 * Called when there are no references to a particular object
			 * unset the variables used in the class
			 */
			public function __destruct() {
				foreach ($this as $key => $value) {
					unset($this->$key);
				}
			}

			/**
			 * Add a dialplan for call center
			 * @var string $domain_uuid		the multi-tenant id
			 * @var string $value	string to be cached
			 */
			public function dialplan() {

				//normalize the fax forward number
					if (strlen($this->fax_forward_number) > 3) {
						//$fax_forward_number = preg_replace("~[^0-9]~", "",$fax_forward_number);
						$this->fax_forward_number = str_replace(" ", "", $this->fax_forward_number);
						$this->fax_forward_number = str_replace("-", "", $this->fax_forward_number);
					}

				//set the forward prefix
					if (strripos($this->fax_forward_number, '$1') === false) {
						$this->forward_prefix = ''; //not found
					} else {
						$this->forward_prefix = $this->forward_prefix.$this->fax_forward_number.'#'; //found
					}

				//set the dialplan_uuid
					if (strlen($this->dialplan_uuid) == 0) {
						$this->dialplan_uuid = uuid();
					}
					else {
						//build previous details delete array
							$array['dialplan_details'][0]['dialplan_uuid'] = $this->dialplan_uuid;
							$array['dialplan_details'][0]['domain_uuid'] = $this->domain_uuid;

						//grant temporary permissions
							$p = new permissions;
							$p->add('dialplan_detail_delete', 'temp');

						//execute delete
							$database = new database;
							$database->app_name = 'fax';
							$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
							$database->delete($array);
							unset($array);

						//revoke temporary permissions
							$p->delete('dialplan_detail_delete', 'temp');
					}

				//set the fax name
		 			$fax_name = ($this->fax_name != '') ? $this->fax_name : format_phone($this->destination_number);
		 
		 		//set the  last fax
		 			if (strlen($_SESSION['fax']['last_fax']['text']) > 0) {
						$last_fax = "last_fax=".$_SESSION['fax']['last_fax']['text'];
					}
					else {
						$last_fax = "last_fax=\${caller_id_number}-\${strftime(%Y-%m-%d-%H-%M-%S)}";
					}
				
				//set the rx_fax
					$rxfax_data = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'].'/'.$this->fax_extension.'/inbox/'.$this->forward_prefix.'${last_fax}.tif';
		
				//build the xml dialplan
					$dialplan_xml = "<extension name=\"".$fax_name ."\" continue=\"false\" uuid=\"".$this->dialplan_uuid."\">\n";
					$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$this->destination_number."$\">\n";
					$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"fax_uuid=".$this->fax_uuid."\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"api_hangup_hook=lua app/fax/resources/scripts/hangup_rx.lua\"/>\n";
					foreach($_SESSION['fax']['variable'] as $data) {
						if (substr($data,0,8) == "inbound:") {
							$dialplan_xml .= "		<action application=\"set\" data=\"".substr($data,8,strlen($data))."\"/>\n";
						}
						elseif (substr($data,0,9) == "outbound:") {}
						else {
							$dialplan_xml .= "		<action application=\"set\" data=\"".$data."\"/>\n";
						}
					}
					$dialplan_xml .= "		<action application=\"set\" data=\"".$last_fax."\"/>\n";
					$dialplan_xml .= "		<action application=\"playback\" data=\"silence_stream://2000\"/>\n";
					$dialplan_xml .= "		<action application=\"rxfax\" data=\"$rxfax_data\"/>\n";
					$dialplan_xml .= "		<action application=\"hangup\" data=\"\"/>\n";
					$dialplan_xml .= "	</condition>\n";
					$dialplan_xml .= "</extension>\n";

				//build the dialplan array
					$dialplan["app_uuid"] = "24108154-4ac3-1db6-1551-4731703a4440";
					$dialplan["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_uuid"] = $this->dialplan_uuid;
					$dialplan["dialplan_name"] = ($this->fax_name != '') ? $this->fax_name : format_phone($this->destination_number);
					$dialplan["dialplan_number"] = $this->fax_extension;
					$dialplan["dialplan_context"] = $_SESSION['context'];
					$dialplan["dialplan_continue"] = "false";
					$dialplan["dialplan_xml"] = $dialplan_xml;
					$dialplan["dialplan_order"] = "310";
					$dialplan["dialplan_enabled"] = "true";
					$dialplan["dialplan_description"] = $this->fax_description;
					$dialplan_detail_order = 10;

				//prepare the array
					$array['dialplans'][] = $dialplan;

				//add the dialplan permission
					$p = new permissions;
					$p->add("dialplan_add", 'temp');
					$p->add("dialplan_detail_add", 'temp');
					$p->add("dialplan_edit", 'temp');
					$p->add("dialplan_detail_edit", 'temp');

				//save the dialplan
					$database = new database;
					$database->app_name = 'fax';
					$database->app_uuid = '24108154-4ac3-1db6-1551-4731703a4440';
					$database->save($array);
					//$message = $database->message;

				//remove the temporary permission
					$p->delete("dialplan_add", 'temp');
					$p->delete("dialplan_detail_add", 'temp');
					$p->delete("dialplan_edit", 'temp');
					$p->delete("dialplan_detail_edit", 'temp');

				//synchronize the xml config
					save_dialplan_xml();

				//clear the cache
					$cache = new cache;
					$cache->delete("dialplan:".$_SESSION['context']);

				//return the dialplan_uuid
					return $dialplan_response;

			}
		}
	}

/*
$o = new fax;
$c->domain_uuid = "";
$c->dialplan_uuid = "";
$c->fax_name = "";
$c->fax_extension = $fax_extension;
$c->fax_forward_number = $fax_forward_number;
$c->destination_number = $fax_destination_number;
$c->fax_description = $fax_description;
$c->dialplan();
*/

?>