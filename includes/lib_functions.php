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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

	if (!function_exists('software_version')) {
		function software_version() {
			return '3.3';
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

			$html  = "<table width='50%' border='0' cellpadding='1' cellspacing='0'>\n";
			$html .= "<tr>\n";
			$html .= "<td id=\"cell".$field_name."1\" width='100%'>\n";
			$html .= "\n";
			$html .= "<select id=\"".$field_name."\" name=\"".$field_name."\" class='formfld' style='width: 100%;' onchange=\"if (document.getElementById('".$field_name."').value == 'Other') { /*enabled*/ document.getElementById('".$field_name."_other').style.width='95%'; document.getElementById('cell".$field_name."2').width='70%'; document.getElementById('cell".$field_name."1').width='30%'; document.getElementById('".$field_name."_other').disabled = false; document.getElementById('".$field_name."_other').className='txt'; document.getElementById('".$field_name."_other').focus(); } else { /*disabled*/ document.getElementById('".$field_name."_other').value = ''; document.getElementById('cell".$field_name."1').width='95%'; document.getElementById('cell".$field_name."2').width='5%'; document.getElementById('".$field_name."_other').disabled = true; document.getElementById('".$field_name."_other').className='frmdisabled' } \">\n";
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
			$html .= "<input id=\"".$field_name."_other\" name=\"".$field_name."_other\" value='' style='width: 5%;' disabled onload='document.getElementById('".$field_name."_other').disabled = true;' type='text' class='frmdisabled'>\n";
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
		function th_order_by($field_name, $columntitle, $order_by, $order) {

			$html = "<th nowrap>&nbsp; &nbsp; ";
			if (strlen($order_by)==0) {
				$html .= "<a href='?order_by=$field_name&order=desc' title='ascending'>$columntitle</a>";
			}
			else {
				if ($order=="asc") {
					$html .= "<a href='?order_by=$field_name&order=desc' title='ascending'>$columntitle</a>";
				}
				else {
					$html .= "<a href='?order_by=$field_name&order=asc' title='descending'>$columntitle</a>";
				}
			}
			$html .= "&nbsp; &nbsp; </th>";
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
		if (strlen($_SESSION["format_phone_array"]) == 0) {
			$_SESSION["format_phone_array"] = ""; //clear the menu
			global $domain_uuid, $db;
			$sql = "select * from v_vars ";
			$sql .= "where var_name = 'format_phone' ";
			$sql .= "and var_enabled = 'true' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$_SESSION["format_phone_array"][] = $row["var_value"];
				}
				unset ($prep_statement);
			}
		}
		foreach ($_SESSION["format_phone_array"] as &$format) {
			$format_count = substr_count($format, 'x');
			$format_count = $format_count + substr_count($format, 'R');
			if ($format_count == strlen($phone_number)) {
				//format the number
				$phone_number = format_string($format, $phone_number);
			}
		}
		return $phone_number;
	}

//browser detection without browscap.ini dependency
	function http_user_agent() { 
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

		return array(
			'userAgent' => $u_agent,
			'name'      => $bname,
			'version'   => $version,
			'platform'  => $platform,
			'pattern'    => $pattern
		);
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
	function generate_password($length = 10, $strength = 4) {
		$password = '';
		$charset = '';
		if ($strength >= 1) { $charset .= "0123456789"; }
		if ($strength >= 2) { $charset .= "abcdefghijkmnopqrstuvwxyz";	}
		if ($strength >= 3) { $charset .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";	}
		if ($strength >= 4) { $charset .= "!!!!!^$%*?....."; }
		srand((double)microtime() * rand(1000000, 9999999));
		while ($length > 0) {
				$password.= $charset[rand(0, strlen($charset)-1)];
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

?>