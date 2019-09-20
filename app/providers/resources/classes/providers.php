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
	Portions created by the Initial Developer are Copyright (C) 2017 - 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/


/**
 * providers class provides methods for providing an overview
 *
 * @method boolean add
 */
if (!class_exists('providers')) {
	class providers {

		//define variables
		public $debug;
		public $domain_uuid;
		public $array;

		/**
		 * Called when the object is created
		 */
		public function __construct() {

		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			if (isset($this)) foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * nodes array
		 */
		public function nodes($provider) {

			//build the skyetel node array
				if ($provider == 'skyetel') {
					$x = 0;
					$nodes[$x]['uuid'] = 'af26484f-03a1-4f93-857b-b8cef1ac28f5';
					$nodes[$x]['cidr'] = '52.41.52.34/32';
					$nodes[$x]['description'] = 'Skyetel - North West';
					$x++;
					$nodes[$x]['uuid'] = '87eb7803-9b96-41d3-ac5a-42ed3f213777';
					$nodes[$x]['cidr'] = '52.8.201.128/32';
					$nodes[$x]['description'] = 'Skyetel - South West';
					$x++;
					$nodes[$x]['uuid'] = 'cf61ab01-1465-4eca-9152-2e6fd4a02073';
					$nodes[$x]['cidr'] = '52.60.138.31/32';
					$nodes[$x]['description'] = 'Skyetel - North East';
					$x++;
					$nodes[$x]['uuid'] = 'b43ebeef-c214-492b-8f22-4bfc6b647668';
					$nodes[$x]['cidr'] = '50.17.48.216/32';
					$nodes[$x]['description'] = 'Skyetel - South East';
					$x++;
					$nodes[$x]['uuid'] = '28615834-4517-4515-b474-5ca54ee958fc';
					$nodes[$x]['cidr'] = '35.156.192.164/32';
					$nodes[$x]['description'] = 'Skyetel - Europe';
				}

			//build the voicetel node array
				if ($provider == 'voicetel') {
					$x = 0;
					$nodes[$x]['uuid'] = 'dae9ab41-73c1-4792-bfdb-eccbedeebac9';
					$nodes[$x]['cidr'] = '104.225.6.160/29';
					$nodes[$x]['description'] = 'VoiceTel - SJC';
					$x++;
					$nodes[$x]['uuid'] = '3167d47d-0679-4336-b4c4-be68cdc28e5b';
					$nodes[$x]['cidr'] = '104.225.13.72/29';
					$nodes[$x]['description'] = 'VoiceTel - DFW';
					$x++;
					$nodes[$x]['uuid'] = 'e3d2e9b8-5807-4175-9ffc-57480ac94f83';
					$nodes[$x]['cidr'] = '148.59.176.0/23';
					$nodes[$x]['description'] = 'VoiceTel - Anycast';
					$x++;
					$nodes[$x]['uuid'] = 'a1d70666-ab25-425a-9884-60530f4dd8b7';
					$nodes[$x]['cidr'] = '192.73.246.104/29';
					$nodes[$x]['description'] = 'VoiceTel - DEN';
					$x++;
					$nodes[$x]['uuid'] = '0bf5450f-c23b-4139-9e89-884bd0972912';
					$nodes[$x]['cidr'] = '192.73.250.96/29';
					$nodes[$x]['description'] = 'VoiceTel - ORD';
					$x++;
					$nodes[$x]['uuid'] = '4c60a07b-d166-4792-8af7-02b7d4ea53ca';
					$nodes[$x]['cidr'] = '199.101.184.144/29';
					$nodes[$x]['description'] = 'VoiceTel - CLT';
					$x++;
					$nodes[$x]['uuid'] = '181dcaf8-f990-4869-90e3-97de7d2daa5a';
					$nodes[$x]['cidr'] = '192.73.251.104/29';
					$nodes[$x]['description'] = 'VoiceTel - NYC';
					$x++;
					$nodes[$x]['uuid'] = 'b32db3ae-4ce8-4793-ad30-fbb6478b9249';
					$nodes[$x]['cidr'] = '94.75.246.128/25';
					$nodes[$x]['description'] = 'VoiceTel - AMS';
					$x++;
					$nodes[$x]['uuid'] = '5782acb1-845c-4d33-8241-569f83854283';
					$nodes[$x]['cidr'] = '50.18.88.40/32';
					$nodes[$x]['description'] = 'VoiceTel - Amazon West';
					$x++;
					$nodes[$x]['uuid'] = 'de15a965-46a7-4e26-a495-7dba758bbd72';
					$nodes[$x]['cidr'] = '52.9.226.100/32';
					$nodes[$x]['description'] = 'VoiceTel - Amazon West 2';
					$x++;
					$nodes[$x]['uuid'] = '5782acb1-845c-4d33-8241-569f83854283';
					$nodes[$x]['cidr'] = '52.201.88.130/32';
					$nodes[$x]['description'] = 'VoiceTel - Amazon East';
					$x++;
					$nodes[$x]['uuid'] = '0df05db5-1dea-4c46-b9c5-6af1ff87b929';
					$nodes[$x]['cidr'] = '52.20.181.50/32';
					$nodes[$x]['description'] = 'VoiceTel - Amazon East 2';
					$x++;
					$nodes[$x]['uuid'] = 'a48edb1c-6eb2-4015-b23f-872f96421bca';
					$nodes[$x]['cidr'] = '104.197.10.30/32';
					$nodes[$x]['description'] = 'VoiceTel - GCE North';
				}

			//return the array
				return $nodes;
		}

		/**
		 * gateways array
		 */
		public function gateways($provider) {

			//build the arrays
				if ($provider == 'skyetel') {
					$x=0;
					$gateways[$x]['uuid'] = '22245a48-552c-463a-a723-ce01ebbd69a2';
					$gateways[$x]['name'] = 'term.skyetel.com';
					$gateways[$x]['proxy'] = 'term.skyetel.com';
					$gateways[$x]['username'] = 'username';
					$gateways[$x]['password'] = 'password';
					$gateways[$x]['register'] = 'false';
					$gateways[$x]['caller_id_in_from'] = 'true';
					$gateways[$x]['supress_cng'] = 'true';
					$gateways[$x]['sip_cid_type'] = 'pid';
					$gateways[$x]['enabled'] = 'true';
					$x++;
					$gateways[$x]['uuid'] = 'b171ba70-06a5-4560-82be-596ed9d00041';
					$gateways[$x]['name'] = 'skyetel.34';
					$gateways[$x]['proxy'] = '52.41.52.34';
					$gateways[$x]['username'] = 'username';
					$gateways[$x]['password'] = 'password';
					$gateways[$x]['register'] = 'false';
					$gateways[$x]['caller_id_in_from'] = 'true';
					$gateways[$x]['supress_cng'] = 'true';
					$gateways[$x]['sip_cid_type'] = 'pid';
					$gateways[$x]['enabled'] = 'true';
					$x++;
					$gateways[$x]['uuid'] = '4864ac6e-9e50-4fff-8381-2c508f8912b5';
					$gateways[$x]['name'] = 'skyetel.128';
					$gateways[$x]['proxy'] = '52.8.201.128';
					$gateways[$x]['username'] = 'username';
					$gateways[$x]['password'] = 'password';
					$gateways[$x]['register'] = 'false';
					$gateways[$x]['caller_id_in_from'] = 'true';
					$gateways[$x]['supress_cng'] = 'true';
					$gateways[$x]['sip_cid_type'] = 'pid';
					$gateways[$x]['enabled'] = 'true';
					$x++;
					$gateways[$x]['uuid'] = '5553606b-e543-4427-bb63-ebed16001937';
					$gateways[$x]['name'] = 'skyetel.216';
					$gateways[$x]['proxy'] = '50.17.48.216';
					$gateways[$x]['username'] = 'username';
					$gateways[$x]['password'] = 'password';
					$gateways[$x]['register'] = 'false';
					$gateways[$x]['caller_id_in_from'] = 'true';
					$gateways[$x]['supress_cng'] = 'true';
					$gateways[$x]['sip_cid_type'] = 'pid';
					$gateways[$x]['enabled'] = 'true';
					$x++;
					$gateways[$x]['uuid'] = '4939b402-c307-4261-b271-4126e6e703b4';
					$gateways[$x]['name'] = 'hc.skyetel.com';
					$gateways[$x]['proxy'] = 'hc.skyetel.com';
					$gateways[$x]['username'] = 'username';
					$gateways[$x]['password'] = 'password';
					$gateways[$x]['register'] = 'false';
					$gateways[$x]['caller_id_in_from'] = 'true';
					$gateways[$x]['supress_cng'] = 'true';
					$gateways[$x]['sip_cid_type'] = 'pid';
					$gateways[$x]['enabled'] = 'false';
					$x++;
					$gateways[$x]['uuid'] = 'a66925c6-8057-4be8-8ee6-8d3a16ac851d';
					$gateways[$x]['name'] = 'skyetel.hc.28';
					$gateways[$x]['proxy'] = '52.32.223.28';
					$gateways[$x]['username'] = 'username';
					$gateways[$x]['password'] = 'password';
					$gateways[$x]['register'] = 'false';
					$gateways[$x]['caller_id_in_from'] = 'true';
					$gateways[$x]['supress_cng'] = 'true';
					$gateways[$x]['sip_cid_type'] = 'pid';
					$gateways[$x]['enabled'] = 'false';
					$x++;
					$gateways[$x]['uuid'] = 'ea361ca5-c815-4e33-be07-ec1ecdb9b12e';
					$gateways[$x]['name'] = 'skyetel.hc.107';
					$gateways[$x]['proxy'] = '52.4.178.107';
					$gateways[$x]['username'] = 'username';
					$gateways[$x]['password'] = 'password';
					$gateways[$x]['register'] = 'false';
					$gateways[$x]['caller_id_in_from'] = 'true';
					$gateways[$x]['supress_cng'] = 'true';
					$gateways[$x]['sip_cid_type'] = 'pid';
					$gateways[$x]['enabled'] = 'false';
				}
				if ($provider == 'voicetel') {
					$x=0;
					$gateways[$x]['uuid'] = 'd61be0f0-3a4c-434a-b9f6-4fef15e1a634';
					$gateways[$x]['name'] = 'VoiceTel';
					$gateways[$x]['proxy'] = 'geo.voicetel.com';
					$gateways[$x]['username'] = 'username';
					$gateways[$x]['password'] = 'password';
					$gateways[$x]['register'] = 'false';
					$gateways[$x]['enabled'] = 'true';
					$x++;
				}

			//return the array
				return $gateways;
		}


		/**
		 * outbound_routes array
		 */
		public function outbound_routes($provider) {

			//set the application uuid
				$app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';

			//build the array
				if ($provider == 'skyetel') {
					$x = 0;
					$outbound_routes[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
					$outbound_routes[$x]['dialplan_uuid'] = '777bf012-9746-4ccb-a7cc-95c1714f15fe';
					$outbound_routes[$x]['app_uuid'] = $app_uuid;
					$outbound_routes[$x]['dialplan_name'] = $provider;
					$outbound_routes[$x]['dialplan_order'] = '100';
					$outbound_routes[$x]['dialplan_continue'] = 'false';
					$outbound_routes[$x]['dialplan_expression'] = '^\+?1?(\d{10})$';
					$outbound_routes[$x]['dialplan_prefix'] = '1';
					$outbound_routes[$x]['dialplan_variables'][] = 'sip_h_X-Tenant=${domain_name}';
					$outbound_routes[$x]['dialplan_variables'][] = 'effective_caller_id_name=${outbound_caller_id_name}';
					$outbound_routes[$x]['dialplan_variables'][] = 'effective_caller_id_number=${outbound_caller_id_number}';
					$outbound_routes[$x]['dialplan_context'] = '${domain_name}';
					$outbound_routes[$x]['dialplan_enabled'] = 'true';
					$outbound_routes[$x]['dialplan_description'] = '10-11 digits';
					$x++;
					$outbound_routes[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
					$outbound_routes[$x]['dialplan_uuid'] = '43273e08-6cfb-4171-bd1d-4b3b9168ca66';
					$outbound_routes[$x]['app_uuid'] = $app_uuid;
					$outbound_routes[$x]['dialplan_name'] = $provider;
					$outbound_routes[$x]['dialplan_order'] = '100';
					$outbound_routes[$x]['dialplan_continue'] = 'false';
					$outbound_routes[$x]['dialplan_expression'] = '(^911$|^933$)';
					$outbound_routes[$x]['dialplan_prefix'] = '';
					$outbound_routes[$x]['dialplan_variables'][] = 'sip_h_X-Tenant=${domain_name}';
					$outbound_routes[$x]['dialplan_variables'][] = 'effective_caller_id_name=${emergency_caller_id_name}';
					$outbound_routes[$x]['dialplan_variables'][] = 'effective_caller_id_number=${emergency_caller_id_number}';
					$outbound_routes[$x]['dialplan_context'] = '${domain_name}';
					$outbound_routes[$x]['dialplan_enabled'] = 'true';
					$outbound_routes[$x]['dialplan_description'] = '911 emergency';
				}
				if ($provider == 'voicetel') {
					$x = 0;
					$outbound_routes[$x]['domain_uuid'] = $_SESSION['domain_uuid'];
					$outbound_routes[$x]['dialplan_uuid'] = '513e3710-1cbd-48da-b8f1-792eae471d3a';
					$outbound_routes[$x]['app_uuid'] = $app_uuid;
					$outbound_routes[$x]['dialplan_name'] = $provider;
					$outbound_routes[$x]['dialplan_order'] = '100';
					$outbound_routes[$x]['dialplan_continue'] = 'false';
					$outbound_routes[$x]['dialplan_expression'] = '^\+?1?(\d{10})$';
					$outbound_routes[$x]['dialplan_prefix'] = '1';
					$outbound_routes[$x]['dialplan_variables'][] = 'effective_caller_id_name=${outbound_caller_id_name}';
					$outbound_routes[$x]['dialplan_variables'][] = 'effective_caller_id_number=${outbound_caller_id_number}';
					$outbound_routes[$x]['dialplan_context'] = '${domain_name}';
					$outbound_routes[$x]['dialplan_enabled'] = 'true';
					$outbound_routes[$x]['dialplan_description'] = '10-11 digits';
				}

			//return the array
				return $outbound_routes;
		}

		/**
		 * setup the provider
		 */
		public function setup($provider) {

			//validate the provider
				switch ($provider) {
					case 'voicetel':
						break;
					case 'skyetel': 
						break;
					default: 
						$provider = '';
				}

			//get the domains access control uuid
				$sql = "select access_control_uuid from v_access_controls ";
				$sql .= "where access_control_name = 'domains' ";
				if ($this->debug) {
					echo $sql."<br />\n";
				}
				$database = new database;
				$access_control_uuid = $database->select($sql, null, 'column');
				unset($sql);

			//get the existing nodes
				$sql = "select * from v_access_control_nodes ";
				$sql .= "where access_control_uuid = :access_control_uuid ";
				$sql .= "and node_cidr <> '' ";
				if ($this->debug) {
					echo $sql."<br />\n";
				}
				$parameters['access_control_uuid'] = $access_control_uuid;
				$database = new database;
				$access_control_nodes = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);

			//get the sip profile name
				$sql = "select sip_profile_name from v_sip_profiles ";
				$sql .= "where sip_profile_enabled = 'true' ";
				$sql .= "order by sip_profile_name asc ";
				$sql .= "limit 1; ";
				if ($this->debug) {
					echo $sql."<br />\n";
				}
				$database = new database;
				$sip_profile_name = $database->select($sql, null, 'column');
				unset($sql);

			//add the nodes
				$x = 0;
				$nodes = $this->nodes($provider);
				foreach ($nodes as $row) {
					$array['access_control_nodes'][$x]['access_control_node_uuid'] = $row['uuid'];
					$array['access_control_nodes'][$x]['access_control_uuid'] = $access_control_uuid;
					$array['access_control_nodes'][$x]['node_type'] = 'allow';
					$array['access_control_nodes'][$x]['node_cidr'] = $row['cidr'];
					$array['access_control_nodes'][$x]['node_description']= $row['description'];
					$x++;
				}

			//add gateways
				$x = 0;
				$gateways = $this->gateways($provider);
				foreach ($gateways as $row) {
					$array['gateways'][$x]['gateway_uuid'] = $row['uuid'];
					$array['gateways'][$x]['gateway'] = $row['name'];
					$array['gateways'][$x]['username'] = $row['username'];
					$array['gateways'][$x]['password'] = $row['password'];
					$array['gateways'][$x]['proxy'] = $row['proxy'];
					$array['gateways'][$x]['register'] = $row['register'];
					$array['gateways'][$x]['retry_seconds'] = '30';
					$array['gateways'][$x]['ping'] = '90';
					$array['gateways'][$x]['expire_seconds'] = '800';
					if (isset($row['supress_cng'])) {
						$array['gateways'][$x]['caller_id_in_from'] = $row['caller_id_in_from'];
					}
					if (isset($row['supress_cng'])) {
						$array['gateways'][$x]['supress_cng'] = $row['supress_cng'];
					}
					if (isset($row['sip_cid_type'])) {
						$array['gateways'][$x]['sip_cid_type'] = $row['sip_cid_type'];
					}
					$array['gateways'][$x]['context'] = 'public';
					$array['gateways'][$x]['profile'] = $sip_profile_name;
					$array['gateways'][$x]['enabled'] = $row['enabled'];
					$array['gateways'][$x]['description'] = '';
					$x++;
				}

			//add outbound routes
				$x = 0;
				$outbound_routes = $this->outbound_routes($provider);
				foreach ($outbound_routes as $row) {
					//$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
					$array['dialplans'][$x]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['app_uuid'] = $row['app_uuid'];
					$array['dialplans'][$x]['dialplan_name'] = $row['dialplan_name'];
					$array['dialplans'][$x]['dialplan_order'] = $row['dialplan_order'];
					$array['dialplans'][$x]['dialplan_continue'] = $row['dialplan_continue'];
					$array['dialplans'][$x]['dialplan_context'] = $row['dialplan_context'];
					$array['dialplans'][$x]['dialplan_enabled'] = $row['dialplan_enabled'];
					$array['dialplans'][$x]['dialplan_description'] = $row['dialplan_description'];
					$y = 0;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = '${user_exists}';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'false';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'condition';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'destination_number';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $row['dialplan_expression'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'sip_h_X-accountcode=${accountcode}';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;

					if (is_array($row['dialplan_variables'])) {
						foreach($row['dialplan_variables'] as $value) {
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = $value;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
							$y++;
						}
					}
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'call_direction=outbound';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'unset';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'call_timeout';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'hangup_after_bridge=true';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'inherit_codec=true';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'ignore_display_updates=true';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'callee_id_number=$1';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'set';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'continue_on_fail=true';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
					$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'sleep';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = '${sleep}';
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
					$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
					$y++;
					foreach ($gateways as $gateway) {
						if ($gateway['enabled'] == 'true') {
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
							$array['dialplans'][$x]['dialplan_details'][$y]['domain_uuid'] = $row['domain_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_uuid'] = $row['dialplan_uuid'];
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_tag'] = 'action';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_type'] = 'bridge';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_data'] = 'sofia/gateway/'.$gateway['uuid'].'/'.$row['dialplan_prefix'].'$1';
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_order'] = $y * 10;
							$array['dialplans'][$x]['dialplan_details'][$y]['dialplan_detail_group'] = '0';
						}
						$y++;
					}
					$x++;
				}

			//save to the data
				$database = new database;
				$database->app_name = 'outbound_routes';
				$database->app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';
				$database->save($array);
				unset($array);

			//update the dialplan xml
				$dialplans = new dialplan;
				$dialplans->source = "details";
				$dialplans->destination = "database";
				$dialplans->uuid = $dialplan_uuid;
				$dialplans->xml();

			//create the event socket connection
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

			//get the hostname
				if ($fp) {  $sip_profile_hostname = event_socket_request($fp, 'api switchname'); }

			//clear the cache
				$cache = new cache;
				$cache->delete("configuration:acl.conf");
				$cache->delete("configuration:sofia.conf:".$sip_profile_hostname);

			//reload acl and rescan the sip profile
				if ($fp) { event_socket_request($fp, "api reloadacl"); }
				if ($fp) { event_socket_request($fp, "api sofia profile ".$sip_profile_name." rescan"); }
		}

		/**
		 * delete the provider
		 */
		public function delete($provider) {

			//validate the provider
				switch ($provider) {
					case 'voicetel':
						break;
					case 'skyetel': 
						break;
					default: 
						$provider = '';
				}

			//delete each node
				$nodes = $this->nodes($provider);
				if (is_array($nodes) && @sizeof($nodes) != 0) {
					$x = 0;
					foreach ($nodes as $row) {
						//build delete array
							$array['access_control_nodes'][$x]['access_control_node_uuid'] = $row['uuid'];
						$x++;
					}
					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = new permissions;
							$p->add('access_control_node_delete', 'temp');
						//execute delete
							$database = new database;
							$database->app_name = 'providers';
							$database->app_uuid = 'b12946a2-f5f7-4eb9-8b79-7d90b89f8aef';
							$database->delete($array);
							unset($array);
						//revoke temporary permissions
							$p->delete('access_control_node_delete', 'temp');
					}
				}

			//get the sip profile name
				$sql = "select sip_profile_name from v_sip_profiles ";
				$sql .= "where sip_profile_enabled = 'true' ";
				$sql .= "order by sip_profile_name asc ";
				$sql .= "limit 1; ";
				if ($this->debug) {
					echo $sql."<br />\n";
				}
				$database = new database;
				$sip_profile_name = $database->select($sql, null, 'column');
				unset($sql);

			//create the event socket connection
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

			//get the hostname
				if ($fp) { $sip_profile_hostname = event_socket_request($fp, 'api switchname'); }


			//delete outbound routes
				$outbound_routes = $this->outbound_routes($provider);
				$x = 0;
				foreach ($outbound_routes as $row) {
					//build child data delete array
						$array['dialplan_details'][$x]['dialplan_uuid'] = $row['dialplan_uuid'];
					//build parent delete array
						$array['dialplans'][$x]['dialplan_uuid'] = $row['dialplan_uuid'];
					$x++;
				}
				if (is_array($array) && @sizeof($array) != 0) {
					//grant temporary permissions
						$p = new permissions;
						$p->add('dialplan_detail_delete', 'temp');
						$p->add('dialplan_delete', 'temp');
					//execute delete
						$database = new database;
						$database->app_name = 'providers';
						$database->app_uuid = 'b12946a2-f5f7-4eb9-8b79-7d90b89f8aef';
						$database->delete($array);
						unset($array);
					//revoke temporary permissions
						$p->delete('dialplan_detail_delete', 'temp');
						$p->delete('dialplan_delete', 'temp');
				}

			//delete each gateway
				$gateways = $this->gateways($provider);
				$x = 0;
				foreach ($gateways as $row) {
					//stop the gateway
						$cmd = "sofia profile ".$sip_profile_name." killgw ".$row['uuid'];
						if ($fp) { event_socket_request($fp, "api ".$cmd); }

					//build gateway delete array
						$array['gateways'][$x]['gateway_uuid'] = $row['uuid'];
					$x++;
				}
				if (is_array($array) && @sizeof($array) != 0) {
					//grant temporary permissions
						$p = new permissions;
						$p->add('gateway_delete', 'temp');
					//execute delete
						$database = new database;
						$database->app_name = 'providers';
						$database->app_uuid = 'b12946a2-f5f7-4eb9-8b79-7d90b89f8aef';
						$database->delete($array);
						unset($array);
					//revoke temporary permissions
						$p->delete('gateway_delete', 'temp');
				}

			//clear the cache
				$cache = new cache;
				$cache->delete("configuration:acl.conf");
				$cache->delete("configuration:sofia.conf:".$sip_profile_hostname);

			//reload acl and rescan the sip profile
				if ($fp) { event_socket_request($fp, "api reloadacl"); }
				if ($fp) { event_socket_request($fp, "api sofia profile ".$sip_profile_name." rescan"); }
		}

	}
}

/*
//example use
	$provider = new providers;
	$reports->domain_uuid = $_SESSION['domain_uuid'];
	$provider->setup();
*/

?>