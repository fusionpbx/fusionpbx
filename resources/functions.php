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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

	if (!function_exists('software_version')) {
		function software_version() {
			return '4.0.0';
		}
	}

	if (!function_exists('version')) {
		function version() {
			return software_version();
		}
	}

	if (!function_exists('numeric_version')) {
		function numeric_version() {
			$v = explode('.', software_version());
			$n = ($v[0] * 10000 + $v[1] * 100 + $v[2]);
			return $n;
		}
	}

	if (!function_exists('check_float')) {
		function check_float($string) {
			$string = str_replace(",",".",$string);
			return trim($string);
		}
	}

	if (!function_exists('check_str')) {
		function check_str($string) {
			global $db_type;
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
				$string = pg_escape_string($string);
			}
			if ($db_type == "mysql") {
				$tmp_str = mysql_real_escape_string($string);
				if (strlen($tmp_str)) {
					$string = $tmp_str;
				}
				else {
					$search = array("\x00", "\n", "\r", "\\", "'", "\"", "\x1a");
					$replace = array("\\x00", "\\n", "\\r", "\\\\" ,"\'", "\\\"", "\\\x1a");
					$string = str_replace($search, $replace, $string);
				}
			}
			return trim($string); //remove white space
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
			//uuid version 4
			return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				// 32 bits for "time_low"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

				// 16 bits for "time_mid"
				mt_rand( 0, 0xffff ),

				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand( 0, 0x0fff ) | 0x4000,

				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand( 0, 0x3fff ) | 0x8000,

				// 48 bits for "node"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
			);
		}
		//echo uuid();
	}

	if (!function_exists('is_uuid')) {
		function is_uuid($uuid) {
			//uuid version 4
			$regex = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
			return preg_match($regex, $uuid);
		}
	}

	if (!function_exists('recursive_copy')) {
		function recursive_copy($src,$dst) {
			$dir = opendir($src);
			if (!$dir) {
				throw new Exception("recursive_copy() source directory '".$src."' does not exist.");
			}
			if (!is_dir($dst)) {
				if (!mkdir($dst)) {
					throw new Exception("recursive_copy() failed to create destination directory '".$dst."'");
				}
			}
			while(false !== ( $file = readdir($dir)) ) {
				if (( $file != '.' ) && ( $file != '..' )) {
					if ( is_dir($src . '/' . $file) ) {
						recursive_copy($src . '/' . $file,$dst . '/' . $file);
					}
					else {
						copy($src . '/' . $file,$dst . '/' . $file);
					}
				}
			}
			closedir($dir);
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
			//search for the permission
				if (count($_SESSION["permissions"]) > 0) {
					foreach($_SESSION["permissions"] as $row) {
						if ($row['permission_name'] == $permission) {
							$result = true;
							break;
						}
					}
				}
			//return the result
				return $result;
		}
	}

	if (!function_exists('group_members')) {
		function group_members($db, $user_uuid) {
			global $domain_uuid;
			$sql = "select * from v_group_users ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			$group_members = "||";
			foreach($result as $field) {
				//get the list of groups
				$group_members .= $field['group_name']."||";
			}
			unset($sql, $result, $row_count);
			return $group_members;
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
		function superadmin_list($db) {
			global $domain_uuid;
			$sql = "select * from v_group_users ";
			$sql .= "where group_name = 'superadmin' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			$superadmin_list = "||";
			foreach($result as $field) {
				//get the list of superadmins
				$superadmin_list .= $field['user_uuid']."||";
			}
			unset($sql, $result, $row_count);
			return $superadmin_list;
		}
	}
	//superadmin_list($db);

	if (!function_exists('if_superadmin')) {
		function if_superadmin($superadmin_list, $user_uuid) {
			if (stripos($superadmin_list, "||".$user_uuid."||") === false) {
				return false; //user_uuid does not exist
			}
			else {
				return true; //user_uuid exists
			}
		}
	}

	if (!function_exists('html_select_other')) {
		function html_select_other($db, $table_name, $field_name, $sql_where_optional, $field_current_value) {
			//html select other : build a select box from distinct items in db with option for other
			global $domain_uuid;

			$html  = "<table border='0' cellpadding='1' cellspacing='0'>\n";
			$html .= "<tr>\n";
			$html .= "<td id=\"cell".$field_name."1\">\n";
			$html .= "\n";
			$html .= "<select id=\"".$field_name."\" name=\"".$field_name."\" class='formfld' onchange=\"if (document.getElementById('".$field_name."').value == 'Other') { /*enabled*/ document.getElementById('".$field_name."_other').style.display=''; document.getElementById('".$field_name."_other').className='formfld'; document.getElementById('".$field_name."_other').focus(); } else { /*disabled*/ document.getElementById('".$field_name."_other').value = ''; document.getElementById('".$field_name."_other').style.display='none'; } \">\n";
			$html .= "<option value=''></option>\n";

			$sql = "SELECT distinct($field_name) as $field_name FROM $table_name $sql_where_optional ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			if ($result_count > 0) { //if user account exists then show login
				//print_r($result);
				foreach($result as $field) {
					if (strlen($field[$field_name]) > 0) {
						if ($field_current_value == $field[$field_name]) {
							$html .= "<option value=\"".$field[$field_name]."\" selected>".$field[$field_name]."</option>\n";
						}
						else {
							$html .= "<option value=\"".$field[$field_name]."\">".$field[$field_name]."</option>\n";
						}
					}
				}
			}
			unset($sql, $result, $result_count);

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
		function html_select($db, $table_name, $field_name, $sql_where_optional, $field_current_value, $field_value = '', $style = '') {
			//html select other : build a select box from distinct items in db with option for other
			global $domain_uuid;

			if (strlen($field_value) > 0) {
			$html .= "<select id=\"".$field_value."\" name=\"".$field_value."\" class='formfld' style='".$style."'>\n";
			$html .= "<option value=\"\"></option>\n";
				$sql = "SELECT distinct($field_name) as $field_name, $field_value FROM $table_name $sql_where_optional order by $field_name asc ";
			}
			else {
				$html .= "<select id=\"".$field_name."\" name=\"".$field_name."\" class='formfld' style='".$style."'>\n";
				$html .= "<option value=\"\"></option>\n";
				$sql = "SELECT distinct($field_name) as $field_name FROM $table_name $sql_where_optional ";
			}

			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			if ($result_count > 0) { //if user account exists then show login
				foreach($result as $field) {
					if (strlen($field[$field_name]) > 0) {
						if ($field_current_value == $field[$field_name]) {
							if (strlen($field_value) > 0) {
								$html .= "<option value=\"".$field[$field_value]."\" selected>".$field[$field_name]."</option>\n";
							}
							else {
								$html .= "<option value=\"".$field[$field_name]."\" selected>".$field[$field_name]."</option>\n";
							}
						}
						else {
							if (strlen($field_value) > 0) {
								$html .= "<option value=\"".$field[$field_value]."\">".$field[$field_name]."</option>\n";
							}
							else {
								$html .= "<option value=\"".$field[$field_name]."\">".$field[$field_name]."</option>\n";
							}
						}
					}
				}
			}
			unset($sql, $result, $result_count);
			$html .= "</select>\n";

		return $html;
		}
	}
	//$table_name = 'v_templates'; $field_name = 'templatename'; $sql_where_optional = "where domain_uuid = '$domain_uuid' "; $field_current_value = '';
	//echo html_select($db, $table_name, $field_name, $sql_where_optional, $field_current_value);

	if (!function_exists('html_select_on_change')) {
		function html_select_on_change($db, $table_name, $field_name, $sql_where_optional, $field_current_value, $onchange, $field_value = '') {
			//html select other : build a select box from distinct items in db with option for other
			global $domain_uuid;

			$html .= "<select id=\"".$field_name."\" name=\"".$field_name."\" class='formfld' onchange=\"".$onchange."\">\n";
			$html .= "<option value=''></option>\n";

			$sql = "SELECT distinct($field_name) as $field_name FROM $table_name $sql_where_optional order by $field_name asc ";
			//echo $sql;
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			//echo $result_count;
			if ($result_count > 0) { //if user account exists then show login
				//print_r($result);
				foreach($result as $field) {
					if (strlen($field[$field_name]) > 0) {
						if ($field_current_value == $field[$field_name]) {
								if (strlen($field_value) > 0) {
									$html .= "<option value=\"".$field[$field_value]."\" selected>".$field[$field_name]."</option>\n";
								}
								else {
									$html .= "<option value=\"".$field[$field_name]."\" selected>".$field[$field_name]."</option>\n";
								}
						}
						else {
								if (strlen($field_value) > 0) {
									$html .= "<option value=\"".$field[$field_value]."\">".$field[$field_name]."</option>\n";
								}
								else {
									$html .= "<option value=\"".$field[$field_name]."\">".$field[$field_name]."</option>\n";
								}
						}
					}
				}
			}
			unset($sql, $result, $result_count);
			$html .= "</select>\n";

		return $html;
		}
	}

	if (!function_exists('th_order_by')) {
		//html table header order by
		function th_order_by($field_name, $columntitle, $order_by, $order, $app_uuid = '', $css = '', $additional_get_params='') {
			if (strlen($app_uuid) > 0) { $app_uuid = "&app_uuid=".$app_uuid; }	// accomodate need to pass app_uuid where necessary (inbound/outbound routes lists)
			if (strlen($additional_get_params) > 0) {$additional_get_params = '&'.$additional_get_params; } // you may need to pass other parameters
			$html = "<th ".$css." nowrap>";
			if (strlen($order_by)==0) {
				$html .= "<a href='?order_by=$field_name&order=desc".$app_uuid."$additional_get_params' title='ascending'>$columntitle</a>";
			}
			else {
				if ($order=="asc") {
					$html .= "<a href='?order_by=$field_name&order=desc".$app_uuid."$additional_get_params' title='ascending'>$columntitle</a>";
				}
				else {
					$html .= "<a href='?order_by=$field_name&order=asc".$app_uuid."$additional_get_params' title='descending'>$columntitle</a>";
				}
			}
			$html .= "</th>";
			return $html;
		}
	}
	////example usage
		//$table_name = 'tblcontacts'; $field_name = 'contactcategory'; $sql_where_optional = "", $field_current_value ='';
		//echo html_select_other($db, $table_name, $field_name, $sql_where_optional, $field_current_value);
	////  On the page that recieves the POST
		//if (check_str($_POST["contactcategory"]) == "Other") { //echo "found: ".$contactcategory;
		//  $contactcategory = check_str($_POST["contactcategoryother"]);
		//}

	if (!function_exists('log_add')) {
		function log_add($db, $log_type, $log_status, $log_desc, $log_add_user, $log_add_user_ip) {
			return; //this disables the function
			global $domain_uuid;

			$sql = "insert into logs ";
			$sql .= "(";
			$sql .= "log_type, ";
			$sql .= "log_status, ";
			$sql .= "log_desc, ";
			$sql .= "log_add_user, ";
			$sql .= "log_add_user_ip, ";
			$sql .= "log_add_date ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$log_type', ";
			$sql .= "'$log_status', ";
			$sql .= "'$log_desc', ";
			$sql .= "'$log_add_user', ";
			$sql .= "'$log_add_user_ip', ";
			$sql .= "now() ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
	}
	//$log_type = ''; $log_status=''; $log_add_user=''; $log_desc='';
	//log_add($db, $log_type, $log_status, $log_desc, $log_add_user, $_SERVER["REMOTE_ADDR"]);

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

					if ($file_size ==  0){
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
									break;
								case "png":
									break;
								case "gif":
									break;
								case "bmp":
									break;
								case "psd":
									break;
								case "tif":
									break;
								default:
									return false;
							}
					}
					if ($file_type == "file") {
						switch (strtolower($file_ext)) {
							case "doc":
								break;
							case "pdf":
								break;
							case "ppt":
								break;
							case "xls":
								break;
							case "zip":
								break;
							case "exe":
								break;
							default:
								return false;
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

						if (move_uploaded_file($tmp_name, $dest_dir.'/'.$file_name)){
							 return $file_name;
						}
						else {
							echo "File upload failed!  Here's some debugging info:\n";
							return false;
						}
						exit;

			} //end function
	}

	if ( !function_exists('sys_get_temp_dir')) {
		function sys_get_temp_dir() {
			if( $temp=getenv('TMP') )        return $temp;
			if( $temp=getenv('TEMP') )        return $temp;
			if( $temp=getenv('TMPDIR') )    return $temp;
			$temp=tempnam(__FILE__,'');
			if (file_exists($temp)) {
				unlink($temp);
				return dirname($temp);
			}
			return null;
		}
	}
	//echo realpath(sys_get_temp_dir());

	if (!function_exists('username_exists')) {
		function username_exists($username) {
			global $db, $domain_uuid;
			$sql = "select * from v_users ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and username = '".$username."' ";
			//$sql .= "and user_enabled = 'true' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			if ($result_count > 0) {
				return true;
			}
			else {
				return false;
			}
		}
	}

	if (!function_exists('add_extension_user')) {
		function add_extension_user($extension_uuid, $username) {
			global $db, $domain_uuid;
			//get the user_uuid by using the username
				$sql = "select * from v_users ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and username = '$username' ";
				//$sql .= "and user_enabled = 'true' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset($prep_statement);
				foreach ($result as &$row) {
					//check if the user_uuid exists in v_extension_users
						$sql = "select * from v_extension_users ";
						$sql .= "where domain_uuid = '$domain_uuid' ";
						$sql .= "and user_uuid = '".$row["user_uuid"]."' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$extension_users_result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						unset($prep_statement);
					//assign the extension to the user
						if (count($extension_users_result) == 0) {
							$sql = "insert into v_extension_users ";
							$sql .= "(";
							$sql .= "domain_uuid, ";
							$sql .= "extension_uuid, ";
							$sql .= "user_uuid ";
							$sql .= ") ";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'$domain_uuid', ";
							$sql .= "'$extension_uuid', ";
							$sql .= "'".$row["user_uuid"]."' ";
							$sql .= ")";
							$db->exec(check_sql($sql));
							unset($sql);
						}
				}
				unset ($result);
		}
	}

	if (!function_exists('user_add')) {
		function user_add($username, $password, $user_email='') {
			global $db, $domain_uuid, $v_salt;
			$user_uuid = uuid();
			if (strlen($username) == 0) { return false; }
			if (strlen($password) == 0) { return false; }
			if (!username_exists($username)) {
				//salt used with the password to create a one way hash
					$salt = generate_password('20', '4');
				//add the user account
					$user_type = 'Individual';
					$user_category = 'user';
					$sql = "insert into v_users ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "user_uuid, ";
					$sql .= "username, ";
					$sql .= "password, ";
					$sql .= "salt, ";
					if (strlen($user_email) > 0) { $sql .= "user_email, "; }
					$sql .= "add_date, ";
					$sql .= "add_user ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$user_uuid', ";
					$sql .= "'$username', ";
					$sql .= "'".md5($salt.$password)."', ";
					$sql .= "'$salt', ";
					if (strlen($user_email) > 0) { $sql .= "'$user_email', "; }
					$sql .= "now(), ";
					$sql .= "'".$_SESSION["username"]."' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

				//add the user to the member group
					$group_name = 'user';
					$sql = "insert into v_group_users ";
					$sql .= "(";
					$sql .= "group_user_uuid, ";
					$sql .= "domain_uuid, ";
					$sql .= "group_name, ";
					$sql .= "user_uuid ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$group_name', ";
					$sql .= "'$user_uuid' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
			} //end if !username_exists
		} //end function definition
	} //end function_exists

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
		if (is_numeric($phone_number)) {
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
		return $phone_number;
	}

