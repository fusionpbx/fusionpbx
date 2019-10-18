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
	if (!permission_exists('message_add') && !permission_exists('message_edit')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define the http request
	function http_request($url, $method, $headers = null, $content)  {
		$options = array(
			'http'=>array(
			'method'=>$method,
			'header'=>$headers,
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
		$message_from = $_POST["message_from"];
		$message_to = $_POST["message_to"];
		$message_text = $_POST["message_text"];
		$message_media = $_FILES["message_media"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the source phone number
			$phone_number = preg_replace('{[\D]}', '', $message_to);

		//error check
			if (
				!is_numeric($message_from) ||
				!is_numeric($message_to) ||
				$message_text == '') {
					exit;
			}

		// handle media (if any)
			if (is_array($message_media) && @sizeof($message_media) != 0) {
				// reorganize media array, ignore errored files
				$f = 0;
				foreach ($message_media['error'] as $index => $error) {
					if ($error == 0) {
						$tmp_media[$f]['uuid'] = uuid();
						$tmp_media[$f]['name'] = $message_media['name'][$index];
						$tmp_media[$f]['type'] = $message_media['type'][$index];
						$tmp_media[$f]['tmp_name'] = $message_media['tmp_name'][$index];
						$tmp_media[$f]['size'] = $message_media['size'][$index];
						$f++;
					}
				}
				$message_media = $tmp_media;
				unset($tmp_media, $f);
			}
			$message_type = is_array($message_media) && @sizeof($message_media) != 0 ? 'mms' : 'sms';

		//get the contact uuid
			$sql = "select c.contact_uuid ";
			$sql .= "from v_contacts as c, v_contact_phones as p ";
			$sql .= "where p.contact_uuid = c.contact_uuid ";
			$sql .= "and p.phone_number like :phone_number ";
			$sql .= "and c.domain_uuid = :domain_uuid ";
			$parameters['phone_number'] = '%'.$phone_number.'%';
			$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
			$contact_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

		//build the message array
			$message_uuid = uuid();
			$array['messages'][0]['domain_uuid'] = $_SESSION["domain_uuid"];
			$array['messages'][0]['message_uuid'] = $message_uuid;
			$array['messages'][0]['user_uuid'] = $_SESSION["user_uuid"];
			$array['messages'][0]['contact_uuid'] = $contact_uuid;
			$array['messages'][0]['message_type'] = $message_type;
			$array['messages'][0]['message_direction'] = 'outbound';
			$array['messages'][0]['message_date'] = 'now()';
			$array['messages'][0]['message_from'] = $message_from;
			$array['messages'][0]['message_to'] = $message_to;
			$array['messages'][0]['message_text'] = $message_text;

		//build message media array (if necessary)
			$p = new permissions;
			if (is_array($message_media) && @sizeof($message_media) != 0) {
				foreach($message_media as $index => $media) {
					$array['message_media'][$index]['message_media_uuid'] = $media['uuid'];
					$array['message_media'][$index]['message_uuid'] = $message_uuid;
					$array['message_media'][$index]['domain_uuid'] = $_SESSION["domain_uuid"];
					$array['message_media'][$index]['user_uuid'] = $_SESSION["user_uuid"];
					$array['message_media'][$index]['message_media_type'] = strtolower(pathinfo($media['name'], PATHINFO_EXTENSION));
					$array['message_media'][$index]['message_media_url'] = $media['name'];
					$array['message_media'][$index]['message_media_content'] = base64_encode(file_get_contents($media['tmp_name']));
				}

				$p->add('message_media_add', 'temp');
			}

		//save to the data
			$database = new database;
			$database->app_name = 'messages';
			$database->app_uuid = '4a20815d-042c-47c8-85df-085333e79b87';
			$database->save($array);
			unset($array);

		//remove any temporary permissions
			$p->delete('message_media_add', 'temp');

		//santize the from
			$message_from = preg_replace('{[\D]}', '', $message_from);

		//prepare message to send
			$message['to'] = $message_to;
			$message['text'] = $message_text;
			if (is_array($message_media) && @sizeof($message_media) != 0) {
				$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
				foreach ($message_media as $index => $media) {
					$path = $protocol.$_SERVER['HTTP_HOST'].'/app/messages/message_media.php?id='.$media['uuid'].'&action=download&.'.strtolower(pathinfo($media['name'], PATHINFO_EXTENSION));
					$message['media'][] = $path;
				}
			}
			$http_content = json_encode($message);

		//settings needed for REST API
			$http_method = $_SESSION['message']['http_method']['text'];
			$http_content_type = $_SESSION['message']['http_content_type']['text'];
			$http_destination = $_SESSION['message']['http_destination']['text'];
			$http_auth_enabled = $_SESSION['message']['http_auth_enabled']['boolean'];
			$http_auth_type = $_SESSION['message']['http_auth_type']['text'];
			$http_auth_user = $_SESSION['message']['http_auth_user']['text'];
			$http_auth_password = $_SESSION['message']['http_auth_password']['text'];

		//exchange variable name with their values
			$http_destination = str_replace("\${from}", $message_from, $http_destination);

		//send the message to the provider
			$headers[] = "Content-type: ".trim($http_content_type);
			if ($http_auth_type == 'basic') {
				$headers[] = "Authorization: Basic ".base64_encode($http_auth_user.':'.$http_auth_password);
			}
			$response = http_request($http_destination, $http_method, $headers, $http_content);

		//redirect the user
			return true;
	}

?>