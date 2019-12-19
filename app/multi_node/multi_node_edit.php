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
	Copyright (C) 2008-2016 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('multi_node_add') || permission_exists('multi_node_edit')) {
		//echo "access granted";exit;
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action as an add or an update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$extension_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}
	$domain_uuid = check_str($_SESSION['domain_uuid']);

//get the http values and set them as php variables
	if (count($_POST) > 0) {
		 //echo $action;exit;
		// die(print_r($_POST));
		//get the values from the HTTP POST and save them as PHP variables
		$name  						=	$_POST['name'];
		$hostname  					=	$_POST['hostname'];
		$virtualhost  				=	$_POST['virtualhost'];
		$username  					=	$_POST['username'];
		$password  					=	$_POST['password'];
		$port  						=	$_POST['port'];
		$node_priority  			=	$_POST['node_priority'];
		$exchange_name  			=	$_POST['exchange_name'];
		$exchange_type  			=	$_POST['exchange_type'];
		$circuit_breaker_ms  		=	$_POST['circuit_breaker_ms'];
		$reconnect_interval_ms  	=	$_POST['reconnect_interval_ms'];
		$send_queue_size  			=	$_POST['send_queue_size'];
		$enable_fallback_format_fields  		=	$_POST['enable_fallback_format_fields'];
		$format_fields  			=	$_POST['format_fields'];
		$event_filter  				=	$_POST['event_filter'];
		$switch_name				=	$_POST['switch_name'];
		

		if ($action == "add") {

			$sql = "INSERT INTO v_multinode 
					(multinode_uuid, domain_uuid, name, node_priority, switch_name, hostname, virtualhost, username, password, 
					port, exchange_name, exchange_type, circuit_breaker_ms, reconnect_interval_ms, 
					send_queue_size, enable_fallback_format_fields, format_fields, event_filter)";
			$sql .= "VALUES 
			('".uuid()."', '".$domain_uuid."', '".$name."', '".$node_priority."', '".$switch_name."', '".$hostname."',
			'".$virtualhost."', '".$username."', '".$password."', '".$port."', '".$exchange_name."', '".$exchange_type."',
			'".$circuit_breaker_ms."', '".$reconnect_interval_ms."', '".$send_queue_size."', '".$enable_fallback_format_fields."',
			 '".$format_fields."', '".$event_filter."'
			) ";
			
			// die();
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();									
			}
			unset($prep_statement, $sql);

					//$event_socket_ip_address = '127.0.0.1';
                                        $cmd = "api switchname";
                                        $response = trim(event_socket_request_cmd($cmd));
                                        unset($cmd);
                                        if ( $switch_name == $response){
                                                save_amqp_xml($response);
                                                $cmd = "api reload mod_amqp";
                                                event_socket_request_cmd($cmd);
                                                unset($cmd);
                                        }
                                        else
                                        {
                                                echo "sorry";
                                                //save_amqp_xml();
                                        }

			header('Location: multi_node.php');
			// die("ok");
		}
	
	}