//browser detection without browscap.ini dependency
	function http_user_agent($info = '') {
		$u_agent = $_SERVER['HTTP_USER_AGENT'];
		$bname = 'Unknown';
		$platform = 'Unknown';
		$version= "";

		//get the platform?
			if (preg_match('/linux/i', $u_agent)) {
				$platform = 'linux';
			}
			elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
				$platform = 'mac';
			}
			elseif (preg_match('/windows|win32/i', $u_agent)) {
				$platform = 'windows';
			}

		//get the name of the useragent yes seperately and for good reason
			if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
			{
				$bname = 'Internet Explorer';
				$ub = "MSIE";
			}
			elseif(preg_match('/Firefox/i',$u_agent))
			{
				$bname = 'Mozilla Firefox';
				$ub = "Firefox";
			}
			elseif(preg_match('/Chrome/i',$u_agent))
			{
				$bname = 'Google Chrome';
				$ub = "Chrome";
			}
			elseif(preg_match('/Safari/i',$u_agent))
			{
				$bname = 'Apple Safari';
				$ub = "Safari";
			}
			elseif(preg_match('/Opera/i',$u_agent))
			{
				$bname = 'Opera';
				$ub = "Opera";
			}
			elseif(preg_match('/Netscape/i',$u_agent))
			{
				$bname = 'Netscape';
				$ub = "Netscape";
			}

		//finally get the correct version number
			$known = array('Version', $ub, 'other');
			$pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
			if (!preg_match_all($pattern, $u_agent, $matches)) {
				// we have no matching number just continue
			}

		// see how many we have
			$i = count($matches['browser']);
			if ($i != 1) {
				//we will have two since we are not using 'other' argument yet
				//see if version is before or after the name
				if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
					$version= $matches['version'][0];
				}
				else {
					$version= $matches['version'][1];
				}
			}
			else {
				$version= $matches['version'][0];
			}

		// check if we have a number
			if ($version==null || $version=="") {$version="?";}

		switch ($info) {
			case "agent": return $u_agent; break;
			case "name": return $bname; break;
			case "version": return $version; break;
			case "platform": return $platform; break;
			case "pattern": return $pattern; break;
			default :
				return array(
					'userAgent' => $u_agent,
					'name' => $bname,
					'version' => $version,
					'platform' => $platform,
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
			while($chunklen > 0)
			{
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
					}else   fseek($fp, $position-$chunklen);
					$position = $position - $chunklen;
			}
			fclose($fp);
			return $ret;
	}

