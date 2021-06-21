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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

	if (!function_exists('mb_strtoupper')) {
		function mb_strtoupper($string) {
			return strtoupper($string);
		}
	}

	if (!function_exists('check_float')) {
		function check_float($string) {
			$string = str_replace(",",".",$string);
			return trim($string);
		}
	}

	if (!function_exists('check_str')) {
		function check_str($string, $trim = true) {
			global $db_type, $db;
			//when code in db is urlencoded the ' does not need to be modified
			if ($db_type == "sqlite") {
				if (function_exists('sqlite_escape_string')) {
					$string = sqlite_escape_string($string);
				}
				else {
					$string = str_replace("'","''",$string);
				}
			}
			if ($db_type == "pgsql") {
				$string = str_replace("'","''",$string);
			}
			if ($db_type == "mysql") {
				if(function_exists('mysql_real_escape_string')){
					$tmp_str = mysql_real_escape_string($string);
				}
				else{
					$tmp_str = mysqli_real_escape_string($db, $string);
				}
				if (strlen($tmp_str)) {
					$string = $tmp_str;
				}
				else {
					$search = array("\x00", "\n", "\r", "\\", "'", "\"", "\x1a");
					$replace = array("\\x00", "\\n", "\\r", "\\\\" ,"\'", "\\\"", "\\\x1a");
					$string = str_replace($search, $replace, $string);
				}
			}
			$string = ($trim) ? trim($string) : $string;
			return $string;
		}
	}

	if (!function_exists('check_sql')) {
		function check_sql($string) {
			return trim($string); //remove white space
		}
	}

	if (!function_exists('check_cidr')) {
		function check_cidr ($cidr,$ip_address) {
			list ($subnet, $mask) = explode ('/', $cidr);
			return ( ip2long ($ip_address) & ~((1 << (32 - $mask)) - 1) ) == ip2long ($subnet);
		}
	}

	if (!function_exists('fix_postback')) {
		function fix_postback($post_array) {
			foreach ($post_array as $index => $value) {
				if (is_array($value)) { fix_postback($value); }
				else {
					$value = str_replace('"', "&#34;", $value);
					$value = str_replace("'", "&#39;", $value);
					$post_array[$index] = $value;
				}
			}
			return $post_array;
		}
	}

	if (!function_exists('uuid')) {
		function uuid() {
			$uuid = null;
			if (PHP_OS === 'FreeBSD') {
				$uuid = trim(shell_exec("uuid -v 4"));
				if (is_uuid($uuid)) {
					return $uuid;
				}
				else {
					echo "Please install the following package.\n";
					echo "pkg install ossp-uuid\n";
					exit;
				}
			}
			if (PHP_OS === 'Linux') {
				$uuid = trim(file_get_contents('/proc/sys/kernel/random/uuid'));
				if (is_uuid($uuid)) {
					return $uuid;
				}
				else {
					$uuid = trim(shell_exec("uuidgen"));
					if (is_uuid($uuid)) {
						return $uuid;
					}
					else {
						echo "Please install the uuidgen.\n";
						exit;
					}
				}
			}
			if ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') && function_exists('com_create_guid')) {
				$uuid = trim(com_create_guid(), '{}');
				if (is_uuid($uuid)) {
					return $uuid;
				}
				else {
					echo "The com_create_guid() function failed to create a uuid.\n";
					exit;
				}
			}
		}
	}

	if (!function_exists('is_uuid')) {
		function is_uuid($uuid) {
			if (gettype($uuid) == 'string') {
				$regex = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';
				return preg_match($regex, $uuid);
			}
			return false;
		}
	}

	if (!function_exists('recursive_copy')) {
		if (file_exists('/bin/cp')) {
			function recursive_copy($source, $destination, $options = '') {
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'SUN') {
					//copy -R recursive, preserve attributes for SUN
					$cmd = 'cp -Rp '.$source.'/* '.$destination;
				}
				else {
					//copy -R recursive, -L follow symbolic links, -p preserve attributes for other Posix systemss
					$cmd = 'cp -RLp '.$options.' '.$source.'/* '.$destination;
				}
				exec ($cmd);
			}
		}
		elseif(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			function recursive_copy($source, $destination, $options = '') {
				$source = normalize_path_to_os($source);
				$destination = normalize_path_to_os($destination);
				exec("xcopy /E /Y \"$source\" \"$destination\"");
			}
		}
		else {
			function recursive_copy($source, $destination, $options = '') {
				$dir = opendir($source);
				if (!$dir) {
					throw new Exception("recursive_copy() source directory '".$source."' does not exist.");
				}
				if (!is_dir($destination)) {
					if (!mkdir($destination,02770,true)) {
						throw new Exception("recursive_copy() failed to create destination directory '".$destination."'");
					}
				}
				while(false !== ( $file = readdir($dir)) ) {
					if (( $file != '.' ) && ( $file != '..' )) {
						if ( is_dir($source . '/' . $file) ) {
							recursive_copy($source . '/' . $file,$destination . '/' . $file);
						}
						else {
							copy($source . '/' . $file,$destination . '/' . $file);
						}
					}
				}
				closedir($dir);
			}
		}
	}

	if (!function_exists('recursive_delete')) {
		if (file_exists('/usr/bin/find')) {
			function recursive_delete($directory) {
				if (isset($directory) && strlen($directory) > 8) {
					exec('/usr/bin/find '.$directory.'/* -name "*" -delete');
					//exec('rm -Rf '.$directory.'/*');
					clearstatcache();
				}
			}
		}
		elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			function recursive_delete($directory) {
				$directory = normalize_path_to_os($directory);
				//$this->write_debug("del /S /F /Q \"$dir\"");
				exec("del /S /F /Q \"$directory\"");
				clearstatcache();
			}
		}
		else {
			function recursive_delete($directory) {
				foreach (glob($directory) as $file) {
					if (is_dir($file)) {
						//$this->write_debug("rm dir: ".$file);
						recursive_delete("$file/*");
						rmdir($file);
					}
					else {
						//$this->write_debug("delete file: ".$file);
						unlink($file);
					}
				}
				clearstatcache();
			}
		}
	}

	if (!function_exists('if_group')) {
		function if_group($group) {
			//set default false
				$result = false;
			//search for the permission
				if (count($_SESSION["groups"]) > 0) {
					foreach($_SESSION["groups"] as $row) {
						if ($row['group_name'] == $group) {
							$result = true;
							break;
						}
					}
				}
			//return the result
				return $result;
		}
	}

	if (!function_exists('permission_exists')) {
		function permission_exists($permission) {
			//set default false
				$result = false;
			//find the permission
				if (is_array($_SESSION["permissions"]) && $_SESSION["permissions"][$permission] == true) {
					$result = true;
				}
			//return the result
				return $result;
		}
	}

	if (!function_exists('if_group_member')) {
		function if_group_member($group_members, $group) {
			if (stripos($group_members, "||".$group."||") === false) {
				return false; //group does not exist
			}
			else {
				return true; //group exists
			}
		}
	}

	if (!function_exists('superadmin_list')) {
		function superadmin_list() {
			global $domain_uuid;
			$sql = "select * from v_user_groups ";
			$sql .= "where group_name = 'superadmin' ";
			$database = new database;
			$result = $database->select($sql, null, 'all');
			$superadmin_list = "||";
			if (is_array($result) && @sizeof($result) != 0) {
				foreach ($result as $field) {
					//get the list of superadmins
					$superadmin_list .= $field['user_uuid']."||";
				}
			}
			unset($sql, $result, $field);
			return $superadmin_list;
		}
	}

	if (!function_exists('if_superadmin')) {
		function if_superadmin($superadmin_list, $user_uuid) {
			if (stripos($superadmin_list, "||".$user_uuid."||") === false) {
				return false;
			}
			else {
				return true; //user_uuid exists
			}
		}
	}

	if (!function_exists('html_select_other')) {
		function html_select_other($table_name, $field_name, $sql_where_optional, $field_current_value) {
			//html select other: build a select box from distinct items in db with option for other
			global $domain_uuid;
			$table_name = preg_replace("#[^a-zA-Z0-9_]#", "", $table_name);
			$field_name = preg_replace("#[^a-zA-Z0-9_]#", "", $field_name);

			$html = "<table border='0' cellpadding='1' cellspacing='0'>\n";
			$html .= "<tr>\n";
			$html .= "<td id=\"cell".escape($field_name)."1\">\n";
			$html .= "\n";
			$html .= "<select id=\"".escape($field_name)."\" name=\"".escape($field_name)."\" class='formfld' onchange=\"if (document.getElementById('".$field_name."').value == 'Other') { /*enabled*/ document.getElementById('".$field_name."_other').style.display=''; document.getElementById('".$field_name."_other').className='formfld'; document.getElementById('".$field_name."_other').focus(); } else { /*disabled*/ document.getElementById('".$field_name."_other').value = ''; document.getElementById('".$field_name."_other').style.display='none'; } \">\n";
			$html .= "<option value=''></option>\n";

			$sql = "select distinct(".$field_name.") as ".$field_name." ";
			$sql .= "from ".$table_name." ".$sql_where_optional." ";
			$database = new database;
			$result = $database->select($sql, null, 'all');
			if (is_array($result) && @sizeof($result) != 0) {
				foreach($result as $field) {
					if (strlen($field[$field_name]) > 0) {
						$html .= "<option value=\"".escape($field[$field_name])."\" ".($field_current_value == $field[$field_name] ? "selected='selected'" : null).">".escape($field[$field_name])."</option>\n";
					}
				}
			}
			unset($sql, $result, $field);

			$html .= "<option value='Other'>Other</option>\n";
			$html .= "</select>\n";
			$html .= "</td>\n";
			$html .= "<td id=\"cell".$field_name."2\" width='5'>\n";
			$html .= "<input id=\"".$field_name."_other\" name=\"".$field_name."_other\" value='' type='text' class='formfld' style='display: none;'>\n";
			$html .= "</td>\n";
			$html .= "</tr>\n";
			$html .= "</table>";

			return $html;
		}
	}

	if (!function_exists('html_select')) {
		function html_select($table_name, $field_name, $sql_where_optional, $field_current_value, $field_value = '', $style = '', $on_change = '') {
			//html select: build a select box from distinct items in db
			global $domain_uuid;

			$table_name = preg_replace("#[^a-zA-Z0-9_]#", "", $table_name);
			$field_name = preg_replace("#[^a-zA-Z0-9_]#", "", $field_name);
			$field_value = preg_replace("#[^a-zA-Z0-9_]#", "", $field_value);
		
			if (strlen($field_value) > 0) {
				$html .= "<select id=\"".$field_value."\" name=\"".$field_value."\" class='formfld' style='".$style."' ".($on_change != '' ? "onchange=\"".$on_change."\"" : null).">\n";
				$html .= "	<option value=\"\"></option>\n";

				$sql = "select distinct(".$field_name.") as ".$field_name.", ".$field_value." from ".$table_name." ".$sql_where_optional." order by ".$field_name." asc ";
			}
			else {
				$html .= "<select id=\"".$field_name."\" name=\"".$field_name."\" class='formfld' style='".$style."' ".($on_change != '' ? "onchange=\"".$on_change."\"" : null).">\n";
				$html .= "	<option value=\"\"></option>\n";

				$sql = "select distinct(".$field_name.") as ".$field_name." from ".$table_name." ".$sql_where_optional." ";
			}

			$database = new database;
			$result = $database->select($sql, null, 'all');
			if (is_array($result) && @sizeof($result) != 0) {
				foreach($result as $field) {
					if (strlen($field[$field_name]) > 0) {
						$selected = $field_current_value == $field[$field_name] ? "selected='selected'" : null;
						$array_key = strlen($field_value) > 0 ? $field_value : $field_name;
						$html .= "<option value=\"".urlencode($field[$array_key])."\" ".$selected.">".urlencode($field[$field_name])."</option>\n";
					}
				}
			}
			unset($sql, $result, $field);
			$html .= "</select>\n";

			return $html;
		}
	}

	if (!function_exists('th_order_by')) {
		//html table header order by
		function th_order_by($field_name, $column_title, $order_by, $order, $app_uuid = '', $css = '', $http_get_params = '', $description = '') {
			global $text;
			if (is_uuid($app_uuid) > 0) { $app_uuid = "&app_uuid=".urlencode($app_uuid); }	// accomodate need to pass app_uuid where necessary (inbound/outbound routes lists)

			$field_name = preg_replace("#[^a-zA-Z0-9_]#", "", $field_name);
			$field_value = preg_replace("#[^a-zA-Z0-9_]#", "", $field_value);

			$sanitized_parameters = '';
			if (isset($http_get_params) && strlen($http_get_params) > 0) {
				$parameters = explode('&', $http_get_params);
				if (is_array($parameters)) {
					foreach ($parameters as $parameter) {
						if (substr_count($parameter, '=') != 0) {
							$array = explode('=', $parameter);
							$key = preg_replace('#[^a-zA-Z0-9_\-]#', '', $array['0']);
							$value = urldecode($array['1']);
							if ($key == 'order_by' && strlen($value) > 0) {
								//validate order by
								$sanitized_parameters .= "&order_by=". preg_replace('#[^a-zA-Z0-9_\-]#', '', $value);
							}
							else if ($key == 'order' && strlen($value) > 0) {
								//validate order
								switch ($value) {
									case 'asc':
										$sanitized_parameters .= "&order=asc";
										break;
									case 'desc':
										$sanitized_parameters .= "&order=desc";
										break;
								}
							}
							else if (strlen($value) > 0 && is_numeric($value)) {
								$sanitized_parameters .= "&".$key."=".$value;
							}
							else {
								$sanitized_parameters .= "&".$key."=".urlencode($value);
							}
						}
					}
				}
			}

			$html = "<th ".$css." nowrap='nowrap'>";
			$description = (strlen($description) > 0) ? $description . ', ': '';
			if (strlen($order_by) == 0) {
				$order = 'asc';
			}
			if ($order_by == $field_name) {
				if ($order == "asc") {
					$description .= $text['label-order'].' '.$text['label-descending'];
					$html .= "<a href='?order_by=".urlencode($field_name)."&order=desc".$app_uuid.$sanitized_parameters."' title=\"".escape($description)."\">".escape($column_title)."</a>";
				}
				else {
					$description .= $text['label-order'].' '.$text['label-ascending'];
					$html .= "<a href='?order_by=".urlencode($field_name)."&order=asc".$app_uuid.$sanitized_parameters."' title=\"".escape($description)."\">".escape($column_title)."</a>";
				}
			}
			else {
				$description .= $text['label-order'].' '.$text['label-ascending'];
				$html .= "<a href='?order_by=".urlencode($field_name)."&order=asc".$app_uuid.$sanitized_parameters."' title=\"".escape($description)."\">".escape($column_title)."</a>";
			}
			$html .= "</th>";
			return $html;
		}
	}

	if (!function_exists('get_ext')) {
		function get_ext($filename) {
			preg_match('/[^?]*/', $filename, $matches);
			$string = $matches[0];

			$pattern = preg_split('/\./', $string, -1, PREG_SPLIT_OFFSET_CAPTURE);

			// check if there is any extension
			if(count($pattern) == 1){
				//echo 'No File Extension Present';
				return '';
			}

			if(count($pattern) > 1) {
				$filenamepart = $pattern[count($pattern)-1][0];
				preg_match('/[^?]*/', $filenamepart, $matches);
				return $matches[0];
			}
		}
		//echo "ext: ".get_ext('test.txt');
	}

	if (!function_exists('file_upload')) {
		function file_upload($field = '', $file_type = '', $dest_dir = '') {

			$uploadtempdir = $_ENV["TEMP"]."\\";
			ini_set('upload_tmp_dir', $uploadtempdir);

			$tmp_name = $_FILES[$field]["tmp_name"];
			$file_name = $_FILES[$field]["name"];
			$file_type = $_FILES[$field]["type"];
			$file_size = $_FILES[$field]["size"];
			$file_ext = get_ext($file_name);
			$file_name_orig = $file_name;
			$file_name_base = substr($file_name, 0, (strlen($file_name) - (strlen($file_ext)+1)));
			//$dest_dir = '/tmp';

			if ($file_size == 0) {
				return;
			}

			if (!is_dir($dest_dir)) {
				echo "dest_dir not found<br />\n";
				return;
			}

			//check if allowed file type
			if ($file_type == "img") {
				switch (strtolower($file_ext)) {
					case "jpg":
					case "png":
					case "gif":
					case "bmp":
					case "psd":
					case "tif": break;
					default: return false;
				}
			}
			if ($file_type == "file") {
				switch (strtolower($file_ext)) {
					case "doc":
					case "pdf":
					case "ppt":
					case "xls":
					case "zip":
					case "exe": break;
					default: return false;
				}
			}

			//find unique filename: check if file exists if it does then increment the filename
				$i = 1;
				while( file_exists($dest_dir.'/'.$file_name)) {
					if (strlen($file_ext)> 0) {
						$file_name = $file_name_base . $i .'.'. $file_ext;
					}
					else {
						$file_name = $file_name_orig . $i;
					}
					$i++;
				}

			//echo "file_type: ".$file_type."<br />\n";
			//echo "tmp_name: ".$tmp_name."<br />\n";
			//echo "file_name: ".$file_name."<br />\n";
			//echo "file_ext: ".$file_ext."<br />\n";
			//echo "file_name_orig: ".$file_name_orig."<br />\n";
			//echo "file_name_base: ".$file_name_base."<br />\n";
			//echo "dest_dir: ".$dest_dir."<br />\n";

			//move the file to upload directory
			//bool move_uploaded_file  ( string $filename, string $destination  )

				if (move_uploaded_file($tmp_name, $dest_dir.'/'.$file_name)) {
						return $file_name;
				}
				else {
					echo "File upload failed!  Here's some debugging info:\n";
					return false;
				}
				exit;

		}
	}

	if (!function_exists('sys_get_temp_dir')) {
		function sys_get_temp_dir() {
			if ($temp = getenv('TMP')) { return $temp; }
			if ($temp = getenv('TEMP')) { return $temp; }
			if ($temp = getenv('TMPDIR')) { return $temp; }
			$temp = tempnam(__FILE__,'');
			if (file_exists($temp)) {
				unlink($temp);
				return dirname($temp);
			}
			return null;
		}
	}
	//echo realpath(sys_get_temp_dir());

	if (!function_exists('normalize_path')) {
		//don't use DIRECTORY_SEPARATOR as it will change on a per platform basis and we need consistency
		function normalize_path($path) {
			return str_replace(array('/','\\'), '/', $path);
		}
	}

	if (!function_exists('normalize_path_to_os')) {
		function normalize_path_to_os($path) {
			return str_replace(array('/','\\'), DIRECTORY_SEPARATOR, $path);
		}
	}

	if (!function_exists('username_exists')) {
		function username_exists($username) {
			global $domain_uuid;
			$sql = "select count(*) from v_users ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and username = :username ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['username'] = $username;
			$database = new database;
			$num_rows = $database->select($sql, $parameters, 'column');
			return $num_rows > 0 ? true : false;
		}
	}

	if (!function_exists('add_extension_user')) {
		function add_extension_user($extension_uuid, $username) {
			global $domain_uuid;
			//get the user_uuid by using the username
				$sql = "select user_uuid from v_users ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and username = :username ";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['username'] = $username;
				$database = new database;
				$user_uuid = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

				if (is_uuid($user_uuid)) {
					//check if the user_uuid exists in v_extension_users
						$sql = "select count(*) from v_extension_users ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and user_uuid = :user_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['user_uuid'] = $user_uuid;
						$database = new database;
						$num_rows = $database->select($sql, $parameters, 'column');
						unset($sql, $parameters);

					//assign the extension to the user
						if ($num_rows == 0) {
							//build insert array
								$extension_user_uuid = uuid();
								$array['extension_users'][$x]['extension_user_uuid'] = $extension_user_uuid;
								$array['extension_users'][$x]['domain_uuid'] = $domain_uuid;
								$array['extension_users'][$x]['extension_uuid'] = $extension_uuid;
								$array['extension_users'][$x]['user_uuid'] = $row["user_uuid"];
							//grant temporary permissions
								$p = new permissions;
								$p->add('extension_user_add', 'temp');
							//execute insert
								$database = new database;
								$database->app_name = 'function-add_extension_user';
								$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
								$database->save($array);
								unset($array);
							//revoke temporary permissions
								$p->delete('extension_user_add', 'temp');
						}
				}
		}
	}

	if (!function_exists('user_add')) {
		function user_add($username, $password, $user_email = '') {
			global $domain_uuid;
			if (strlen($username) == 0) { return false; }
			if (strlen($password) == 0) { return false; }
			if (!username_exists($username)) {
				//build user insert array
					$user_uuid = uuid();
					$salt = generate_password('20', '4');
					$array['users'][0]['user_uuid'] = $user_uuid;
					$array['users'][0]['domain_uuid'] = $domain_uuid;
					$array['users'][0]['username'] = $username;
					$array['users'][0]['password'] = md5($salt.$password);
					$array['users'][0]['salt'] = $salt;
					if (valid_email($user_email)) {
						$array['users'][0]['user_email'] = $user_email;
					}
					$array['users'][0]['add_date'] = 'now()';
					$array['users'][0]['add_user'] = $_SESSION["username"];

				//build user group insert array
					$user_group_uuid = uuid();
					$array['user_groups'][0]['user_group_uuid'] = $user_group_uuid;
					$array['user_groups'][0]['domain_uuid'] = $domain_uuid;
					$array['user_groups'][0]['group_name'] = 'user';
					$array['user_groups'][0]['user_uuid'] = $user_uuid;

				//grant temporary permissions
					$p = new permissions;
					$p->add('user_add', 'temp');
					$p->add('user_group_add', 'temp');
				//execute insert
					$database = new database;
					$database->app_name = 'function-user_add';
					$database->app_uuid = '15a8d74b-ac7e-4468-add4-3e6ebdcb8e22';
					$database->save($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('user_add', 'temp');
					$p->delete('user_group_add', 'temp');
			}
		}
	}

function switch_module_is_running($fp, $mod) {
	if (!$fp) {
		//if the handle does not exist create it
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		//if the handle still does not exist show an error message
			if (!$fp) {
				$msg = "<div align='center'>Connection to Event Socket failed.<br /></div>";
			}
	}
	if ($fp) {
		//send the api command to check if the module exists
		$switchcmd = "module_exists $mod";
		$switch_result = event_socket_request($fp, 'api '.$switchcmd);
		unset($switchcmd);
		if (trim($switch_result) == "true") {
			return true;
		}
		else {
			return false;
		}
	}
	else {
		return false;
	}
}
//switch_module_is_running('mod_spidermonkey');

//format a number (n) replace with a number (r) remove the number
function format_string ($format, $data) {
	$x=0;
	$tmp = '';
	for ($i = 0; $i <= strlen($format); $i++) {
		$tmp_format = strtolower(substr($format, $i, 1));
		if ($tmp_format == 'x') {
			$tmp .= substr($data, $x, 1);
			$x++;
		}
		elseif ($tmp_format == 'r') {
			$x++;
		}
		else {
			$tmp .= $tmp_format;
		}
	}
	return $tmp;
}

//get the format and use it to format the phone number
	function format_phone($phone_number) {
		if (is_numeric(trim($phone_number, ' +'))) {
			if (isset($_SESSION["format"]["phone"])) {
				$phone_number = trim($phone_number, ' +');
				foreach ($_SESSION["format"]["phone"] as &$format) {
					$format_count = substr_count($format, 'x');
					$format_count = $format_count + substr_count($format, 'R');
					$format_count = $format_count + substr_count($format, 'r');
					if ($format_count == strlen($phone_number)) {
						//format the number
						$phone_number = format_string($format, $phone_number);
					}
				}
			}
		}
		return $phone_number;
	}

//format seconds into hh:mm:ss
	function format_hours($seconds) {
		$hours = floor($seconds / 3600);
		$minutes = floor(($seconds / 60) % 60);
		$seconds = $seconds % 60;
		if (strlen($minutes) == 1) { $minutes = '0'.$minutes; }
		if (strlen($seconds) == 1) { $seconds = '0'.$seconds; }
		return "$hours:$minutes:$seconds";
	}

//browser detection without browscap.ini dependency
	function http_user_agent($info = '') {

		//set default values
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
			$browser_name = 'Unknown';
			$platform = 'Unknown';
			$version = '';
			$mobile = false;

		//get the platform
			if (preg_match('/linux/i', $user_agent)) {
				$platform = 'Linux';
			}
			elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
				$platform = 'Apple';
			}
			elseif (preg_match('/windows|win32/i', $user_agent)) {
				$platform = 'Windows';
			}

		//set mobile to true or false
			if (preg_match('/mobile/i', $user_agent)) {
				$platform = 'Mobile';
				$mobile = true;
			}
			elseif (preg_match('/android/i', $user_agent)) {
				$platform = 'Android';
				$mobile = true;
			}

		//get the name of the useragent
			if (preg_match('/MSIE/i',$user_agent) || preg_match('/Trident/i',$user_agent)) {
				$browser_name = 'Internet Explorer';
				$browser_name_short = 'MSIE';
			}
			elseif (preg_match('/Firefox/i',$user_agent)) {
				$browser_name = 'Mozilla Firefox';
				$browser_name_short = 'Firefox';
			}
			elseif (preg_match('/Chrome/i',$user_agent)) {
				$browser_name = 'Google Chrome';
				$browser_name_short = 'Chrome';
			}
			elseif (preg_match('/Safari/i',$user_agent)) {
				$browser_name = 'Apple Safari';
				$browser_name_short = 'Safari';
			}
			elseif (preg_match('/Opera/i',$user_agent)) {
				$browser_name = 'Opera';
				$browser_name_short = 'Opera';
			}
			elseif (preg_match('/Netscape/i',$user_agent)) {
				$browser_name = 'Netscape';
				$browser_name_short = 'Netscape';
			}
			else {
				$browser_name = 'Unknown';
				$browser_name_short = 'Unknown';
			}

		//finally get the correct version number
			$known = array('Version', $browser_name_short, 'other');
			$pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
			if (!preg_match_all($pattern, $user_agent, $matches)) {
				//we have no matching number just continue
			}

		//see how many we have
			$i = count($matches['browser']);
			if ($i != 1) {
				//we will have two since we are not using 'other' argument yet
				//see if version is before or after the name
				if (strripos($user_agent,"Version") < strripos($user_agent,$browser_name_short)) {
					$version= $matches['version'][0];
				}
				else {
					$version= $matches['version'][1];
				}
			}
			else {
				$version= $matches['version'][0];
			}

		//check if we have a number
			if ($version == null || $version == "") { $version = "?"; }

		//return the data
			switch ($info) {
				case "agent": return $user_agent; break;
				case "name": return $browser_name; break;
				case "name_short": return $browser_name_short; break;
				case "version": return $version; break;
				case "platform": return $platform; break;
				case "mobile": return $mobile; break;
				case "pattern": return $pattern; break;
				default :
					return array(
						'user_agent' => $user_agent,
						'name' => $browser_name,
						'name_short' => $browser_name_short,
						'version' => $version,
						'platform' => $platform,
						'mobile' => $mobile,
						'pattern' => $pattern
					);
			}
	}

