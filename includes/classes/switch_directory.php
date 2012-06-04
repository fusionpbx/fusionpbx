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
	class switch_directory {
		public $domain_uuid;
		public $domain_name;
		public $db_type;
		public $extension;
		public $number_alias;
		public $password;
		public $vm_password;
		public $accountcode;
		public $effective_caller_id_name;
		public $effective_caller_id_number;
		public $outbound_caller_id_name;
		public $outbound_caller_id_number;
		public $limit_max=5;
		public $limit_destination;
		public $vm_enabled=1;
		public $vm_mailto;
		public $vm_attach_file;
		public $vm_keep_local_after_email;
		public $user_context;
		public $range;
		public $autogen_users;
		public $toll_allow;
		public $call_group;
		public $hold_music;
		public $auth_acl;
		public $cidr;
		public $sip_force_contact;
		public $sip_force_expires;
		public $nibble_account;
		public $mwi_account;
		public $sip_bypass_media;
		public $enabled;
		public $description;
		public $cidr;
		public $number_alias;

		// get domain_uuid
			public function get_domain_uuid() {
				return $this->domain_uuid;
			}
		// set domain_uuid
			public function set_domain_uuid($domain_uuid){
				$this->domain_uuid = $domain_uuid;
			}

		// get domain_name
			public function get_domain_name() {
				return $this->domain_name;
			}
		// set domain_name
			public function set_domain_name($domain_name){
				$this->domain_name = $domain_name;
			}

		// get db_type
			public function get_db_type() {
				return $this->db_type;
			}
		// set db_type
			public function set_db_type($db_type){
				$this->db_type = $db_type;
			}

		// get extension
			public function get_extension() {
				return $this->extension;
			}
		// set extension
			public function set_extension($extension){
				$this->extension = $extension;
			}

		public function add() {
			global $db;
			$domain_uuid = $this->domain_uuid;
			$domain_name = $this->domain_name;
			$extension = $this->extension;
			$number_alias = $this->number_alias;
			$password = $this->password;
			$autogen_users = $this->autogen_users;
			$provisioning_list = $this->provisioning_list;
			$vm_password = $this->vm_password;
			$accountcode = $this->accountcode;
			$effective_caller_id_name = $this->effective_caller_id_name;
			$effective_caller_id_number = $this->effective_caller_id_number;
			$outbound_caller_id_name = $this->outbound_caller_id_name;
			$outbound_caller_id_number = $this->outbound_caller_id_number;
			$limit_max = $this->limit_max;
			$limit_destination = $this->limit_destination;
			$vm_enabled = $this->vm_enabled;
			$vm_mailto = $this->vm_mailto;
			$vm_attach_file = $this->vm_attach_file;
			$vm_keep_local_after_email = $this->vm_keep_local_after_email;
			$user_context = $this->user_context;
			$toll_allow = $this->toll_allow;
			$call_group = $this->call_group;
			$hold_music = $this->hold_music;
			$auth_acl = $this->auth_acl;
			$cidr = $this->cidr;
			$sip_force_contact = $this->sip_force_contact;
			$sip_force_expires = $this->sip_force_expires;
			$nibble_account = $this->nibble_account;
			$mwi_account = $this->mwi_account;
			$sip_bypass_media = $this->sip_bypass_media;
			$enabled = $this->enabled;
			$description = $this->description;

			$db->beginTransaction();
			for ($i=1; $i<=$range; $i++) {
				if (extension_exists($extension)) {
					//extension exists
				}
				else {
					//extension does not exist add it
					$password = generate_password();
					$sql = "insert into v_extensions ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "extension_uuid, ";
					$sql .= "extension, ";
					$sql .= "number_alias, ";
					$sql .= "password, ";
					$sql .= "provisioning_list, ";
					$sql .= "vm_password, ";
					$sql .= "accountcode, ";
					$sql .= "effective_caller_id_name, ";
					$sql .= "effective_caller_id_number, ";
					$sql .= "outbound_caller_id_name, ";
					$sql .= "outbound_caller_id_number, ";
					$sql .= "limit_max, ";
					$sql .= "limit_destination, ";
					$sql .= "vm_enabled, ";
					$sql .= "vm_mailto, ";
					$sql .= "vm_attach_file, ";
					$sql .= "vm_keep_local_after_email, ";
					$sql .= "user_context, ";
					$sql .= "toll_allow, ";
					$sql .= "call_group, ";
					$sql .= "hold_music, ";
					$sql .= "auth_acl, ";
					$sql .= "cidr, ";
					$sql .= "sip_force_contact, ";
					if (strlen($sip_force_expires) > 0) {
						$sql .= "sip_force_expires, ";
					}
					if (strlen($nibble_account) > 0) {
						$sql .= "nibble_account, ";
					}
					if (strlen($mwi_account) > 0) {
						$sql .= "mwi_account, ";
					}
					$sql .= "sip_bypass_media, ";
					$sql .= "enabled, ";
					$sql .= "description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$extension_uuid', ";
					$sql .= "'$extension', ";
					$sql .= "'$number_alias', ";
					$sql .= "'$password', ";
					$sql .= "'$provisioning_list', ";
					$sql .= "'user-choose', ";
					$sql .= "'$accountcode', ";
					$sql .= "'$effective_caller_id_name', ";
					$sql .= "'$effective_caller_id_number', ";
					$sql .= "'$outbound_caller_id_name', ";
					$sql .= "'$outbound_caller_id_number', ";
					$sql .= "'$limit_max', ";
					$sql .= "'$limit_destination', ";
					$sql .= "'$vm_enabled', ";
					$sql .= "'$vm_mailto', ";
					$sql .= "'$vm_attach_file', ";
					$sql .= "'$vm_keep_local_after_email', ";
					$sql .= "'$user_context', ";
					$sql .= "'$toll_allow', ";
					$sql .= "'$call_group', ";
					$sql .= "'$hold_music', ";
					$sql .= "'$auth_acl', ";
					$sql .= "'$cidr', ";
					$sql .= "'$sip_force_contact', ";
					if (strlen($sip_force_expires) > 0) {
						$sql .= "'$sip_force_expires', ";
					}
					if (strlen($nibble_account) > 0) {
						$sql .= "'$nibble_account', ";
					}
					if (strlen($mwi_account) > 0) {
						if (strpos($mwi_account, '@') === false) {
							if (count($_SESSION["domains"]) > 1) {
								$mwi_account .= "@".$domain_name;
							}
							else {
								$mwi_account .= "@\$\${domain}";
							}
						}
						$sql .= "'$mwi_account', ";
					}
					$sql .= "'$sip_bypass_media', ";
					$sql .= "'$enabled', ";
					$sql .= "'$description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}
				$extension++;
			}
			$db->commit();
		}

		public function update() {
			global $db;

			$domain_uuid = $this->domain_uuid;
			$domain_name = $this->domain_name;
			$extension = $this->extension;
			$number_alias = $this->number_alias;
			$password = $this->password;
			$autogen_users = $this->autogen_users;
			$provisioning_list = $this->provisioning_list;
			$vm_password = $this->vm_password;
			$accountcode = $this->accountcode;
			$effective_caller_id_name = $this->effective_caller_id_name;
			$effective_caller_id_number = $this->effective_caller_id_number;
			$outbound_caller_id_name = $this->outbound_caller_id_name;
			$outbound_caller_id_number = $this->outbound_caller_id_number;
			$limit_max = $this->limit_max;
			$limit_destination = $this->limit_destination;
			$vm_enabled = $this->vm_enabled;
			$vm_mailto = $this->vm_mailto;
			$vm_attach_file = $this->vm_attach_file;
			$vm_keep_local_after_email = $this->vm_keep_local_after_email;
			$user_context = $this->user_context;
			$toll_allow = $this->toll_allow;
			$call_group = $this->call_group;
			$hold_music = $this->hold_music;
			$auth_acl = $this->auth_acl;
			$cidr = $this->cidr;
			$sip_force_contact = $this->sip_force_contact;
			$sip_force_expires = $this->sip_force_expires;
			$nibble_account = $this->nibble_account;
			$mwi_account = $this->mwi_account;
			$sip_bypass_media = $this->sip_bypass_media;
			$enabled = $this->enabled;
			$description = $this->description;

			//$user_list_array = explode("|", $user_list);
			//foreach($user_list_array as $tmp_user){
			//	$user_password = generate_password();
			//	if (strlen($tmp_user) > 0) {
			//		user_add($tmp_user, $user_password, $user_email);
			//	}
			//}
			//unset($tmp_user);

			if (strlen($password) == 0) {
				$password = generate_password();
			}

			$sql = "update v_extensions set ";
			$sql .= "extension = '$extension', ";
			$sql .= "number_alias = '$number_alias', ";
			$sql .= "password = '$password', ";
			$sql .= "provisioning_list = '$provisioning_list', ";
			if (strlen($vm_password) > 0) {
				$sql .= "vm_password = '$vm_password', ";
			}
			else {
				$sql .= "vm_password = 'user-choose', ";
			}
			$sql .= "accountcode = '$accountcode', ";
			$sql .= "effective_caller_id_name = '$effective_caller_id_name', ";
			$sql .= "effective_caller_id_number = '$effective_caller_id_number', ";
			$sql .= "outbound_caller_id_name = '$outbound_caller_id_name', ";
			$sql .= "outbound_caller_id_number = '$outbound_caller_id_number', ";
			$sql .= "limit_max = '$limit_max', ";
			$sql .= "limit_destination = '$limit_destination', ";
			$sql .= "vm_enabled = '$vm_enabled', ";
			$sql .= "vm_mailto = '$vm_mailto', ";
			$sql .= "vm_attach_file = '$vm_attach_file', ";
			$sql .= "vm_keep_local_after_email = '$vm_keep_local_after_email', ";
			$sql .= "user_context = '$user_context', ";
			$sql .= "toll_allow = '$toll_allow', ";
			$sql .= "call_group = '$call_group', ";
			$sql .= "hold_music = '$hold_music', ";
			$sql .= "auth_acl = '$auth_acl', ";
			$sql .= "cidr = '$cidr', ";
			$sql .= "sip_force_contact = '$sip_force_contact', ";
			if (strlen($sip_force_expires) == 0) {
				$sql .= "sip_force_expires = null, ";
			}
			else {
				$sql .= "sip_force_expires = '$sip_force_expires', ";
			}
			if (strlen($nibble_account) == 0) {
				$sql .= "nibble_account = null, ";
			}
			else {
				$sql .= "nibble_account = '$nibble_account', ";
			}
			if (strlen($mwi_account) > 0) {
				if (strpos($mwi_account, '@') === false) {
					if (count($_SESSION["domains"]) > 1) {
						$mwi_account .= "@".$domain_name;
					}
					else {
						$mwi_account .= "@\$\${domain}";
					}
				}
			}
			$sql .= "mwi_account = '$mwi_account', ";
			$sql .= "sip_bypass_media = '$sip_bypass_media', ";
			$sql .= "enabled = '$enabled', ";
			$sql .= "description = '$description' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and extension_uuid = '$extension_uuid'";
			$db->exec(check_sql($sql));
			unset($sql);
		}

		function delete() {
			global $db;
			$domain_uuid = $this->domain_uuid;
			$extension_uuid = $this->extension_uuid;
			if (strlen($extension_uuid)>0) {
				$sql = "";
				$sql .= "delete from v_extensions ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and extension_uuid = '$extension_uuid' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql);
			}
		}

		function import_sql($data){
			$count=count($data);
			$keys=$values=SplFixedArray($count);
			$keys=array_keys($data);
			$values=array_values($data);
			for($i=0;$i<$count;$i++){
				$keys[$i]= str_replace("-", "_", $keys[$i]); 
				$this->{$keys[$i]}=$values[$i];			
			}
		}

		function set_bool(&$var,$default=null){
			$var=strtolower($var);
			if		($var==="true")  return;
			elseif	($var==="false") return;
			elseif	($var==true)  $var="true";
			elseif	($var==false) $var="false";
			elseif(!is_null($default)) {
				$var=$default;
				$this->set_bool($var);
				}
		}

		function generate_xml($single=1){
			//switch_account_code!! How should we be passing this??
			
			if ($this->enabled== "false" || !$this->enabled) {
				return false;//This the best way??
			}
			
			$this->vm_password = str_replace("#", "", $this->vm_password); //preserves leading zeros//**Generic Validation!
			
			/*if(!in_array($this->vm_enabled,array("false","true"))) {//**Generic Validation!
				$this->vm_enabled = "true";
			}
			if(!in_array($this->vm_attach_file,array("false","true"))) {//**Generic Validation!
				$this->vm_attach_file = "true";
			}
			if(!in_array($this->vm_keep_local_after_email,array("false","true"))) {//**Generic Validation!
				$this->vm_keep_local_after_email = "true";
			}
			 */
			$this->set_bool($this->vm_enabled,1);
			$this->set_bool($this->vm_attach_file,1);
			$this->set_bool($this->vm_keep_local_after_email,1);

			//remove invalid characters from the file names //**Generic Validation!
			$this->extension = str_replace(" ", "_", $this->extension);
			$this->extension = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $this->extension);

			/*if (!$extension_xml_condensed) { <--- what do I do with this??
				$fout = fopen($_SESSION['switch']['extensions']['dir']."/v_".$extension.".xml","w");
				$tmp_xml .= "<include>\n";
			}*/
			if (strlen($this->cidr)) {
				$this->cidr = " cidr=\"" . $this->cidr . "\"";
			}
			if (strlen($this->number_alias)) {
				$this->number_alias = " number-alias=\"".$this->number_alias."\"";
			}
			if($single)	$tmp_xml  = "<include>\n";
			else		$tmp_xml  = "";
			$tmp_xml .= "  <user id=\"".$this->extension."\"".$this->cidr."".$this->number_alias.">\n";
			$tmp_xml .= "    <params>\n";
			$tmp_xml .= "      <param name=\"password\" value=\"" . $this->password . "\"/>\n";
			$tmp_xml .= "      <param name=\"vm-enabled\" value=\"".$this->vm_enabled."\"/>\n";

			if ($this->vm_enabled=="true"){
				$tmp_xml .= "      <param name=\"vm-password\" value=\"" . $this->vm_password . "\"/>\n";
				if(strlen($this->vm_mailto)) {
					$tmp_xml .= "      <param name=\"vm-email-all-messages\" value=\"true\"/>\n";
					$tmp_xml .= "      <param name=\"vm-attach-file\" value=\"".$this->vm_attach_file."\"/>\n";
					$tmp_xml .= "      <param name=\"vm-keep-local-after-email\" value=\"".$this->vm_keep_local_after_email."\"/>\n";
					$tmp_xml .= "      <param name=\"vm-mailto\" value=\"" . $this->vm_mailto . "\"/>\n";
				}
			}
			if (strlen($this->mwi_account)) {
				$tmp_xml .= "      <param name=\"MWI-Account\" value=\"" . $this->mwi_account . "\"/>\n";
			}
			if (strlen($this->auth_acl)) {
				$tmp_xml .= "      <param name=\"auth-acl\" value=\"" . $this->auth_acl . "\"/>\n";
			}
			$tmp_xml .= "    </params>\n";
			
			$tmp_xml .= "    <variables>\n";
			if (strlen($this->hold_music)) {
				$tmp_xml .= "      <variable name=\"hold_music\" value=\"" . $this->hold_music . "\"/>\n";
			}
			if (strlen($this->toll_allow)){
				$tmp_xml .= "      <variable name=\"toll_allow\" value=\"" . $this->toll_allow . "\"/>\n";
			}
			if (strlen($this->accountcode)){
				$tmp_xml .= "      <variable name=\"accountcode\" value=\"" . $this->accountcode . "\"/>\n";
			}
			$tmp_xml .= "      <variable name=\"user_context\" value=\"" . $this->user_context . "\"/>\n";
			if (strlen($this->effective_caller_id_name)) {
				$tmp_xml .= "      <variable name=\"effective_caller_id_name\" value=\"" . $this->effective_caller_id_name . "\"/>\n";
			}
			if (strlen($this->outbound_caller_id_number)) {
				$tmp_xml .= "      <variable name=\"effective_caller_id_number\" value=\"" . $this->effective_caller_id_number . "\"/>\n";
			}
			if (strlen($this->outbound_caller_id_name)) {
				$tmp_xml .= "      <variable name=\"outbound_caller_id_name\" value=\"" . $this->outbound_caller_id_name . "\"/>\n";
			}
			if (strlen($this->outbound_caller_id_number)) {
				$tmp_xml .= "      <variable name=\"outbound_caller_id_number\" value=\"" . $this->outbound_caller_id_number . "\"/>\n";
			}
			if (!strlen($this->limit_max)) {//**validation
				$this->limit_max=5;
			}
			$tmp_xml .= "      <variable name=\"limit_max\" value=\"" . $this->limit_max . "\"/>\n";
			if (strlen($this->limit_destination)) {
				$tmp_xml .= "      <variable name=\"limit_destination\" value=\"" . $this->limit_destination . "\"/>\n";
			}
			if (strlen($this->sip_force_contact)) {
				$tmp_xml .= "      <variable name=\"sip-force-contact\" value=\"" . $this->sip_force_contact . "\"/>\n";
			}
			if (strlen($this->sip_force_expires)) {
				$tmp_xml .= "      <variable name=\"sip-force-expires\" value=\"" . $this->sip_force_expires . "\"/>\n";
			}
			if (strlen($this->nibble_account)) {
				$tmp_xml .= "      <variable name=\"nibble_account\" value=\"" . $this->nibble_account . "\"/>\n";
			}
			switch ($this->sip_bypass_media) {
				case "bypass-media":
						$tmp_xml .= "      <variable name=\"bypass_media\" value=\"true\"/>\n";
						break;
				case "bypass-media-after-bridge":
						$tmp_xml .= "      <variable name=\"bypass_media_after_bridge\" value=\"true\"/>\n";
						break;
				case "proxy-media":
						$tmp_xml .= "      <variable name=\"proxy_media\" value=\"true\"/>\n";
						break;
			}
			$tmp_xml .= "    </variables>\n";
			$tmp_xml .= "  </user>\n";
			if($single) { $tmp_xml .= "</include>\n"; }

			return $tmp_xml;
		}

		function xml_save_all() {
			global $db, $config;
			$domain_uuid = $this->domain_uuid;
			$domain_name = $this->domain_name;

			//get the system settings paths and set them as variables
				$settings_array = v_settings();
				foreach($settings_array as $name => $value) {
					$$name = $value;
				}

			//determine the extensions parent directory
				$extension_parent_dir = realpath($_SESSION['switch']['extensions']['dir']."/..");

			// delete all old extensions to prepare for new ones
				if($dh = opendir($_SESSION['switch']['extensions']['dir'])) {
					$files = Array();
					while($file = readdir($dh)) {
						if($file != "." && $file != ".." && $file[0] != '.') {
							if(is_dir($dir . "/" . $file)) {
								//this is a directory do nothing
							} else {
								//check if file is an extension; verify the file numeric and the extension is xml
								if (substr($file,0,2) == 'v_' && substr($file,-4) == '.xml') {
									unlink($_SESSION['switch']['extensions']['dir']."/".$file);
								}
							}
						}
					}
					closedir($dh);
				}

			$sql = "";
			$sql .= "select * from v_extensions ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "order by call_group asc ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$i = 0;
			$extension_xml_condensed = false;
			if ($extension_xml_condensed) {
				$fout = fopen($_SESSION['switch']['extensions']['dir']."/v_extensions.xml","w");
				$tmp_xml = "<include>\n";
			}
			while($row = $prep_statement->fetch(PDO::FETCH_ASSOC)) {
				$call_group = $row['call_group'];
				$call_group = str_replace(";", ",", $call_group);
				$tmp_array = explode(",", $call_group);
				foreach ($tmp_array as &$tmp_call_group) {
					if (strlen($tmp_call_group) > 0) {
						if (strlen($call_group_array[$tmp_call_group]) == 0) {
							$call_group_array[$tmp_call_group] = $row['extension'];
						}
						else {
							$call_group_array[$tmp_call_group] = $call_group_array[$tmp_call_group].','.$row['extension'];
						}
					}
					$i++;
				}

				if ($row['enabled'] != "false") {
					//$this->import_sql($row);//Do I need to be worried about ghost values? Maybe I should make a new object?
					//if (strlen($switch_account_code)) $this->accountcode=$switch_account_code;
					//$tmp_xml.=$this->generate_xml(1);

					$one_row=new fs_directory;
					$one_row->import_sql($row);//make a new object to flush ghost rows. And we can call this as static.
					if (strlen($switch_account_code)) $one_row->accountcode=$switch_account_code;
					$tmp_xml.=$one_row->generate_xml(false);

					if (!$extension_xml_condensed) {
						$tmp_xml .= "</include>\n";
						fwrite($fout, $tmp_xml);
						unset($tmp_xml);
						fclose($fout);
					}
				}
			}
			unset ($prep_statement);
			if ($extension_xml_condensed) {
				$tmp_xml .= "</include>\n";
				fwrite($fout, $tmp_xml);
				unset($tmp_xml);
				fclose($fout);
			}

			//define the group members
				$tmp_xml = "<!--\n";
				$tmp_xml .= "	NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE\n";
				$tmp_xml .= "\n";
				$tmp_xml .= "	FreeSWITCH works off the concept of users and domains just like email.\n";
				$tmp_xml .= "	You have users that are in domains for example 1000@domain.com.\n";
				$tmp_xml .= "\n";
				$tmp_xml .= "	When freeswitch gets a register packet it looks for the user in the directory\n";
				$tmp_xml .= "	based on the from or to domain in the packet depending on how your sofia profile\n";
				$tmp_xml .= "	is configured.  Out of the box the default domain will be the IP address of the\n";
				$tmp_xml .= "	machine running FreeSWITCH.  This IP can be found by typing \"sofia status\" at the\n";
				$tmp_xml .= "	CLI.  You will register your phones to the IP and not the hostname by default.\n";
				$tmp_xml .= "	If you wish to register using the domain please open vars.xml in the root conf\n";
				$tmp_xml .= "	directory and set the default domain to the hostname you desire.  Then you would\n";
				$tmp_xml .= "	use the domain name in the client instead of the IP address to register\n";
				$tmp_xml .= "	with FreeSWITCH.\n";
				$tmp_xml .= "\n";
				$tmp_xml .= "	NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE NOTICE\n";
				$tmp_xml .= "-->\n";
				$tmp_xml .= "\n";
				$tmp_xml .= "<include>\n";
				$tmp_xml .= "	<!--the domain or ip (the right hand side of the @ in the addr-->\n";
				if ($extension_dir_name == "default") { 
					$tmp_xml .= "	<domain name=\"\$\${domain}\">\n";
				}
				else {
					$tmp_xml .= "	<domain name=\"".$extension_dir_name."\">\n";
				}
				$tmp_xml .= "		<params>\n";
				$tmp_xml .= "			<param name=\"dial-string\" value=\"{sip_invite_domain=\${domain_name},presence_id=\${dialed_user}@\${dialed_domain}}\${sofia_contact(\${dialed_user}@\${dialed_domain})}\"/>\n";
				$tmp_xml .= "		</params>\n";
				$tmp_xml .= "\n";
				$tmp_xml .= "		<variables>\n";
				$tmp_xml .= "			<variable name=\"record_stereo\" value=\"true\"/>\n";
				$tmp_xml .= "			<variable name=\"default_gateway\" value=\"\$\${default_provider}\"/>\n";
				$tmp_xml .= "			<variable name=\"default_areacode\" value=\"\$\${default_areacode}\"/>\n";
				$tmp_xml .= "			<variable name=\"transfer_fallback_extension\" value=\"operator\"/>\n";
				$tmp_xml .= "			<variable name=\"export_vars\" value=\"domain_name\"/>\n";
				$tmp_xml .= "		</variables>\n";
				$tmp_xml .= "\n";
				$tmp_xml .= "		<groups>\n";
				$tmp_xml .= "			<group name=\"".$extension_dir_name."\">\n";
				$tmp_xml .= "			<users>\n";
				$tmp_xml .= "				<X-PRE-PROCESS cmd=\"include\" data=\"".$extension_dir_name."/*.xml\"/>\n";
				$tmp_xml .= "			</users>\n";
				$tmp_xml .= "			</group>\n";
				$tmp_xml .= "\n";
				$previous_call_group = "";
				foreach ($call_group_array as $key => $value) {
					$call_group = $key;
					$extension_list = $value;
					if (strlen($call_group) > 0) {
						if ($previous_call_group != $call_group) {
							$tmp_xml .= "			<group name=\"$call_group\">\n";
							$tmp_xml .= "				<users>\n";
							$tmp_xml .= "					<!--\n";
							$tmp_xml .= "					type=\"pointer\" is a pointer so you can have the\n";
							$tmp_xml .= "					same user in multiple groups.  It basically means\n";
							$tmp_xml .= "					to keep searching for the user in the directory.\n";
							$tmp_xml .= "					-->\n";
							$extension_array = explode(",", $extension_list);
							foreach ($extension_array as &$tmp_extension) {
								$tmp_xml .= "					<user id=\"$tmp_extension\" type=\"pointer\"/>\n";
							}
							$tmp_xml .= "				</users>\n";
							$tmp_xml .= "			</group>\n";
							$tmp_xml .= "\n";
						}
						$previous_call_group = $call_group;
					}
					unset($call_group);
				}
				$tmp_xml .= "		</groups>\n";
				$tmp_xml .= "\n";
				$tmp_xml .= "	</domain>\n";
				$tmp_xml .= "</include>";

			//remove invalid characters from the file names
				$extension_dir_name = str_replace(" ", "_", $extension_dir_name);
				$extension_dir_name = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $extension_dir_name);

			//write the xml file
				$fout = fopen($extension_parent_dir."/".$extension_dir_name.".xml","w");
				fwrite($fout, $tmp_xml);
				unset($tmp_xml);
				fclose($fout);

			//syncrhonize the phone directory
				sync_directory();

			//apply settings reminder
				$_SESSION["reload_xml"] = true;

			//call reloadxml direct
				//$cmd = "api reloadxml";
				//event_socket_request_cmd($cmd);
				//unset($cmd);

		}  //end function
	} //class

?>