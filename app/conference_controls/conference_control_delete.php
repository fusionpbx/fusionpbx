<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('conference_control_delete') && permission_exists('conference_control_detail_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the data
	if (is_uuid($_GET["id"])) {

		$conference_control_uuid = $_GET["id"];

		//delete conference control detail
			$array['conference_control_details'][0]['conference_control_uuid'] = $conference_control_uuid;
		//delete conference control
			$array['conference_controls'][0]['conference_control_uuid'] = $conference_control_uuid;

			$database = new database;
			$database->app_name = 'conference_controls';
			$database->app_uuid = 'e1ad84a2-79e1-450c-a5b1-7507a043e048';
			$database->delete($array);
			unset($array);

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header('Location: conference_controls.php');

?>