//generate a random password with upper, lowercase and symbols
	function generate_password($length = 0, $strength = 0) {
		$password = '';
		$charset = '';
		if ($length === 0 && $strength === 0) { //set length and strenth if specified in default settings and strength isn't numeric-only
			$length = (is_numeric($_SESSION["security"]["password_length"]["var"])) ? $_SESSION["security"]["password_length"]["var"] : 10;
			$strength = (is_numeric($_SESSION["security"]["password_strength"]["var"])) ? $_SESSION["security"]["password_strength"]["var"] : 4;
		}
		if ($strength >= 1) { $charset .= "0123456789"; }
		if ($strength >= 2) { $charset .= "abcdefghijkmnopqrstuvwxyz";	}
		if ($strength >= 3) { $charset .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";	}
		if ($strength >= 4) { $charset .= "!!!!!^$%*?....."; }
		srand((double)microtime() * rand(1000000, 9999999));
		while ($length > 0) {
				$password .= $charset[rand(0, strlen($charset)-1)];
				$length--;
		}
		return $password;
	}
	//echo generate_password(4, 4);

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
			$result = '';
			if (trim(strtoupper($tmp_array[0])) != "+OK") {
				$tmp_field_name_array = explode ($tmp_delimiter, $tmp_array[0]);
				$x = 0;
				foreach ($tmp_array as $row) {
					if ($x > 0) {
						$tmp_field_value_array = explode ($tmp_delimiter, $tmp_array[$x]);
						$y = 0;
						foreach ($tmp_field_value_array as $tmp_value) {
							$tmp_name = $tmp_field_name_array[$y];
							if (trim(strtoupper($tmp_value)) != "+OK") {
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
			$regex = '/^[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}$/';
			if ($email != "" && preg_match($regex, $email) == 1) {
				return true; // email address has valid syntax
			}
			else {
				return false; // email address does not have valid syntax
			}
		}
	}

// ellipsis nicely truncate long text
	if(!function_exists('ellipsis')) {
		function ellipsis($string, $max_characters, $preserve_word = true) {
			if ($max_characters+$x >= strlen($string)) { return $string; }
			if ($preserve_word) {
				for ($x = 0; $x < strlen($string); $x++) {
					if ($string{$max_characters+$x} == " ") {
						return substr($string,0,$max_characters+$x)." ...";
					}
					else { continue; }
				}
			}
			else {
				return substr($string,0,$max_characters)." ...";
			}
		}
	}

//function to show the list of sound files
	if (!function_exists('recur_sounds_dir')) {
		function recur_sounds_dir($dir) {
			global $dir_array;
			global $dir_path;
			$dir_list = opendir($dir);
			while ($file = readdir ($dir_list)) {
				if ($file != '.' && $file != '..') {
					$newpath = $dir.'/'.$file;
					$level = explode('/',$newpath);
					if (substr($newpath, -4) == ".svn" ||
						substr($newpath, -4) == ".git") {
						//ignore .svn and .git dir and subdir
					}
					else {
						if (is_dir($newpath)) { //directories
							recur_sounds_dir($newpath);
						}
						else { //files
							if (strlen($newpath) > 0) {
								//make the path relative
									$relative_path = substr($newpath, strlen($dir_path), strlen($newpath));
								//remove the 8000-48000 khz from the path
									$relative_path = str_replace("/8000/", "/", $relative_path);
									$relative_path = str_replace("/16000/", "/", $relative_path);
									$relative_path = str_replace("/32000/", "/", $relative_path);
									$relative_path = str_replace("/48000/", "/", $relative_path);
								//remove the default_language, default_dialect, and default_voice (en/us/callie) from the path
									$file_array = explode( "/", $relative_path );
									$x = 1;
									$relative_path = '';
									foreach( $file_array as $tmp) {
										if ($x == 5) { $relative_path .= $tmp; }
										if ($x > 5) { $relative_path .= '/'.$tmp; }
										$x++;
									}
								//add the file if it does not exist in the array
									if (isset($dir_array[$relative_path])) {
										//already exists
									}
									else {
										//add the new path
											if (strlen($relative_path) > 0) { $dir_array[$relative_path] = '0'; }
									}
							}
						}
					}
				}
			}
			ksort($dir_array, SORT_STRING);
			closedir($dir_list);
		}
	}

//function to convert hexidecimal color value to rgb value
	if (!function_exists('hex2rgb')) {
		function hex2rgb($hex, $delim = '') {
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

//function to send email
	if (!function_exists('send_email')) {
		function send_email($eml_recipients, $eml_subject, $eml_body, &$eml_error = '', $eml_from_address = '', $eml_from_name = '', $eml_priority = 3) {
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


			ERROR RESPONSE:

				Error messages are stored in the variable passed into $eml_error BY REFERENCE

			*/

			include_once("resources/phpmailer/class.phpmailer.php");
			include_once("resources/phpmailer/class.smtp.php");

			$regexp = '/^[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}$/';

			$mail = new PHPMailer();
			$mail -> IsSMTP();
			$mail -> Host = $_SESSION['email']['smtp_host']['var'];
			if ($_SESSION['email']['smtp_port']['var'] != '') {
				$mail -> Port = $_SESSION['email']['smtp_port']['var'];
			}
			if ($_SESSION['email']['smtp_auth']['var'] == "true") {
				$mail -> SMTPAuth = $_SESSION['email']['smtp_auth']['var'];
			}
			if ($_SESSION['email']['smtp_username']['var']) {
				$mail -> Username = $_SESSION['email']['smtp_username']['var'];
				$mail -> Password = $_SESSION['email']['smtp_password']['var'];
			}
			if ($_SESSION['email']['smtp_secure']['var'] == "none") {
				$_SESSION['email']['smtp_secure']['var'] = '';
			}
			if ($_SESSION['email']['smtp_secure']['var'] != '') {
				$mail -> SMTPSecure = $_SESSION['email']['smtp_secure']['var'];
			}
			$eml_from_address = ($eml_from_address != '') ? $eml_from_address : $_SESSION['email']['smtp_from']['var'];
			$eml_from_name = ($eml_from_name != '') ? $eml_from_name : $_SESSION['email']['smtp_from_name']['var'];
			$mail -> SetFrom($eml_from_address, $eml_from_name);
			$mail -> AddReplyTo($eml_from_address, $eml_from_name);
			$mail -> Subject = $eml_subject;
			$mail -> MsgHTML($eml_body);
			$mail -> Priority = $eml_priority;

			$address_found = false;

			if (!is_array($eml_recipients)) { // must be a single or delimited recipient address(s)
				$eml_recipients = str_replace(' ', '', $eml_recipients);
				if (substr_count(',', $eml_recipients)) { $delim = ','; }
				if (substr_count(';', $eml_recipients)) { $delim = ';'; }
				if ($delim) { $eml_recipients = explode($delim, $eml_recipients); } // delimiter found, convert to array of addresses
			}

			if (is_array($eml_recipients)) { // check if multiple recipients
				foreach ($eml_recipients as $eml_recipient) {
					if (is_array($eml_recipient)) { // check if each recipient has multiple fields
						if ($eml_recipient["address"] != '' && preg_match($regexp, $eml_recipient["address"]) == 1) { // check if valid address
							switch ($eml_recipient["delivery"]) {
								case "cc" :		$mail -> AddCC($eml_recipient["address"], ($eml_recipient["name"]) ? $eml_recipient["name"] : $eml_recipient["address"]);			break;
								case "bcc" :	$mail -> AddBCC($eml_recipient["address"], ($eml_recipient["name"]) ? $eml_recipient["name"] : $eml_recipient["address"]);			break;
								default :		$mail -> AddAddress($eml_recipient["address"], ($eml_recipient["name"]) ? $eml_recipient["name"] : $eml_recipient["address"]);
							}
							$address_found = true;
						}
					}
					else if ($eml_recipient != '' && preg_match($regexp, $eml_recipient) == 1) { // check if recipient value is simply (only) an address
						$mail -> AddAddress($eml_recipient);
						$address_found = true;
					}
				}

				if (!$address_found) {
					$eml_error = "No valid e-mail address provided.";
					return false;
				}

			}
			else { // just a single e-mail address found, not an array of addresses
				if ($eml_recipients != '' && preg_match($regexp, $eml_recipients) == 1) { // check if email syntax is valid
					$mail -> AddAddress($eml_recipients);
				}
				else {
					$eml_error = "No valid e-mail address provided.";
					return false;
				}
			}

			if (!$mail -> Send()) {
				$eml_error = $mail -> ErrorInfo;
				return false;
			}
			else {
				return true;
			}

			$mail	->	ClearAddresses();
			$mail	->	SmtpClose();

			unset($mail);
		}
	}

//encrypt a string
	if (!function_exists('encrypt')) {
		function encrypt($key, $str_to_enc) {
			return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $str_to_enc, MCRYPT_MODE_CBC, md5(md5($key))));
		}
	}

//decrypt a string
	if (!function_exists('decrypt')) {
		function decrypt($key, $str_to_dec) {
			return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($str_to_dec), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
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
		function img_spacer($width = '1px', $height = '1px', $border = 'none') {
			return "<img src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' style='width: ".$width."; height: ".$height."; border: ".$border.";'>";
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

?>
