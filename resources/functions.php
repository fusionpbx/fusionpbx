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
	  Portions created by the Initial Developer are Copyright (C) 2008-2025
	  the Initial Developer. All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	  Tim Fry <tim@fusionpbx.com>
	  Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	*/

	if (!function_exists('str_contains')) {
		/**
		 * Determine if a string contains a given substring
		 * <p>Performs a case-sensitive check indicating if <b>needle</b> is contained in <b>haystack</b>.</p>
		 * @param string $haystack The string to search in.
		 * @param string $needle The substring to search for in the <b>haystack</b>.
		 * @return bool Returns <i>true</i> if <b>needle</b> is in <b>haystack</b>, <i>false</i> otherwise
		 * @link https://www.php.net/manual/en/function.str-contains.php Official PHP documentation
		 * @see str_ends_with(), str_starts_with(), strpos(), stripos(), strrpos(), strripos(), strstr(), strpbrk(), substr(), preg_match()
		 */
		function str_contains(string $haystack, string $needle): bool {
			return strpos($haystack, $needle) !== false;
		}
	}

	if (!function_exists('str_starts_with')) {
		/**
		 * Checks if a string starts with a given substring
		 * <p>Performs a case-sensitive check indicating if <b>haystack</b> begins with <b>needle</b>.</p>
		 * @param string $haystack The string to search in.
		 * @param string $needle The substring to search for in the <b>haystack</b>.
		 * @return bool Returns <i>true</i> if <b>haystack</b> begins with <b>needle</b>, <i>false</i> otherwise
		 * @link https://www.php.net/manual/en/function.str-starts-with.php Official PHP documentation
		 */
		function str_starts_with(string $haystack, string $needle): bool {
			return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
		}
	}

	if (!function_exists('str_ends_with')) {
		/**
		 * Checks if a string ends with a given substring
		 * <p>Performs a case-sensitive check indicating if <b>haystack</b> ends with <b>needle</b>.</p>
		 * @param string $haystack The string to search in.
		 * @param string $needle The substring to search for in the <b>haystack</b>.
		 * @return bool Returns <i>true</i> if <b>haystack</b> ends with <b>needle</b>, <i>false</i> otherwise.
		 */
		function str_ends_with(string $haystack, string $needle): bool {
			return substr_compare($haystack, $needle, -1*strlen($needle)) === 0;
		}
	}

	if (!function_exists('mb_strtoupper')) {

		function mb_strtoupper($string) {
			return strtoupper($string);
		}

	}

	if (!function_exists('check_float')) {

		function check_float($string) {
			$string = str_replace(",", ".", $string ?? '');
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
				} else {
					$string = str_replace("'", "''", $string);
				}
			}
			if ($db_type == "pgsql") {
				$string = str_replace("'", "''", $string);
			}
			if ($db_type == "mysql") {
				if (function_exists('mysql_real_escape_string')) {
					$tmp_str = mysql_real_escape_string($string);
				} else {
					$tmp_str = mysqli_real_escape_string($db, $string);
				}
				if (!empty($tmp_str)) {
					$string = $tmp_str;
				} else {
					$search = array("\x00", "\n", "\r", "\\", "'", "\"", "\x1a");
					$replace = array("\\x00", "\\n", "\\r", "\\\\", "\'", "\\\"", "\\\x1a");
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

		/**
		 * Checks if the $ip_address is within the range of the given $cidr
		 * @param string|array $cidr
		 * @param string $ip_address
		 * @return bool return true if the IP address is in CIDR or if it is empty
		 */
		function check_cidr($cidr, $ip_address) {

			//no cidr restriction
			if (empty($cidr)) {
				return true;
			}

			//check to see if the user's remote address is in the cidr array
			if (is_array($cidr)) {
			    	//cidr is an array
				foreach ($cidr as $value) {
					if (check_cidr($value, $ip_address)) {
						return true;
					}
				}
			} else {
				//cidr is a string
				list ($subnet, $mask) = explode('/', $cidr);
				return (ip2long($ip_address) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
			}

			//value not found in cidr
			return false;
		}

	}

	if (!function_exists('fix_postback')) {

		function fix_postback($post_array) {
			foreach ($post_array as $index => $value) {
				if (is_array($value)) {
					fix_postback($value);
				} else {
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
				$uuid = trim(shell_exec("uuidgen"));
				if (is_uuid($uuid)) {
					return $uuid;
				} else {
					echo "Please install uuidgen.\n";
					exit;
				}
			}
			if (PHP_OS === 'Linux') {
				$uuid = trim(file_get_contents('/proc/sys/kernel/random/uuid'));
				if (is_uuid($uuid)) {
					return $uuid;
				} else {
					$uuid = trim(shell_exec("uuidgen"));
					if (is_uuid($uuid)) {
						return $uuid;
					} else {
						echo "Please install uuidgen.\n";
						exit;
					}
				}
			}
			if ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') && function_exists('com_create_guid')) {
				$uuid = trim(com_create_guid(), '{}');
				if (is_uuid($uuid)) {
					return $uuid;
				} else {
					echo "The com_create_guid() function failed to create a uuid.\n";
					exit;
				}
			}
		}

	}

	if (!function_exists('is_uuid')) {

		function is_uuid($str) {
			$is_uuid = false;
			if (gettype($str) == 'string') {
				if (substr_count($str, '-') != 0 && strlen($str) == 36) {
					$regex = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';
					$is_uuid = preg_match($regex, $str);
				} else if (strlen(preg_replace("#[^a-fA-F0-9]#", '', $str)) == 32) {
					$regex = '/^[0-9A-F]{32}$/i';
					$is_uuid = preg_match($regex, $str);
				}
			}
			return $is_uuid;
		}

	}

	if (!function_exists('is_xml')) {

		function is_xml($string) {
			$pattern = '/^<\?xml(?:\s+[^>]+\s*)?\?>\s*<(\w+)>.*<\/\1>\s*$/s';
			return preg_match($pattern, $string) === 1;
		}

	}

	if (!function_exists('recursive_copy')) {
		if (file_exists('/bin/cp')) {

			function recursive_copy($source, $destination, $options = '') {
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'SUN') {
					//copy -R recursive, preserve attributes for SUN
					$cmd = 'cp -Rp ' . $source . '/* ' . $destination;
				} else {
					//copy -R recursive, -L follow symbolic links, -p preserve attributes for other Posix systemss
					$cmd = 'cp -RLp ' . $options . ' ' . $source . '/* ' . $destination;
				}
				exec($cmd);
			}

		} elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

			function recursive_copy($source, $destination, $options = '') {
				$source = normalize_path_to_os($source);
				$destination = normalize_path_to_os($destination);
				exec("xcopy /E /Y \"$source\" \"$destination\"");
			}

		} else {

			function recursive_copy($source, $destination, $options = '') {
				$dir = opendir($source);
				if (!$dir) {
					throw new Exception("recursive_copy() source directory '" . $source . "' does not exist.");
				}
				if (!is_dir($destination)) {
					if (!mkdir($destination, 02770, true)) {
						throw new Exception("recursive_copy() failed to create destination directory '" . $destination . "'");
					}
				}
				while (false !== ( $file = readdir($dir))) {
					if (( $file != '.' ) && ( $file != '..' )) {
						if (is_dir($source . '/' . $file)) {
							recursive_copy($source . '/' . $file, $destination . '/' . $file);
						} else {
							copy($source . '/' . $file, $destination . '/' . $file);
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
					exec('/usr/bin/find ' . $directory . '/* -name "*" -delete');
					//exec('rm -Rf '.$directory.'/*');
					clearstatcache();
				}
			}

		} elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

			function recursive_delete($directory) {
				$directory = normalize_path_to_os($directory);
				//$this->write_debug("del /S /F /Q \"$dir\"");
				exec("del /S /F /Q \"$directory\"");
				clearstatcache();
			}

		} else {

			function recursive_delete($directory) {
				foreach (glob($directory) as $file) {
					if (is_dir($file)) {
						//$this->write_debug("rm dir: ".$file);
						recursive_delete("$file/*");
						rmdir($file);
					} else {
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
			if (isset($_SESSION['groups']) && count($_SESSION["groups"]) > 0) {
				foreach ($_SESSION["groups"] as $row) {
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

	//check if the permission exists
	if (!function_exists('permission_exists')) {

		function permission_exists($permission_name) {
			global $domain_uuid, $user_uuid;
			$database = database::new();
			$permission = permissions::new($database, $domain_uuid, $user_uuid);
			return $permission->exists($permission_name);
		}

	}

	if (!function_exists('if_group_member')) {

		function if_group_member($group_members, $group) {
			if (stripos($group_members, "||" . $group . "||") === false) {
				return false; //group does not exist
			} else {
				return true; //group exists
			}
		}

	}

	if (!function_exists('superadmin_list')) {

		function superadmin_list() {
			global $domain_uuid;
			$sql = "select * from v_user_groups ";
			$sql .= "where group_name = 'superadmin' ";
			$database = database::new();
			$result = $database->select($sql, null, 'all');
			$superadmin_list = "||";
			if (is_array($result) && @sizeof($result) != 0) {
				foreach ($result as $field) {
					//get the list of superadmins
					$superadmin_list .= $field['user_uuid'] . "||";
				}
			}
			unset($sql, $result, $field);
			return $superadmin_list;
		}

	}

	if (!function_exists('if_superadmin')) {

		function if_superadmin($superadmin_list, $user_uuid) {
			if (stripos($superadmin_list, "||" . $user_uuid . "||") === false) {
				return false;
			} else {
				return true; //user_uuid exists
			}
		}

	}

	if (!function_exists('html_select_other')) {

		function html_select_other($table_name, $field_name, $sql_where_optional, $field_current_value, $sql_order_by = null, $label_other = 'Other...') {
			//html select other: build a select box from distinct items in db with option for other
			global $domain_uuid;
			$table_name = preg_replace("#[^a-zA-Z0-9_]#", "", $table_name);
			$field_name = preg_replace("#[^a-zA-Z0-9_]#", "", $field_name);

			$html = "<table border='0' cellpadding='1' cellspacing='0'>\n";
			$html .= "<tr>\n";
			$html .= "<td id=\"cell" . escape($field_name) . "1\">\n";
			$html .= "\n";
			$html .= "<select id=\"" . escape($field_name) . "\" name=\"" . escape($field_name) . "\" class='formfld' onchange=\"if (document.getElementById('" . $field_name . "').value == 'Other') { /*enabled*/ document.getElementById('" . $field_name . "_other').style.display=''; document.getElementById('" . $field_name . "_other').className='formfld'; document.getElementById('" . $field_name . "_other').focus(); } else { /*disabled*/ document.getElementById('" . $field_name . "_other').value = ''; document.getElementById('" . $field_name . "_other').style.display='none'; } \">\n";
			$html .= "<option value=''></option>\n";

			$sql = "select distinct(" . $field_name . ") as " . $field_name . " ";
			$sql .= "from " . $table_name . " " . $sql_where_optional . " ";
			$sql .= "order by " . (!empty($sql_order_by) ? $sql_order_by : $field_name . ' asc');
			$database = database::new();
			$result = $database->select($sql, null, 'all');
			if (is_array($result) && @sizeof($result) != 0) {
				foreach ($result as $field) {
					if (!empty($field[$field_name])) {
						$html .= "<option value=\"" . escape($field[$field_name]) . "\" " . ($field_current_value == $field[$field_name] ? "selected='selected'" : null) . ">" . escape($field[$field_name]) . "</option>\n";
					}
				}
			}
			unset($sql, $result, $field);

			$html .= "<option value='' disabled='disabled'></option>\n";
			$html .= "<option value='Other'>" . $label_other . "</option>\n";
			$html .= "</select>\n";
			$html .= "</td>\n";
			$html .= "<td id=\"cell" . $field_name . "2\" width='5'>\n";
			$html .= "<input id=\"" . $field_name . "_other\" name=\"" . $field_name . "_other\" value='' type='text' class='formfld' style='display: none;'>\n";
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

			if (!empty($field_value)) {
				$html .= "<select id=\"" . $field_value . "\" name=\"" . $field_value . "\" class='formfld' style='" . $style . "' " . (!empty($on_change) ? "onchange=\"" . $on_change . "\"" : null) . ">\n";
				$html .= "	<option value=\"\"></option>\n";

				$sql = "select distinct(" . $field_name . ") as " . $field_name . ", " . $field_value . " from " . $table_name . " " . $sql_where_optional . " order by " . $field_name . " asc ";
			} else {
				$html .= "<select id=\"" . $field_name . "\" name=\"" . $field_name . "\" class='formfld' style='" . $style . "' " . (!empty($on_change) ? "onchange=\"" . $on_change . "\"" : null) . ">\n";
				$html .= "	<option value=\"\"></option>\n";

				$sql = "select distinct(" . $field_name . ") as " . $field_name . " from " . $table_name . " " . $sql_where_optional . " ";
			}

			$database = database::new();
			$result = $database->select($sql, null, 'all');
			if (is_array($result) && @sizeof($result) != 0) {
				foreach ($result as $field) {
					if (!empty($field[$field_name])) {
						$selected = $field_current_value == $field[$field_name] ? "selected='selected'" : null;
						$array_key = empty($field_value) ? $field_name : $field_value;
						$html .= "<option value=\"" . urlencode($field[$array_key]) . "\" " . $selected . ">" . urlencode($field[$field_name]) . "</option>\n";
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
			if (is_uuid($app_uuid) > 0) {
				$app_uuid = "&app_uuid=" . urlencode($app_uuid);
			} // accomodate need to pass app_uuid where necessary (inbound/outbound routes lists)

			$field_name = preg_replace("#[^a-zA-Z0-9_]#", "", $field_name);
			$field_value = preg_replace("#[^a-zA-Z0-9_]#", "", $field_value ?? '');

			$sanitized_parameters = '';
			if (isset($http_get_params) && !empty($http_get_params)) {
				$parameters = explode('&', $http_get_params);
				if (is_array($parameters)) {
					foreach ($parameters as $parameter) {
						if (substr_count($parameter, '=') != 0) {
							$array = explode('=', $parameter);
							$key = preg_replace('#[^a-zA-Z0-9_\-]#', '', $array['0']);
							$value = urldecode($array['1']);
							if ($key == 'order_by' && !empty($value)) {
								//validate order by
								$sanitized_parameters .= "&order_by=" . preg_replace('#[^a-zA-Z0-9_\-]#', '', $value);
							} else if ($key == 'order' && !empty($value)) {
								//validate order
								switch ($value) {
									case 'asc':
										$sanitized_parameters .= "&order=asc";
										break;
									case 'desc':
										$sanitized_parameters .= "&order=desc";
										break;
								}
							} else if (!empty($value) && is_numeric($value)) {
								$sanitized_parameters .= "&" . $key . "=" . $value;
							} else {
								$sanitized_parameters .= "&" . $key . "=" . urlencode($value);
							}
						}
					}
				}
			}

			$html = "<th " . $css . " nowrap='nowrap'>";
			$description = empty($description) ? '' : $description . ', ';
			if (empty($order_by)) {
				$order = 'asc';
			}
			if ($order_by == $field_name) {
				if ($order == "asc") {
					$description .= $text['label-order'] . ' ' . $text['label-descending'];
					$html .= "<a href='?order_by=" . urlencode($field_name) . "&order=desc" . $app_uuid . $sanitized_parameters . "' title=\"" . escape($description) . "\">" . escape($column_title) . "</a>";
				} else {
					$description .= $text['label-order'] . ' ' . $text['label-ascending'];
					$html .= "<a href='?order_by=" . urlencode($field_name) . "&order=asc" . $app_uuid . $sanitized_parameters . "' title=\"" . escape($description) . "\">" . escape($column_title) . "</a>";
				}
			} else {
				$description .= $text['label-order'] . ' ' . $text['label-ascending'];
				$html .= "<a href='?order_by=" . urlencode($field_name) . "&order=asc" . $app_uuid . $sanitized_parameters . "' title=\"" . escape($description) . "\">" . escape($column_title) . "</a>";
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
			if (count($pattern) == 1) {
				//echo 'No File Extension Present';
				return '';
			}

			if (count($pattern) > 1) {
				$filenamepart = $pattern[count($pattern) - 1][0];
				preg_match('/[^?]*/', $filenamepart, $matches);
				return $matches[0];
			}
		}

		//echo "ext: ".get_ext('test.txt');
	}

	if (!function_exists('file_upload')) {

		function file_upload($field = '', $file_type = '', $dest_dir = '') {

			$uploadtempdir = $_ENV["TEMP"] . "\\";
			ini_set('upload_tmp_dir', $uploadtempdir);

			$tmp_name = $_FILES[$field]["tmp_name"];
			$file_name = $_FILES[$field]["name"];
			$file_type = $_FILES[$field]["type"];
			$file_size = $_FILES[$field]["size"];
			$file_ext = get_ext($file_name);
			$file_name_orig = $file_name;
			$file_name_base = substr($file_name, 0, (strlen($file_name) - (strlen($file_ext) + 1)));
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
			while (file_exists($dest_dir . '/' . $file_name)) {
				if (!empty($file_ext)) {
					$file_name = $file_name_base . $i . '.' . $file_ext;
				} else {
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

			if (move_uploaded_file($tmp_name, $dest_dir . '/' . $file_name)) {
				return $file_name;
			} else {
				echo "File upload failed!  Here's some debugging info:\n";
				return false;
			}
			exit;
		}

	}

	if (!function_exists('sys_get_temp_dir')) {

		function sys_get_temp_dir() {
			if ($temp = getenv('TMP')) {
				return $temp;
			}
			if ($temp = getenv('TEMP')) {
				return $temp;
			}
			if ($temp = getenv('TMPDIR')) {
				return $temp;
			}
			$temp = tempnam(__FILE__, '');
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
			return str_replace(array('/', '\\'), '/', $path);
		}

	}

	if (!function_exists('normalize_path_to_os')) {

		function normalize_path_to_os($path) {
			return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
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
			$database = database::new();
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
			$database = database::new();
			$user_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

			if (is_uuid($user_uuid)) {
				//check if the user_uuid exists in v_extension_users
				$sql = "select count(*) from v_extension_users ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and user_uuid = :user_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['user_uuid'] = $user_uuid;
				$database = database::new();
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
					$p = permissions::new();
					$p->add('extension_user_add', 'temp');
					//execute insert
					$database = database::new();
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
			if (empty($username)) {
				return false;
			}
			if (empty($password)) {
				return false;
			}
			if (!username_exists($username)) {
				//build user insert array
				$user_uuid = uuid();
				$salt = generate_password('20', '4');
				$array['users'][0]['user_uuid'] = $user_uuid;
				$array['users'][0]['domain_uuid'] = $domain_uuid;
				$array['users'][0]['username'] = $username;
				$array['users'][0]['password'] = md5($salt . $password);
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
				$p = permissions::new();
				$p->add('user_add', 'temp');
				$p->add('user_group_add', 'temp');
				//execute insert
				$database = database::new();
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

	function switch_module_is_running($mod, event_socket $esl = null) {
		//if the object does not exist create it
		if ($esl === null) {
			$esl = event_socket::create();
		}
		//if we are not connected to freeswitch show an error message
		if (!$esl->is_connected()) {
			return false;
		}
		//send the api command to check if the module exists
		$switch_result = event_socket::api("module_exists $mod");
		return (trim($switch_result) == "true");
	}
//print (switch_module_is_running('mod_spidermonkey') ? "true" : "false");

//format a number (n) replace with a number (r) remove the number
	function format_string($format, $data) {
		//nothing to do so return
		if (empty($format))
			return $data;

		//preset values
		$x = 0;
		$tmp = '';

		//count the characters
		$format_count = substr_count($format, 'x');
		$format_count = $format_count + substr_count($format, 'R');
		$format_count = $format_count + substr_count($format, 'r');

		//format the string if it matches
		if ($format_count == strlen($data)) {
			for ($i = 0; $i <= strlen($format); $i++) {
				$tmp_format = strtolower(substr($format, $i, 1));
				if ($tmp_format == 'x') {
					$tmp .= substr($data, $x, 1);
					$x++;
				} elseif ($tmp_format == 'r') {
					$x++;
				} else {
					$tmp .= $tmp_format;
				}
			}
		}
		if (empty($tmp)) {
			return $data;
		} else {
			return $tmp;
		}
	}

//get the format and use it to format the phone number
	function format_phone($phone_number) {
		if (is_numeric(trim($phone_number ?? '', ' +'))) {
			if (isset($_SESSION["format"]["phone"])) {
				$phone_number = trim($phone_number, ' +');
				foreach ($_SESSION["format"]["phone"] as $format) {
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
		$seconds = (int) $seconds; //convert seconds to an integer
		$hours = floor($seconds / 3600);
		$minutes = floor(floor($seconds / 60) % 60);
		$seconds = $seconds % 60;
		return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
	}

//format seconds
	function format_seconds($seconds) {
	    return gmdate("H:i:s", $seconds);
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
		} elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
			$platform = 'Apple';
		} elseif (preg_match('/windows|win32/i', $user_agent)) {
			$platform = 'Windows';
		}

		//set mobile to true or false
		if (preg_match('/mobile/i', $user_agent)) {
			$platform = 'Mobile';
			$mobile = true;
		} elseif (preg_match('/android/i', $user_agent)) {
			$platform = 'Android';
			$mobile = true;
		}

		//get the name of the useragent
		if (preg_match('/MSIE/i', $user_agent) || preg_match('/Trident/i', $user_agent)) {
			$browser_name = 'Internet Explorer';
			$browser_name_short = 'MSIE';
		} elseif (preg_match('/Firefox/i', $user_agent)) {
			$browser_name = 'Mozilla Firefox';
			$browser_name_short = 'Firefox';
		} elseif (preg_match('/Chrome/i', $user_agent)) {
			$browser_name = 'Google Chrome';
			$browser_name_short = 'Chrome';
		} elseif (preg_match('/Safari/i', $user_agent)) {
			$browser_name = 'Apple Safari';
			$browser_name_short = 'Safari';
		} elseif (preg_match('/Opera/i', $user_agent)) {
			$browser_name = 'Opera';
			$browser_name_short = 'Opera';
		} elseif (preg_match('/Netscape/i', $user_agent)) {
			$browser_name = 'Netscape';
			$browser_name_short = 'Netscape';
		} else {
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
			if (strripos($user_agent, "Version") < strripos($user_agent, $browser_name_short)) {
				$version = $matches['version'][0];
			} else {
				$version = $matches['version'][1];
			}
		} else {
			$version = $matches['version'][0];
		}

		//check if we have a number
		if ($version == null || $version == "") {
			$version = "?";
		}

		//return the data
		switch ($info) {
			case "agent": return $user_agent;
				break;
			case "name": return $browser_name;
				break;
			case "name_short": return $browser_name_short;
				break;
			case "version": return $version;
				break;
			case "platform": return $platform;
				break;
			case "mobile": return $mobile;
				break;
			case "pattern": return $pattern;
				break;
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
	function tail($file, $num_to_get = 10) {
		$esl = fopen($file, 'r');
		$position = filesize($file);
		$chunklen = 4096;
		if ($position - $chunklen <= 0) {
			fseek($esl, 0);
		} else {
			fseek($esl, $position - $chunklen);
		}
		$data = "";
		$ret = "";
		$lc = 0;
		while ($chunklen > 0) {
			$data = fread($esl, $chunklen);
			$dl = strlen($data);
			for ($i = $dl - 1; $i >= 0; $i--) {
				if ($data[$i] == "\n") {
					if ($lc == 0 && $ret != "")
						$lc++;
					$lc++;
					if ($lc > $num_to_get)
						return $ret;
				}
				$ret = $data[$i] . $ret;
			}
			if ($position - $chunklen <= 0) {
				fseek($esl, 0);
				$chunklen = $chunklen - abs($position - $chunklen);
			} else {
				fseek($esl, $position - $chunklen);
			}
			$position = $position - $chunklen;
		}
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
		if ($strength >= 1) {
			$chars .= "0123456789";
		}
		if ($strength >= 2) {
			$chars .= "abcdefghijkmnopqrstuvwxyz";
		}
		if ($strength >= 3) {
			$chars .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		}
		if ($strength >= 4) {
			$chars .= "!^$%*?.";
		}
		for ($i = 0; $i < $length; $i++) {
			$password .= $chars[random_int(0, strlen($chars) - 1)];
		}
		return $password;
	}

//check password strength against requirements (if any)
	function check_password_strength($password, $text, $type = 'default') {

		//initialize the settigns object
		$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid']]);

		if (!empty($password)) {
			if ($type == 'default') {
				$req['length'] = $settings->get('extension', 'password_length', '10');
				$req['number'] = $settings->get('extension', 'password_number', true);
				$req['lowercase'] = $settings->get('extension', 'password_lowercase', true);
				$req['uppercase'] = $settings->get('extension', 'password_uppercase', false);
				$req['special'] = $settings->get('extension', 'password_special', false);
			} elseif ($type == 'user') {
				$req['length'] = $settings->get('users', 'password_length', '10');
				$req['number'] = $settings->get('users', 'password_number', true);
				$req['lowercase'] = $settings->get('users', 'password_lowercase', true);
				$req['uppercase'] = $settings->get('users', 'password_uppercase', false);
				$req['special'] = $settings->get('users', 'password_special', false);
			}
			if (is_numeric($req['length']) && $req['length'] != 0 && !preg_match_all('$\S*(?=\S{' . $req['length'] . ',})\S*$', $password)) { // length
				$msg_errors[] = $req['length'] . '+ ' . $text['label-characters'];
			}
			if ($req['number'] && !preg_match_all('$\S*(?=\S*[\d])\S*$', $password)) { //number
				$msg_errors[] = '1+ ' . $text['label-numbers'];
			}
			if ($req['lowercase'] && !preg_match_all('$\S*(?=\S*[a-z])\S*$', $password)) { //lowercase
				$msg_errors[] = '1+ ' . $text['label-lowercase_letters'];
			}
			if ($req['uppercase'] && !preg_match_all('$\S*(?=\S*[A-Z])\S*$', $password)) { //uppercase
				$msg_errors[] = '1+ ' . $text['label-uppercase_letters'];
			}
			if ($req['special'] && !preg_match_all('$\S*(?=\S*[\W])\S*$', $password)) { //special
				$msg_errors[] = '1+ ' . $text['label-special_characters'];
			}
			if (is_array($msg_errors) && sizeof($msg_errors) > 0) {
				message::add($_SESSION["message"] = $text['message-password_requirements'] . ': ' . implode(', ', $msg_errors), 'negative', 6000);
				return false;
			} else {
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
			$esl = @fopen($url, 'rb', false, $ctx);
			if (!$esl) {
				throw new Exception("Problem with $url, $php_errormsg");
			}
			$response = @stream_get_contents($esl);
			if ($response === false) {
				throw new Exception("Problem reading data from $url, $php_errormsg");
			}
			return $response;
		}

	}

//convert the string to a named array
	if (!function_exists('csv_to_named_array')) {

		function csv_to_named_array($tmp_str, $tmp_delimiter) {
			$tmp_array = explode("\n", $tmp_str);
			$result = array();
			if (trim(strtoupper($tmp_array[0])) !== "+OK") {
				$tmp_field_name_array = explode($tmp_delimiter, $tmp_array[0]);
				$x = 0;
				foreach ($tmp_array as $row) {
					if ($x > 0) {
						$tmp_field_value_array = explode($tmp_delimiter, $tmp_array[$x]);
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

	function number_pad($number, $n) {
		return str_pad((int) $number, $n, "0", STR_PAD_LEFT);
	}

// validate email address syntax
	if (!function_exists('valid_email')) {

		function valid_email($email) {
			return (filter_var($email, FILTER_VALIDATE_EMAIL)) ? true : false;
		}

	}

//function to convert hexidecimal color value to rgb string/array value
	if (!function_exists('hex_to_rgb')) {

		function hex_to_rgb($hex, $delim = null, $include_alpha = false, $alpha = 1) {
			$hex = str_replace("#", "", $hex);

			if (strlen($hex) == 3) {
				$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
				$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
				$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
			}
			else {
				$r = hexdec(substr($hex, 0, 2));
				$g = hexdec(substr($hex, 2, 2));
				$b = hexdec(substr($hex, 4, 2));
			}
			$rgb = array($r, $g, $b);
			if ($include_alpha) { $rgb[] = $alpha; }

			if (!empty($delim)) {
				return implode($delim, $rgb); // return rgb delimited string
			}
			else {
				return $rgb; // return array of rgb values
			}
		}

	}

//function to convert a hex or rgb/a color to an rgba array
	if (!function_exists('color_to_rgba_array')) {

		function color_to_rgba_array($string, $alpha = null) {
			if (!empty($string)) {
				if (strpos($string, '#') === 0) { //is hex
					return hex_to_rgb($string, null, true, $alpha);
				}
				else if (strpos($string, 'rgb') === 0) { //is rgb/a
					$string = str_replace(['rgba(','rgb(',')'], '', $string); //values to csv
					$array = explode(',', $string); //create array
					if (!empty($array)) {
						if (@sizeof($array) == 3) { //add alpha
							$array[] = $alpha ?? 1;
						}
						else if (@sizeof($array) == 4 && !empty($alpha)) { //replace alpha
							$array[3] = $alpha;
						}
					}
					return !empty($array) && is_array($array) ? $array : false;
				}
			}
			return false;
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
					$r = hexdec(substr($color, 0, 1) . substr($color, 0, 1));
					$g = hexdec(substr($color, 1, 1) . substr($color, 1, 1));
					$b = hexdec(substr($color, 2, 1) . substr($color, 2, 1));
				} else {
					$r = hexdec(substr($color, 0, 2));
					$g = hexdec(substr($color, 2, 2));
					$b = hexdec(substr($color, 4, 2));
				}
				$color = $r . ',' . $g . ',' . $b;
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
					$r = hexdec(substr($color, 0, 1) . substr($color, 0, 1));
					$g = hexdec(substr($color, 1, 1) . substr($color, 1, 1));
					$b = hexdec(substr($color, 2, 1) . substr($color, 2, 1));
				} else {
					$r = hexdec(substr($color, 0, 2));
					$g = hexdec(substr($color, 2, 2));
					$b = hexdec(substr($color, 4, 2));
				}
				$color = $r . ',' . $g . ',' . $b;
			}

			//color to array, pop alpha
			if (substr_count($color, ',') > 0) {
				$color = str_replace(' ', '', $color);
				$wrapper = false;
				if (substr_count($color, 'rgb') != 0) {
					$color = str_replace('rgb', '', $color);
					$color = str_replace('a(', '', $color);
					$color = str_replace('(', '', $color);
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
					$hex = '';
					for ($i = 0; $i <= 2; $i++) {
						$hex_color = dechex($color[$i]);
						if (strlen($hex_color) == 1) {
							$hex_color = '0' . $hex_color;
						}
						$hex .= $hex_color;
					}
					return $hash . $hex;
				} else { //rgb(a)
					$rgb = implode(',', $color);
					if (!empty($alpha)) {
						$rgb .= ',' . $alpha;
						$a = 'a';
					}
					if ($wrapper) {
						$rgb = 'rgb' . ($a ?? '') . '(' . $rgb . ')';
					}
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
			} else {
				$s = $d / (1 - abs((2 * $l) - 1));
				switch ($max) {
					case $r:
						$h = 60 * fmod((($g - $b) / $d), 6);
						if ($b > $g) {
							$h += 360;
						}
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

		function hsl_to_rgb($h, $s, $l) {
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
			} else if ($h < 120) {
				$r = $x;
				$g = $c;
				$b = 0;
			} else if ($h < 180) {
				$r = 0;
				$g = $c;
				$b = $x;
			} else if ($h < 240) {
				$r = 0;
				$g = $x;
				$b = $c;
			} else if ($h < 300) {
				$r = $x;
				$g = 0;
				$b = $c;
			} else {
				$r = $c;
				$g = 0;
				$b = $x;
			}

			$r = ($r + $m) * 255;
			$g = ($g + $m) * 255;
			$b = ($b + $m) * 255;

			if ($r > 255) {
				$r = 255;
			}
			if ($g > 255) {
				$g = 255;
			}
			if ($b > 255) {
				$b = 255;
			}

			if ($r < 0) {
				$r = 0;
			}
			if ($g < 0) {
				$g = 0;
			}
			if ($b < 0) {
				$b = 0;
			}

			return array(floor($r), floor($g), floor($b));
		}

	}

//function to send email
	if (!function_exists('send_email')) {

		function send_email($email_recipients, $email_subject, $email_body, &$email_error = '', $email_from_address = '', $email_from_name = '', $email_priority = 3, $email_debug_level = 0, $email_attachments = '', $email_read_confirmation = false) {
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

			  Error messages are stored in the variable passed into $email_error BY REFERENCE

			 */

			//add the email recipients
			$address_found = false;
			if (!is_array($email_recipients)) { // must be a single or delimited recipient address(s)
				$email_recipients = str_replace(' ', '', $email_recipients);
				$email_recipients = str_replace(',', ';', $email_recipients);
				$email_recipients = explode(';', $email_recipients); // convert to array of addresses
			}

			foreach ($email_recipients as $email_recipient) {
				if (is_array($email_recipient)) { // check if each recipient has multiple fields
					if (!empty($email_recipient["address"]) && valid_email($email_recipient["address"])) { // check if valid address
						$recipients = $email_recipient["address"];
						$address_found = true;
					}
				} else if (!empty($email_recipient) && valid_email($email_recipient)) { // check if recipient value is simply (only) an address
					$email_recipients = $email_recipient;
					$address_found = true;
				}
			}
			if (is_array($recipients)) {
				$email_recipients = implode(",", $recipients);
			}
			if (!$address_found) {
				$email_error = "No valid e-mail address provided.";
				return false;
			}

			//get the from address and name
			$email_from_address = (!empty($email_from_address)) ? $email_from_address : $_SESSION['email']['smtp_from']['text'];
			$email_from_name = (!empty($email_from_name)) ? $email_from_name : $_SESSION['email']['smtp_from_name']['text'];

			//send email
			$email = new email;
			$email->recipients = $email_recipients;
			$email->subject = $email_subject;
			$email->body = $email_body;
			$email->from_address = $email_from_address;
			$email->from_name = $email_from_name;
			$email->attachments = $email_attachments;
			$email->debug_level = 3;
			$sent = $email->send();
			//$email_error = $email->email_error;
		}

	}

//encrypt a string
	if (!function_exists('encrypt')) {

		function encrypt($key, $data) {
			$encryption_key = base64_decode($key);
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
			$encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
			return base64_encode($encrypted . '::' . $iv);
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
			return is_string($str) && is_array(json_decode($str, true)) ? true : false;
		}

	}

//mac detection
	if (!function_exists('is_mac')) {

		function is_mac($str) {
			return preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $str) == 1 && strlen(preg_replace("#[^a-fA-F0-9]#", '', $str)) == 12 ? true : false;
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

//format device address
	if (!function_exists('format_device_address')) {

		function format_device_address($str, $delim = '-', $case = 'lower') {
			if (empty($str)) {
				return false;
			}
			$str = preg_replace("#[^a-fA-F0-9]#", '', $str); //remove formatting, if any
			if (is_mac($str)) {
				$str = join($delim, str_split($str, 2));
			} else if (is_uuid($str)) {
				$str = substr($str, 0, 8) . '-' . substr($str, 8, 4) . '-' . substr($str, 12, 4) . '-' . substr($str, 16, 4) . '-' . substr($str, 20, 12);
			}
			$str = $case == 'upper' ? strtoupper($str) : strtolower($str);
			return $str;
		}

	}

//transparent gif
	if (!function_exists('img_spacer')) {

		function img_spacer($width = '1px', $height = '1px', $custom = null) {
			return "<img src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' style='width: " . $width . "; height: " . $height . "; " . $custom . "'>";
		}

	}

//lower case
	function lower_case($string) {
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($string, 'UTF-8');
		} else {
			return strtolower($string);
		}
	}

//upper case
	function upper_case($string) {
		if (function_exists('mb_strtoupper')) {
			return mb_strtoupper($string, 'UTF-8');
		} else {
			return strtoupper($string);
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
				case 'down': $direction = 'keydown';
					break;
				case 'press': $direction = 'keypress';
					break;
				case 'up': $direction = 'keyup';
					break;
			}
			//check for element exceptions
			if (is_array($exceptions)) {
				if (sizeof($exceptions) > 0) {
					$exceptions = "!$(e.target).is('" . implode(',', $exceptions) . "') && ";
				}
			}
			//quote if selector is id or class
			$subject = ($subject != 'window' && $subject != 'document') ? "'" . $subject . "'" : $subject;
			//output script
			echo "\n\n\n";
			if ($script_wrapper) {
				echo "<script language='JavaScript' type='text/javascript'>\n";
			}
			echo "	$(" . $subject . ").on('" . $direction . "', function(e) {\n";
			echo "		if (" . $exceptions . $key_code . ") {\n";
			if (!empty($prompt)) {
				$action = (!empty($action)) ? $action : "alert('" . $key . "');";
				echo "			if (confirm('" . $prompt . "')) {\n";
				echo "				e.preventDefault();\n";
				echo "				" . $action . "\n";
				echo "			}\n";
			} else {
				echo "			e.preventDefault();\n";
				echo "			" . $action . "\n";
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
			$radius_value = (!empty($radius_value)) ? $radius_value : $default;
			$br_a = explode(' ', $radius_value);
			foreach ($br_a as $index => $br) {
				if (substr_count($br, '%') > 0) {
					$br_b[$index]['number'] = str_replace('%', '', $br);
					$br_b[$index]['unit'] = '%';
				} else {
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
			} else if (sizeof($br_b) == 2) {
				$br['tl']['n'] = $br_b[0]['number'];
				$br['tr']['n'] = $br_b[0]['number'];
				$br['br']['n'] = $br_b[1]['number'];
				$br['bl']['n'] = $br_b[1]['number'];
				$br['tl']['u'] = $br_b[0]['unit'];
				$br['tr']['u'] = $br_b[0]['unit'];
				$br['br']['u'] = $br_b[1]['unit'];
				$br['bl']['u'] = $br_b[1]['unit'];
			} else {
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

		function string_to_regex($string, $prefix = '') {
			$original_string = $string;
			//escape the plus
			if (substr($string, 0, 1) == "+") {
				$string = "^\\+(" . substr($string, 1) . ")$";
			}
			//add prefix
			if (!empty($prefix)) {
				if (!empty($prefix) && strlen($prefix) < 4) {
					$plus = (substr($string, 0, 1) == "+") ? '' : '\+?';
					$prefix = $plus . $prefix . '?';
				} else {
					$prefix = '(?:' . $prefix . ')?';
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
				$string = "^" . $string;
			}
			//add $ to the end of the string if missing
			if (substr($string, -1) != "$") {
				$string = $string . "$";
			}
			//add the round brackets
			if (!strstr($string, '(')) {
				if (strstr($string, '^')) {
					$string = str_replace("^", "^" . $prefix . "(", $string);
				} else {
					$string = '^(' . $string;
				}
				if (strstr($string, '$')) {
					$string = str_replace("$", ")$", $string);
				} else {
					$string = $string . ')$';
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
			if (!empty($_SESSION['theme']['font_source_key']['text'])) {
				if (!is_array($_SESSION['fonts_available']) || sizeof($_SESSION['fonts_available']) == 0) {
					/*
					  sort options:
					  alpha 		- alphabetically
					  date 		- by date added (most recent font added or updated first)
					  popularity 	- by popularity (most popular family first)
					  style 		- by number of styles available (family with most styles first)
					  trending 	- by families seeing growth in usage (family seeing the most growth first)
					 */
					$google_api_url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=' . $_SESSION['theme']['font_source_key']['text'] . '&sort=' . $sort;
					$response = file_get_contents($google_api_url);
					if (!empty($response)) {
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
			} else {
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

			$file = file_get_contents($_SERVER["DOCUMENT_ROOT"] . $file_to_parse);
			$lines = explode("\n", $file);

			$style_counter = 0;
			foreach ($lines as $line_number => $line) {
				if (!empty($line_styles_begin) && $line_number < $line_styles_begin - 1) {
					continue;
				}
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
					for ($l = $style_line['begins'] + 1; $l < $style_line['ends']; $l++) {
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
							$value = trim(trim($tmp[1]), ';');
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
							$font_begin = strpos($style_value, "'") + 1;
							$font_end = strpos($style_value, "'", $font_begin);
							$font_name = substr($style_value, $font_begin, $font_end - $font_begin);
							//determine modifiers
							$weight = (is_numeric($style_group['font-weight']) || strtolower($style_group['font-weight']) == 'bold') ? strtolower($style_group['font-weight']) : null;
							$italic = (strtolower($style_group['font-style']) == 'italic') ? 'italic' : null;
							//add font to array
							$fonts[$font_name][] = $weight . $italic;
						}
					}
					//echo "\n\n/*".print_r($fonts, true)."*/\n\n";

					if (is_array($fonts)) {
						foreach ($fonts as $font_name => $modifiers) {
							$modifiers = array_unique($modifiers);
							$import_font_string = str_replace(' ', '+', $font_name);
							if (is_array($modifiers) && sizeof($modifiers) > 0) {
								$import_font_string .= ':' . implode(',', $modifiers);
							}
							$import_fonts[] = $import_font_string;
						}
						//echo "\n\n/*".print_r($import_fonts, true)."*/\n\n";
						$import_string = "@import url(//fonts.googleapis.com/css?family=" . implode('|', $import_fonts) . ");";
						echo $import_string . "\n";
					}
				}
			}
		}

	}

//retrieve array of countries
	if (!function_exists('get_countries')) {

		function get_countries() {
			$sql = "select * from v_countries order by country asc";
			$database = database::new();
			$result = $database->select($sql, null, 'all');
			unset($sql);

			return is_array($result) && @sizeof($result) != 0 ? $result : false;
		}

	}

//make directory with event socket
	function event_socket_mkdir($dir) {
		//connect to fs
		$esl = event_socket::create();
		if (!$esl->is_connected()) {
			return false;
		}
		//send the mkdir command to freeswitch
		//build and send the mkdir command to freeswitch
		$switch_cmd = "lua mkdir.lua " . escapeshellarg($dir);
		$switch_result = event_socket::api($switch_cmd);
		//check result
		if (trim($switch_result) == "-ERR no reply") {
			return true;
		}

		//can not create directory
		return false;
	}

/**
 * Escape the user data
 * <p>Escapes all characters that have HTML character entity
 * @param string $string the value to escape
 * @return string
 * @link https://www.php.net/htmlentities
 */
function escape($string) {
	if (is_string($string)) {
		return htmlentities($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	} elseif (is_numeric($string)) {
		return $string;
	} else {
		$string = (array) $string;
		if (isset($string[0])) {
			return htmlentities($string[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
	}
	return false;
}

/**
 * Escape the user data for a textarea
 * <p>Escapes & " ' < and > characters</p>
 * @param string $string the value to escape
 * @return string
 * @link https://www.php.net/htmlspecialchars
 */
function escape_textarea($string) {
	return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

//output pre-formatted array keys and values
	if (!function_exists('view_array')) {

		function view_array($array, $exit = true, $return = false) {
			$html = "<br><pre style='text-align: left;'>" . print_r($array, true) . '</pre><br>';
			if ($return) {
				return $html;
			} else {
				echo $html;
			}
			$exit and exit();
		}

	}

//format db date and/or time to local date and/or time
	if (!function_exists('format_when_local')) {

		function format_when_local($when, $format = 'dt', $include_seconds = false) {
			if (!empty($when)) {
				// determine when format
				if (substr_count($when, ' ') > 0) { // date and time
					$tmp = explode(' ', $when);
					$date = $tmp[0];
					$time = $tmp[1];
				} else if (substr_count($when, '-') > 0) { // date only
					$date = $when;
				} else if (substr_count($when, ':') > 0) { // time only
					$time = $when;
				}
				unset($when, $tmp);

				// format date
				if (!empty($date)) {
					$tmp = explode('-', $date);
					$date = $tmp[1] . '-' . $tmp[2] . '-' . $tmp[0];
				}

				// format time
				if (!empty($time)) {
					$tmp = explode(':', $time);
					if ($tmp[0] >= 0 && $tmp[0] <= 11) {
						$meridiem = 'AM';
						$hour = ($tmp[0] == 0) ? 12 : $tmp[0];
					} else {
						$meridiem = 'PM';
						$hour = ($tmp[0] > 12) ? ($tmp[0] - 12) : $tmp[0];
					}
					$minute = $tmp[1];
					$second = $tmp[2];
				}

				// structure requested time format
				$time = $hour . ':' . $minute;
				if ($include_seconds) {
					$time .= ':' . $second;
				}
				$time .= ' ' . $meridiem;

				$return['d'] = $date;
				$return['t'] = $time;
				$return['dt'] = $date . ' ' . $time;

				return $return[$format];
			} else {
				return false;
			}
		}

	}

//define email button (src: https://buttons.cm)
	if (!function_exists('email_button')) {

		function email_button($text = 'Click Here!', $link = 'URL', $bg_color = '#dddddd', $fg_color = '#000000', $radius = '') {

			// default button radius
			$radius = !empty($radius) ? $radius : '3px';

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
					  <v:roundrect xmlns:v='urn:schemas-microsoft-com:vml' xmlns:w='urn:schemas-microsoft-com:office:word' href='" . $link . "' style='height: 35px; v-text-anchor: middle; width: 140px;' arcsize='" . $arc . "%' stroke='f' fillcolor='" . $bg_color . "'>
							<w:anchorlock/>
							<center>
					<![endif]-->
					<a href='" . $link . "' style='background-color: " . $bg_color . "; border-radius: " . $radius . "; color: " . $fg_color . "; display: inline-block; font-family: sans-serif; font-size: 13px; font-weight: bold; line-height: 35px; text-align: center; text-decoration: none; width: 140px; -webkit-text-size-adjust: none;'>" . $text . "</a>
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

		function order_by($col, $dir, $col_default = '', $dir_default = 'asc', $sort = '') {
			global $db_type;
			$order_by = ' order by ';
			$col = preg_replace('#[^a-zA-Z0-9-_.]#', '', $col ?? '');
			$dir = !empty($dir) && strtolower($dir) == 'desc' ? 'desc' : 'asc';
			if (!empty($col)) {
				if ($sort == 'natural' && $db_type == "pgsql") {
					return $order_by . 'natural_sort(' . $col . '::text) ' . $dir . ' ';
				} else {
					return $order_by . $col . ' ' . $dir . ' ';
				}
			} else if (!empty($col_default)) {
				if (is_array($col_default) && @sizeof($col_default) != 0) {
					foreach ($col_default as $k => $column) {
						$direction = (is_array($dir_default) && @sizeof($dir_default) != 0 && (strtolower($dir_default[$k]) == 'asc' || strtolower($dir_default[$k]) == 'desc')) ? $dir_default[$k] : 'asc';
						$order_bys[] = $column . ' ' . $direction . ' ';
					}
					if (is_array($order_bys) && @sizeof($order_bys) != 0) {
						return $order_by . implode(', ', $order_bys);
					}
				} else {
					if ($sort == 'natural' && $db_type == "pgsql") {
						return $order_by . 'natural_sort(' . $col_default . '::text) ' . $dir_default . ' ';
					} else {
						return $order_by . $col_default . ' ' . $dir_default . ' ';
					}
				}
			}
		}

	}

//validate and format limit and offset clause of select statement
	if (!function_exists('limit_offset')) {

		function limit_offset($limit = null, $offset = 0) {
			$regex = '#[^0-9]#';
			if (!empty($limit)) {
				$limit = preg_replace($regex, '', $limit);
				$offset = preg_replace($regex, '', $offset ?? '');
				if (is_numeric($limit) && $limit > 0) {
					$clause = ' limit ' . $limit;
					$offset = is_numeric($offset) ? $offset : 0;
					$clause .= ' offset ' . $offset;
				}
				return $clause . ' ';
			} else {
				return '';
			}
		}

	}

//add a random_bytes function when it doesn't exist for old versions of PHP
	if (!function_exists('random_bytes')) {

		function random_bytes($length) {
			$chars .= "0123456789";
			$chars .= "abcdefghijkmnopqrstuvwxyz";
			$chars .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
			for ($i = 0; $i < $length; $i++) {
				$string .= $chars[random_int(0, strlen($chars) - 1)];
			}
			return $string . ' ';
		}

	}

//add a hash_equals function when it doesn't exist for old versions of PHP
	if (!function_exists('hash_equals')) {

		function hash_equals($var1, $var2) {
			if ($var1 == $var2) {
				return true;
			} else {
				return false;
			}
		}

	}

//convert bytes to readable human format
	if (!function_exists('byte_convert')) {

		function byte_convert($bytes, $precision = 2) {
			static $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
			$step = 1024;
			$i = 0;
			while (($bytes / $step) > 0.9) {
				$bytes = $bytes / $step;
				$i++;
			}
			return round($bytes, $precision) . ' ' . $units[$i];
		}

	}

//convert bytes to readable human format
	if (!function_exists('random_int')) {

		function random_int($min, $max) {
			return rand($min, $max);
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
					return !empty($_SESSION['persistent']) && is_array($_SESSION['persistent'][$_SERVER['PHP_SELF']]) && @sizeof($_SESSION['persistent'][$_SERVER['PHP_SELF']]) != 0 ? true : false;
					break;
				case 'load':
					// $array is expected to be the name of the array to create containing the key / value pairs
					if ($array && !is_array($array)) {
						global $$array;
					}
					if (!empty($_SESSION['persistent']) && is_array($_SESSION['persistent'][$_SERVER['PHP_SELF']]) && @sizeof($_SESSION['persistent'][$_SERVER['PHP_SELF']]) != 0) {
						foreach ($_SESSION['persistent'][$_SERVER['PHP_SELF']] as $key => $value) {
							if ($key != 'XID' && $key != 'ACT' && $key != 'RET') {
								if ($array && !is_array($array)) {
									$$array[$key] = $value;
								} else {
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
			foreach ($arr as $key => $unused) {
				return $key;
			}
			return NULL;
		}

	}

//get accountcode
	if (!function_exists('get_accountcode')) {

		function get_accountcode() {
			if (!empty($accountcode = $_SESSION['domain']['accountcode']['text'] ?? '')) {
				if ($accountcode == "none") {
					return;
				}
			} else {
				$accountcode = $_SESSION['domain_name'];
			}
			return $accountcode;
		}

	}

//user exists
	if (!function_exists('user_exists')) {

		function user_exists($login, $domain_name = null) {
			//connect to freeswitch
			$esl = event_socket::create();
			if (!$esl->is_connected()) {
				return false;
			}

			if (is_null($domain_name)) {
				$domain_name = $_SESSION['domain_name'];
			}
			$switch_cmd = "user_exists id '$login' '$domain_name'";
			$switch_result = event_socket::api($switch_cmd);
			return ($switch_result == 'true' ? true : false);
		}

	}

//git pull
if (!function_exists('git_pull')) {
	function git_pull($path) {

		//set the realpath
		$path = realpath($path);

		//return false if the path is invalid or inaccessible
		if ($path === false) {
			return false;
		}

		//add the safe.directory
		if (is_git_safe_directory($path)) {
			$command = 'git config --global --add safe.directory '.escapeshellarg($path);
			exec($command);
		}

		//set the original working directory
		$cwd = getcwd();

		//set the new working directory
		chdir($path);

		//specify how to reconcile divergent branches
		exec('git config pull.rebase false');

		//git pull
		exec("GIT_SSH_COMMAND='ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no' git pull 2>&1", $response_source_update);

		//set the default update status
		$update_status = false;

		//return false
		if (sizeof($response_source_update) == 0) {
			chdir($cwd);
			return array('result' => false, 'message' => null);
		}

		//set the update_status boolean value
		foreach ($response_source_update as $response_line) {
			if (substr_count($response_line, "Updating ") > 0 || substr_count($response_line, "Already up to date.") > 0) {
				$update_status = true;
			}

			if (substr_count($response_line, "error") > 0) {
				$update_status = false;
				break;
			}
		}

		//set the original working directory
		chdir($cwd);

		//return the array
		return array('result' => $update_status,
				'message' => $response_source_update);

	}
}

/**
 * Check if the given directory is in the array of git safe directories.
 *
 * @param string $directory The directory to check.
 * @return bool Returns true if the directory is safe, false otherwise.
 */
function is_git_safe_directory($directory) {

	// Set the project root
	$project_root = dirname(__DIR__, 1);
	//echo "project_root $project_root\n";

	// Define an array of safe directories
	$safe_directories = [];
	$safe_directories[] = $project_root.'/app/bulk_account_settings';
	$safe_directories[] = $project_root.'/app/call_center_summary';
	$safe_directories[] = $project_root.'/app/conference_cdr';
	$safe_directories[] = $project_root.'/app/device_logs';
	$safe_directories[] = $project_root.'/app/dialplan_tools';
	$safe_directories[] = $project_root.'/app/edit';
	$safe_directories[] = $project_root.'/app/invoices';
	$safe_directories[] = $project_root.'/app/maintenance';
	$safe_directories[] = $project_root.'/app/messages';
	$safe_directories[] = $project_root.'/app/providers';
	$safe_directories[] = $project_root.'/app/speech';
	$safe_directories[] = $project_root.'/app/sql_query';
	$safe_directories[] = $project_root.'/app/transcribe';

	// Normalize the directory path
	$normalized_directory = realpath($directory);

	// Check if the normalized directory is in the list of safe directories
	return in_array($normalized_directory, $safe_directories);
}

//git repo validation
if (!function_exists('is_git_repo')) {
	function is_git_repo($path) {
		//normalize the path
		$path = realpath($path);

		//return false if the path is invalid or inaccessible
		if ($path === false) {
			return false;
		}

		//check if the .git directory exists in the given path
		$result = is_dir($path . '/.git');

		//return false if not a git repo
		if ($result === false) {
			return false;
		}

		//return the path if it is a repo
		return is_dir($path . '/.git');
	}
}

//git repo version information
if (!function_exists('git_repo_info')) {
	function git_repo_info($path) {

		//return false if the path is invalid or inaccessible
		if(!is_dir($path)) {
			return false;
		}

		//get the current working directory
		$cwd = getcwd();

		//set the new working directory
		chdir($path);

		//get the current branch
		exec("git rev-parse --abbrev-ref HEAD 2>&1", $git_branch, $git_branch_return);
		$repo['branch'] = $git_branch[0];

		//get the current commit id
		exec("git log --pretty=format:'%H' -n 1 2>&1", $git_commit, $git_commit_return);
		$repo['commit'] = $git_commit[0];

		//get the remote origin url for updates
		exec("git config --get remote.origin.url", $git_url);
		$repo['url'] = preg_replace('/\.git$/', '', $git_url[0] );

		//add the path to the repo array
		$repo['path'] = $path;

		//to-do detect remote over ssh and reformat to equivalent https url

		//set the working directory to the original directory
		chdir($cwd);

		//return the result
		if (!$git_branch_return && !$git_commit_return && $git_url) {
			return $repo;
		}
		else {
			return false;
		}

	}
}

//git locate app repositories
if (!function_exists('git_find_repos')) {
	function git_find_repos($path) {

		//scan the directories
		$apps = scandir($path);

		//prepare the array
		$git_repos = array();

		//loop through the applicaitons
		foreach ($apps as $app) {
			//skip this iteration of the loop
			if ($app == '.' or $app == '..') {
				continue;
			}

			//build the git_repos array
			if (is_git_repo($path."/".$app)) {
				$git_repos[$path."/".$app][] = $app;
			}
		}

		//return the array
		return $git_repos;
	}
}

//get contents of the supplied url
if (!function_exists('url_get_contents')) {
	function url_get_contents($URL){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $URL);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}

//get system memory details
if (!function_exists('get_memory_details')) {
	function get_memory_details() {
		if (PHP_OS == 'Linux') {
			$meminfo = file_get_contents("/proc/meminfo");
			$data = [];

			foreach (explode("\n", $meminfo) as $line) {
				if (preg_match('/^(\w+):\s+(\d+)\skB$/', $line, $matches)) {
					$data[$matches[1]] = $matches[2];
				}
			}

			if (isset($data['MemTotal']) && isset($data['MemAvailable'])) {
				$array['total_memory'] = $data['MemTotal'];
				$array['available_memory'] = $data['MemAvailable'];
				$array['used_memory'] = $array['total_memory'] - $array['available_memory'];

				$array['memory_usage'] = ($array['used_memory'] / $array['total_memory']) * 100;
				$array['memory_percent'] = round($array['memory_usage'], 2);
				return $array;
			}
		}

		if (PHP_OS == 'FreeBSD') {
			//define the output array
			$output = [];

			// get the memory information using sysctl
			exec('sysctl -n hw.physmem hw.pagesize vm.stats.vm.v_free_count vm.stats.vm.v_inactive_count vm.stats.vm.v_cache_count vm.stats.vm.v_wire_count', $output);

			if (count($output) === 6) {
				list($array['total_memory'], $page_size, $free_pages, $inactive_pages, $cache_pages, $wired_pages) = $output;

				// total memory in bytes
				$array['total_memory'] = (int)$array['total_memory'];

				// pages to bytes conversion
				$array['available_memory'] = ($free_pages + $inactive_pages + $cache_pages) * (int)$page_size;
				$array['used_memory'] = $array['total_memory'] - $array['available_memory'];

				// calculate memory usage percentage
				$array['memory_usage'] = ($array['used_memory'] / $array['total_memory']) * 100;

				$array['memory_percent'] = round($array['memory_usage'], 2) . '%';
				return $array;
			}
		}

		return false;
	}
}