//echo $action;exit;
//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//set the domain_uuid
			if (permission_exists('extension_domain')) {
				$domain_uuid = $_POST["domain_uuid"];
			}
			else {
				$domain_uuid = $_SESSION['domain_uuid'];
			}

		//check for all required data
			$msg = '';
			if (strlen($extension) == 0) { $msg .= $text['message-required'].$text['label-extension']."<br>\n"; }
			if (permission_exists('extension_enabled')) {
				if (strlen($enabled) == 0) { $msg .= $text['message-required'].$text['label-enabled']."<br>\n"; }
			}
			//if (strlen($description) == 0) { $msg .= $text['message-required']."Description<br>\n"; }
			// if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			// 	require_once "resources/header.php";
			// 	require_once "resources/persist_form_var.php";
			// 	echo "<div align='center'>\n";
			// 	echo "<table><tr><td>\n";
			// 	echo $msg."<br />";
			// 	echo "</td></tr></table>\n";
			// 	persistformvar($_POST);
			// 	echo "</div>\n";
			// 	require_once "resources/footer.php";
			// 	return;
			// }

		//set the default user context
			if (permission_exists("extension_user_context")) {
				//allow a user assigned to super admin to change the user_context
			}
			else {
				//if the user_context was not set then set the default value
				$user_context = $_SESSION['domain_name'];
			}


		//add or update the database
			if ($_POST["persistformvar"] != "true") {

				//add the user to the database
					$user_email = '';
					if ($_SESSION["user"]["unique"]["text"] != "global") {
					if ($autogen_users == "true") {
						$auto_user = $extension;
						for ($i=1; $i<=$range; $i++) {
							$user_last_name = $auto_user;
							$user_password = generate_password();
							user_add($auto_user, $user_password, $user_email);
							$generated_users[$i]['username'] = $auto_user;
							$generated_users[$i]['password'] = $user_password;
							$auto_user++;
						}
						unset($auto_user);
					}
				}

				

			
			//assign the device to the extension 
				if ($action == "update") {

					$domain_uuid = $_SESSION['domain_uuid'];
					$multinode_uuid = $_REQUEST['multinode_uuid'];
					
					$sql = "update v_multinode set ";
					
					// $sql .= "multinode_uuid   =   '".check_str($multinode_uuid)."', ";
					// $sql .= "domain_uuid    =   '".check_str($domain_uuid)."', ";
					$sql .= "name   =   '".check_str($name)."', ";
					$sql .= "node_priority  =   '".check_str($node_priority)."', ";
					$sql .= "switch_name    =   '".check_str($switch_name)."', ";
					$sql .= "hostname   =   '".check_str($hostname)."', ";
					$sql .= "virtualhost    =   '".check_str($virtualhost)."', ";
					$sql .= "username   =   '".check_str($username)."', ";
					$sql .= "password   =   '".check_str($password)."', ";
					$sql .= "port   =   '".check_str($port)."', ";
					$sql .= "exchange_name  =   '".check_str($exchange_name)."', ";
					$sql .= "exchange_type  =   '".check_str($exchange_type)."', ";
					$sql .= "circuit_breaker_ms =   '".check_str($circuit_breaker_ms)."', ";
					$sql .= "reconnect_interval_ms  =   '".check_str($reconnect_interval_ms)."', ";
					$sql .= "send_queue_size    =   '".check_str($send_queue_size)."', ";
					$sql .= "enable_fallback_format_fields  =   '".check_str($enable_fallback_format_fields)."', ";
					$sql .= "format_fields  =   '".check_str($format_fields)."', ";
					$sql .= "event_filter   =   '".check_str($event_filter)."' ";

					$sql .= "where multinode_uuid = '".check_str($multinode_uuid)."' and ";
					$sql .= "domain_uuid = '".check_str($domain_uuid)."'";

					// echo $sql;

					$db->exec(check_sql($sql));
					
					// die(print_r($_REQUEST));

					unset($sql);

					//check the permissions
					// if (permission_exists('extension_add') || permission_exists('extension_edit')) {

						// die("welcome");
						//synchronize configuration
							// if (is_writable($_SESSION['switch']['multinode']['dir'])) {
								// require_once "app/extensions/resources/classes/extension.php";
								// require_once "app/multi_node/resources/classes/multinode.php";
								// $ext = new multinode;
								// $ext->xml();
	
								// die($ext);
	
								// unset($ext);
							// }
					// }

					//$event_socket_ip_address = '127.0.0.1';
				        $cmd = "api switchname";
				        $response = trim(event_socket_request_cmd($cmd));
				        unset($cmd);
					if ( $switch_name == $response){
						save_amqp_xml($response);
					        $cmd = "api reload mod_amqp";
					        event_socket_request_cmd($cmd);
					        unset($cmd);
					}
					else
					{
						echo "sorry";
						//save_amqp_xml();
					}
					header("Location: multi_node.php");
				}

			//save to the data
				// $database = new database;
				// $database->app_name = 'extensions';
				// $database->app_uuid = null;
				// $database->save($array);
				// $message = $database->message;
				//echo "<pre>".print_r($message, true)."<pre>\n";
				//exit;

			//check the permissions
				if (permission_exists('multi_node_add') || permission_exists('multi_node_edit')) {

					// die("welcome");
					// //synchronize configuration
					// 	if (is_writable($_SESSION['switch']['extensions']['dir'])) {
					// 		// require_once "app/extensions/resources/classes/extension.php";
					// 		require_once "app/multi_node/resources/classes/multinode.php";
					// 		$ext = new extension;
					// 		$ext->xml();

					// 		die($ext);

					// 		unset($ext);
					// 	}

					//write the provision files
						if (strlen($_SESSION['provision']['path']['text']) > 0) {
							if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/provision')) {
								$prov = new provision;
								$prov->domain_uuid = $domain_uuid;
								$response = $prov->write();
							}
						}

					//clear the cache
						// $cache = new cache;
						// $cache->delete("directory:".$extension."@".$user_context);
						// if (permission_exists('number_alias') && strlen($number_alias) > 0) {
						// 	$cache->delete("directory:".$number_alias."@".$user_context);
						// }
				}

			//show the action and redirect the user
				if ($action == "add") {
					//show the action and redirect the user
					//action add
					$_SESSION["message"] = $text['message-add'];
					// header("Location: multi_node_edit.php?id=".$multinode_uuid);
					header("Location: multi_node.php");
					
				}
				if ($action == "update") {
					if ($action == "update") {
						$_SESSION["message"] = $text['message-update'];
					}
					else {
						$_SESSION["message"] = $text['message-add'];
					}
					// header("Location: multi_node_edit.php?id=".$multinode_uuid);
					//synchronize settings
					//$event_socket_ip_address = '127.0.0.1';
                                        $cmd = "api switchname";
                                        $response = trim(event_socket_request_cmd($cmd));
                                        unset($cmd);
                                        if ( $switch_name == $response){
                                                save_amqp_xml($response);
                                                $cmd = "api reload mod_amqp";
                                                event_socket_request_cmd($cmd);
                                                unset($cmd);
                                        }
                                        else
                                        {
                                                echo "sorry";
                                                //save_amqp_xml();
                                        }

					//save_amqp_xml();
					header("Location: multi_node_edit.php?id=".$multinode_uuid);
					return;
				}
		} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$multinode_uuid = $_GET["id"];
		$sql = "select * from v_multinode ";
		$sql .= "where multinode_uuid = '".check_str($multinode_uuid)."' ";
		$sql .= "and domain_uuid = '".check_str($domain_uuid)."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		// die(print_r($result));
		foreach ($result as &$row) {
			$name = $row["name"];
			$node_priority = $row["node_priority"];
			$switch_name = $row["switch_name"];
			$hostname = $row["hostname"];
			$virtualhost = $row["virtualhost"];
			$username = $row["username"];
			$password = $row["password"];
			$port = $row["port"];
			$exchange_name = $row["exchange_name"];
			$exchange_type = $row["exchange_type"];
			$circuit_breaker_ms = $row["circuit_breaker_ms"];
			$reconnect_interval_ms = $row["reconnect_interval_ms"];
			$send_queue_size = $row["send_queue_size"];
			$enable_fallback_format_fields = $row["enable_fallback_format_fields"];
			$format_fields = $row["format_fields"];
			$event_filter = $row["event_filter"];

		}
		unset ($prep_statement);
		
	}
	else {
	
	}