//tail php function for non posix systems
	function tail($file, $num_to_get=10) {
		$fp = fopen($file, 'r');
		$position = filesize($file);
		$chunklen = 4096;
		if($position-$chunklen<=0) {
			fseek($fp,0);
		}
		else {
			fseek($fp, $position-$chunklen);
		}
		$data="";$ret="";$lc=0;
		while($chunklen > 0) {
			$data = fread($fp, $chunklen);
			$dl=strlen($data);
			for($i=$dl-1;$i>=0;$i--){
				if($data[$i]=="\n"){
					if($lc==0 && $ret!="")$lc++;
					$lc++;
					if($lc>$num_to_get)return $ret;
				}
				$ret=$data[$i].$ret;
			}
			if($position-$chunklen<=0){
				fseek($fp,0);
				$chunklen=$chunklen-abs($position-$chunklen);
			}
			else {
				fseek($fp, $position-$chunklen);
			}
			$position = $position - $chunklen;
		}
		fclose($fp);
		return $ret;
	}

//generate a random password with upper, lowercase and symbols
	function generate_password($length = 0, $strength = 0) {
		$password = '';
		$chars = '';
		if ($length === 0 && $strength === 0) { //set length and strenth if specified in default settings and strength isn't numeric-only
			$length = (is_numeric($_SESSION["users"]["password_length"]["numeric"])) ? $_SESSION["users"]["password_length"]["numeric"] : 20;
			$strength = (is_numeric($_SESSION["users"]["password_strength"]["numeric"])) ? $_SESSION["users"]["password_strength"]["numeric"] : 4;
		}
		if ($strength >= 1) { $chars .= "0123456789"; }
		if ($strength >= 2) { $chars .= "abcdefghijkmnopqrstuvwxyz"; }
		if ($strength >= 3) { $chars .= "ABCDEFGHIJKLMNPQRSTUVWXYZ"; }
		if ($strength >= 4) { $chars .= "!^$%*?."; }
		for ($i = 0; $i < $length; $i++) {
			$password .= $chars[random_int(0, strlen($chars)-1)];
		}
		return $password;
	}

