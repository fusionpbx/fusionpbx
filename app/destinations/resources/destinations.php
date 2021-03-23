<?php

//includes
	include_once "root.php"; 
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('destination_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the action
	$action = $_REQUEST['action'];
	$destination_type = $_REQUEST['destination_type'];

//sanitize the variables
	$action = preg_replace('#[^a-zA-Z0-9_\-]#', '', $action);
	$destination_type = preg_replace('#[^a-zA-Z0-9_\-]#', '', $destination_type);

//get the destinations
	$destination = new destinations;
	$destinations = $destination->get($destination_type);

//show the select
	echo "	<select name='subaction' id='action' class='formfld' style='".$select_style."'>\n";
	echo "	<option value=''></option>\n";
	foreach($destinations as $key => $rows) {
		$singular = $destination->singular($key);
		if ($key == $action && permission_exists("{$singular}_destinations")) {
			if (is_array($rows)) {
				foreach($rows as $row) {

					//set the variables
					$select_label = $row['label'];
					$select_value = $row['destination'];

					//update the select values
					$select_value = str_replace("\${domain_name}", $_SESSION['domain_name'], $select_value);
					$select_value = str_replace("\${context}", $_SESSION['domain_name'], $select_value);
					$select_label = str_replace("\${domain_name}", $_SESSION['domain_name'], $select_label);
					$select_label = str_replace("\${context}", $_SESSION['domain_name'], $select_label);

					$select_label = str_replace('&low_bar;', '_', $select_label);
					$select_label = str_replace("&#9993", 'email-icon', $select_label);
					//$select_label = escape(trim($select_label));
					$select_label = str_replace('email-icon', '&#9993', $select_label);

					//add the select option
					$uuid = isset($row[$singular.'_uuid']) ? $row[$singular.'_uuid'] : $row['uuid'];
					echo "		<option id='{$uuid}' value='".$select_value."'>".$select_label."</option>\n";
				}
			}
		}
	}
	echo "	</select>\n";

?>