//begin the page content
	require_once "resources/header.php";
	if ($action == "update") {
	$document['title'] = $text['title-extension-edit'];
	}
	elseif ($action == "add") {
		$document['title'] = $text['title-multi-node-add'];
	}

	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	// echo "\n";
	// echo "function enable_change(enable_over) {\n";
	// echo "	var endis;\n";
	// echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	// echo "	document.iform.range_from.disabled = endis;\n";
	// echo "	document.iform.range_to.disabled = endis;\n";
	// echo "}\n";
	// echo "\n";
	echo "function show_advanced_config() {\n";
	echo "	$('#show_advanced_box').slideToggle();\n";
	echo "	$('#show_advanced').slideToggle();\n";
	echo "}\n";
	echo "\n";
	// echo "function copy_extension() {\n";
	// echo "	var new_ext = prompt('".$text['message-extension']."');\n";
	// echo "	if (new_ext != null) {\n";
	// echo "		if (!isNaN(new_ext)) {\n";
	// echo "			document.location.href='extension_copy.php?id=".$extension_uuid."&ext=' + new_ext;\n";
	// echo "		}\n";
	// echo "		else {\n";
	// echo "			var new_number_alias = prompt('".$text['message-number_alias']."');\n";
	// echo "			if (new_number_alias != null) {\n";
	// echo "				if (!isNaN(new_number_alias)) {\n";
	// echo "					document.location.href='extension_copy.php?id=".$extension_uuid."&ext=' + new_ext + '&alias=' + new_number_alias;\n";
	// echo "				}\n";
	// echo "			}\n";
	// echo "		}\n";
	// echo "	}\n";
	// echo "}\n";
	echo "</script>";

	echo "<form method='post' name='frm' id='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpdding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-multinode-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>".$text['header-multinode-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='multi_node.php'\" value='".$text['button-back']."'>\n";
	
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "	<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='name' autocomplete='off' maxlength='255' value=\"$name\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-multinode']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-hostname']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='hostname' autocomplete='off' maxlength='255' value=\"$hostname\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-form-hostname']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-virtualhost']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='virtualhost' autocomplete='off' maxlength='255' value=\"$virtualhost\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-form-virtualhost']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-username']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='username' autocomplete='off' maxlength='255' value=\"$username\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-form-username']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='password' autocomplete='off' maxlength='255' value=\"$password\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-form-password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-port']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='port' autocomplete='off' maxlength='255' value=\"$port\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-form-port']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	

	echo "<tr>\n";
	
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-node-priority']."\n";
	
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	?>
		<select class='formfld' name='node_priority' autocomplete='off' required='required'>
			<option value='primary' <?php echo ($node_priority == 'primary') ? 'selected' : '' ?> >Primary</option>";
			<option value='secondary' <?php echo ($node_priority == 'secondary') ? 'selected' : '' ?> >Secondary</option>";
		</select>

	<?php
	
	echo "<br />\n";
	echo $text['description-form-node-priority']."\n";
	echo "</td>\n";
	echo "</tr>\n";


//set the defaults
if (strlen($exchange_name) == 0) { $exchange_name = 'TAP.Events'; }
if (strlen($exchange_type) == 0) { $exchange_type = 'topic'; }

if (strlen($circuit_breaker_ms) == 0) { $circuit_breaker_ms = '10000'; }
if (strlen($reconnect_interval_ms) == 0) { $reconnect_interval_ms = '1000'; }

if (strlen($send_queue_size) == 0) { $send_queue_size = '5000'; }
if (strlen($enable_fallback_format_fields) == 0) { $enable_fallback_format_fields = '1'; }

if (strlen($format_fields) == 0) { $format_fields = '#FreeSWITCH,FreeSWITCH-Hostname,Event-Name,Event-Subclass,Unique-ID'; }
if (strlen($event_filter) == 0) { $event_filter = 'CUSTOM,CONFERENCE_DATA'; }

	
	//--- begin: show_advanced -----------------------

	echo "<tr>\n";
	echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap>\n";

	echo "	<div id=\"show_advanced_box\">\n";
	echo "		<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "		<tr>\n";
	echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">&nbsp;</td>\n";
	echo "		<td width=\"70%\" class=\"vtable\">\n";
	echo "			<input type=\"button\" class=\"btn\" onClick=\"show_advanced_config()\" value=\"".$text['button-advanced']."\"></input>\n";
	echo "		</td>\n";
	echo "		</tr>\n";
	echo "		</table>\n";
	echo "	</div>\n";

	echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-exchange-name']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "   <input class='formfld' type='text' name='exchange_name' maxlength='255' value=\"$exchange_name\">\n";
	echo "   <br />\n";
	echo $text['description-form-exchange-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-exchange-type']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "   <input class='formfld' type='text' name='exchange_type' maxlength='255' value=\"$exchange_type\">\n";
	echo "   <br />\n";
	echo $text['description-form-exchange-type']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-circuit_breaker_ms']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "   <input class='formfld' type='text' name='circuit_breaker_ms' maxlength='255' value=\"$circuit_breaker_ms\">\n";
	echo "   <br />\n";
	echo $text['description-form-circuit_breaker_ms']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-reconnect_interval_ms']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "   <input class='formfld' type='text' name='reconnect_interval_ms' maxlength='255' value=\"$reconnect_interval_ms\">\n";
	echo "   <br />\n";
	echo $text['description-form-reconnect_interval_ms']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-send_queue_size']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "   <input class='formfld' type='text' name='send_queue_size' maxlength='255' value=\"$send_queue_size\">\n";
	echo "   <br />\n";
	echo $text['description-form-send_queue_size']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-enable_fallback_format_fields']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "   <input class='formfld' type='text' name='enable_fallback_format_fields' maxlength='255' value=\"$enable_fallback_format_fields\">\n";
	echo "   <br />\n";
	echo $text['description-form-enable_fallback_format_fields']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-format_fields']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea class='formfld' name='format_fields' rows='4'>$format_fields</textarea>\n";
	echo "<br />\n";
	echo $text['description-format_fields']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-event_filter']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea class='formfld' name='event_filter' rows='4'>$event_filter</textarea>\n";
	echo "<br />\n";
	echo $text['description-event_filter']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-form-switch-name']."\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "   <input class='formfld' type='text' name='switch_name' maxlength='255' value=\"$switch_name\">\n";
	echo "   <br />\n";
	echo $text['description-form-switch-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	</table>\n";
	echo "	</div>";

	echo "</td>\n";
	echo "</tr>\n";

	//--- end: show_advanced -----------------------

	

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='multinode_uuid' value='".$multinode_uuid."'>\n";
		echo "		<input type='hidden' name='id' id='id' value='".$multinode_uuid."'>";
		if (!permission_exists('extension_domain')) {
			echo "		<input type='hidden' name='domain_uuid' id='domain_uuid' value='".$_SESSION['domain_uuid']."'>";
		}
		echo "		<input type='hidden' name='delete_type' id='delete_type' value=''>";
		echo "		<input type='hidden' name='delete_uuid' id='delete_uuid' value=''>";
	}
	echo "			<br>";
	echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//capture enter key to submit form
	echo "<script>\n";
	echo "	$(window).keypress(function(event){\n";
	echo "		if (event.which == 13) { submit_form(); }\n";
	echo "	});\n";
	// convert password fields to
	echo "	function submit_form() {\n";
	echo "		$('input:password').css('visibility','hidden');\n";
	echo "		$('input:password').attr({type:'text'});\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