//check password strength against requirements (if any)
	function check_password_strength($password, $text, $type = 'default') {
		if ($password != '') {
			if ($type == 'default') {
				$req['length'] = $_SESSION['extension']['password_length']['numeric'];
				$req['number'] = ($_SESSION['extension']['password_number']['boolean'] == 'true') ? true : false;
				$req['lowercase'] = ($_SESSION['extension']['password_lowercase']['boolean'] == 'true') ? true : false;
				$req['uppercase'] = ($_SESSION['extension']['password_uppercase']['boolean'] == 'true') ? true : false;
				$req['special'] = ($_SESSION['extension']['password_special']['boolean'] == 'true') ? true : false;
			} elseif ($type == 'user') {
				$req['length'] = $_SESSION['user']['password_length']['numeric'];
				$req['number'] = ($_SESSION['user']['password_number']['boolean'] == 'true') ? true : false;
				$req['lowercase'] = ($_SESSION['user']['password_lowercase']['boolean'] == 'true') ? true : false;
				$req['uppercase'] = ($_SESSION['user']['password_uppercase']['boolean'] == 'true') ? true : false;
				$req['special'] = ($_SESSION['user']['password_special']['boolean'] == 'true') ? true : false;
			}
			if (is_numeric($req['length']) && $req['length'] != 0 && !preg_match_all('$\S*(?=\S{'.$req['length'].',})\S*$', $password)) { // length
				$msg_errors[] = $req['length'].'+ '.$text['label-characters'];
			}
			if ($req['number'] && !preg_match_all('$\S*(?=\S*[\d])\S*$', $password)) { //number
				$msg_errors[] = '1+ '.$text['label-numbers'];
			}
			if ($req['lowercase'] && !preg_match_all('$\S*(?=\S*[a-z])\S*$', $password)) { //lowercase
				$msg_errors[] = '1+ '.$text['label-lowercase_letters'];
			}
			if ($req['uppercase'] && !preg_match_all('$\S*(?=\S*[A-Z])\S*$', $password)) { //uppercase
				$msg_errors[] = '1+ '.$text['label-uppercase_letters'];
			}
			if ($req['special'] && !preg_match_all('$\S*(?=\S*[\W])\S*$', $password)) { //special
				$msg_errors[] = '1+ '.$text['label-special_characters'];
			}
			if (is_array($msg_errors) && sizeof($msg_errors) > 0) {
				message::add($_SESSION["message"] = $text['message-password_requirements'].': '.implode(', ', $msg_errors), 'negative', 6000);
				return false;
			}
			else {
				return true;
			}
		}
		return true;
	}

