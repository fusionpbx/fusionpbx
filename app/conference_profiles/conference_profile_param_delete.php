<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('conference_profile_param_delete')) {
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
	if (is_uuid($_GET["id"]) && is_uuid($_GET["conference_profile_uuid"])) {

		$conference_profile_param_uuid = $_GET["id"];
		$conference_profile_uuid = $_GET["conference_profile_uuid"];

		//delete conference_profile_param
			$array['conference_profile_params'][0]['conference_profile_param_uuid'] = $conference_profile_param_uuid;

			$database = new database;
			$database->app_name = 'conference_profiles';
			$database->app_uuid = 'c33e2c2a-847f-44c1-8c0d-310df5d65ba9';
			$database->delete($array);
			unset($array);

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header('Location: conference_profile_edit.php?id='.$conference_profile_uuid);

?>
