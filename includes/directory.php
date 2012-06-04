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
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";

if (!function_exists('phone_letter_to_number')) {
	function phone_letter_to_number($tmp) {
		if ($tmp == "a" | $tmp == "b" | $tmp == "c") { return 2; }
		if ($tmp == "d" | $tmp == "e" | $tmp == "f") { return 3; }
		if ($tmp == "g" | $tmp == "h" | $tmp == "i") { return 4; }
		if ($tmp == "j" | $tmp == "k" | $tmp == "l") { return 5; }
		if ($tmp == "m" | $tmp == "n" | $tmp == "o") { return 6; }
		if ($tmp == "p" | $tmp == "q" | $tmp == "r" | $tmp == "s") { return 7; }
		if ($tmp == "t" | $tmp == "u" | $tmp == "v") { return 8; }
		if ($tmp == "w" | $tmp == "x" | $tmp == "y" | $tmp == "z") { return 9; }
	}
}

if (!function_exists('sync_directory')) {
	function sync_directory() {

		global $domain_uuid, $db;
		$settings_array = v_settings();
		foreach($settings_array as $name => $value) {
			$$name = $value;
		}

		$tmp = "include(\"config.js\");\n";
		$tmp .= "//var sounds_dir\n";
		$tmp .= "var admin_pin = \"\";\n";
		$tmp .= "var search_type = \"\";\n";
		$tmp .= "//var tmp_dir\n";
		$tmp .= "var digitmaxlength = 0;\n";
		$tmp .= "var timeoutpin = 5000;\n";
		$tmp .= "var timeouttransfer = 5000;\n";
		$tmp .= "\n";
		$tmp .= "var dtmf = new Object( );\n";
		$tmp .= "dtmf.digits = \"\";\n";
		$tmp .= "\n";
		$tmp .= "function mycb( session, type, obj, arg ) {\n";
		$tmp .= "	try {\n";
		$tmp .= "		if ( type == \"dtmf\" ) {\n";
		$tmp .= "			console_log( \"info\", \"digit: \"+obj.digit+\"\\n\" );\n";
		$tmp .= "			if ( obj.digit == \"#\" ) {\n";
		$tmp .= "				//console_log( \"info\", \"detected pound sign.\\n\" );\n";
		$tmp .= "				exit = true;\n";
		$tmp .= "				return( false );\n";
		$tmp .= "			}\n";
		$tmp .= "			if ( obj.digit == \"*\" ) {\n";
		$tmp .= "				//console_log( \"info\", \"detected pound sign.\\n\" );\n";
		$tmp .= "				exit = true;\n";
		$tmp .= "				return( false );\n";
		$tmp .= "			}\n";
		$tmp .= "			dtmf.digits += obj.digit;\n";
		$tmp .= "			if ( dtmf.digits.length >= digitmaxlength ) {\n";
		$tmp .= "				exit = true;\n";
		$tmp .= "				return( false );\n";
		$tmp .= "			}\n";
		$tmp .= "		}\n";
		$tmp .= "	} catch (e) {\n";
		$tmp .= "		console_log( \"err\", e+\"\\n\" );\n";
		$tmp .= "	}\n";
		$tmp .= "	return( true );\n";
		$tmp .= "} //end function mycb\n";
		$tmp .= "\n";
		$tmp .= "function directory_search(search_type) {\n";
		$tmp .= "\n";
		$tmp .= "	digitmaxlength = 3;\n";
		$tmp .= "	session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-enter_person.wav\");\n";
		$tmp .= "	if (search_type == \"last_name\") {\n";
		$tmp .= "		session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-last_name.wav\", mycb, \"dtmf\");\n";
		$tmp .= "		session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-to_search_by.wav\", mycb, \"dtmf\");\n";
		$tmp .= "		session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-first_name.wav\", mycb, \"dtmf\");\n";
		$tmp .= "	}\n";
		$tmp .= "	if (search_type == \"first_name\") {\n";
		$tmp .= "		session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-first_name.wav\", mycb, \"dtmf\");\n";
		$tmp .= "		session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-to_search_by.wav\", mycb, \"dtmf\");\n";
		$tmp .= "		session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-last_name.wav\", mycb, \"dtmf\");\n";
		$tmp .= "	}\n";
		$tmp .= "	session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-press.wav\", mycb, \"dtmf\");\n";
		$tmp .= "	session.execute(\"say\", \"en name_spelled iterated 1\");\n";
		$tmp .= "	session.collectInput( mycb, dtmf, timeoutpin );\n";
		$tmp .= "	var dtmf_search = dtmf.digits;\n";
		$tmp .= "	//console_log( \"info\", \"--\" + dtmf.digits + \"--\\n\" );\n";
		$tmp .= "	if (dtmf_search == \"1\") {\n";
		$tmp .= "		//console_log( \"info\", \"press 1 detected: \" + dtmf.digits + \"\\n\" );\n";
		$tmp .= "		//console_log( \"info\", \"press 1 detected: \" + search_type + \"\\n\" );\n";
		$tmp .= "		if (search_type == \"last_name\") {\n";
		$tmp .= "			//console_log( \"info\", \"press 1 detected last_name: \" + search_type + \"\\n\" );\n";
		$tmp .= "			search_type = \"first_name\";\n";
		$tmp .= "		}\n";
		$tmp .= "		else {\n";
		$tmp .= "			//console_log( \"info\", \"press 1 detected first_name: \" + search_type + \"\\n\" );\n";
		$tmp .= "			search_type = \"last_name\";\n";
		$tmp .= "		}\n";
		$tmp .= "		dtmf_search = \"\";\n";
		$tmp .= "		dtmf.digits = \"\";\n";
		$tmp .= "		directory_search(search_type);\n";
		$tmp .= "		return;\n";
		$tmp .= "	}\n";
		$tmp .= "	console_log( \"info\", \"first 3 letters of first or last name: \" + dtmf.digits + \"\\n\" );\n";
		$tmp .= "\n";
		$tmp .= "	//session.execute(\"say\", \"en name_spelled pronounced mark\");\n";
		$tmp .= "	//<action application=\"say\" data=\"en name_spelled iterated \${destination_number}\"/>\n";
		$tmp .= "	//session.execute(\"say\", \"en number iterated 12345\");\n";
		$tmp .= "	//session.execute(\"say\", \"en number pronounced 1001\");\n";
		$tmp .= "	//session.execute(\"say\", \"en short_date_time pronounced [timestamp]\");\n";
		$tmp .= "	//session.execute(\"say\", \"en CURRENT_TIME pronounced CURRENT_TIME\");\n";
		$tmp .= "	//session.execute(\"say\", \"en CURRENT_DATE pronounced CURRENT_DATE\");\n";
		$tmp .= "	//session.execute(\"say\", \"en CURRENT_DATE_TIME pronounced CURRENT_DATE_TIME\");\n";
		$tmp .= "\n";
		$tmp .= "\n";
		$tmp .= "	//take each name and convert it to the equivalent number in php when this file is generated\n";
		$tmp .= "	//then test each number see if it matches the user dtmf search keys\n";
		$tmp .= "\n";
		$tmp .= "	var result_array = new Array();\n";
		$tmp .= "	var x = 0;\n";

		//get a list of extensions and the users assigned to them
			$sql = "";
			$sql .= " select * from v_extensions ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$x = 0;
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				//print_r($row);
				$extension = $row["extension"];
				$effective_caller_id_name = $row["effective_caller_id_name"];
				//$user_list = $row["user_list"];
				//$user_list = trim($user_list, "|");
				//echo $user_list."<br />\n";
				//$username_array = explode ("|", $user_list);
				//print_r($username_array);
				foreach ($username_array as &$username) {
					if (strlen($username) > 0) {
						$sql = "";
						$sql .= "select * from v_users ";
						$sql .= "where domain_uuid = '$domain_uuid' ";
						$sql .= "and username = '$username' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$tmp_result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						foreach ($tmp_result as &$row_tmp) {
							$user_first_name = $row_tmp["user_first_name"];
							$user_last_name = $row_tmp["user_last_name"];
							if ($user_first_name == "na") { $user_first_name = ""; }
							if ($user_last_name == "na") { $user_last_name = ""; }
							if (strlen($user_first_name.$user_last_name) == 0) {
								$name_array = explode (" ", $effective_caller_id_name);
								$user_first_name = $name_array[0];
								if (count($name_array) > 1) {
									$user_last_name = $name_array[1];
								}
							}
							
							break; //limit to 1 row
						}
						$f1 = phone_letter_to_number(substr($user_first_name, 0,1)); 
						$f2 = phone_letter_to_number(substr($user_first_name, 1,1));
						$f3 = phone_letter_to_number(substr($user_first_name, 2,1));

						$l1 = phone_letter_to_number(substr($user_last_name, 0,1)); 
						$l2 = phone_letter_to_number(substr($user_last_name, 1,1));
						$l3 = phone_letter_to_number(substr($user_last_name, 2,1));

						//echo $sql." extension: $extension  firstname $user_first_name lastname $user_last_name $tmp<br />";

						$tmp .= "	if (search_type == \"first_name\" && dtmf_search == \"".$f1.$f2.$f3."\" || search_type == \"last_name\" && dtmf_search == \"".$l1.$l2.$l3."\") {\n";
						$tmp .= "		result_array[x]=new Array()\n";
						$tmp .= "		result_array[x]['first_name'] =\"".$user_first_name."\";\n";
						$tmp .= "		result_array[x]['last_name'] =\"".$user_last_name."\";\n";
						$tmp .= "		result_array[x]['extension'] = \"".$extension."\";\n";
						$tmp .= "		//console_log( \"info\", \"found: ".$user_first_name." ".$user_last_name."\\n\" );\n";
						$tmp .= "		x++;\n";
						$tmp .= "	}\n";
					}
				}
			}
			unset ($prep_statement);

		$tmp .= "\n";
		$tmp .= "\n";
		$tmp .= "	//say the number of results that matched\n";
		$tmp .= "	\$result_count = result_array.length;\n";
		$tmp .= "	session.execute(\"say\", \"en number iterated \"+\$result_count);\n";
		$tmp .= "	session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-result_match.wav\", mycb, \"dtmf\");\n";
		$tmp .= "\n";
		$tmp .= "	//clear values\n";
		$tmp .= "	dtmf_search = 0;\n";
		$tmp .= "	dtmf.digits = '';\n";
		$tmp .= "\n";
		$tmp .= "	if (\$result_count == 0) {\n";
		$tmp .= "		//session.execute(\"transfer\", \"*347 XML default\");\n";
		$tmp .= "		directory_search(search_type);\n";
		$tmp .= "		return;\n";
		$tmp .= "	}\n";
		$tmp .= "\n";
		$tmp .= "	session.execute(\"set\", \"tts_engine=flite\");\n";
		$tmp .= "	session.execute(\"set\", \"tts_voice=rms\");  //rms //kal //awb //slt\n";
		$tmp .= "	session.execute(\"set\", \"playback_terminators=#\");\n";
		$tmp .= "	//session.speak(\"flite\",\"kal\",\"Thanks for.. calling\");\n";
		$tmp .= "\n";
		$tmp .= "	i=1;\n";
		$tmp .= "	for ( i in result_array ) {\n";
		$tmp .= "\n";
		$tmp .= "		//say first name and last name is at extension 1001\n";
		$tmp .= "		//session.execute(\"speak\", result_array[i]['first_name']);\n";
		$tmp .= "		//session.execute(\"speak\", result_array[i]['last_name']);\n";
		$tmp .= "		session.execute(\"say\", \"en name_spelled pronounced \"+result_array[i]['first_name']);\n";
		$tmp .= "		session.execute(\"sleep\", \"500\");\n";
		$tmp .= "		session.execute(\"say\", \"en name_spelled pronounced \"+result_array[i]['last_name']);\n";
		$tmp .= "		session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-at_extension.wav\", mycb, \"dtmf\");\n";
		$tmp .= "		session.execute(\"say\", \"en number pronounced \"+result_array[i]['extension']);\n";
		$tmp .= "\n";
		$tmp .= "		//to select this entry press 1\n";
		$tmp .= "		session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-to_select_entry.wav\", mycb, \"dtmf\");\n";
		$tmp .= "		session.streamFile( sounds_dir+\"/en/us/callie/directory/48000/dir-press.wav\", mycb, \"dtmf\");\n";
		$tmp .= "		session.execute(\"say\", \"en number iterated 1\");\n";
		$tmp .= "\n";
		$tmp .= "		//console_log( \"info\", \"first name: \" + result_array[i]['first_name'] + \"\\n\" );\n";
		$tmp .= "		//console_log( \"info\", \"last name: \" + result_array[i]['last_name'] + \"\\n\" );\n";
		$tmp .= "		//console_log( \"info\", \"extension: \" + result_array[i]['extension'] + \"\\n\" );\n";
		$tmp .= "\n";
		$tmp .= "		//if 1 is pressed then transfer the call\n";
		$tmp .= "		dtmf.digits = session.getDigits(1, \"#\", 3000);\n";
		$tmp .= "		if (dtmf.digits == \"1\") {\n";
		$tmp .= "			console_log( \"info\", \"directory: call transfered to: \" + result_array[i]['extension'] + \"\\n\" );\n";
		$tmp .= "			session.execute(\"transfer\", result_array[i]['extension']+\" XML default\");\n";
		$tmp .= "		}\n";
		$tmp .= "\n";
		$tmp .= "	}\n";
		$tmp .= "}\n";
		$tmp .= "\n";
		$tmp .= "\n";
		$tmp .= "if ( session.ready() ) {\n";
		$tmp .= "	session.answer();\n";
		$tmp .= "	search_type = \"last_name\";\n";
		$tmp .= "	directory_search(search_type);\n";
		$tmp .= "	session.hangup(\"NORMAL_CLEARING\");\n";
		$tmp .= "}\n";
		$tmp .= "";

		//write the file
		$fout = fopen($_SESSION['switch']['scripts']['dir']."/directory.js","w");
		fwrite($fout, $tmp);
		fclose($fout);

	} //end sync_directory
} //end if function exists

sync_directory();
?>