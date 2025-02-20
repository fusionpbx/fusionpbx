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
	Copyright (C) 2014-2024
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//define the provision class
	class provision {

		public $domain_uuid;
		public $domain_name;
		public $template_dir;
		public $device_address;
		public $device_template;
		private $settings;
		private $database;

		public function __construct($params = []) {

			//preset the the values
				$settings = null;
				$domain_uuid = null;

			//use the parameters to set the values if they exist
				if (isset($params['database'])) {
					$this->database = $params['database'];
				}
				if (isset($params['settings'])) {
					$settings = $params['settings'];
				}
				if (isset($params['domain_uuid'])) {
					$domain_uuid = $params['domain_uuid'];
				}

			//check if we can use the settings object to get the database object
				if (!empty($settings) && empty($this->database)) {
					$this->database = $settings->database();
				}

			//fill in missing
				if (empty($this->database)) {
					$this->database = database::new();
				}
				if (empty($settings)) {
					$settings = new settings(['database' => $this->database, 'domain_uuid' => $domain_uuid]);
				}

			//assign to the object
				$this->settings = $settings;
				$this->domain_uuid = $domain_uuid;

			//get the project root
				$project_root = dirname(__DIR__, 4);

			//set the default template directory
				if (PHP_OS == "Linux") {
					//set the default template dir
						if (empty($this->template_dir)) {
							if (file_exists('/usr/share/fusionpbx/templates/provision')) {
								$this->template_dir = '/usr/share/fusionpbx/templates/provision';
							}
							elseif (file_exists('/etc/fusionpbx/resources/templates/provision')) {
								$this->template_dir = '/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				}
				elseif (PHP_OS == "FreeBSD") {
					//if the FreeBSD port is installed use the following paths by default.
						if (empty($this->template_dir)) {
							if (file_exists('/usr/local/share/fusionpbx/templates/provision')) {
								$this->template_dir = '/usr/local/share/fusionpbx/templates/provision';
							}
							elseif (file_exists('/usr/local/etc/fusionpbx/resources/templates/provision')) {
								$this->template_dir = '/usr/local/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				}
				else if (PHP_OS == "NetBSD") {
					//set the default template_dir
						if (empty($this->template_dir)) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				}
				else if (PHP_OS == "OpenBSD") {
					//set the default template_dir
						if (empty($this->template_dir)) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				}
				else {
					//set the default template_dir
						if (empty($this->template_dir)) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				}

			//normalize the device address
				if (isset($this->device_address)) {
					$this->device_address = strtolower(preg_replace('#[^a-fA-F0-9./]#', '', $this->device_address));
				}
		}

		public function get_domain_uuid() {
			return $this->domain_uuid;
		}

		//define the function which checks to see if the device address exists in devices
		private function device_exists($device_address) {
			//normalize the device address
				$device_address = strtolower(preg_replace('#[^a-fA-F0-9./]#', '', $device_address));
			//check in the devices table for a specific device address
				$sql = "select count(*) from v_devices ";
				$sql .= "where device_address = :device_address ";
				$sql .= "and device_address <> '000000000000' ";
				$parameters['device_address'] = $device_address;
				$num_rows = $this->database->select($sql, $parameters, 'column');
				if ($num_rows > 0) {
					return true;
				}
				else {
					return false;
				}
				unset($sql, $parameters, $num_rows);
		}

		//set the device address in the correct format for the specific vendor
		public function format_address($device_address, $vendor) {
			switch (strtolower($vendor)) {
				case "algo":
					return strtoupper($device_address);
				case "aastra":
					return strtoupper($device_address);
				case "cisco":
					return strtoupper($device_address);
				case "linksys":
					return strtolower($device_address);
				case "mitel":
					return strtoupper($device_address);
				case "polycom":
					return strtolower($device_address);
				case "snom":
					return strtolower($device_address);
				case "escene":
					return strtolower($device_address);
				case "grandstream":
					return strtolower($device_address);
				case "yealink":
					return strtolower($device_address);
				case "gigaset":
					return strtoupper($device_address);
				default:
					return strtolower($device_address);
			}
		}

		//send http error
		private function http_error($error) {
			if ($error === "404") {
				header("HTTP/1.0 404 Not Found");
				echo "<html>\n";
				echo "<head><title>404 Not Found</title></head>\n";
				echo "<body bgcolor=\"white\">\n";
				echo "<center><h1>404 Not Found</h1></center>\n";
				echo "<hr><center>nginx/1.12.1</center>\n";
				echo "</body>\n";
				echo "</html>\n";
			}
			exit();
		}

		//define a function to check if a contact exists in the contacts array
		private function contact_exists($contacts, $uuid) {
			if (is_array($contacts[$uuid])) {
				return true;
			}
			else {
				return false;
			}
		}

		private function contact_append(&$contacts, &$line, $domain_uuid, $device_user_uuid, $category) {

			$sql = "select c.contact_uuid, c.contact_organization, c.contact_name_given, c.contact_name_family, ";
			$sql .= "c.contact_type, c.contact_category, p.phone_label,";
			$sql .= "p.phone_number, p.phone_extension, p.phone_primary ";
			$sql .= "from v_contacts as c, v_contact_phones as p ";
			$sql .= "where c.contact_uuid = p.contact_uuid ";
			$sql .= "and p.phone_type_voice = '1' ";
			$sql .= "and c.domain_uuid = :domain_uuid ";
			if ($category == 'groups') {
				$sql .= "and c.contact_uuid in ( ";
				$sql .= "	select contact_uuid from v_contact_groups ";
				$sql .= "	where group_uuid in ( ";
				$sql .= "		select group_uuid from v_user_groups ";
				$sql .= "		where user_uuid = :device_user_uuid ";
				$sql .= "		and domain_uuid = :domain_uuid ";
				$sql .= "	)) ";
				$parameters['device_user_uuid'] = $device_user_uuid;
			}
			if ($category == 'users') {
				$sql .= "and c.contact_uuid in ( ";
				$sql .= "	select contact_uuid from v_contact_users ";
				$sql .= "	where user_uuid = :device_user_uuid ";
				$sql .= "	and domain_uuid = :domain_uuid ";
				$sql .= ") ";
				$parameters['device_user_uuid'] = $device_user_uuid;
			}
			$parameters['domain_uuid'] = $domain_uuid;
			$database_contacts = $this->database->select($sql, $parameters, 'all');
			if (is_array($database_contacts)) {
				$x = 0;
				foreach ($database_contacts as $row) {
					$uuid = $row['contact_uuid'];
					$phone_label = strtolower($row['phone_label'] ?? '');
					$contact_category = strtolower($row['contact_category'] ?? '');

					$contact = array();
					$contacts[] = &$contact;
					$contact['category']				= ($category == 'all') ? 'groups' : $category;
					$contact['contact_uuid']			= $row['contact_uuid'];
					$contact['contact_type']			= $row['contact_type'];
					$contact['contact_category']		= $row['contact_category'];
					$contact['contact_organization']	= $row['contact_organization'];
					$contact['contact_name_given']		= $row['contact_name_given'];
					$contact['contact_name_family']		= $row['contact_name_family'];

					$contact['numbers']					= array();
					$numbers = &$contact['numbers'];

					if (($row['phone_primary'] == '1') || (!isset($contact['phone_number']))) {
						$contact['phone_label']			= $phone_label;
						$contact['phone_number']		= $row['phone_number'];
						$contact['phone_extension']		= $row['phone_extension'];
					}

					$numbers[$x]['line_number']			= $line['line_number'] ?? null;
					$numbers[$x]['phone_label']			= $phone_label;
					$numbers[$x]['phone_number']		= $row['phone_number'];
					$numbers[$x]['phone_extension']		= $row['phone_extension'];
					$numbers[$x]['phone_primary']		= $row['phone_primary'];

					$contact['phone_number_' . $phone_label] = $row['phone_number'];
					unset($contact, $numbers, $uuid, $phone_label);
					$x++;
				}
				unset($sql, $parameters);

			}
		}

		public function render() {

			//debug
				$debug = $_REQUEST['debug'] ?? ''; // array

			//get the variables
				$domain_uuid = $this->domain_uuid;
				$domain_name = $this->domain_name;
				$device_template = $this->device_template;
				$template_dir = $this->template_dir;
				$device_address = $this->device_address;
				$file = $this->file;

			//set the device address to lower case to be consistent with the database
				$device_address = strtolower($device_address);

			//get the device template
				//if (!empty($_REQUEST['template'])) {
				//	$device_template = $_REQUEST['template'];
				//	$search = array('..', '/./');
				//	$device_template = str_replace($search, "", $device_template);
				//	$device_template = str_replace('//', '/', $device_template);
				//}

			//remove ../ and slashes in the file name
				$search = array('..', '/', '\\', '/./', '//');
				$file = str_replace($search, "", $file);

			//get the domain_name
				if (empty($domain_name)) {
					$sql = "select domain_name from v_domains ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $domain_uuid;
					$domain_name = $this->database->select($sql, $parameters, 'column');
					unset($sql, $parameters);
				}

			//build the provision array
				$provision = $this->settings->get('provision', null, []);
				foreach ($provision as $key => $value) {
					$provision[$key] = $value;
				}

			//add the http auth password to the array
				if (isset($provision["http_auth_password"]) && is_array($provision["http_auth_password"])) {
					$provision["http_auth_password"] = $provision["http_auth_password"][0];
				}

			//check to see if the device_address exists in devices
				//if (empty($_REQUEST['user_id']) || empty($_REQUEST['userid'])) {
					if ($this->device_exists($device_address)) {

						//get the device_template
							$sql = "select * from v_devices ";
							$sql .= "where device_address = :device_address ";
							$sql .= "and device_address <> '000000000000' ";
							$parameters['device_address'] = $device_address;
							$row = $this->database->select($sql, $parameters, 'row');
							unset($sql, $parameters);
							if (is_array($row) && sizeof($row) != 0) {

								//checks either device enabled
									if ($row['device_enabled'] != 'true') {
										syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempted but the device is not enabled for ".escape($device_address));
										if ($this->settings->get('provision','debug', false)) {
											echo "<br/>device disabled<br/>";
										}
										else {
											$this->http_error('404');
										}
										exit;
									}

								//register that we have seen the device
									$sql = "update v_devices ";
									$sql .= "set device_provisioned_date = :device_provisioned_date, device_provisioned_method = :device_provisioned_method, device_provisioned_ip = :device_provisioned_ip, device_provisioned_agent = :device_provisioned_agent ";
									$sql .= "where domain_uuid = :domain_uuid ";
									$sql .= "and device_address = :device_address  ";
									$parameters['domain_uuid'] = $domain_uuid;
									$parameters['device_address'] = strtolower($device_address);
									$parameters['device_provisioned_date'] = 'now()';
									$parameters['device_provisioned_method'] = (isset($_SERVER["HTTPS"]) ? 'https' : 'http');
									$parameters['device_provisioned_ip'] = $_SERVER['REMOTE_ADDR'];
									$parameters['device_provisioned_agent'] = $_SERVER['HTTP_USER_AGENT'];
									$this->database->execute($sql, $parameters);
									unset($sql, $parameters);

								//set the variables from values in the database
									$device_uuid = $row["device_uuid"];
									$device_label = $row["device_label"];
									$device_template = $row["device_template"];
									$device_profile_uuid = $row["device_profile_uuid"];
									$device_user_uuid = $row["device_user_uuid"];
									$device_model = $row["device_model"];
									$device_firmware_version = $row["device_firmware_version"];
									if (!empty($row["device_vendor"])) {
										$device_vendor = strtolower($row["device_vendor"]);
									}
									$device_location = $row["device_location"];
									$device_enabled = $row["device_enabled"];
									$device_description = $row["device_description"];
							}
							unset($row);

						//find a template that was defined on another phone and use that as the default.
							if (empty($device_template)) {
								$sql = "select * from v_devices ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and device_enabled = 'true' ";
								$sql .= "limit 1 ";
								$parameters['domain_uuid'] = $domain_uuid;
								$row = $this->database->select($sql, $parameters, 'row');
								if (!empty($row) && is_array($row) && sizeof($row) != 0) {
									$device_label = $row["device_label"];
									$device_template = $row["device_template"];
									$device_profile_uuid = $row["device_profile_uuid"];
									$device_model = $row["device_model"];
									$device_firmware_version = $row["device_firmware_version"];
									$device_vendor = strtolower($row["device_vendor"]);
									$device_location = strtolower($row["device_location"]);
									$device_enabled = $row["device_enabled"];
									$device_description = $row["device_description"];
								}
								unset($sql, $row, $parameters);
							}
					}
					else {
						//use the user_agent to pre-assign a template for 1-hit provisioning. Enter the a unique string to match in the user agent, and the template it should match.
							$templates['Linksys/SPA-2102'] = 'linksys/spa2102';
							$templates['Linksys/SPA-3102'] = 'linksys/spa3102';
							$templates['Linksys/SPA-9212'] = 'linksys/spa921';

							$templates['Cisco/SPA301'] = 'cisco/spa301';
							$templates['Cisco/SPA301D'] = 'cisco/spa302d';
							$templates['Cisco/SPA303'] = 'cisco/spa303';
							$templates['Cisco/SPA501G'] = 'cisco/spa501g';
							$templates['Cisco/SPA502G'] = 'cisco/spa502g';
							$templates['Cisco/SPA504G'] = 'cisco/spa504g';
							$templates['Cisco/SPA508G'] = 'cisco/spa508g';
							$templates['Cisco/SPA509G'] = 'cisco/spa509g';
							$templates['Cisco/SPA512G'] = 'cisco/spa512g';
							$templates['Cisco/SPA514G'] = 'cisco/spa514g';
							$templates['Cisco/SPA525G2'] = 'cisco/spa525g2';

							$templates['snom300-SIP'] = 'snom/300';
							$templates['snom320-SIP'] = 'snom/320';
							$templates['snom360-SIP'] = 'snom/360';
							$templates['snom370-SIP'] = 'snom/370';
							$templates['snom820-SIP'] = 'snom/820';
							$templates['snom-m3-SIP'] = 'snom/m3';

							$templates['Fanvil X6'] = 'fanvil/x6';
							$templates['Fanvil i30'] = 'fanvil/i30';

							$templates['yealink SIP-CP860'] = 'yealink/cp860';
#							$templates['yealink SIP-CP860'] = 'yealink/cp920';
#							$templates['yealink SIP-CP860'] = 'yealink/cp960';
							$templates['yealink SIP-T19P'] = 'yealink/t19p';
							$templates['yealink SIP-T20P'] = 'yealink/t20p';
							$templates['yealink SIP-T21P'] = 'yealink/t21p';
							$templates['yealink SIP-T22P'] = 'yealink/t22p';
							$templates['yealink SIP-T23G'] = 'yealink/t23g';
							$templates['yealink SIP-T23P'] = 'yealink/t23p';
							$templates['yealink SIP-T26P'] = 'yealink/t26p';
							$templates['yealink SIP-T27G'] = 'yealink/t27g';
							$templates['Yealink SIP-T29G'] = 'yealink/t27p';
							$templates['yealink SIP-T28P'] = 'yealink/t28p';
							$templates['Yealink SIP-T29G'] = 'yealink/t29g';
							$templates['yealink SIP-T29P'] = 'yealink/t29p';
							$templates['Yealink SIP-T32G'] = 'yealink/t32g';
							$templates['Yealink SIP-T33G'] = 'yealink/t33g';
							$templates['Yealink SIP-T38G'] = 'yealink/t38g';
							$templates['Yealink SIP-T40P'] = 'yealink/t40p';
							$templates['Yealink SIP-T41G'] = 'yealink/t41g';
							$templates['Yealink SIP-T41P'] = 'yealink/t41p';
							$templates['Yealink SIP-T41S'] = 'yealink/t41s';
							$templates['Yealink SIP-T42G'] = 'yealink/t42g';
							$templates['Yealink SIP-T42S'] = 'yealink/t42s';
							$templates['Yealink SIP-T46G'] = 'yealink/t46g';
							$templates['Yealink SIP-T46S'] = 'yealink/t46s';
							$templates['Yealink SIP-T48G'] = 'yealink/t48g';
							$templates['Yealink SIP-T48S'] = 'yealink/t48s';
							$templates['Yealink SIP-T49G'] = 'yealink/t49g';
							$templates['Yealink SIP-T52S'] = 'yealink/t52s';
							$templates['Yealink SIP-T54S'] = 'yealink/t54s';
							$templates['Yealink SIP-T56A'] = 'yealink/t56a';
							$templates['Yealink SIP-T58'] = 'yealink/t58v';
							$templates['VP530P'] = 'yealink/vp530';
							$templates['Yealink SIP-W52P'] = 'yealink/w52p';
							$templates['Yealink SIP-W56P'] = 'yealink/w56p';

							$templates['HW DP750'] = 'grandstream/dp750';
							$templates['HW GXP1450'] = 'grandstream/gxp1450';
							$templates['HW GXP1628'] = 'grandstream/gxp16xx';
							$templates['HW GXP1610'] = 'grandstream/gxp16xx';
							$templates['HW GXP1620'] = 'grandstream/gxp16xx';
							$templates['HW GXP1625'] = 'grandstream/gxp16xx';
							$templates['HW GXP1630'] = 'grandstream/gxp16xx';
							$templates['HW GXP1760W'] = 'grandstream/gxp17xx';
							$templates['HW GXP1780'] = 'grandstream/gxp17xx';
							$templates['HW GXP1782'] = 'grandstream/gxp17xx';
							$templates['HW GXP2124'] = 'grandstream/gxp2124';
							$templates['HW GXP2130'] = 'grandstream/gxp2130';
							$templates['HW GXP2135'] = 'grandstream/gxp2135';
							$templates['HW GXP2140'] = 'grandstream/gxp2140';
							$templates['HW GXP2160'] = 'grandstream/gxp2160';
							$templates['HW GXP2170'] = 'grandstream/gxp2170';
							$templates['HW GXV3140'] = 'grandstream/gxv3140';
							$templates['HW GXV3240'] = 'grandstream/gxv3240';
							$templates['HW GXV3175'] = 'grandstream/gxv3175';

							$templates['PolycomVVX-VVX_101-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_201-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_300-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_301-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_310-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_311-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_400-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_410-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_500-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_501-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_600-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_601-UA/4'] = 'polycom/4.x';
							$templates['PolycomVVX-VVX_101-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_201-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_300-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_301-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_310-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_311-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_400-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_410-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_500-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_501-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_600-UA/5'] = 'polycom/5.x';
							$templates['PolycomVVX-VVX_601-UA/5'] = 'polycom/5.x';

							$templates['Vesa VCS754'] = 'vtech/vcs754';
							$templates['Wget/1.11.3'] = 'konftel/kt300ip';

							$templates['Flyingvoice FIP10'] = 'flyingvoice/fip10';
							$templates['Flyingvoice FIP11C'] = 'flyingvoice/fip11c';
							$templates['Flyingvoice FIP12WP'] = 'flyingvoice/fip12wp';
							$templates['Flyingvoice FIP13G'] = 'flyingvoice/fip13g';
							$templates['Flyingvoice FIP14G'] = 'flyingvoice/fip14g';
							$templates['Flyingvoice FIP15G'] = 'flyingvoice/fip15g';
							$templates['Flyingvoice FIP16'] = 'flyingvoice/fip16';
							$templates['Flyingvoice FIP16PLUS'] = 'flyingvoice/fip16plus';

							foreach ($templates as $key=>$value) {
								if(stripos($_SERVER['HTTP_USER_AGENT'],$key)!== false) {
									$device_template = $value;
									break;
								}
							}
							unset($templates);

						//device address does not exist in the table so add it
							if ($this->settings->get('provision','auto_insert_enabled',false)) {

								//get a new primary key
								$device_uuid = uuid();

								//prepare the array
								$x = 0;
								$array['devices'][$x]['domain_uuid'] = $domain_uuid;
								$array['devices'][$x]['device_uuid'] = $device_uuid;
								$array['devices'][$x]['device_address'] = $device_address;
								$array['devices'][$x]['device_vendor'] = $device_vendor;
								$array['devices'][$x]['device_enabled'] = 'true';
								$array['devices'][$x]['device_template'] = $device_template;
								$array['devices'][$x]['device_description'] = $_SERVER['HTTP_USER_AGENT'];

								//add the dialplan permission
								$p = permissions::new();
								$p->add("device_add", "temp");
								$p->add("device_edit", "temp");

								//save to the data
								$this->database->app_name = 'devices';
								$this->database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
								if (!empty($device_uuid)) {
									$this->database->uuid($device_uuid);
								}
								$this->database->save($array);
								$message = $this->database->message;

								//remove the temporary permission
								$p->delete("device_add", "temp");
								$p->delete("device_edit", "temp");
							}
					}
				//}

			//alternate device_uuid
				if (is_uuid($device_uuid)) {
					$sql = "select * from v_devices ";
					$sql .= "where device_uuid = :device_uuid ";
					$sql .= "and device_enabled = 'true' ";
					$parameters['device_uuid'] = $device_uuid;
					$row = $this->database->select($sql, $parameters, 'row');
					if (is_array($row) && sizeof($row) != 0) {
						$device_uuid_alternate = $row["device_uuid_alternate"];
						unset($sql, $row, $parameters);
						if (is_uuid($device_uuid_alternate)) {
							//override the original device_uuid
							$device_uuid = $device_uuid_alternate;

							//get the new devices information
							$sql = "select * from v_devices ";
							$sql .= "where device_uuid = :device_uuid ";
							$parameters['device_uuid'] = $device_uuid;
							$row = $this->database->select($sql, $parameters, 'row');
							if (is_array($row) && sizeof($row) != 0) {
								if ($row["device_enabled"] == "true") {
									$device_label = $row["device_label"];

									//if the device vendor match then use the alternate device template
									if ($device_vendor == $row["device_vendor"]) {
										$device_template = $row["device_template"];
									}

									$device_profile_uuid = $row["device_profile_uuid"];
									$device_firmware_version = $row["device_firmware_version"];
									$device_user_uuid = $row["device_user_uuid"];
									$device_location = strtolower($row["device_location"]);
									$device_enabled = $row["device_enabled"];
									$device_description = $row["device_description"];
								}
							}
							unset($sql, $row, $parameters);
						}
					}
				}

			//get the device settings table in the provision category from the profile and update the provision array
				if (is_uuid($device_uuid) && is_uuid($device_profile_uuid)) {
					$sql = "select * from v_device_profile_settings ";
					$sql .= "where device_profile_uuid = :device_profile_uuid ";
					$sql .= "and profile_setting_enabled = 'true' ";
					$parameters['device_profile_uuid'] = $device_profile_uuid;
					$device_profile_settings = $this->database->select($sql, $parameters, 'all');
					if (is_array($device_profile_settings) && sizeof($device_profile_settings) != 0) {
						foreach($device_profile_settings as $row) {
							$key = $row['profile_setting_name'];
							$value = $row['profile_setting_value'];
							$provision[$key] = $value;
						}
					}
					unset ($parameters, $device_profile_settings, $sql);
				}

			//get the device settings table in the provision category and update the provision array
				if (is_uuid($device_uuid)) {
					$sql = "select * from v_device_settings ";
					$sql .= "where device_uuid = :device_uuid ";
					$sql .= "and device_setting_enabled = 'true' ";
					$parameters['device_uuid'] = $device_uuid;
					$device_settings = $this->database->select($sql, $parameters, 'all');
					if (is_array($device_settings) && sizeof($device_settings) != 0) {
						foreach($device_settings as $row) {
							$key = $row['device_setting_subcategory'];
							$value = $row['device_setting_value'];
							$provision[$key] = $value;
						}
					}
					unset ($parameters, $device_settings, $sql);
				}

			//set the template directory
				if (!empty($provision["template_dir"])) {
					$template_dir = $provision["template_dir"];
				}

			//if the domain name directory exists then only use templates from it
				if (is_dir($template_dir.'/'.$domain_name)) {
					$device_template = $domain_name.'/'.$device_template;
				}

			//initialize a template object
				$view = new template();
				$view->engine = $this->settings->get('provision', 'template_engine', 'smarty');
				$view->template_dir = $template_dir ."/".$device_template."/";
				$view->cache_dir = sys_get_temp_dir();
				$view->init();

			//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number

				//create a device address with back slashes for backwards compatability
					//$address_dash = substr($device_address, 0,2).'-'.substr($device_address, 2,2).'-'.substr($device_address, 4,2).'-'.substr($device_address, 6,2).'-'.substr($device_address, 8,2).'-'.substr($device_address, 10,2);

				//get the provisioning information
					if (is_uuid($device_uuid)) {
						//get the device lines array
							$sql = "select * from v_device_lines ";
							$sql .= "where device_uuid = :device_uuid ";
							$sql .= "and (enabled = 'true' or enabled is null or enabled = '') ";
							$parameters['device_uuid'] = $device_uuid;
							//$database_device_lines = $this->database->select($sql, $parameters, 'all');
							foreach ($this->database->select($sql, $parameters, 'all') as $row) {
								$id = $row['line_number'];
								$device_lines[$id] = $row;
							}
							unset($sql, $parameters);

						//get the device profile keys
							if (is_uuid($device_profile_uuid)) {
								$sql = "select ";
								$sql .= "profile_key_id as device_key_id, ";
								$sql .= "profile_key_category as device_key_category, ";
								$sql .= "profile_key_vendor as device_key_vendor, ";
								$sql .= "profile_key_type as device_key_type, ";
								$sql .= "profile_key_subtype as device_key_subtype, ";
								$sql .= "profile_key_line as device_key_line, ";
								$sql .= "profile_key_value as device_key_value, ";
								$sql .= "profile_key_extension as device_key_extension, ";
								$sql .= "profile_key_protected as device_key_protected, ";
								$sql .= "profile_key_label as device_key_label, ";
								$sql .= "profile_key_icon as device_key_icon ";
								$sql .= "from v_device_profile_keys ";
								$sql .= "where device_profile_uuid = :device_profile_uuid ";
								if (strtolower($device_vendor) == 'escene'){
									$sql .= "and (lower(profile_key_vendor) = 'escene' or lower(profile_key_vendor) = 'escene programmable' or profile_key_vendor is null) ";
								}
								else {
									$sql .= "and (lower(profile_key_vendor) = :device_vendor or profile_key_vendor is null) ";
									$parameters['device_vendor'] = $device_vendor;
								}
								$sql .= "order by ";
								$sql .= "profile_key_vendor asc, ";
								$sql .= "case profile_key_category ";
								$sql .= "when 'line' then 1 ";
								$sql .= "when 'memory' then 2 ";
								$sql .= "when 'programmable' then 3 ";
								$sql .= "when 'expansion' then 4 ";
								$sql .= "else 100 end, ";
								if ($GLOBALS['db_type'] == "mysql") {
									$sql .= "profile_key_id asc ";
								}
								else {
									$sql .= "cast(profile_key_id as numeric) asc ";
								}
								$parameters['device_profile_uuid'] = $device_profile_uuid;
								$keys = $this->database->select($sql, $parameters, 'all');

								//add the profile keys to the device keys array
								if (is_array($keys) && sizeof($keys) != 0) {
									foreach($keys as $row) {
										//set the variables
										$id = $row['device_key_id'];
										$category = $row['device_key_category'];
										$device_key_vendor = $row['device_key_vendor'];
										$device_key_line = $row['device_key_line'];

										//build the device keys array
										$device_keys[$category][$id] = $row;
										$device_lines[$device_key_line]['device_key_owner'] = "profile";

										//add line_keys to the polycom array
										if ($row['device_key_vendor'] == 'polycom' && $row['device_key_type'] == 'line') {
											$device_lines[$device_key_line]['line_keys'] = $row['device_key_value'];
										}

										//kept temporarily for backwards comptability to allow custom templates to be updated
										$device_keys[$id] = $row;
										$device_keys[$id]['device_key_owner'] = "profile";
									}
								}
								unset($sql, $parameters, $keys);
							}

						//get the device keys
							$sql = "select * from v_device_keys ";
							$sql .= "where device_uuid = :device_uuid ";
							if (strtolower($device_vendor) == 'escene'){
								$sql .= "and (lower(device_key_vendor) = 'escene' or lower(device_key_vendor) = 'escene programmable' or device_key_vendor is null) ";
							}
							else {
								$sql .= "and (lower(device_key_vendor) = :device_vendor or device_key_vendor is null) ";
								$parameters['device_vendor'] = $device_vendor;
							}
							$sql .= "order by ";
							$sql .= "device_key_vendor asc, ";
							$sql .= "case device_key_category ";
							$sql .= "when 'line' then 1 ";
							$sql .= "when 'memory' then 2 ";
							$sql .= "when 'programmable' then 3 ";
							$sql .= "when 'expansion' then 4 ";
							$sql .= "else 100 end, ";
							if ($GLOBALS['db_type'] == "mysql") {
								$sql .= "device_key_id asc ";
							}
							else {
								$sql .= "cast(device_key_id as numeric) asc ";
							}
							$parameters['device_uuid'] = $device_uuid;
							$keys = $this->database->select($sql, $parameters, 'all');

						//override profile keys with the device keys
							if (is_array($keys)) {
								foreach($keys as $row) {
									//set the variables
									$id = $row['device_key_id'];
									$category = $row['device_key_category'];
									$device_key_line = $row['device_key_line'];

									//add line_keys to the polycom array
									if ($row['device_key_vendor'] == 'polycom' && $row['device_key_type'] == 'line') {
										$device_lines[$device_key_line]['line_keys'] = $row['device_key_value'];
									}

									//build the device keys array
									$device_keys[$category][$id] = $row;
									$device_keys[$category][$id]['device_key_owner'] = "device";

									//kept temporarily for backwards comptability to allow custom templates to be updated
									$device_keys[$id] = $row;
									$device_keys[$id]['device_key_owner'] = "device";
								}
							}
							unset($sql, $parameters, $keys);

						//set the variables
							if (is_array($device_lines) && sizeof($device_lines) != 0) {
								foreach($device_lines as $row) {
									//set the variables
										$line_number = $row['line_number'];
										$register_expires = $row['register_expires'];
										$sip_transport = strtolower($row['sip_transport']);
										$sip_port = $row['sip_port'];

									//set defaults
										if (empty($register_expires)) { $register_expires = "120"; }
										if (empty($sip_transport)) { $sip_transport = "tcp"; }
										if (!isset($sip_port)) {
											if ($line_number == "" || $line_number == "1") {
												$sip_port = "5060";
											}
											else {
												$sip_port = "506".($line_number + 1);
											}
										}

									//convert seconds to minutes for grandstream
										if ($device_vendor == 'grandstream') {
											$register_expires = round($register_expires / 60);
										}

									//set a lines array index is the line number
										$lines[$line_number] = $row;
										$lines[$line_number]['register_expires'] = $register_expires;
										$lines[$line_number]['sip_transport'] = strtolower($sip_transport);
										$lines[$line_number]['sip_port'] = $sip_port;
										$lines[$line_number]['server_address'] = $row["server_address"];
										$lines[$line_number]['outbound_proxy'] = $row["outbound_proxy_primary"];
										$lines[$line_number]['server'][1]['address'] = $row["server_address_primary"];
										$lines[$line_number]['server'][2]['address'] = $row["server_address_secondary"];
										$lines[$line_number]['outbound_proxy_primary'] = $row["outbound_proxy_primary"];
										$lines[$line_number]['outbound_proxy_secondary'] = $row["outbound_proxy_secondary"];
										$lines[$line_number]['display_name'] = $row["display_name"];
										$lines[$line_number]['auth_id'] = $row["auth_id"];
										$lines[$line_number]['user_id'] = $row["user_id"];
										$lines[$line_number]['password'] = $row["password"];
										$lines[$line_number]['user_password'] = $row["password"];
										$lines[$line_number]['shared_line'] = $row["shared_line"];

									//assign the variables for line one - short name
										if ($line_number == "1") {
											$view->assign("server_address", $row["server_address"]);
											$view->assign("outbound_proxy", $row["outbound_proxy_primary"]);
											$view->assign("outbound_proxy_primary", $row["outbound_proxy_primary"]);
											$view->assign("outbound_proxy_secondary", $row["outbound_proxy_secondary"]);
											$view->assign("display_name", $row["display_name"]);
											$view->assign("auth_id", $row["auth_id"]);
											$view->assign("user_id", $row["user_id"]);
											$view->assign("sip_transport", $sip_transport);
											$view->assign("sip_port", $sip_port);
											$view->assign("register_expires", $register_expires);
											$view->assign("shared_line", $row["shared_line"]);
										}

									//assign the variables with the line number as part of the name
										$view->assign("server_address_".$line_number, $row["server_address"]);
										$view->assign("outbound_proxy_".$line_number, $row["outbound_proxy_primary"]);
										$view->assign("outbound_proxy_primary_".$line_number, $row["outbound_proxy_primary"]);
										$view->assign("outbound_proxy_secondary_".$line_number, $row["outbound_proxy_secondary"]);
										$view->assign("display_name_".$line_number, $row["display_name"]);
										$view->assign("auth_id_".$line_number, $row["auth_id"]);
										$view->assign("user_id_".$line_number, $row["user_id"]);
										$view->assign("user_password_".$line_number, $row["password"]);
										$view->assign("sip_transport_".$line_number, $sip_transport);
										$view->assign("sip_port_".$line_number, $sip_port);
										$view->assign("register_expires_".$line_number, $register_expires);
										$view->assign("shared_line_".$line_number, $row["shared_line"]);
								}
							}
					}

				//assign the arrays
					$view->assign("lines", $lines);
					$view->assign("account", $lines);
					$view->assign("user", $lines);

				//get the list of contact directly assigned to the user
					if (is_uuid($domain_uuid)) {
						if ($this->settings->get('contact','permissions',false)) {
							//get the contacts assigned to the groups and add to the contacts array
								if (is_uuid($device_user_uuid) && $this->settings->get('contact','contact_groups', false)) {
									$this->contact_append($contacts, $line, $domain_uuid, $device_user_uuid, 'groups');
								}

							//get the contacts assigned to the user and add to the contacts array
								if (is_uuid($device_user_uuid) && $this->settings->get('contact','contact_users', false)) {
									$this->contact_append($contacts, $line, $domain_uuid, $device_user_uuid, 'users');
								}
						}
						else {
							//show all contacts for the domain
								$this->contact_append($contacts, $line, $domain_uuid, null, 'all');
						}
					}

				//get the extensions and add them to the contacts array
					if (is_uuid($device_uuid) && is_uuid($domain_uuid) && $this->settings->get('provision','contact_extensions',false)) {

						//get contacts from the database
							$sql = "select extension_uuid as contact_uuid, directory_first_name, directory_last_name, ";
							$sql .= "effective_caller_id_name, effective_caller_id_number, ";
							$sql .= "number_alias, extension, call_group ";
							$sql .= "from v_extensions ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and enabled = 'true' ";
							$sql .= "and directory_visible = 'true' ";
							$sql .= "order by directory_first_name, effective_caller_id_name asc ";
							$parameters['domain_uuid'] = $domain_uuid;
							$extensions = $this->database->select($sql, $parameters, 'all');
							if (is_array($extensions) && sizeof($extensions) != 0) {
								foreach ($extensions as $row) {
									//get the contact_uuid
										$uuid = $row['contact_uuid'];
									//get the names
										if (!empty($row['directory_first_name'])) {
											$contact_name_given = $row['directory_first_name'];
											$contact_name_family = $row['directory_last_name'];
										} else {
											$name_array = explode(" ", $row['effective_caller_id_name'] ?? '');
											$contact_name_given = array_shift($name_array);
											$contact_name_family = trim(implode(' ', $name_array));
										}
									//get the phone_extension
										if (is_numeric($row['extension'])) {
											$phone_extension = $row['extension'];
										}
										else {
											$phone_extension = $row['number_alias'];
										}
									//save the contact array values
										$contacts[$uuid]['category'] = 'extensions';
										$contacts[$uuid]['contact_uuid'] = $row['contact_uuid'];
										$contacts[$uuid]['contact_category'] = 'extensions';
										$contacts[$uuid]['contact_name_given'] = $contact_name_given;
										$contacts[$uuid]['contact_name_family'] = $contact_name_family;
										$contacts[$uuid]['phone_extension'] = $phone_extension;
										$contacts[$uuid]['call_group'] = $row['call_group'];
									//unset the variables
										unset($name_array, $contact_name_given, $contact_name_family, $phone_extension);
								}
							}
							unset($sql, $parameters);
					}

				//assign the contacts array to the template
					if (is_array($contacts)) {
						$view->assign("contacts", $contacts);
						unset($contacts);
					}

				//debug information
					if ($debug == "array") {
						echo "<pre>\n";
						print_r($device_lines);
						print_r($device_keys);
						echo "<pre>\n";
						exit;
					}

				//list of variable names
					$variable_names = [];
					$variable_names[] = 'domain_name';
					$variable_names[] = 'user_id';
					$variable_names[] = 'auth_id';
					$variable_names[] = 'extension';
					$variable_names[] = 'register_expires';
					$variable_names[] = 'sip_transport';
					$variable_names[] = 'sip_port';
					$variable_names[] = 'server_address';
					$variable_names[] = 'outbound_proxy';
					$variable_names[] = 'outbound_proxy_primary';
					$variable_names[] = 'outbound_proxy_secondary';
					$variable_names[] = 'display_name';
					$variable_names[] = 'location';
					$variable_names[] = 'description';

				//add location and description to the lines array
					foreach($lines as $id => $row) {
						$lines[$id]['location'] = $device_location;
						$lines[$id]['description'] = $device_description;
					}

				//update the device keys by replacing variables with their values
					if (!empty($device_keys['line']) && is_array($device_keys)) {
						$types = array("line", "memory", "expansion", "programmable");
						foreach ($types as $type) {
							if (!empty($device_keys[$type]) && is_array($device_keys[$type])) {
								foreach($device_keys[$type] as $row) {
									//get the variables
									$device_key_line = $row['device_key_line'];
									$device_key_id = $row['device_key_id'];
									$device_key_value = $row['device_key_value'];
									$device_key_extension = $row['device_key_extension'];
									$device_key_label = $row['device_key_label'];
									$device_key_icon = $row['device_key_icon'];

									//replace the variables
									foreach($variable_names as $name) {
										if (!empty($row['device_key_value'])) {
											$device_key_value = str_replace("\${".$name."}", $lines[$device_key_line][$name], $device_key_value);
										}
										if (!empty($row['device_key_extension'])) {
											$device_key_extension = str_replace("\${".$name."}", $lines[$device_key_line][$name], $device_key_extension);
										}
										if (!empty($row['device_key_label'])) {
											$device_key_label = str_replace("\${".$name."}", $lines[$device_key_line][$name], $device_key_label);
										}
										if (!empty($row['device_key_icon'])) {
											$device_key_icon = str_replace("\${".$name."}", $lines[$device_key_line][$name], $device_key_icon);
										}
									}

									//update the device kyes array
									$device_keys[$type][$device_key_id]['device_key_value'] = $device_key_value;
									$device_keys[$type][$device_key_id]['device_key_extension'] = $device_key_extension;
									$device_keys[$type][$device_key_id]['device_key_label'] = $device_key_label;
									$device_keys[$type][$device_key_id]['device_key_icon'] = $device_key_icon;
								}
							}
						}
					}

				//assign the keys array
					if (!empty($device_keys)) {
						$view->assign("keys", $device_keys);
					}

				//set the variables
					$types = array("line", "memory", "expansion", "programmable");
					foreach ($types as $type) {
						if (!empty($device_keys[$type]) && is_array($device_keys[$type])) {
							foreach($device_keys[$type] as $row) {
								//set the variables
									$device_key_category = $row['device_key_category'];
									$device_key_id = $row['device_key_id']; //1
									$device_key_type = $row['device_key_type']; //line
									$device_key_line = $row['device_key_line'];
									$device_key_value = $row['device_key_value']; //1
									$device_key_extension = $row['device_key_extension'];
									$device_key_label = $row['device_key_label']; //label
									$device_key_icon = $row['device_key_icon']; //icon

								//add general variables
									$device_key_value = str_replace("\${domain_name}", $domain_name, $device_key_value);
									$device_key_extension = str_replace("\${domain_name}", $domain_name, $device_key_extension);
									$device_key_label = str_replace("\${domain_name}", $domain_name, $device_key_label);
									$device_key_icon = str_replace("\${domain_name}", $domain_name, $device_key_icon);

								//grandstream modes are different based on the category
									if ($device_vendor == "grandstream") {
										if ($device_key_category == "line") {
											switch ($device_key_type) {
												case "line": $device_key_type  = "0"; break;
												case "shared line": $device_key_type  = "1"; break;
												case "speed dial": $device_key_type  = "10"; break;
												case "blf": $device_key_type  = "11"; break;
												case "presence watcher": $device_key_type  = "12"; break;
												case "eventlist blf": $device_key_type  = "13"; break;
												case "speed dial active": $device_key_type  = "14"; break;
												case "dial dtmf": $device_key_type  = "15"; break;
												case "voicemail": $device_key_type  = "16"; break;
												case "call return": $device_key_type  = "17"; break;
												case "transfer": $device_key_type  = "18"; break;
												case "call park": $device_key_type  = "19"; break;
												case "monitored call park": $device_key_type  = "26"; break;
												case "intercom": $device_key_type  = "20"; break;
												case "ldap search": $device_key_type  = "21"; break;
												case "phonebook": $device_key_type = "30"; break;
											}
										}
										if ($device_key_category == "memory" || $device_key_category == "expansion") {
											switch ($device_key_type) {
												case "speed dial": $device_key_type  = "0"; break;
												case "blf": $device_key_type  = "1"; break;
												case "presence watcher": $device_key_type  = "2"; break;
												case "eventlist blf": $device_key_type  = "3"; break;
												case "speed dial active": $device_key_type  = "4"; break;
												case "dial dtmf": $device_key_type  = "5"; break;
												case "voicemail": $device_key_type  = "6"; break;
												case "call return": $device_key_type  = "7"; break;
												case "transfer": $device_key_type  = "8"; break;
												case "call park": $device_key_type  = "9"; break;
												case "monitored call park": $device_key_type  = "16"; break;
												case "intercom": $device_key_type  = "10"; break;
												case "ldap search": $device_key_type  = "11"; break;
											}
										}
									}

								//assign the variables
									if (empty($device_key_category)) {
										$view->assign("key_id_".$device_key_id, $device_key_id);
										$view->assign("key_type_".$device_key_id, $device_key_type);
										$view->assign("key_line_".$device_key_id, $device_key_line);
										$view->assign("key_value_".$device_key_id, $device_key_value);
										$view->assign("key_extension_".$device_key_id, $device_key_extension);
										$view->assign("key_label_".$device_key_id, $device_key_label);
										$view->assign("key_icon_".$device_key_id, $device_key_icon);
									}
									else {
										$view->assign($device_key_category."_key_id_".$device_key_id, $device_key_id);
										$view->assign($device_key_category."_key_type_".$device_key_id, $device_key_type);
										$view->assign($device_key_category."_key_line_".$device_key_id, $device_key_line);
										$view->assign($device_key_category."_key_value_".$device_key_id, $device_key_value);
										$view->assign($device_key_category."_key_extension_".$device_key_id, $device_key_extension);
										$view->assign($device_key_category."_key_label_".$device_key_id, $device_key_label);
										$view->assign($device_key_category."_key_icon_".$device_key_id, $device_key_icon);
									}
							}
						}
					}

				//set the device address in the correct format
					$device_address = $this->format_address($device_address, $device_vendor);

				//set date/time for versioning provisioning templates
					$time = date($this->settings->get('provision','version_format', "dmyHi"));

				//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number
					$view->assign("device_address", $device_address);
					$view->assign("address", $device_address);
					$view->assign("mac", $device_address);
					$view->assign("time", $time);
					$view->assign("label", $device_label);
					$view->assign("device_label", $device_label);
					$view->assign("firmware_version", $device_firmware_version);
					$view->assign("domain_name", $domain_name);
					$view->assign("project_path", PROJECT_PATH);
					$view->assign("server1_address", $server1_address ?? '');
					$view->assign("proxy1_address", $proxy1_address ?? '');
					$view->assign("user_id", $user_id ?? '');
					$view->assign("password", $password ?? '');
					$view->assign("template", $device_template);
					$view->assign("location", $device_location);
					$view->assign("device_location", $device_location);
					$view->assign("microtime", microtime(true));

				//personal ldap password
					global $laddr_salt;
					if (is_uuid($device_user_uuid)) {
						$sql = "select contact_uuid from v_users where user_uuid = :device_user_uuid ";
						$parameters['device_user_uuid'] = $device_user_uuid;
						$contact_uuid = $this->database->select($sql, $parameters, 'column');
						$view->assign("ldap_username", "uid=" . $contact_uuid . "," . $this->settings->get('provision','grandstream_ldap_user_base', ''));
						$view->assign("ldap_password",md5($laddr_salt.$device_user_uuid));
						unset($sql, $parameters);
					}

				//get the time zone
					$time_zone_name = $this->settings->get('domain','time_zone', '');
					if (!empty($time_zone_name)) {
						$time_zone_offset_raw = get_time_zone_offset($time_zone_name)/3600;
						$time_zone_offset_hours = floor($time_zone_offset_raw);
						$time_zone_offset_minutes = ($time_zone_offset_raw - $time_zone_offset_hours) * 60;
						$time_zone_offset_minutes = number_pad($time_zone_offset_minutes, 2);
						if ($time_zone_offset_raw > 0) {
							$time_zone_offset_hours = number_pad($time_zone_offset_hours, 2);
							$time_zone_offset_hours = "+".$time_zone_offset_hours;
						}
						else {
							$time_zone_offset_hours = str_replace("-", "", $time_zone_offset_hours);
							$time_zone_offset_hours = "-".number_pad($time_zone_offset_hours, 2);
						}
						$time_zone_offset = $time_zone_offset_hours.":".$time_zone_offset_minutes;
						if (!isset($provision["time_zone_offset"])) {
							$provision["time_zone_offset"] = $time_zone_offset;
						}
					}

				//set the daylight savings time
					if (!isset($provision["yealink_time_zone_start_time"])) {
						$provision["yealink_time_zone_start_time"] = $provision["daylight_savings_start_month"]."/".$provision["daylight_savings_start_day"]."/".$provision["daylight_savings_start_time"];
					}
					if (!isset($provision["yealink_time_zone_end_time"])) {
						$provision["yealink_time_zone_end_time"] = $provision["daylight_savings_stop_month"]."/".$provision["daylight_savings_stop_day"]."/".$provision["daylight_savings_stop_time"];
					}

				//replace the dynamic provision variables that are defined in default, domain, and device settings
					if (is_array($provision)) {
						foreach($provision as $key=>$val) {
							if (!empty($val) && is_string($val) && strpos($val, '{$domain_name}') !== false) {
								$val = str_replace('{$domain_name}', $domain_name, $val);
							}
							if (!empty($val) && is_string($val) && strpos($val, '${domain_name}') !== false) {
								$val = str_replace('${domain_name}', $domain_name, $val);
							}
							$view->assign($key, $val);
						}
					}

				//if $file is not provided then look for a default file that exists
					if (empty($file)) {
						if (file_exists($template_dir."/".$device_template ."/{\$address}")) {
							$file = "{\$address}";
						}
						elseif (file_exists($template_dir."/".$device_template ."/{\$address}.xml")) {
							$file = "{\$address}.xml";
						}
						elseif (file_exists($template_dir."/".$device_template ."/{\$mac}")) {
							$file = "{\$mac}";
						}
						elseif (file_exists($template_dir."/".$device_template ."/{\$mac}.xml")) {
							$file = "{\$mac}.xml";
						}
						elseif (file_exists($template_dir."/".$device_template ."/{\$mac}.cfg")) {
							$file = "{\$mac}.cfg";
						}
						else {
							$this->http_error('404');
							exit;
						}
					}
					else {
						//make sure the file exists
						if (!file_exists($template_dir."/".$device_template ."/".$file)) {
							$this->http_error('404');
							if ($this->settings->get('provision','debug',false)) {
								echo ":$template_dir/$device_template/$file<br/>";
								echo "template_dir: $template_dir<br/>";
								echo "device_template: $device_template<br/>";
								echo "file: $file";
							}
							exit;
						}
					}

				//output template to string for header processing
					$file_contents = $view->render($file);

				//log file for testing
					if ($this->settings->get('provision','debug',false)) {
						$tmp_file = "/tmp/provisioning_log.txt";
						$fh = fopen($tmp_file, 'w') or die("can't open file");
						$tmp_string = $device_address."\n";
						fwrite($fh, $tmp_string);
						fclose($fh);
					}

					$this->file = $file;

				//returned the rendered template
					return $file_contents;

		} //end render function

		function write() {
			//build the provision array
				$provision = $this->settings->get('provision', null, []);
				foreach ($provision as $key => $val) {
					if (isset($val['var'])) {
						$value = $val['var'];
					} elseif (isset($val['text'])) {
						$value = $val['text'];
					} elseif (isset($val['boolean'])) {
						$value = $val['boolean'];
					} elseif (isset($val['numeric'])) {
						$value = $val['numeric'];
					} elseif (is_array($val) && !is_uuid($val['uuid'])) {
						$value = $val;
					}
					if (isset($value)) {
						$provision[$key] = $value;
					}
					unset($value);
				}

			//check either we have destination path to write files
				if (empty($provision["path"])) {
					return;
				}

			//get the devices from database
				$sql = "select * from v_devices ";
				//$sql .= "where domain_uuid = :domain_uuid ";
				//$parameters['domain_uuid'] = $this->domain_uuid;
				$result = $this->database->select($sql, null, 'all');

			//process each device
				if (is_array($result)) {
					foreach ($result as $row) {
						//get the values from the database and set as variables
							$domain_uuid = $row["domain_uuid"];
							$device_uuid = $row["device_uuid"];
							$device_address = $row["device_address"];
							$device_label = $row["device_label"];
							$device_vendor = strtolower($row["device_vendor"] ?? '');
							$device_model = $row["device_model"];
							$device_firmware_version = $row["device_firmware_version"];
							$device_enabled = $row["device_enabled"];
							$device_template = $row["device_template"];
							$device_username = $row["device_username"];
							$device_password = $row["device_password"];
							$device_description = $row["device_description"];

						//clear the cache
							clearstatcache();

						//loop through the provision template directory
							$dir_array = array();
							if (!empty($device_template)) {
								$template_path = path_join($this->template_dir, $device_template);
								$dir_list = opendir($template_path);
								if ($dir_list) {
									$x = 0;
									while (false !== ($file = readdir($dir_list))) {
										$ignore = $file == "." || $file == ".." || substr($file, -3) == ".db" ||
											substr($file, -4) == ".svn" || substr($file, -4) == ".git";
										if (!$ignore) {
											$dir_array[] = path_join($template_path, $file);
											if ($x > 1000) { break; };
											$x++;
										}
									}
									closedir($dir_list);
									unset($x, $file);
								}
								unset($dir_list, $template_path);
							}

						//loop through the provision templates
							if (is_array($dir_array)) {
								foreach ($dir_array as $template_path) {
									if (is_dir($template_path)) continue;
									if (!file_exists($template_path)) continue;

									//template file name
										$file_name = basename($template_path);

									//configure device object
										$this->domain_uuid = $domain_uuid;
										$this->device_address = $device_address;
										$this->file = $file_name;

									//format the device address
										$address_formatted = $this->format_address($device_address, $device_vendor);

									//replace {$mac} in the file name
										$file_name = str_replace("{\$mac}", $address_formatted, $file_name);
										$file_name = str_replace("{\$address}", $address_formatted, $file_name);

									//render and write configuration to file
										$provision_dir_array = explode(";", $provision["path"]);
										if (is_array($provision_dir_array)) {
											foreach ($provision_dir_array as $directory) {
												//destinatino file path
													$dest_path = path_join($directory, $file_name);

													if ($device_enabled == 'true') {
														//output template to string for header processing
															$file_contents = $this->render();

														//write the file
															if (!is_dir($directory)) {
																mkdir($directory, 0777, true);
															}
															$fh = fopen($dest_path,"w") or die("Unable to write to $directory for provisioning. Make sure the path exists and permissons are set correctly.");
															fwrite($fh, $file_contents);
															fclose($fh);
													}
													else { // device disabled
														//remove only files with `{$mac}` name
															if (strpos($template_path, '{$mac}') !== false){
																unlink($dest_path);
															}
													}

													unset($dest_path);
											}
										}
									//unset variables
										unset($file_name, $provision_dir_array);
								}
							}

						//unset variables
							unset($dir_array);
					}
				}
		} //end write function

	} //end provision class

?>