//based on Wez Furlong do_post_request
	if (!function_exists('send_http_request')) {
		function send_http_request($url, $data, $method = "POST", $optional_headers = null) {
			$params = array('http' => array(
						'method' => $method,
						'content' => $data
						));
			if ($optional_headers !== null) {
				$params['http']['header'] = $optional_headers;
			}
			$ctx = stream_context_create($params);
			$fp = @fopen($url, 'rb', false, $ctx);
			if (!$fp) {
				throw new Exception("Problem with $url, $php_errormsg");
			}
			$response = @stream_get_contents($fp);
			if ($response === false) {
				throw new Exception("Problem reading data from $url, $php_errormsg");
			}
			return $response;
		}
	}

//convert the string to a named array
	if(!function_exists('csv_to_named_array')) {
		function csv_to_named_array($tmp_str, $tmp_delimiter) {
			$tmp_array = explode ("\n", $tmp_str);
			$result = array();
			if (trim(strtoupper($tmp_array[0])) !== "+OK") {
				$tmp_field_name_array = explode ($tmp_delimiter, $tmp_array[0]);
				$x = 0;
				foreach ($tmp_array as $row) {
					if ($x > 0) {
						$tmp_field_value_array = explode ($tmp_delimiter, $tmp_array[$x]);
						$y = 0;
						foreach ($tmp_field_value_array as $tmp_value) {
							$tmp_name = $tmp_field_name_array[$y];
							if (trim(strtoupper($tmp_value)) !== "+OK") {
								$result[$x][$tmp_name] = $tmp_value;
							}
							$y++;
						}
					}
					$x++;
				}
				unset($row);
			}
			return $result;
		}
	}

function get_time_zone_offset($remote_tz, $origin_tz = 'UTC') {
	$origin_dtz = new DateTimeZone($origin_tz);
	$remote_dtz = new DateTimeZone($remote_tz);
	$origin_dt = new DateTime("now", $origin_dtz);
	$remote_dt = new DateTime("now", $remote_dtz);
	$offset = $remote_dtz->getOffset($remote_dt) - $origin_dtz->getOffset($origin_dt);
	return $offset;
}

function number_pad($number,$n) {
	return str_pad((int) $number,$n,"0",STR_PAD_LEFT);
}

// validate email address syntax
	if(!function_exists('valid_email')) {
		function valid_email($email) {
			$regex = '/^[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+(\.[A-z0-9]{2,7})?$/';
			if ($email != "" && preg_match($regex, $email) == 1) {
				return true; // email address has valid syntax
			}
			else {
				return false; // email address does not have valid syntax
			}
		}
	}

//function to convert hexidecimal color value to rgb string/array value
	if (!function_exists('hex_to_rgb')) {
		function hex_to_rgb($hex, $delim = '') {
			$hex = str_replace("#", "", $hex);

			if (strlen($hex) == 3) {
				$r = hexdec(substr($hex,0,1).substr($hex,0,1));
				$g = hexdec(substr($hex,1,1).substr($hex,1,1));
				$b = hexdec(substr($hex,2,1).substr($hex,2,1));
			}
			else {
				$r = hexdec(substr($hex,0,2));
				$g = hexdec(substr($hex,2,2));
				$b = hexdec(substr($hex,4,2));
			}
			$rgb = array($r, $g, $b);

			if ($delim != '') {
				return implode($delim, $rgb); // return rgb delimited string
			}
			else {
				return $rgb; // return array of rgb values
			}
		}
	}

//function to get a color's luminence level -- dependencies: rgb_to_hsl()
	if (!function_exists('get_color_luminence')) {
		function get_color_luminence($color) {
			//convert hex to rgb
			if (substr_count($color, ',') == 0) {
				$color = str_replace(' ', '', $color);
				$color = str_replace('#', '', $color);
				if (strlen($color) == 3) {
					$r = hexdec(substr($color,0,1).substr($color,0,1));
					$g = hexdec(substr($color,1,1).substr($color,1,1));
					$b = hexdec(substr($color,2,1).substr($color,2,1));
				}
				else {
					$r = hexdec(substr($color,0,2));
					$g = hexdec(substr($color,2,2));
					$b = hexdec(substr($color,4,2));
				}
				$color = $r.','.$g.','.$b;
			}

			//color to array, pop alpha
			if (substr_count($color, ',') > 0) {
				$color = str_replace(' ', '', $color);
				$color = str_replace('rgb', '', $color);
				$color = str_replace('a(', '', $color);
				$color = str_replace(')', '', $color);
				$color = explode(',', $color);
				$hsl = rgb_to_hsl($color[0], $color[1], $color[2]);
			}

			//return luminence value
			return (is_array($hsl) && is_numeric($hsl[2])) ? $hsl[2] : null;
		}
	}

