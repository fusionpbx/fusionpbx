<?php

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('fax_queue_delete')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the message
	message::add($text['message-delete']);

//delete the data
	if (isset($_GET["id"]) && is_uuid($_GET["id"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: fax_queue.php');
				exit;
			}

		//get the id
			$id = $_GET["id"];

		//verify the record belongs to the current domain
			$sql = "select fax_queue_uuid from v_fax_queue ";
			$sql .= "where fax_queue_uuid = :fax_queue_uuid ";
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['fax_queue_uuid'] = $id;
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$row = $database->select($sql, $parameters, 'row');
			unset($sql, $parameters);
			if (empty($row)) {
				header('Location: fax_queue.php');
				exit;
			}

		//delete the data
			$array['fax_queue'][]['fax_queue_uuid'] = $id;
			$database->delete($array);
			unset($array);

		//redirect the user
			header('Location: fax_queues.php');
	}


?>