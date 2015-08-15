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
	Copyright (C) 2015
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
			public $destination_number;

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

				//delete previous dialplan
					if (strlen($this->dialplan_uuid) > 0) {
						//delete the previous dialplan
						$sql = "delete from v_dialplans ";
						$sql .= "where dialplan_uuid = '".$this->dialplan_uuid."' ";
						$sql .= "and domain_uuid = '".$this->domain_uuid."' ";
						$this->db->exec($sql);

						$sql = "delete from v_dialplan_details ";
						$sql .= "where dialplan_uuid = '".$this->dialplan_uuid."' ";
						$sql .= "and domain_uuid = '".$this->domain_uuid."' ";
						$this->db->exec($sql);
						unset($sql);
					}
					unset($prep_statement);

				//build the dialplan array
					$dialplan["app_uuid"] = "24108154-4ac3-1db6-1551-4731703a4440";
					$dialplan["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_name"] = ($this->fax_name != '') ? $this->fax_name : format_phone($this->destination_number);
					$dialplan["dialplan_number"] = $this->destination_number;
					$dialplan["dialplan_context"] = $_SESSION['context'];
					$dialplan["dialplan_continue"] = "false";
					$dialplan["dialplan_order"] = "310";
					$dialplan["dialplan_enabled"] = "true";
					$dialplan["dialplan_description"] = $this->fax_description;
					$dialplan_detail_order = 10;

				//add the public condition
					$y = 1;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "^".$this->destination_number."\$";
					$dialplan["dialplan_details"][$y]["dialplan_detail_break"] = "";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "answer";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "fax_uuid=".$this->fax_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "api_hangup_hook=lua app/fax/resources/scripts/hangup_rx.lua";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					foreach($_SESSION['fax']['variable'] as $data) {
						$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
						$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
						$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
						if (substr($data,0,8) == "inbound:") {
							$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = substr($data,8,strlen($data));
						}
						elseif (substr($data,0,9) == "outbound:") {}
						else { 
							$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $data;
						}
						$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
						$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
						$y++;
					}
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "last_fax=\${caller_id_number}-\${strftime(%Y-%m-%d-%H-%M-%S)}";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "playback";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "silence_stream://2000";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "rxfax";
					if (count($_SESSION["domains"]) > 1) {
						$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $_SESSION['switch']['storage']['dir'].'/fax/'.$_SESSION['domain_name'].'/'.$fax_extension.'/inbox/'.$forward_prefix.'${last_fax}.tif';
					}
					else {
						$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $_SESSION['switch']['storage']['dir'].'/fax/'.$fax_extension.'/inbox/'.$forward_prefix.'${last_fax}.tif';
					}
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "hangup";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;

				//add the dialplan permission
					$p = new permissions;
					$p->add("dialplan_add", 'temp');
					$p->add("dialplan_detail_add", 'temp');
					$p->add("dialplan_edit", 'temp');
					$p->add("dialplan_detail_edit", 'temp');

				//save the dialplan
					$orm = new orm;
					$orm->name('dialplans');
					$orm->save($dialplan);
					$dialplan_response = $orm->message;
					$this->dialplan_uuid = $dialplan_response['uuid'];

				//if new dialplan uuid then update the call center queue
					$sql = "update v_fax ";
					$sql .= "set dialplan_uuid = '".$this->dialplan_uuid."' ";
					$sql .= "where fax_uuid = '".$this->fax_uuid."' ";
					$sql .= "and domain_uuid = '".$this->domain_uuid."' ";
					$this->db->exec($sql);
					unset($sql);

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
$c->fax_description = "";
$c->destination_number = "";
$c->dialplan();
*/

?>