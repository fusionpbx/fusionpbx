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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//get the user settings
	$sql = "select user_uuid, domain_uuid from v_user_settings ";
	$sql .= "where user_setting_category = 'message' ";
	$sql .= "and user_setting_subcategory = 'key' ";
	$sql .= "and user_setting_value = :key ";
	$sql .= "and user_setting_enabled = 'true' ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->bindParam(':key', $_GET['key']);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_NAMED);
	}

//default authorized to false
	$authorized = 'false';

//get the user
	if (isset($row['user_uuid']) && strlen($row['user_uuid']) > 0) {
		$domain_uuid = $row['domain_uuid'];
		$user_uuid = $row['user_uuid'];
		$authorized = 'true';
	}

//authorization failed
	if ($authorized == 'false') {
		//log the failed auth attempt to the system, to be available for fail2ban.
		openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
		syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] authentication failed for ".$_GET['key']);
		closelog();

		//send http 404
		header("HTTP/1.0 404 Not Found");
		echo "<html>\n";
		echo "<head><title>404 Not Found</title></head>\n";
		echo "<body bgcolor=\"white\">\n";
		echo "<center><h1>404 Not Found</h1></center>\n";
		echo "<hr><center>nginx/1.12.1</center>\n";
		echo "</body>\n";
		echo "</html>\n";
		exit();
	}

//get the data
	$json = file_get_contents('php://input');

//decode the json
	$message = json_decode($json, true);

//get a unique id
	$message_uuid = uuid();

//get the source phone number
	$phone_number = $message["from"];
	$phone_number = preg_replace('{[\D]}', '', $phone_number);

//get the contact uuid
	$sql = "SELECT c.contact_uuid ";
	$sql .= "FROM v_contacts as c, v_contact_phones as p ";
	$sql .= "WHERE p.contact_uuid = c.contact_uuid ";
	//$sql .= "and p.phone_number = :phone_number ";
	$sql .= "and p.phone_number = '".$phone_number."' ";
	$sql .= "and c.domain_uuid = '".$domain_uuid."' ";
	$prep_statement = $db->prepare($sql);
	//$prep_statement->bindParam(':phone_number', $phone_number);
	$prep_statement->execute();
	$row = $prep_statement->fetch(PDO::FETCH_NAMED);
	$contact_uuid = $row['contact_uuid'];
	//$contact_name_given = $row['contact_name_given'];
	//$contact_name_family = $row['contact_name_family'];
	//$contact_organization = $row['contact_organization'];

//build the array
	$array['messages'][0]["domain_uuid"] = $domain_uuid;
	$array['messages'][0]["user_uuid"] = $user_uuid;
	$array['messages'][0]["contact_uuid"] = $contact_uuid;
	$array['messages'][0]['message_uuid'] = $message_uuid;
	$array['messages'][0]['message_json'] = $json;
	$array['messages'][0]['message_direction'] = 'inbound';
	$array['messages'][0]['message_date'] = 'now()';
	$array['messages'][0]['message_type'] = 'sms';
	$array['messages'][0]['message_from'] = $message["from"];
	$array['messages'][0]['message_to'] = $message["to"];
	$array['messages'][0]['message_text'] = $message["text"];

//get the media
	if (is_array($message["media"])) {
		foreach($message["media"] as $media) {
			$media_extension = pathinfo($media, PATHINFO_EXTENSION);
			if ($media_extension !== "xml") {
				$array['messages'][0]['message_media_type'] = $media_extension;
				$array['messages'][0]['message_media_url'] = $media;
				$array['messages'][0]['message_media_content'] = base64_encode(file_get_contents($media));
			}
		}
	}

//convert the array to json
	$array_json = json_encode($array);

//add the dialplan permission
	$p = new permissions;
	$p->add("message_add", "temp");

//save to the data
	$database = new database;
	$database->app_name = 'messages';
	$database->app_uuid = '4a20815d-042c-47c8-85df-085333e79b87';
	$database->uuid($message_uuid);
	$database->save($array);
	$result = $database->message;

//remove the temporary permission
	$p->delete("message_add", "temp");

//get the list of extensions using the user_uuid
	$sql = "select * from v_domains as d, v_extensions as e ";
	$sql .= "where extension_uuid in (select extension_uuid from v_extension_users where user_uuid = '".$user_uuid."') ";
	$sql .= "and e.domain_uuid = d.domain_uuid ";
	$sql .= "and e.enabled = 'true' ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$extensions = $prep_statement->fetchall(PDO::FETCH_NAMED);
	}

//create the event socket connection
	if (is_array($extensions)) {
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	}

//send the sip message
	if (is_array($extensions)) {
		foreach ($extensions as $row) {
			$domain_name = $row['domain_name'];
			$extension = $row['extension'];
			$number_alias = $row['number_alias'];

			//send the sip messages
			$command = "luarun app/messages/resources/send.lua ".$message["from"]."@".$domain_name." ".$extension."@".$domain_name."  '".$message["text"]."'";

			//send the command
			$response = event_socket_request($fp, "api ".$command);
			$response = event_socket_request($fp, "api log notice ".$command);
		}
	}

//set the file
	//$file = '/tmp/sms.txt';

//save the file
	//file_put_contents($file, $json);

//save the data to the file system
	//file_put_contents($file, $json."\n");
	//file_put_contents($file, $array_json."\nfrom: ".$message["from"]." to: ".$message["to"]." text: ".$message["text"]."\n$sql_test\njson: ".$json."\n".$saved_result."\n");

?>
