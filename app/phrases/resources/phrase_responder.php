<?php

/*
 * FusionPBX
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is FusionPBX
 *
 * The Initial Developer of the Original Code is
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Portions created by the Initial Developer are Copyright (C) 2008-2024
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

require_once dirname(__DIR__, 3) . '/resources/require.php';

//check permissions
if (permission_exists('phrase_add') || permission_exists('phrase_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

// Disable output buffering and compression
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', 'off');
ini_set('implicit_flush', 1);
ob_implicit_flush(1);

// Set headers to ensure immediate response
header('Content-Type: text/plain');
header('Cache-Control: no-cache');
header('Content-Encoding: none');

function fetch_recordings(database $database): array {
	global $domain_uuid;
	// guard against corrupt data
	if (empty($domain_uuid) || !is_uuid($domain_uuid)) {
		throw new Exception('Domain is invalid');
	}
	// always return an array
	$return_value = [];
	$sql = "select recording_uuid, recording_name, recording_filename, domain_uuid from v_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by recording_name asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$recordings = $database->select($sql, $parameters, 'all');
	if (!empty($recordings)) {
		$return_value = $recordings;
	}
	return $return_value;
}

function fetch_sound_files(settings $settings) {
	$return_value = [];
	//get the switch sound files
	$file = new file($settings);
	$sound_files = $file->sounds();

	//try finding display_name in the switch sound files
	if (!empty($sound_files)) {
		$return_value = $sound_files;
	}
	return $return_value;
}

function fetch_phrase_details(settings $settings, string $phrase_uuid): array {
	global $domain_uuid;
	// guard against corrupt data
	if (empty($domain_uuid) || !is_uuid($domain_uuid)) {
		throw new Exception('Domain is invalid');
	}
	if (empty($phrase_uuid) || !is_uuid($phrase_uuid)) {
		throw new Exception('Phrase UUID is invalid');
	}

	$database = $settings->database();
	// set the return value to be an empty array
	$return_value = [];
	// get the phrase details
	if (!empty($phrase_uuid)) {
		$sql = "select * from v_phrase_details ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and phrase_uuid = :phrase_uuid ";
		$sql .= "order by phrase_detail_order asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['phrase_uuid'] = $phrase_uuid;
		$phrase_details = $database->select($sql, $parameters, 'all');
	}
	//existing details
	if (!empty($phrase_details)) {
		$recordings = fetch_recordings($database);
		$sound_files = fetch_sound_files($settings);
		//update the array to include the recording name for display in select box
		foreach ($phrase_details as &$row) {
			$row['display_name'] = '';
			$file = basename($row['phrase_detail_data']);
			//get the display_name from recording name based on the file matched
			foreach ($recordings as $key => $recordings_row) {
				//match on filename first and then domain_uuid
				if ($recordings_row['recording_filename'] === $file && $recordings_row['domain_uuid'] === $row['domain_uuid']) {
					$row['display_name'] = $recordings[$key]['recording_name'];
					break;
				}
			}
			//check if display_name was not found in the recording names
			if (strlen($row['display_name']) === 0) {
				//try finding display_name in the switch sound files
				if (!empty($sound_files)) {
					//use optimized php function with strict comparison
					$i = array_search($row['phrase_detail_data'], $sound_files, true);
					//if found in the switch sound files
					if ($i !== false) {
						//set the display_name to the switch sound file name
						$row['display_name'] = $sound_files[$i];
					}
				}
			}
		}
		$return_value = $phrase_details;
	}
	return $return_value;
}

function fetch_domain_uuid(database $database): string {
	$domain = $_SERVER['HTTP_HOST'];
	$domain_uuid = '';
	$sql = 'select domain_uuid from v_domains where domain_name = :domain';
	$parameters = [];
	$parameters['domain'] = $domain;
	$result = $database->select($sql, $parameters, 'column');
	if (!empty($result)) {
		$domain_uuid = $result;
	}
	return $domain_uuid;
}

function send_message(string $json_data) {
	echo $json_data . "\n";
	ob_flush();
	flush();
}

$config = config::load();
$database = database::new(['config' => $config]);
$domain_uuid = fetch_domain_uuid($database);
$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid]);

// Set default response
$response = ['code' => 200, 'message' => ''];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$message = '';
	// Get the raw POST data
	$input = file_get_contents('php://input');

	// Parse JSON data
	$data = json_decode($input, true); // Decode JSON as associative array

	if (isset($data['request'])) {
		try {
			//check the data source requested
			switch ($data['request']) {
				case 'sound_files':
					$message = fetch_sound_files($settings);
					break;
				case 'recordings':
					$message = fetch_recordings($database);
					break;
				case 'phrase_details':
					$phrase_uuid = $data['data'];
					$message = fetch_phrase_details($settings, $phrase_uuid);
					break;
			}
		} catch (Exception $e) {
			$response['code'] = 500;
			$message = $e->getMessage();
		}
	}
	//save the message
	$response['message'] = $message;
	//send the response
	send_message(json_encode($response));
	exit();
}

exit();
