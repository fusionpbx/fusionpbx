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

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('message_add') || permission_exists('message_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	$action = "add";

//define the http request
	function http_request($url, $method, $headers = null, $content)  {
		$options = array(
			'http'=>array(
			'method'=>$method,
			'header'=> $headers,
			'content'=>$content
		));
		$context = stream_context_create($options);
		$response = file_get_contents($url, false, $context);
		if ($response === false) {
			throw new Exception("Problem reading data from $url, $php_errormsg");
		}
		return $response;
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$message_type = check_str($_POST["message_type"]);
		$message_from = check_str($_POST["message_from"]);
		$message_to = check_str($_POST["message_to"]);
		$message_text = check_str($_POST["message_text"]);
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the source phone number
			$phone_number = preg_replace('{[\D]}', '', $message_to);

		//error check
		if (
			($message_type != 'sms' && $message_type != 'mms' && $message_type != 'chat') ||
			!is_numeric($message_from) ||
			!is_numeric($message_to) ||
			$message_text == '') {
				exit;
		}

		//get the contact uuid
			//$sql = "SELECT trim(c.contact_name_given || ' ' || c.contact_name_family || ' (' || c.contact_organization || ')') AS name, p.phone_number AS number ";
			$sql = "SELECT c.contact_uuid ";
			$sql .= "FROM v_contacts as c, v_contact_phones as p ";
			$sql .= "WHERE p.contact_uuid = c.contact_uuid ";
			//$sql .= "and p.phone_number = :phone_number ";
			$sql .= "and p.phone_number like '%".$phone_number."%' ";
			$sql .= "and c.domain_uuid = '".$domain_uuid."' ";
			$prep_statement = $db->prepare($sql);
			//$prep_statement->bindParam(':phone_number', $phone_number);
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_NAMED);
			$contact_uuid = $row['contact_uuid'];

		//set the message id
			$message_uuid = uuid();

		//build the message array
			$message['domain_uuid'] = $_SESSION["domain_uuid"];
			$message['message_uuid'] = uuid();
			$message['user_uuid'] = $_SESSION["user_uuid"];
			$message['contact_uuid'] = $contact_uuid;
			$message['message_type'] = $message_type;
			$message['message_direction'] = 'outbound';
			$message['message_date'] = 'now()';
			$message['message_from'] = $message_from;
			$message['message_to'] = $message_to;
			$message['message_text'] = $message_text;

		//prepare the array
			$array['messages'][0] = $message;

		//save to the data
			$database = new database;
			$database->app_name = 'messages';
			$database->app_uuid = null;
			$database->uuid($message_uuid);
			$database->save($array);
			$message = $database->message;

		//debug info
			//echo "<pre>";
			//print_r($message);
			//echo "</pre>";
			//exit;

		//send the message to the provider
			$array["to"] = $message_to;
			$array["text"] = $message_text;
			//$array["media"] = '';
			$http_content = json_encode($array);

		//settings needed for REST API
			$http_method = $_SESSION['message']['http_method']['text'];
			$http_content_type = $_SESSION['message']['http_content_type']['text'];
			$http_destination = $_SESSION['message']['http_destination']['text'];
			$http_auth_enabled = $_SESSION['message']['http_auth_enabled']['boolean'];
			$http_auth_type = $_SESSION['message']['http_auth_type']['text'];
			$http_auth_user = $_SESSION['message']['http_auth_user']['text'];
			$http_auth_password = $_SESSION['message']['http_auth_password']['text'];

		//santize the from
			$message_from = preg_replace('{[\D]}', '', $message_from);

		//exchange variable name with their values
			$http_destination = str_replace("\${from}", $message_from, $http_destination);

		//send the message to the provider
			$headers[] = "Content-type: ".trim($http_content_type);
			if ($http_auth_type == 'basic') {
				$headers[] = "Authorization: Basic ".base64_encode($http_auth_user.':'.$http_auth_password);
			}
			$response = http_request($http_destination, $http_method, $headers, $http_content);
			//echo $response;

		//redirect the user
			//$_SESSION["message"] = $text['message-sent'];
			return true;
	} //(is_array($_POST) && strlen($_POST["persistformvar"]) == 0)

?>