//function to lighten or darken a hexidecimal, rgb, or rgba color value by a percentage -- dependencies: rgb_to_hsl(), hsl_to_rgb()
	if (!function_exists('color_adjust')) {
		function color_adjust($color, $percent) {
			/*
			USAGE
				20% Lighter
					color_adjust('#3f4265', 0.2);
					color_adjust('234,120,6,0.3', 0.2);
				20% Darker
					color_adjust('#3f4265', -0.2); //
					color_adjust('rgba(234,120,6,0.3)', -0.2);
			RETURNS
				Same color format provided (hex in = hex out, rgb(a) in = rgb(a) out)
			*/

			//convert hex to rgb
			if (substr_count($color, ',') == 0) {
				$color = str_replace(' ', '', $color);
				if (substr_count($color, '#') > 0) {
					$color = str_replace('#', '', $color);
					$hash = '#';
				}
				if (strlen($color) == 3) {
					$r = hexdec(substr($color,0,1).substr($color,0,1));
					$g = hexdec(substr($color,1,1).substr($color,1,1));
					$b = hexdec(substr($color,2,1).substr($color,2,1));
				}
				else {
					$r = hexdec(substr($color,0,2));
					$g = hexdec(substr($color,2,2));
					$b = hexdec(substr($color,4,2));
				}
				$color = $r.','.$g.','.$b;
			}

			//color to array, pop alpha
			if (substr_count($color, ',') > 0) {
				$color = str_replace(' ', '', $color);
				$wrapper = false;
				if (substr_count($color, 'rgb') != 0) {
					$color = str_replace('rgb', '', $color);
					$color = str_replace('a(', '', $color);
					$color = str_replace(')', '', $color);
					$wrapper = true;
				}
				$colors = explode(',', $color);
				$alpha = (sizeof($colors) == 4) ? array_pop($colors) : null;
				$color = $colors;
				unset($colors);

				//adjust color using rgb > hsl > rgb conversion
				$hsl = rgb_to_hsl($color[0], $color[1], $color[2]);
				$hsl[2] = $hsl[2] + $percent;
				$color = hsl_to_rgb($hsl[0], $hsl[1], $hsl[2]);

				//return adjusted color in format received
				if (isset($hash) && $hash == '#') { //hex
					for ($i = 0; $i <= 2; $i++) {
						$hex_color = dechex($color[$i]);
						if (strlen($hex_color) == 1) { $hex_color = '0'.$hex_color; }
						$hex .= $hex_color;
					}
					return $hash.$hex;
				}
				else { //rgb(a)
					$rgb = implode(',', $color);
					if ($alpha != '') { $rgb .= ','.$alpha; $a = 'a'; }
					if ($wrapper) { $rgb = 'rgb'.$a.'('.$rgb.')'; }
					return $rgb;
				}
			}

			return $color;
		}
	}

//function to convert an rgb color array to an hsl color array
	if (!function_exists('rgb_to_hsl')) {
		function rgb_to_hsl($r, $g, $b) {
			$r /= 255;
			$g /= 255;
			$b /= 255;

			$max = max($r, $g, $b);
			$min = min($r, $g, $b);

			$h;
			$s;
			$l = ($max + $min) / 2;
			$d = $max - $min;

			if ($d == 0) {
				$h = $s = 0; // achromatic
			}
			else {
				$s = $d / (1 - abs((2 * $l) - 1));
				switch($max){
					case $r:
						$h = 60 * fmod((($g - $b) / $d), 6);
						if ($b > $g) { $h += 360; }
						break;
					case $g:
						$h = 60 * (($b - $r) / $d + 2);
						break;
					case $b:
						$h = 60 * (($r - $g) / $d + 4);
						break;
				}
			}

			return array(round($h, 2), round($s, 2), round($l, 2));
		}
	}

//function to convert an hsl color array to an rgb color array
	if (!function_exists('hsl_to_rgb')) {
		function hsl_to_rgb($h, $s, $l){
			$r;
			$g;
			$b;

			$c = (1 - abs((2 * $l) - 1)) * $s;
			$x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
			$m = $l - ($c / 2);

			if ($h < 60) {
				$r = $c;
				$g = $x;
				$b = 0;
			}
			else if ($h < 120) {
				$r = $x;
				$g = $c;
				$b = 0;
			}
			else if ($h < 180) {
				$r = 0;
				$g = $c;
				$b = $x;
			}
			else if ($h < 240) {
				$r = 0;
				$g = $x;
				$b = $c;
			}
			else if ($h < 300) {
				$r = $x;
				$g = 0;
				$b = $c;
			}
			else {
				$r = $c;
				$g = 0;
				$b = $x;
			}

			$r = ($r + $m) * 255;
			$g = ($g + $m) * 255;
			$b = ($b + $m) * 255;

			if ($r > 255) { $r = 255; }
			if ($g > 255) { $g = 255; }
			if ($b > 255) { $b = 255; }

			if ($r < 0) { $r = 0; }
			if ($g < 0) { $g = 0; }
			if ($b < 0) { $b = 0; }

			return array(floor($r), floor($g), floor($b));
		}
	}

//function to send email
	if (!function_exists('send_email')) {
		function send_email($eml_recipients, $eml_subject, $eml_body, &$eml_error = '', $eml_from_address = '', $eml_from_name = '', $eml_priority = 3, $eml_debug_level = 0, $eml_attachments = '', $eml_read_confirmation = false) {
			/*
			RECIPIENTS NOTE:

				Pass in a single email address...

					user@domain.com

				Pass in a comma or semi-colon delimited string of e-mail addresses...

					user@domain.com,user2@domain2.com,user3@domain3.com
					user@domain.com;user2@domain2.com;user3@domain3.com

				Pass in a simple array of email addresses...

					Array (
						[0] => user@domain.com
						[1] => user2@domain2.com
						[2] => user3@domain3.com
					)

				Pass in a multi-dimentional array of addresses (delivery, address, name)...

					Array (
						[0] => Array (
							[delivery] => to
							[address] => user@domain.com
							[name] => user 1
							)
						[1] => Array (
							[delivery] => cc
							[address] => user2@domain2.com
							[name] => user 2
							)
						[2] => Array (
							[delivery] => bcc
							[address] => user3@domain3.com
							[name] => user 3
							)
					)

			ATTACHMENTS NOTE:

				Pass in as many files as necessary in an array in the following format...

					Array (
						[0] => Array (
							[type] => file (or 'path')
							[name] => filename.ext
							[value] => /folder/filename.ext
							)
						[1] => Array (
							[type] => string
							[name] => filename.ext
							[value] => (string of file contents - if base64, will be decoded automatically)
							)
					)

			ERROR RESPONSE:

				Error messages are stored in the variable passed into $eml_error BY REFERENCE

			*/

			try {
				//include the phpmailer classes
				include_once("resources/phpmailer/class.phpmailer.php");
				include_once("resources/phpmailer/class.smtp.php");

				//regular expression to validate email addresses
				$regexp = '/^[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,7}$/';

				//create the email object and set general settings
				$mail = new PHPMailer();
				$mail->IsSMTP();
				if ($_SESSION['email']['smtp_hostname']['text'] != '') {
					$mail->Hostname = $_SESSION['email']['smtp_hostname']['text'];
				}
				$mail->Host = $_SESSION['email']['smtp_host']['text'];
				if (is_numeric($_SESSION['email']['smtp_port']['numeric'])) {
					$mail->Port = $_SESSION['email']['smtp_port']['numeric'];
				}
				if ($_SESSION['email']['smtp_auth']['text'] == "true") {
					$mail->SMTPAuth = $_SESSION['email']['smtp_auth']['text'];
					$mail->Username = $_SESSION['email']['smtp_username']['text'];
					$mail->Password = $_SESSION['email']['smtp_password']['text'];
				}
				else {
					$mail->SMTPAuth = 'false';
				}
				if ($_SESSION['email']['smtp_secure']['text'] == "none") {
					$_SESSION['email']['smtp_secure']['text'] = '';
				}
				if ($_SESSION['email']['smtp_secure']['text'] != '') {
					$mail->SMTPSecure = $_SESSION['email']['smtp_secure']['text'];
				}
				if (isset($_SESSION['email']['smtp_validate_certificate']) && $_SESSION['email']['smtp_validate_certificate']['boolean'] == "false") {
					// bypass TLS certificate check e.g. for self-signed certificates
					$mail->SMTPOptions = array(
						'ssl' => array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
						)
					);
				}
				$eml_from_address = ($eml_from_address != '') ? $eml_from_address : $_SESSION['email']['smtp_from']['text'];
				$eml_from_name = ($eml_from_name != '') ? $eml_from_name : $_SESSION['email']['smtp_from_name']['text'];
				$mail->SetFrom($eml_from_address, $eml_from_name);
				$mail->AddReplyTo($eml_from_address, $eml_from_name);
				$mail->Subject = $eml_subject;
				$mail->MsgHTML($eml_body);
				$mail->Priority = $eml_priority;
				if ($eml_read_confirmation) {
					$mail->AddCustomHeader('X-Confirm-Reading-To: '.$eml_from_address);
					$mail->AddCustomHeader('Return-Receipt-To: '.$eml_from_address);
					$mail->AddCustomHeader('Disposition-Notification-To: '.$eml_from_address);
				}
				if (is_numeric($eml_debug_level) && $eml_debug_level > 0) {
					$mail->SMTPDebug = $eml_debug_level;
				}

				//add the email recipients
				$address_found = false;
				if (!is_array($eml_recipients)) { // must be a single or delimited recipient address(s)
					$eml_recipients = str_replace(' ', '', $eml_recipients);
					$eml_recipients = str_replace(array(';',','), ' ', $eml_recipients);
					$eml_recipients = explode(' ', $eml_recipients); // convert to array of addresses
				}
				foreach ($eml_recipients as $eml_recipient) {
					if (is_array($eml_recipient)) { // check if each recipient has multiple fields
						if ($eml_recipient["address"] != '' && preg_match($regexp, $eml_recipient["address"]) == 1) { // check if valid address
							switch ($eml_recipient["delivery"]) {
								case "cc" :		$mail->AddCC($eml_recipient["address"], ($eml_recipient["name"]) ? $eml_recipient["name"] : $eml_recipient["address"]);			break;
								case "bcc" :	$mail->AddBCC($eml_recipient["address"], ($eml_recipient["name"]) ? $eml_recipient["name"] : $eml_recipient["address"]);			break;
								default :		$mail->AddAddress($eml_recipient["address"], ($eml_recipient["name"]) ? $eml_recipient["name"] : $eml_recipient["address"]);
							}
							$address_found = true;
						}
					}
					else if ($eml_recipient != '' && preg_match($regexp, $eml_recipient) == 1) { // check if recipient value is simply (only) an address
						$mail->AddAddress($eml_recipient);
						$address_found = true;
					}
				}

				if (!$address_found) {
					$eml_error = "No valid e-mail address provided.";
					return false;
				}

				//add email attachments
				if (is_array($eml_attachments) && sizeof($eml_attachments) > 0) {
					foreach ($eml_attachments as $attachment) {
						//set the name of the file
						$attachment['name'] = $attachment['name'] != '' ? $attachment['name'] : basename($attachment['value']);

						//set the mime type
						switch (substr($attachment['name'], -4)) {
							case ".png":
								$attachment['mime_type'] = 'image/png';
								break;
							case ".pdf":
								$attachment['mime_type'] = 'application/pdf';
								break;
							case ".mp3":
								$attachment['mime_type'] = 'audio/mpeg';
								break;
							case ".wav":
								$attachment['mime_type'] = 'audio/x-wav';
								break;
							case ".opus":
								$attachment['mime_type'] = 'audio/opus';
								break;
							case ".ogg":
								$attachment['mime_type'] = 'audio/ogg';
								break;
						}

						//add the attachments
						if ($attachment['type'] == 'file' || $attachment['type'] == 'path') {
							$mail->AddAttachment($attachment['value'], $attachment['name'], 'base64', $attachment['mime_type']);
						}
						else if ($attachment['type'] == 'string') {
							if (base64_encode(base64_decode($attachment['value'], true)) === $attachment['value']) {
								$mail->AddStringAttachment(base64_decode($attachment['value']), $attachment['name'], 'base64', $attachment['mime_type']);
							}
							else {
								$mail->AddStringAttachment($attachment['value'], $attachment['name'], 'base64', $attachment['mime_type']);
							}
						}
					}
				}

				//send the email
				$mail->Send();
				$mail->ClearAddresses();
				$mail->SmtpClose();
				unset($mail);
				return true;

			}
			catch (Exception $e) {
				$eml_error = $mail->ErrorInfo;
				return false;
			}

		}
	}

