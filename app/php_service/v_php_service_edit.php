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
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('php_service_add') || permission_exists('php_service_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

/*
function pkg_is_service_running($servicename) {
	exec("/bin/ps ax | awk '{ print $5 }'", $psout);
	array_shift($psout);
	foreach($psout as $line) {
		$ps[] = trim(array_pop(explode(' ', array_pop(explode('/', $line)))));
	}
	if(is_service_running($servicename, $ps) or is_process_running($servicename) ) {
		return true;
	}
	else {
		return false;
	}
}

function byte_convert( $bytes ) {
	if ($bytes<=0)
		return '0 Byte';

	$convention=1000; //[1000->10^x|1024->2^x]
	$s=array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB');
	$e=floor(log($bytes,$convention));
	return round($bytes/pow($convention,$e),2).' '.$s[$e];
}
*/

function php_services_sync_package_php() {
	global $db, $domain_uuid, $startup_script_dir, $tmp_dir;
	$sql = "";
	$sql .= "select * from v_php_services ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$tmp_prep_statement = $db->prepare(check_sql($sql));
	$tmp_prep_statement->execute();
	$tmp_result = $tmp_prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($tmp_result as &$row) {
		$service_name = $row["service_name"];
		$tmp_service_name = str_replace(" ", "_", $service_name);
		$service_script = base64_decode($row["service_script"]);
		//$service_enabled = $row["service_enabled"];
		$service_description = $row["service_description"];
		$php_service_file = "php_service_".$tmp_service_name.".php";

		if ($row['service_enabled'] == "false") {
			//delete the php service file
				unlink($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/secure/php_service_'.$tmp_service_name.'.php');
			//delete the start up script
				unlink($startup_script_dir.'/php_service_'.$tmp_service_name.'.sh');
			//delete the pid file
				unlink($tmp_dir.'/php_service_'.$tmp_service_name.'.pid');
		}
		else {
			//write the php service
				$tmp = "<?php\n";
				$tmp .= "// name: ".$service_name." \n";
				$tmp .= "// description: ".$service_description." \n";
				$tmp .= "\n";
				$tmp .= "// set time limit to indefinite execution\n";
				$tmp .= "set_time_limit (0);\n";
				$tmp .= "\n";
				$tmp .= "//run this program as long as the pid file exists\n";
				$tmp .= "\$filename = '".$tmp_dir."/php_service_".$tmp_service_name.".pid';\n";
				$tmp .= "\$fp = fopen(\$filename, 'w');\n";
				$tmp .= "fwrite(\$fp, getmypid());\n";
				$tmp .= "fclose(\$fp);\n";
				$tmp .= "chmod(\"".$tmp_dir."/php_service_".$tmp_service_name.".pid\", 0776);\n";
				$tmp .= "unset(\$filename);\n";
				$tmp .= "\n";

				//$tmp .= "//require_once(\"config.inc\");\n";
				//$tmp .= "//global \$config;\n";
				//$tmp .= "//\$sys_log_address = \$config['syslog']['remoteserver'];\n";
				//$tmp .= "\$sys_log_address = \"127.0.0.1\";\n";
				//$tmp .= "\$syslogport = 514;\n";
				//$tmp .= "echo \"syslog server: \".\$sys_log_address.\"\\n\";\n";
				//$tmp .= "\n";
				//$tmp .= "\n";
				//$tmp .= "\n";
				//$tmp .= "function send_to_syslog(\$sys_log_address, \$syslogport, \$syslogmsg) {\n";
				//$tmp .= "\n";
				//$tmp .= "  \$fp = fsockopen(\"udp://\".\$sys_log_address, \$syslogport, \$errno, \$errstr);\n";
				//$tmp .= "  if (!\$fp) {\n";
				//$tmp .= "      //echo \"ERROR: \$errno - \$errstr<br />\\n\";\n";
				//$tmp .= "  } else {\n";
				//$tmp .= "      fwrite(\$fp, \$syslogmsg);\n";
				//$tmp .= "      fclose(\$fp);\n";
				//$tmp .= "  }\n";
				//$tmp .= "\n";
				//$tmp .= "}\n";
				//$tmp .= "\n";
				//$tmp .= "\n";
				//$tmp .= "//\$msg = \"1.begin loop. \".date('r').\"\\n\";\n";
				//$tmp .= "//\$fp = fopen('/tmp/".$tmp_service_name.".txt', 'a');\n";
				//$tmp .= "//fwrite(\$fp, \$msg.\"\\n\");\n";
				//$tmp .= "//fclose(\$fp);\n";

				//$tmp .= "\n";
				$tmp .= $service_script;
				$tmp .= "\n";
				$tmp .= "?>";

				$fout = fopen($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/secure/'.$php_service_file,"w");
				fwrite($fout, $tmp);
				unset($tmp);
				fclose($fout);

			//add execute permissions to the php service script
				chmod($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/secure/'.$php_service_file, 0776);

			//write the start up script
				// CYGWIN_NT-5.1
				// Darwin
				// FreeBSD
				// HP-UX
				// IRIX64
				// Linux
				// NetBSD
				// OpenBSD
				// SunOS
				// Unix
				// WIN32
				// WINNT
				// Windows
				switch (PHP_OS) {
				case "FreeBSD":
					// make sure the start up directory i set
					if (strlen($startup_script_dir) > 0) {
						$startup_script_dir = "/usr/local/etc/rc.d";
					}
					$tmp = "";
					$tmp = "#!/bin/sh\n";
					$tmp .= "# This file was automatically generated\n";
					$tmp .= "# by the PHP Service handler.\n";
					$tmp .= "# \n";
					$tmp .= "# Copy this script to the startup directory.\n";
					$tmp .= "# cp -a ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/php_service_".$tmp_service_name.".sh ".$startup_script_dir."/php_service_".$tmp_service_name.".sh";
					$tmp .= "# \n";
					$tmp .= "# Usage: ./php_service_".$tmp_service_name.".sh {start|stop|restart}\n";
					$tmp .= "# ".$startup_script_dir."/./php_service_".$tmp_service_name.".sh start";
					$tmp .= "\n";
					$tmp .= "\n";
					$tmp .= "rc_start() {\n";
					$tmp .= "	".PHP_BINDIR."/php ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/".$php_service_file." >> /var/log/php_service_".$tmp_service_name.".log &\n";
					$tmp .= "}\n";
					$tmp .= "\n";
					$tmp .= "rc_stop() {\n";
					$tmp .= "	rm ".$tmp_dir."/php_service_".$tmp_service_name.".pid\n";
					$tmp .= "}\n";
					$tmp .= "\n";
					$tmp .= "case \"\$1\" in\n";
					$tmp .= "	start)\n";
					$tmp .= "		echo \"Starting the service. \"\n";
					$tmp .= "		rc_start\n";
					$tmp .= "		;;\n";
					$tmp .= "	stop)\n";
					$tmp .= "		echo \"Stopping the service. \"\n";
					$tmp .= "		rc_stop\n";
					$tmp .= "		;;\n";
					$tmp .= "	restart)\n";
					$tmp .= "		echo \"Restarting the service. \"\n";
					$tmp .= "		rc_stop\n";
					$tmp .= "		rc_start\n";
					$tmp .= "		;;\n";
					$tmp .= "	*)\n";
					$tmp .= "		echo \"Usage: ".$startup_script_dir."/php_service_".$tmp_service_name.".sh {start|stop|restart}\"\n";
					$tmp .= "		exit 1\n";
					$tmp .= "		;;\n";
					$tmp .= "esac\n";
					$fout = fopen($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/php_service_".$tmp_service_name.".sh","w");
					fwrite($fout, $tmp);
					unset($tmp);
					fclose($fout);

					//add execute permissions to the start script
						chmod($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/php_service_".$tmp_service_name.".sh", 0755);

					break;
				default:
					// make sure the start up directory i set
					if (strlen($startup_script_dir) > 0) {
						$startup_script_dir = "/etc/init.d";
					}
					$tmp = "";
					$tmp .= "#!/bin/sh\n";
					$tmp .= "# /etc/init.d/".$tmp_service_name."\n";
					$tmp .= "# This file was automatically generated\n";
					$tmp .= "# by the PHP Service handler.\n";
					$tmp .= "# \n";
					$tmp .= "# Copy this script to the startup directory.\n";
					$tmp .= "# cp -a ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/php_service_".$tmp_service_name.".sh ".$startup_script_dir."/php_service_".$tmp_service_name.".sh";
					$tmp .= "# \n";
					$tmp .= "# Usage: ./php_service_".$tmp_service_name.".sh {start|stop|restart}\n";
					$tmp .= "# ".$startup_script_dir."/./php_service_".$tmp_service_name.".sh start";
					$tmp .= "\n";
					$tmp .= "\n";
					$tmp .= "rc_start() {\n";
					$tmp .= "	".PHP_BINDIR."/php ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/".$php_service_file." >> /var/log/".tmp_service_name.".log &\n";
					$tmp .= "}\n";
					$tmp .= "\n";
					$tmp .= "rc_stop() {\n";
					$tmp .= "	rm ".$tmp_dir."/php_service_".$tmp_service_name.".pid\n";
					$tmp .= "}\n";
					$tmp .= "\n";
					$tmp .= "case \"\$1\" in\n";
					$tmp .= "	start)\n";
					$tmp .= "		echo \"Starting the service. \"\n";
					$tmp .= "		rc_start\n";
					$tmp .= "		;;\n";
					$tmp .= "	stop)\n";
					$tmp .= "		echo \"Stopping the service. \"\n";
					$tmp .= "		rc_stop\n";
					$tmp .= "		;;\n";
					$tmp .= "	restart)\n";
					$tmp .= "		echo \"Restarting the service. \"\n";
					$tmp .= "		rc_stop\n";
					$tmp .= "		rc_start\n";
					$tmp .= "		;;\n";
					$tmp .= "	*)\n";
					$tmp .= "		echo \"Usage: ".$startup_script_dir."/".$tmp_service_name.".sh {start|stop|restart}\"\n";
					$tmp .= "		exit 1\n";
					$tmp .= "		;;\n";
					$tmp .= "esac\n";
					$tmp .= "\n";
					$tmp .= "exit 0";
					$fout = fopen($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/php_service_".$tmp_service_name.".sh","w");
					fwrite($fout, $tmp);
					unset($tmp);
					fclose($fout);

					//add execute permissions to the start script
						chmod($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/php_service_".$tmp_service_name.".sh", 0755);
				}
		} //end if enabled
	}
}

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$php_service_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//set the http values to variabless
if (count($_POST)>0) {
	$service_name = check_str($_POST["service_name"]);
	$service_script = $_POST["service_script"];
	$service_enabled = check_str($_POST["service_enabled"]);
	$service_description = check_str($_POST["service_description"]);

	//set defaults
	$service_type = "php";

	//setup the default script
		$tmp_service_name = str_replace(" ", "_", $service_name);
		if (strlen($service_script) == 0) {
			$tmp = "\n";
			$tmp .= "\n";
			$tmp .= "\$x = 0;\n";
			$tmp .= "while(\$x == 0) {\n";
			$tmp .= "\n";
			$tmp .= "\n";
			$tmp .= "\n";
			$tmp .= "\n";
			$tmp .= "\n";
			$tmp .= "\n";
			$tmp .= "\n";
			$tmp .= "\n";
			$tmp .= "\n";
			$tmp .= "	if(!file_exists('".$tmp_dir."/php_service_".$tmp_service_name.".pid')) { return; }\n";
			$tmp .= "	usleep(1000000);  //1 000 000 microseconds = 1 second\n";
			//$tmp .= "  //if (\$x > 60){ exit; } //exit after 60 loops for testing\n";
			$tmp .= "} //end while\n";
			$service_script = $tmp;
		}
}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$php_service_uuid = check_str($_POST["php_service_uuid"]);
	}

	//check for all required data
		if (strlen($service_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		//if (strlen($service_script) == 0) { $msg .= "Please provide: Script<br>\n"; }
		if (strlen($service_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($service_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		}

	//add or update the database
	if ($_POST["persistformvar"] != "true") {
		if ($action == "add" && permission_exists('php_service_add')) {
			$php_service_uuid = uuid();
			$sql = "insert into v_php_services ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "php_service_uuid, ";
			$sql .= "service_name, ";
			$sql .= "service_script, ";
			$sql .= "service_enabled, ";
			$sql .= "service_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$php_service_uuid', ";
			$sql .= "'$service_name', ";
			$sql .= "'".base64_encode($service_script)."', ";
			$sql .= "'$service_enabled', ";
			$sql .= "'$service_description' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);

			//create the php service files
				php_services_sync_package_php();

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=v_php_service.php\">\n";
			echo "<div align='center'>\n";
			echo "Add Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		} //if ($action == "add")

		if ($action == "update" && permission_exists('php_service_edit')) {
			$sql = "update v_php_services set ";
			$sql .= "service_name = '$service_name', ";
			$sql .= "service_script = '".base64_encode($service_script)."', ";
			$sql .= "service_enabled = '$service_enabled', ";
			$sql .= "service_description = '$service_description' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and php_service_uuid = '$php_service_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);

			//create the php service files
				php_services_sync_package_php();

			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=v_php_service.php\">\n";
			echo "<div align='center'>\n";
			echo "Update Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		} //if ($action == "update")
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$php_service_uuid = $_GET["id"];
		$sql = "";
		$sql .= "select * from v_php_services ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and php_service_uuid = '$php_service_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$service_name = $row["service_name"];
			$tmp_service_name = str_replace(" ", "_", $service_name);
			$service_script = base64_decode($row["service_script"]);
			$service_enabled = $row["service_enabled"];
			$service_description = $row["service_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//include the header
	require_once "includes/header.php";

// edit area
	echo "    <script language=\"javascript\" type=\"text/javascript\" src=\"".PROJECT_PATH."/includes/edit_area/edit_area_full.js\"></script>\n";
	echo "    <!-- -->\n";

	echo "	<script language=\"Javascript\" type=\"text/javascript\">\n";
	echo "		// initialisation //load,\n";
	echo "		editAreaLoader.init({\n";
	echo "			id: \"service_script\"	// id of the textarea to transform //, |, help\n";
	if (strlen($service_script) < 3000) {
		echo "			,start_highlight: true\n";
	}
	else {
		echo "			,start_highlight: false\n";
		echo "			,display: \"later\"\n";
	}
	echo "			,font_size: \"8\"\n";
	echo "			,allow_toggle: true\n";
	echo "			,language: \"en\"\n";
	echo "			,syntax: \"html\"\n";
	echo "			,toolbar: \"search, go_to_line,|, fullscreen, |, undo, redo, |, select_font, |, syntax_selection, |, change_smooth_selection, highlight, reset_highlight, |, help\" //new_document,\n";
	echo "			,plugins: \"charmap\"\n";
	echo "			,charmap_default: \"arrows\"\n";
	echo "\n";
	echo "    });\n";
	echo "\n";
	echo "    </script>";

//show the form
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";

	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap><b>PHP Service Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap><b>PHP Service Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_php_service.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "Manages multiple dynamic and customizable services. There are many possible uses including alerts, ssh access control, scheduling commands to run, and many others uses that are yet to be discovered.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='service_name' maxlength='255' value=\"$service_name\">\n";
	echo "<br />\n";
	echo "Enter a name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Script:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' style='width: 90%;' wrap='off' rows='17' name='service_script' id='service_script' rows='4'>$service_script</textarea>\n";
	echo "<br />\n";
	echo "Enter the PHP script here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='service_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($service_enabled == "true") { 
		echo "	<option value='true' selected >true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($service_enabled == "false") { 
		echo "	<option value='false' selected >false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='service_description' maxlength='255' value=\"$service_description\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='php_service_uuid' value='$php_service_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "includes/footer.php";
?>