//encrypt a string
	if (!function_exists('encrypt')) {
		function encrypt($key, $data) {
			$encryption_key = base64_decode($key);
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
			$encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
			return base64_encode($encrypted.'::'.$iv);
		}
	}

//decrypt a string
	if (!function_exists('decrypt')) {
		function decrypt($key, $data) {
			$encryption_key = base64_decode($key);
			list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
			return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
		}
	}

//json detection
	if (!function_exists('is_json')) {
		function is_json($str) {
			return (is_string($str) && is_object(json_decode($str))) ? true : false;
		}
	}

//mac detection
	if (!function_exists('is_mac')) {
		function is_mac($str) {
			return (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $str) == 1) ? true : false;
		}
	}

//detect if php is running as command line interface
	if (!function_exists('is_cli')) {
		function is_cli() {
			if (defined('STDIN')) {
				return true;
			}
			if (php_sapi_name() == 'cli' && !isset($_SERVER['HTTP_USER_AGENT']) && is_numeric($_SERVER['argc'])) {
				return true;
			}
			return false;
		}
	}

//format mac address
	if (!function_exists('format_mac')) {
		function format_mac($str, $delim = '-', $case = 'lower') {
			if (is_mac($str)) {
				$str = join($delim, str_split($str, 2));
				$str = ($case == 'upper') ? strtoupper($str) : strtolower($str);
			}
			return $str;
		}
	}

//transparent gif
	if (!function_exists('img_spacer')) {
		function img_spacer($width = '1px', $height = '1px', $custom = null) {
			return "<img src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' style='width: ".$width."; height: ".$height."; ".$custom."'>";
		}
	}

//lower case
	function lower_case($string) {
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($string, 'UTF-8');
		}
		else {
			return strtolower($string);
		}
	}

//upper case
	function upper_case($string) {
		if (function_exists('mb_strtoupper')) {
			return mb_strtoupper($string, 'UTF-8');
		}
		else {
			return strtoupper($string);
		}
	}

//email validate
	if (!function_exists('email_validate')) {
		function email_validate($strEmail){
			$validRegExp =  '/^[a-zA-Z0-9\._-]+@[a-zA-Z0-9\._-]+\.[a-zA-Z]{2,3}$/';
			// search email text for regular exp matches
			preg_match($validRegExp, $strEmail, $matches, PREG_OFFSET_CAPTURE);
			if (count($matches) == 0) {
				return 0;
			}
			else {
				return 1;
			}
		}
	}

//write javascript function that detects select key combinations to perform designated actions
	if (!function_exists('key_press')) {
		function key_press($key, $direction = 'up', $subject = 'document', $exceptions = array(), $prompt = null, $action = null, $script_wrapper = true) {
			//determine key code
				switch (strtolower($key)) {
					case 'escape':
						$key_code = '(e.which == 27)';
						break;
					case 'delete':
						$key_code = '(e.which == 46)';
						break;
					case 'enter':
						$key_code = '(e.which == 13)';
						break;
					case 'backspace':
						$key_code = '(e.which == 8)';
						break;
					case 'space':
						$key_code = '(e.which == 32)';
						break;
					case 'ctrl+s':
						$key_code = '(((e.which == 115 || e.which == 83) && (e.ctrlKey || e.metaKey)) || (e.which == 19))';
						break;
					case 'ctrl+q':
						$key_code = '(((e.which == 113 || e.which == 81) && (e.ctrlKey || e.metaKey)) || (e.which == 19))';
						break;
					case 'ctrl+a':
						$key_code = '(((e.which == 97 || e.which == 65) && (e.ctrlKey || e.metaKey)) || (e.which == 19))';
						break;
					case 'ctrl+c':
						$key_code = '(((e.which == 99 || e.which == 67) && (e.ctrlKey || e.metaKey)) || (e.which == 19))';
						break;
					case 'ctrl+enter':
						$key_code = '(((e.which == 13 || e.which == 10) && (e.ctrlKey || e.metaKey)) || (e.which == 19))';
						break;
					default:
						return;
				}
			//filter direction
				switch ($direction) {
					case 'down': $direction = 'keydown'; break;
					case 'press': $direction = 'keypress'; break;
					case 'up': $direction = 'keyup'; break;
				}
			//check for element exceptions
				if (is_array($exceptions)) {
					if (sizeof($exceptions) > 0) {
						$exceptions = "!$(e.target).is('".implode(',', $exceptions)."') && ";
					}
				}
			//quote if selector is id or class
				$subject = ($subject != 'window' && $subject != 'document') ? "'".$subject."'" : $subject;
			//output script
				echo "\n\n\n";
				if ($script_wrapper) {
					echo "<script language='JavaScript' type='text/javascript'>\n";
				}
				echo "	$(".$subject.").on('".$direction."', function(e) {\n";
				echo "		if (".$exceptions.$key_code.") {\n";
				if ($prompt != '') {
					$action = ($action != '') ? $action : "alert('".$key."');";
					echo "			if (confirm('".$prompt."')) {\n";
					echo "				e.preventDefault();\n";
					echo "				".$action."\n";
					echo "			}\n";
				}
				else {
					echo "			e.preventDefault();\n";
					echo "			".$action."\n";
				}
				echo "		}\n";
				echo "	});\n";
				if ($script_wrapper) {
					echo "</script>\n";
				}
				echo "\n\n\n";
		}
	}

//format border radius values
	if (!function_exists('format_border_radius')) {
		function format_border_radius($radius_value, $default = 5) {
			$radius_value = ($radius_value != '') ? $radius_value : $default;
			$br_a = explode(' ', $radius_value);
			foreach ($br_a as $index => $br) {
				if (substr_count($br, '%') > 0) {
					$br_b[$index]['number'] = str_replace('%', '', $br);
					$br_b[$index]['unit'] = '%';
				}
				else {
					$br_b[$index]['number'] = str_replace('px', '', strtolower($br));
					$br_b[$index]['unit'] = 'px';
				}
			}
			unset($br_a, $br);
			if (sizeof($br_b) == 4) {
				$br['tl']['n'] = $br_b[0]['number'];
				$br['tr']['n'] = $br_b[1]['number'];
				$br['br']['n'] = $br_b[2]['number'];
				$br['bl']['n'] = $br_b[3]['number'];
				$br['tl']['u'] = $br_b[0]['unit'];
				$br['tr']['u'] = $br_b[1]['unit'];
				$br['br']['u'] = $br_b[2]['unit'];
				$br['bl']['u'] = $br_b[3]['unit'];
			}
			else if (sizeof($br_b) == 2) {
				$br['tl']['n'] = $br_b[0]['number'];
				$br['tr']['n'] = $br_b[0]['number'];
				$br['br']['n'] = $br_b[1]['number'];
				$br['bl']['n'] = $br_b[1]['number'];
				$br['tl']['u'] = $br_b[0]['unit'];
				$br['tr']['u'] = $br_b[0]['unit'];
				$br['br']['u'] = $br_b[1]['unit'];
				$br['bl']['u'] = $br_b[1]['unit'];
			}
			else {
				$br['tl']['n'] = $br_b[0]['number'];
				$br['tr']['n'] = $br_b[0]['number'];
				$br['br']['n'] = $br_b[0]['number'];
				$br['bl']['n'] = $br_b[0]['number'];
				$br['tl']['u'] = $br_b[0]['unit'];
				$br['tr']['u'] = $br_b[0]['unit'];
				$br['br']['u'] = $br_b[0]['unit'];
				$br['bl']['u'] = $br_b[0]['unit'];
			}
			unset($br_b);

			return $br; //array
		}
	}

//converts a string to a regular expression
	if (!function_exists('string_to_regex')) {
		function string_to_regex($string, $prefix='') {
			$original_string = $string;
			//escape the plus
				if (substr($string, 0, 1) == "+") {
					$string = "^\\+(".substr($string, 1).")$";
				}
			//add prefix
				if (strlen($prefix) > 0) {
					if (strlen($prefix) > 0 && strlen($prefix) < 4) {
						$plus = (substr($string, 0, 1) == "+") ? '' : '\+?';
						$prefix = $plus.$prefix.'?';
					}
					else {
						$prefix = '(?:'.$prefix.')?';
					}
				}
			//convert N,X,Z syntax to regex
				if (preg_match('/^[NnXxZz]+$/', $original_string)) {
					$string = str_ireplace("N", "[2-9]", $string);
					$string = str_ireplace("X", "[0-9]", $string);
					$string = str_ireplace("Z", "[1-9]", $string);
				}
			//add ^ to the start of the string if missing
				if (substr($string, 0, 1) != "^") {
					$string = "^".$string;
				}
			//add $ to the end of the string if missing
				if (substr($string, -1) != "$") {
					$string = $string."$";
				}
			//add the round brackets
				if (!strstr($string, '(')) {
					if (strstr($string, '^')) {
						$string = str_replace("^", "^".$prefix."(", $string);
					}
					else {
						$string = '^('.$string;
					}
					if (strstr($string, '$')) {
						$string = str_replace("$", ")$", $string);
					}
					else {
						$string = $string.')$';
					}
				}
			//return the result
				return $string;
		}
		//$string = "+12089068227"; echo $string." ".string_to_regex($string)."\n";
		//$string = "12089068227"; echo $string." ".string_to_regex($string)."\n";
		//$string = "2089068227"; echo $string." ".string_to_regex($string)."\n";
		//$string = "^(20890682[0-9][0-9])$"; echo $string." ".string_to_regex($string)."\n";
		//$string = "1208906xxxx"; echo $string." ".string_to_regex($string)."\n";
		//$string = "nxxnxxxxxxx"; echo $string." ".string_to_regex($string)."\n";
		//$string = "208906xxxx"; echo $string." ".string_to_regex($string)."\n";
		//$string = "^(2089068227"; echo $string." ".string_to_regex($string)."\n";
		//$string = "^2089068227)"; echo $string." ".string_to_regex($string)."\n";
		//$string = "2089068227$"; echo $string." ".string_to_regex($string)."\n";
		//$string = "2089068227)$"; echo $string." ".string_to_regex($string)."\n";
	}

//dynamically load available web fonts
	if (!function_exists('get_available_fonts')) {
		function get_available_fonts($sort = 'alpha') {
			if ($_SESSION['theme']['font_source_key']['text'] != '') {
				if (!is_array($_SESSION['fonts_available']) || sizeof($_SESSION['fonts_available']) == 0) {
					/*
					sort options:
						alpha 		- alphabetically
						date 		- by date added (most recent font added or updated first)
						popularity 	- by popularity (most popular family first)
						style 		- by number of styles available (family with most styles first)
						trending 	- by families seeing growth in usage (family seeing the most growth first)
					*/
					$google_api_url = 'https://www.googleapis.com/webfonts/v1/webfonts?key='.$_SESSION['theme']['font_source_key']['text'].'&sort='.$sort;
					$response = file_get_contents($google_api_url);
					if ($response != '') {
						$data = json_decode($response, true);
						$items = $data['items'];
						foreach ($items as $item) {
							$fonts[] = $item['family'];
						}
						//echo "<pre>".print_r($font_list, true)."</pre>";
					}
					$_SESSION['fonts_available'] = $fonts;
					unset($fonts);
				}
				return (is_array($_SESSION['fonts_available']) && sizeof($_SESSION['fonts_available']) > 0) ? $_SESSION['fonts_available'] : array();
			}
			else {
				return false;
			}
		}
	}

//dynamically import web fonts (by reading static css file)
	if (!function_exists('import_fonts')) {
		function import_fonts($file_to_parse, $line_styles_begin = null) {
			/*
			This function reads the contents of $file_to_parse, beginning at $line_styles_begin (if set),
			and attempts to parse the specified google fonts used.  The assumption is that each curly brace
			will be on its own line, each CSS style (attribute: value;) will be on its own line, a single
			Google Fonts name will be used per selector, and that it will be surrounded by SINGLE quotes,
			as shown in the example below:

				.class_name {
					font-family: 'Google Font';
					font-weight: 300;
					font-style: italic;
					}

			If the CSS styles are formatted as described, the necessary @import string should be generated
			correctly.
			*/

			$file = file_get_contents($_SERVER["DOCUMENT_ROOT"].$file_to_parse);
			$lines = explode("\n", $file);

			$style_counter = 0;
			foreach ($lines as $line_number => $line) {
				if ($line_styles_begin != '' && $line_number < $line_styles_begin - 1) { continue; }
				if (substr_count($line, "{") > 0) {
					$style_lines[$style_counter]['begins'] = $line_number;
				}
				if (substr_count($line, "}") > 0) {
					$style_lines[$style_counter]['ends'] = $line_number;
					$style_counter++;
				}
			}
			//echo "\n\n".print_r($style_lines, true)."\n\n";

			if (is_array($style_lines) && sizeof($style_lines) > 0) {

				foreach ($style_lines as $index => $style_line) {
					for ($l = $style_line['begins']+1; $l < $style_line['ends']; $l++) {
						$tmp[] = $lines[$l];
					}
					$style_groups[] = $tmp;
					unset($tmp);
				}
				//echo "\n\n".print_r($style_groups, true)."\n\n";

				if (is_array($style_groups) && sizeof($style_groups) > 0) {

					foreach ($style_groups as $style_group_index => $style_group) {
						foreach ($style_group as $style_index => $style) {
							$tmp = explode(':', $style);
							$attribute = trim($tmp[0]);
							$value = trim(trim($tmp[1]),';');
							$style_array[$attribute] = $value;
						}
						$style_groups[$style_group_index] = $style_array;
						unset($style_array);
					}
					//echo "\n\n".print_r($style_groups, true)."\n\n";

					foreach ($style_groups as $style_group_index => $style_group) {
						$style_value = $style_group['font-family'];
						if (substr_count($style_value, "'") > 0) {
							//determine font
								$font_begin = strpos($style_value, "'")+1;
								$font_end = strpos($style_value, "'", $font_begin);
								$font_name = substr($style_value, $font_begin, $font_end - $font_begin);
							//determine modifiers
								$weight = (is_numeric($style_group['font-weight']) || strtolower($style_group['font-weight']) == 'bold') ? strtolower($style_group['font-weight']) : null;
								$italic = (strtolower($style_group['font-style']) == 'italic') ? 'italic' : null;
							//add font to array
								$fonts[$font_name][] = $weight.$italic;
						}
					}
					//echo "\n\n/*".print_r($fonts, true)."*/\n\n";

					if (is_array($fonts)) {
						foreach ($fonts as $font_name => $modifiers) {
							$modifiers = array_unique($modifiers);
							$import_font_string = str_replace(' ', '+', $font_name);
							if (is_array($modifiers) && sizeof($modifiers) > 0) {
								$import_font_string .= ':'.implode(',', $modifiers);
							}
							$import_fonts[] = $import_font_string;
						}
						//echo "\n\n/*".print_r($import_fonts, true)."*/\n\n";
						$import_string = "@import url(//fonts.googleapis.com/css?family=".implode('|', $import_fonts).");";
						echo $import_string."\n";
					}

				}

			}

		}
	}

//retrieve array of countries
	if (!function_exists('get_countries')) {
		function get_countries() {
			$sql = "select * from v_countries order by country asc";
			$database = new database;
			$result = $database->select($sql, null, 'all');
			unset($sql);

			return is_array($result) && @sizeof($result) != 0 ? $result : false;
		}
	}

//make directory with event socket
	function event_socket_mkdir($dir) {
		//connect to fs
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if (!$fp) {
				return false;
			}
		//send the mkdir command to freeswitch
			if ($fp) {
				//build and send the mkdir command to freeswitch
					$switch_cmd = "lua mkdir.lua ".escapeshellarg($dir);
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					fclose($fp);
				//check result
					if (trim($switch_result) == "-ERR no reply") {
						return true;
					}
			}
		//can not create directory
			return false;
	}

//escape user data
	function escape($string) {
		if (is_array($string)) {
			return false;
		}
		elseif (isset($string) && strlen($string)) {
			return htmlentities($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		else {
			return false;
		}
		//return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	}

//output pre-formatted array keys and values
	if (!function_exists('view_array')) {
		function view_array($array, $exit = true, $return = false) {
			$html = "<br><pre style='text-align: left;'>".print_r($array, true).'</pre><br>';
			if ($return) {
				return $html;
			}
			else {
				echo $html;
			}
			$exit and exit();
		}
	}

//format db date and/or time to local date and/or time
	if (!function_exists('format_when_local')) {
		function format_when_local($when, $format = 'dt', $include_seconds = false) {
			if ($when != '') {
				// determine when format
				if (substr_count($when, ' ') > 0) { // date and time
					$tmp = explode(' ', $when);
					$date = $tmp[0];
					$time = $tmp[1];
				}
				else if (substr_count($when, '-') > 0) { // date only
					$date = $when;
				}
				else if (substr_count($when, ':') > 0) { // time only
					$time = $when;
				}
				unset($when, $tmp);

				// format date
				if ($date != '') {
					$tmp = explode('-', $date);
					$date = $tmp[1].'-'.$tmp[2].'-'.$tmp[0];
				}

				// format time
				if ($time != '') {
					$tmp = explode(':', $time);
					if ($tmp[0] >= 0 && $tmp[0] <= 11) {
						$meridiem = 'AM';
						$hour = ($tmp[0] == 0) ? 12 : $tmp[0];
					}
					else {
						$meridiem = 'PM';
						$hour = ($tmp[0] > 12) ? ($tmp[0] - 12) : $tmp[0];
					}
					$minute = $tmp[1];
					$second = $tmp[2];
				}

				// structure requested time format
				$time = $hour.':'.$minute;
				if ($include_seconds) { $time .= ':'.$second; }
				$time .= ' '.$meridiem;

				$return['d'] = $date;
				$return['t'] = $time;
				$return['dt'] = $date.' '.$time;

				return $return[$format];
			}
			else {
				return false;
			}
		}
	}

//define email button (src: https://buttons.cm)
	if (!function_exists('email_button')) {
		function email_button($text = 'Click Here!', $link = URL, $bg_color = '#dddddd', $fg_color = '#000000', $radius = '') {

			// default button radius
			$radius = $radius != '' ? $radius : '3px';

			// retrieve single/first numeric radius value for ms arc
			$tmp = $radius;
			if (substr_count($radius, ' ') > 0) {
				$tmp = explode(' ', $radius);
				$tmp = $tmp[0];
			}
			$tmp = preg_replace("/[^0-9,.]/", '', $tmp); // remove non-numeric characters
			$arc = floor($tmp / 35 * 100); // calculate percentage

			// create button code
			$btn = "
				<div>
					<!--[if mso]>
					  <v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word' href='".$link."' style='height: 35px; v-text-anchor: middle; width: 140px;' arcsize='".$arc."%' stroke='f' fillcolor='".$bg_color."'>
							<w:anchorlock/>
							<center>
					<![endif]-->
					<a href='".$link."' style='background-color: ".$bg_color."; border-radius: ".$radius."; color: ".$fg_color."; display: inline-block; font-family: sans-serif; font-size: 13px; font-weight: bold; line-height: 35px; text-align: center; text-decoration: none; width: 140px; -webkit-text-size-adjust: none;'>".$text."</a>
					<!--[if mso]>
							</center>
						</v:roundrect>
					<![endif]-->
				</div>
				";

			return $btn;
		}
	}

//validate and format order by clause of select statement
	if (!function_exists('order_by')) {
		function order_by($col, $dir, $col_default = '', $dir_default = 'asc') {
			$order_by = ' order by ';
			$col = preg_replace('#[^a-zA-Z0-9-_.]#', '', $col);
			$dir = strtolower($dir) == 'desc' ? 'desc' : 'asc';
			if ($col != '') {
				return $order_by.$col.' '.$dir.' ';
			}
			else if (is_array($col_default) || $col_default != '') {
				if (is_array($col_default) && @sizeof($col_default) != 0) {
					foreach ($col_default as $k => $column) {
						$direction = (is_array($dir_default) && @sizeof($dir_default) != 0 && (strtolower($dir_default[$k]) == 'asc' || strtolower($dir_default[$k]) == 'desc')) ? $dir_default[$k] : 'asc';
						$order_bys[] = $column.' '.$direction.' ';
					}
					if (is_array($order_bys) && @sizeof($order_bys) != 0) {
						return $order_by.implode(', ', $order_bys);
					}
				}
				else {
					return $order_by.$col_default.' '.$dir_default.' ';
				}
			}
		}
	}

//validate and format limit and offset clause of select statement
	if (!function_exists('limit_offset')) {
		function limit_offset($limit, $offset = 0) {
			$regex = '#[^0-9]#';
			$limit = preg_replace($regex, '', $limit);
			$offset = preg_replace($regex, '', $offset);
			if (is_numeric($limit) && $limit > 0) {
				$clause = ' limit '.$limit;
				$offset = is_numeric($offset) ? $offset : 0;
				$clause .= ' offset '.$offset;
			}
			return $clause.' ';
		}
	}

//add a random_bytes function when it doesn't exist for old versions of PHP
	if (!function_exists('random_bytes')) {
		function random_bytes($length) {
			$chars .= "0123456789";
			$chars .= "abcdefghijkmnopqrstuvwxyz";
			$chars .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
			for ($i = 0; $i < $length; $i++) {
				$string .= $chars[random_int(0, strlen($chars)-1)];
			}
			return $string.' ';
		}
	}

//add a hash_equals function when it doesn't exist for old versions of PHP
	if (!function_exists('hash_equals')) {
		function hash_equals($var1, $var2) {
			if ($var1 == $var2) {
				return true;
			}
			else {
				return false;
			}
		}
	}

//convert bytes to readable human format
	if (!function_exists('byte_convert')) {
		function byte_convert($bytes, $precision = 2) {
			static $units = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
			$step = 1024;
			$i = 0;
			while (($bytes / $step) > 0.9) {
				$bytes = $bytes / $step;
				$i++;
			}
			return round($bytes, $precision).' '.$units[$i];
		}
	}

//convert bytes to readable human format
	if (!function_exists('random_int')) {
		function random_int() {
			return rand ();
		}
	}

//manage submitted form values in a session array
	if (!function_exists('persistent_form_values')) {
		function persistent_form_values($action, $array = null) {
			switch ($action) {
				case 'store':
					// $array is expected to be an array of key / value pairs to store in the session
					if (is_array($array) && @sizeof($array) != 0) {
						$_SESSION['persistent'][$_SERVER['PHP_SELF']] = $array;
					}
					break;
				case 'exists':
					return is_array($_SESSION['persistent'][$_SERVER['PHP_SELF']]) && @sizeof($_SESSION['persistent'][$_SERVER['PHP_SELF']]) != 0 ? true : false;
					break;
				case 'load':
					// $array is expected to be the name of the array to create containing the key / value pairs
					if ($array && !is_array($array)) {
						global $$array;
					}
					if (is_array($_SESSION['persistent'][$_SERVER['PHP_SELF']]) && @sizeof($_SESSION['persistent'][$_SERVER['PHP_SELF']]) != 0) {
						foreach ($_SESSION['persistent'][$_SERVER['PHP_SELF']] as $key => $value) {
							if ($key != 'XID' && $key != 'ACT' && $key != 'RET') {
								if ($array && !is_array($array)) {
									$$array[$key] = $value;
								}
								else {
									global $$key;
									$$key = $value;
								}
							}
						}
						global $unsaved;
						$unsaved = true;
					}
					break;
				case 'view':
					if (is_array($_SESSION['persistent'][$_SERVER['PHP_SELF']]) && @sizeof($_SESSION['persistent'][$_SERVER['PHP_SELF']]) != 0) {
						view_array($_SESSION['persistent'][$_SERVER['PHP_SELF']], false);
					}
					break;
				case 'clear':
					unset($_SESSION['persistent'][$_SERVER['PHP_SELF']]);
					break;
			}
		}
	}

//add alternative array_key_first for older verisons of PHP
	if (!function_exists('array_key_first')) {
	    function array_key_first(array $arr) {
		foreach($arr as $key => $unused) {
		    return $key;
		}
		return NULL;
	    }
	}

//get accountode
	if (!function_exists('get_accountcode')) {
		function get_accountcode() {
			if (strlen($accountcode = $_SESSION['domain']['accountcode']['text']) > 0) {
				if ($accountcode == "none") {
					return;
				}
			}
			else {
				$accountcode = $_SESSION['domain_name'];
			}
			return $accountcode;
		}
	}

// User exists
        if (!function_exists('user_exists')) {
                function user_exists($login, $domain_name = null) {
                	//connect to freeswitch
                        $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
                        if (!$fp) {
                                return false;
                        }

               		//send the user_exists command to freeswitch
                        if ($fp) {
                                //build and send the mkdir command to freeswitch
				if (is_null($domain_name)){
					$domain_name = $_SESSION['domain_name'];
				}
				$switch_cmd = "user_exists id '$login' '$domain_name'";
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				fclose($fp);
				return ($switch_result == 'true'?true:false);
                        }

			//can not create directory
                        return null;
                }
        }

?>
