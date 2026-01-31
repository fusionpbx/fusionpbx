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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * call_recordings class
 */
class call_recordings {

	/**
	 * declare constant variables
	 */
	const app_name = 'follow_me';
	const app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02b7d14';

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 *
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Settings object set in the constructor. Must be a settings object and cannot be null.
	 *
	 * @var settings Settings Object
	 */
	private $settings;

	/**
	 * User UUID set in the constructor. This can be passed in through the $settings_array associative array or set in
	 * the session global array
	 *
	 * @var string
	 */
	private $user_uuid;

	/**
	 * Username set in the constructor. This can be passed in through the $settings_array associative array or set in
	 * the session global array
	 *
	 * @var string
	 */
	private $username;

	/**
	 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
	 * @var string
	 */
	private $domain_uuid;

	/**
	 * Domain name set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
	 * @var string
	 */
	private $domain_name;

	/**
	 * declare the variables
	 */
	private $name;
	private $table;
	private $description_field;
	private $location;
	public $recording_uuid;
	public $binary;

	/**
	 * Initializes the object with setting array.
	 *
	 * @param array $setting_array An array containing settings for domain, user, and database connections. Defaults to
	 *                             an empty array.
	 *
	 * @return void
	 */
	public function __construct(array $setting_array = []) {
		//set domain and user UUIDs
		$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
		$this->user_uuid   = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

		//set objects
		$this->database = $setting_array['database'] ?? database::new();
		$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);

		//assign the variables
		$this->name              = 'call_recording';
		$this->table             = 'call_recordings';
		$this->description_field = 'call_recording_description';
		$this->location          = 'call_recordings.php';
	}

	/**
	 * Deletes one or more records.
	 *
	 * @param array $records An array of record IDs to delete, where each ID is an associative array
	 *                       containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                       whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function delete($records) {
		if (permission_exists($this->name . '_delete')) {

			//add multi-lingual support
			$language = new text;
			$text     = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			//delete multiple records
			if (is_array($records) && @sizeof($records) != 0) {
				//build the delete array
				$x = 0;
				foreach ($records as $record) {
					//add to the array
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						//get the information to delete
						$sql                               = "select call_recording_name, call_recording_path ";
						$sql                               .= "from view_call_recordings ";
						$sql                               .= "where call_recording_uuid = :call_recording_uuid ";
						$parameters['call_recording_uuid'] = $record['uuid'];
						$field                             = $this->database->select($sql, $parameters, 'row');
						if (is_array($field) && @sizeof($field) != 0) {
							//delete the file on the file system
							if (file_exists($field['call_recording_path'] . '/' . $field['call_recording_name'])) {
								unlink($field['call_recording_path'] . '/' . $field['call_recording_name']);
							}

							//build call recording delete array
							$array['xml_cdr'][$x]['xml_cdr_uuid']  = $record['uuid'];
							$array['xml_cdr'][$x]['record_path']   = null;
							$array['xml_cdr'][$x]['record_name']   = null;
							$array['xml_cdr'][$x]['record_length'] = null;

							//increment the id
							$x++;
						}
						unset($sql, $parameters, $field);
					}
				}

				//delete the checked rows
				if (is_array($array) && @sizeof($array) != 0) {

					//add temporary permissions
					$p = permissions::new();
					$p->add('xml_cdr_edit', 'temp');

					//remove record_path, record_name and record_length
					$this->database->app_name = 'xml_cdr';
					$this->database->app_uuid = '4a085c51-7635-ff03-f67b-86e834422848';
					$this->database->save($array, false);
					$message = $this->database->message;
					unset($array);

					//remove the temporary permissions
					$p->delete('xml_cdr_edit', 'temp');

					//set message
					message::add($text['message-delete']);

				}
				unset($records);
			}
		}
	}

	/**
	 * Add transcript to the xml_cdr_transcripts queue
	 *
	 * @param array $params contain domain_uuid, xml_cdr_uuid and call_direction, transcript_json, transcribe_summary
	 *
	 * @return void
	 */
	public function transcribe_queue($params) {
		//set the global variables
		global $database;

		//get the settings for this domain
		$settings = new settings(['database' => $database, 'domain_uuid' => $params['domain_uuid']]);

		//set the domain_uuid
		$domain_uuid = $params['domain_uuid'];

		//define the array
		$array = [];

		//define the variable(s)
		$call_summary = '';

		//summarize the transcript
		if ($settings->get('language_model', 'enabled') && $settings->get('call_recordings', 'summary_enabled') && !empty($params['transcribe_message'])) {
			//get the transcribed text
			$transcribe_text = transcribe::conversation_format($params['transcribe_message'], 'text');

			//get the summary language model prompt
			$default_prompt = "Summarize this conversation with Key Points, Action Items if any, and Sentiment. Use names when they are provided. Keep the summary professional. Return text without markdown.";
			$prompt = $this->settings->get('call_recordings', 'summary_model_prompt', $default_prompt);

			//combine the prompt with the call transcript
			$request_data['prompt'] = $prompt . "```\n".$transcribe_text."\n```";

			//get the summary language model name
			$request_model = $this->settings->get('call_recordings', 'summary_model_name', 'ministral-3:8b');

			//load the language model and get the call summary
			$language_model = new language_model();
			$params['transcript_summary'] = $language_model->request($request_model, $request_data);

			//get the summary from the params
			$transcript_summary = $params['transcript_summary'] ?? '';

			//format the call recording transcript summary
			require_once "resources/classes/parsedown.php";
			$parsedown = new Parsedown();
			$parsedown->setSafeMode(true);
			$parsedown->setMarkupEscaped(true);
			$call_summary = str_replace('###', '', $transcript_summary);
			$call_summary = str_replace('&amp;', '&', $parsedown->text($call_summary));
		}

		//prepare the array with the transcript details
		$array['xml_cdr_transcripts'][0]['xml_cdr_transcript_uuid'] = $params['xml_cdr_uuid'];
		$array['xml_cdr_transcripts'][0]['domain_uuid'] = $params['domain_uuid'];
		$array['xml_cdr_transcripts'][0]['xml_cdr_uuid'] = $params['xml_cdr_uuid'];
		$array['xml_cdr_transcripts'][0]['transcript_json'] = $params['transcribe_message'];
		$array['xml_cdr_transcripts'][0]['transcript_summary'] = $params['transcript_summary'] ?? '';

		//add temporary permissions
		$p = permissions::new();
		$p->add('xml_cdr_transcript_add', 'temp');
		$p->add('xml_cdr_transcript_edit', 'temp');

		//save the call recording transcript
		$result = $this->database->save($array, false);
		unset($array);

		//remove the temporary permissions
		$p->delete('xml_cdr_transcript_add', 'temp');
		$p->delete('xml_cdr_transcript_edit', 'temp');

		//get the email settings
		$email_address = $settings->get('call_recordings', 'email_address');
		$email_template = $settings->get('call_recordings', 'email_template');

		//set default language - need to have a way to set the language
		if (!empty($settings->get('domain', 'language'))) {
			$template_language = $settings->get('domain', 'language');
		}
		else {
			$template_language = 'en-us';
		}

		//set the time zone
		$time_zone = $settings->get('domain','time_zone', date_default_timezone_get());

		//set the variables
		$hostname = system('hostname');
		$email_queue_uuid = uuid();
		$email_from = $settings->get('email', 'smtp_from');
		if (isset($record_name)) {
			$email_attachment_type = substr($record_name, -3);
		}

		//send the email
		if (isset($email_address) && !empty($params['transcribe_message'])) {
			//format the transcription variables for text and html
			$transcribe_html = transcribe::conversation_format($params['transcribe_message'], 'html');

			//get the email template
			$sql = "SELECT ";
			$sql .= " domain_name, ";
			$sql .= " caller_id_name, ";
			$sql .= " caller_id_number, ";
			$sql .= " to_char(timezone(:time_zone, start_stamp), 'DD Mon YYYY') as start_date, \n";
			$sql .= " to_char(timezone(:time_zone, start_stamp), 'HH12:MI:SS am') as start_time, \n";
			$sql .= " to_char(timezone(:time_zone, end_stamp), 'DD Mon YYYY') as end_date, \n";
			$sql .= " to_char(timezone(:time_zone, end_stamp), 'HH12:MI:SS am') as end_time, \n";
			$sql .= " duration ";
			$sql .= "FROM v_xml_cdr ";
			$sql .= "WHERE domain_uuid = :domain_uuid ";
			$sql .= "AND xml_cdr_uuid = :xml_cdr_uuid ";
			$parameters['domain_uuid'] = $params['domain_uuid'];
			$parameters['xml_cdr_uuid'] = $params['xml_cdr_uuid'];
			$parameters['time_zone'] = $time_zone;
			$row = $this->database->select($sql, $parameters, 'row');
			$domain_name = $row["domain_name"];
			$caller_id_name = $row["caller_id_name"];
			$caller_id_number = $row["caller_id_number"];
			$start_date = $row["start_date"];
			$start_time = $row["start_time"];
			$end_date = $row["end_date"];
			$end_time = $row["end_time"];
			$time = $row["time"];
			$duration = $row["duration"];
			unset($parameters);

			//get the email template
			$sql = "SELECT * FROM v_email_templates ";
			$sql .= "WHERE (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$sql .= "AND template_language = :template_language ";
			$sql .= "AND template_category = 'call_recordings' ";
			$sql .= "AND template_subcategory = 'transcription' ";
			$sql .= "AND template_enabled = 'true' ";
			$sql .= "ORDER BY domain_uuid DESC ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['template_language'] = $template_language;
			$row = $this->database->select($sql, $parameters, 'row');
			$email_subject = $row["template_subject"];
			$email_body = $row["template_body"];
			unset($parameters);

			//replace variables in email subject
			$email_subject = str_replace('${domain_name}', $domain_name, $email_subject);
			$email_subject = str_replace('${email_address}', $email_address, $email_subject);
			$email_subject = str_replace('${caller_id_name}', $caller_id_name, $email_subject);
			$email_subject = str_replace('${caller_id_number}', $caller_id_number, $email_subject);
			$email_subject = str_replace('${start_date}', $start_date, $email_subject);
			$email_subject = str_replace('${start_time}', $start_time, $email_subject);
			$email_subject = str_replace('${end_date}', $end_date, $email_subject);
			$email_subject = str_replace('${end_time}', $end_time, $email_subject);
			$email_subject = str_replace('${duration}', $duration, $email_subject);
			$email_subject = str_replace('${length}', $duration, $email_subject);
			$email_subject = str_replace('${time}', $start_time, $email_subject);

			//replace variables in email body
			$email_body = str_replace('${domain_name}', $domain_name, $email_body);
			$email_body = str_replace('${email_address}', $email_address, $email_body);
			$email_body = str_replace('${caller_id_name}', $caller_id_name, $email_body);
			$email_body = str_replace('${caller_id_number}', $caller_id_number, $email_body);
			$email_body = str_replace('${start_date}', $start_date, $email_body);
			$email_body = str_replace('${start_time}', $start_time, $email_body);
			$email_body = str_replace('${end_date}', $end_date, $email_body);
			$email_body = str_replace('${end_time}', $end_time, $email_body);
			$email_body = str_replace('${time}', $start_time, $email_body);
			$email_body = str_replace('${duration}', $duration, $email_body);
			$email_body = str_replace('${length}', $duration, $email_body);
			$email_body = str_replace('${summary}', $call_summary, $email_body);
			$email_body = str_replace('${transcript}', $transcribe_html, $email_body);

			//send email with the email_queue
			$array['email_queue'][0]['email_queue_uuid'] = $email_queue_uuid;
			$array['email_queue'][0]['domain_uuid'] = $domain_uuid;
			$array['email_queue'][0]['hostname'] = $hostname;
			$array['email_queue'][0]['email_date'] = 'now()';
			$array['email_queue'][0]['email_from'] = $email_from;
			$array['email_queue'][0]['email_to'] = $email_address;
			$array['email_queue'][0]['email_subject'] = $email_subject;
			$array['email_queue'][0]['email_body'] = $email_body;
			$array['email_queue'][0]['email_status'] = 'waiting';
			$array['email_queue'][0]['email_uuid'] = null;
			$array['email_queue'][0]['email_action_after'] = null;

			//unset the variables
			unset($email_address, $email_subject, $email_body);
			unset($domain_name, $account_code);

			//add the attachment
			// if (is_array($attachments) && @sizeof($attachments) != 0) {
			// 	//build the attachment array
			// 	$array['email_queue_attachments'][0]['email_queue_attachment_uuid'] = uuid();
			// 	$array['email_queue_attachments'][0]['email_queue_uuid'] = $email_queue_uuid;
			// 	$array['email_queue_attachments'][0]['domain_uuid'] = $domain_uuid;
			// 	$array['email_queue_attachments'][0]['email_attachment_type'] = $email_attachment_type;
			// 	$array['email_queue_attachments'][0]['email_attachment_path'] = $record_path;
			// 	$array['email_queue_attachments'][0]['email_attachment_name'] = $record_name;
			// 	$array['email_queue_attachments'][0]['email_attachment_base64'] = null;

			// 	//unset the variables
			// 	unset($email_attachment_type, $record_path, $record_name);
			// }

			//add temporary permissions
			$p = permissions::new();
			$p->add('email_queue_add', 'temp');
			$p->add('email_queue_edit', 'temp');

			//save the call recording transcript
			$result = $this->database->save($array, false);
			unset($array);

			//remove the temporary permissions
			$p->delete('email_queue_add', 'temp');
			$p->delete('email_queue_edit', 'temp');
		} // isset($email_address)
	}

	/**
	 * Add one or more calls recordings to the transcribe queue.
	 *
	 * @param array $records An array of records to transcribe.
	 *
	 * @return void
	 */
	public function transcribe($records) {
		if (permission_exists($this->name . '_view')) {
			//add multi-lingual support
			$language = new text;
			$text = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			//add the settings object
			$transcribe_enabled = $this->settings->get('transcribe', 'enabled', false);
			$transcribe_engine  = $this->settings->get('transcribe', 'engine', '');

			//transcribe multiple recordings
			if ($transcribe_enabled && !empty($transcribe_engine) && is_array($records) && @sizeof($records) != 0) {

				//build the array
				$x = 0;
				foreach ($records as $record) {
					//add to the array
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {

						//get the call recording file name and path
						$sql = "select call_recording_name, call_recording_path, call_direction ";
						$sql .= "from view_call_recordings ";
						$sql .= "where call_recording_uuid = :call_recording_uuid ";
						$sql .= "and call_recording_transcription is null ";
						$parameters['call_recording_uuid'] = $record['uuid'];
						$field = $this->database->select($sql, $parameters, 'row');
						if (
							is_array($field) &&
							@sizeof($field) != 0 &&
							file_exists($field['call_recording_path'] . '/' . $field['call_recording_name'])
						) {
							//prepare the paramaters
							$params['domain_uuid'] = $this->domain_uuid;
							$params['xml_cdr_uuid'] = $record['uuid'];
							$params['call_direction'] = $field['call_direction'];

							//add the recording to the transcribe queue
							$array['transcribe_queue'][$x]['transcribe_queue_uuid'] = $record['uuid'];
							$array['transcribe_queue'][$x]['domain_uuid'] = $this->domain_uuid;
							$array['transcribe_queue'][$x]['hostname'] = gethostname();
							$array['transcribe_queue'][$x]['transcribe_status'] = 'pending';
							$array['transcribe_queue'][$x]['transcribe_app_class'] = 'call_recordings';
							$array['transcribe_queue'][$x]['transcribe_app_method'] = 'transcribe_queue';
							$array['transcribe_queue'][$x]['transcribe_app_params'] = json_encode($params);
							$array['transcribe_queue'][$x]['transcribe_audio_path'] = $field['call_recording_path'];
							$array['transcribe_queue'][$x]['transcribe_audio_name'] = $field['call_recording_name'];

							//increment the id
							$x++;
						}
						unset($sql, $parameters, $field);

					}
				}

				//update the checked rows
				if (is_array($array) && @sizeof($array) != 0) {

					//add temporary permissions
					$p = permissions::new();
					$p->add('transcribe_queue_add', 'temp');

					//remove record_path, record_name and record_length
					$this->database->app_name = 'xml_cdr';
					$this->database->app_uuid = '4a085c51-7635-ff03-f67b-86e834422848';
					$this->database->save($array, false);
					unset($array);

					//remove the temporary permissions
					$p->delete('transcribe_queue_add', 'temp');

					//set message
					message::add($text['message-added_to_queue']);

				}
				unset($records);
			}
		}
	}

	/**
	 * Downloads one or multiple call recordings.
	 *
	 * @param array $records An optional array of recording UUIDs to download. If null, the method will attempt to download a single recording based on the current object's recording_uuid property.
	 *
	 * @return void
	 */
	public function download($records = null) {
		if (permission_exists('call_recording_play') || permission_exists('call_recording_download')) {

			//get the settings
			$record_name  = $this->settings->get('call_recordings', 'record_name', '');
			$storage_type = $this->settings->get('call_recordings', 'storage_type', '');
			$time_zone    = $this->settings->get('domain', 'time_zone', '');

			//set the time zone
			if (!empty($time_zone)) {
				$time_zone = $time_zone;
			} else {
				$time_zone = date_default_timezone_get();
			}

			//single recording
			if (empty($records) || !is_array($records) || @sizeof($records) == 0) {

				//get call recording from database
				if (is_uuid($this->recording_uuid)) {
					$sql = "select ";
					$sql .= "domain_uuid, ";
					$sql .= "call_recording_uuid, ";
					$sql .= "caller_id_name, ";
					$sql .= "caller_id_number, ";
					$sql .= "caller_destination, ";
					$sql .= "destination_number, ";
					$sql .= "call_recording_name, ";
					$sql .= "call_recording_path, ";
					$sql .= "call_recording_transcription, ";
					$sql .= "call_recording_length, ";
					$sql .= "call_recording_date, ";
					$sql .= "call_direction, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'YYYY') AS call_recording_year, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'Month') AS call_recording_month_name, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'MM') AS call_recording_month_number, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'DD') AS call_recording_day, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'HH24MISS') AS call_recording_time, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'DD Mon YYYY') as call_recording_date_formatted, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'HH12:MI:SS am') as call_recording_time_formatted ";
					if (!empty($storage_type) && $storage_type == 'base64' && !empty($row['call_recording_base64'])) {
						$sql .= ", call_recording_base64 ";
					}
					$sql .= "from view_call_recordings ";
					$sql .= "where call_recording_uuid = :call_recording_uuid ";
					$parameters['call_recording_uuid'] = $this->recording_uuid;
					$parameters['time_zone'] = $time_zone;
					$row = $this->database->select($sql, $parameters, 'row');
					if (is_array($row) && @sizeof($row) != 0) {
						$call_recording_uuid = $row['call_recording_uuid'];
						$caller_id_name = $row['caller_id_name'];
						$caller_id_number = $row['caller_id_number'];
						$caller_destination = $row['caller_destination'];
						$destination_number = $row['destination_number'];
						$call_recording_name = $row['call_recording_name'];
						$call_recording_path = $row['call_recording_path'];
						$call_recording_date = $row['call_recording_date'];
						$call_direction = $row['call_direction'];
						$call_recording_year = $row['call_recording_year'];
						$call_recording_month_name = $row['call_recording_month_name'];
						$call_recording_month_number = $row['call_recording_month_number'];
						$call_recording_day = $row['call_recording_day'];
						$call_recording_time = $row['call_recording_time'];
						$call_recording_date_formatted = $row['call_recording_date_formatted'];
						$call_recording_time_formatted = $row['call_recording_time_formatted'];
						$call_recording_base64 = $row['call_recording_base64'];
						if (!empty($storage_type) && $storage_type == 'base64' && !empty($row['call_recording_base64'])) {
							file_put_contents($call_recording_path . '/' . $call_recording_name, base64_decode($row['call_recording_base64']));
						}
					}
					unset($sql, $parameters, $row);
				}

				//build full path
				$full_recording_path = $call_recording_path . '/' . $call_recording_name;

				//created custom name
				$call_recording_name_download = $call_recording_name;
				if (!empty($record_name)) {
					$call_recording_name_download = str_replace('${uuid}', $call_recording_uuid, $record_name);
					$call_recording_name_download = str_replace('${caller_id_name}', $caller_id_name, $call_recording_name_download);
					$call_recording_name_download = str_replace('${caller_id_number}', $caller_id_number, $call_recording_name_download);
					$call_recording_name_download = str_replace('${caller_destination}', $caller_destination, $call_recording_name_download);
					$call_recording_name_download = str_replace('${destination_number}', $destination_number, $call_recording_name_download);
					$call_recording_name_download = str_replace('${date}', $call_recording_date, $call_recording_name_download);
					$call_recording_name_download = str_replace('${call_direction}', $call_direction, $call_recording_name_download);
					$call_recording_name_download = str_replace('${year}', $call_recording_year, $call_recording_name_download);
					$call_recording_name_download = str_replace('${month_name}', $call_recording_month_name, $call_recording_name_download);
					$call_recording_name_download = str_replace('${month_number}', $call_recording_month_number, $call_recording_name_download);
					$call_recording_name_download = str_replace('${day}', $call_recording_day, $call_recording_name_download);
					$call_recording_name_download = str_replace('${time}', $call_recording_time, $call_recording_name_download);
				}

				//download the file
				if ($full_recording_path != '/' && file_exists($full_recording_path)) {
					ob_clean();
					$fd = fopen($full_recording_path, "rb");
					if ($this->binary) {
						header("Content-Type: application/force-download");
						header("Content-Type: application/octet-stream");
						header("Content-Type: application/download");
						header("Content-Description: File Transfer");
					} else {
						$file_ext = pathinfo($call_recording_name, PATHINFO_EXTENSION);
						switch ($file_ext) {
							case "wav" :
								header("Content-Type: audio/x-wav");
								break;
							case "mp3" :
								header("Content-Type: audio/mpeg");
								break;
							case "ogg" :
								header("Content-Type: audio/ogg");
								break;
						}
					}
					$call_recording_name_download = preg_replace('#[^a-zA-Z0-9_\-\.]#', '', $call_recording_name_download);
					header('Content-Disposition: attachment; filename="' . $call_recording_name_download . '"');
					header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
					header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
					if ($this->binary) {
						header("Content-Length: " . filesize($full_recording_path));
					}
					ob_clean();

					//content-range
					if (isset($_SERVER['HTTP_RANGE']) && !$this->binary) {
						$this->range_download($full_recording_path);
					}

					fpassthru($fd);
				}

				//if base64, remove temp recording file
				if (!empty($storage_type) && $storage_type == 'base64' && !empty($call_recording_base64)) {
					@unlink($full_recording_path);
				}

			} else { //multiple recordings

				//add multi-lingual support
				$language = new text;
				$text = $language->get();

				//validate the token
				$token = new token;
				if (!$token->validate($_SERVER['PHP_SELF'])) {
					message::add($text['message-invalid_token'], 'negative');
					header('Location: ' . $this->location);
					exit;
				}

				//drop unchecked records
				foreach ($records as $i => $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$uuids[] = $record['uuid'];
					}
				}
				unset($records, $record);

				//get data for recordings
				if (!empty($uuids) && is_array($uuids) && @sizeof($uuids) != 0) {
					$sql = "select ";
					$sql .= "domain_uuid, ";
					$sql .= "call_recording_uuid, ";
					$sql .= "caller_id_name, ";
					$sql .= "caller_id_number, ";
					$sql .= "caller_destination, ";
					$sql .= "destination_number, ";
					$sql .= "call_recording_name, ";
					$sql .= "call_recording_path, ";
					$sql .= "call_recording_transcription, ";
					$sql .= "call_recording_length, ";
					$sql .= "call_recording_date, ";
					$sql .= "call_direction, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'YYYY') AS call_recording_year, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'Month') AS call_recording_month_name, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'MM') AS call_recording_month_number, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'DD') AS call_recording_day, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'HH24MISS') AS call_recording_time, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'DD Mon YYYY') as call_recording_date_formatted, ";
					$sql .= "TO_CHAR(timezone(:time_zone, call_recording_date), 'HH12:MI:SS am') as call_recording_time_formatted ";
					if (!empty($storage_type) && $storage_type == 'base64') {
						$sql .= ", call_recording_base64 ";
					}
					$sql .= "from view_call_recordings ";
					$sql .= "where call_recording_uuid in ('" . implode("','", $uuids) . "') ";
					$parameters['time_zone'] = $time_zone;
					$rows = $this->database->select($sql, $parameters, 'all');
					if (!empty($rows) && is_array($rows) && @sizeof($rows) != 0) {
						foreach ($rows as $row) {
							$call_recording_uuid = $row['call_recording_uuid'];
							$caller_id_name = $row['caller_id_name'];
							$caller_id_number = $row['caller_id_number'];
							$caller_destination = $row['caller_destination'];
							$destination_number = $row['destination_number'];
							$call_recording_name = $row['call_recording_name'];
							$call_recording_path = $row['call_recording_path'];
							$call_recording_date = $row['call_recording_date'];
							$call_direction = $row['call_direction'];
							$call_recording_year = $row['call_recording_year'];
							$call_recording_month_name = $row['call_recording_month_name'];
							$call_recording_month_number = $row['call_recording_month_number'];
							$call_recording_day = $row['call_recording_day'];
							$call_recording_time = $row['call_recording_time'];
							$call_recording_date_formatted = $row['call_recording_date_formatted'];
							$call_recording_time_formatted = $row['call_recording_time_formatted'];
							if (!empty($storage_type) && $storage_type == 'base64' && !empty($row['call_recording_base64'])) {
								file_put_contents($call_recording_path . '/' . $call_recording_name, base64_decode($row['call_recording_base64']));
							}

							if (file_exists($call_recording_path . '/' . $call_recording_name)) {
								//add the original file to the array - use original file name
								if (empty($record_name)) {
									$full_recording_paths[] = $call_recording_path . '/' . $call_recording_name;
								}

								//created the custom name using the record_name as a template
								if (!empty($record_name)) {
									$call_recording_name_download = str_replace('${uuid}', $call_recording_uuid, $record_name);
									$call_recording_name_download = str_replace('${caller_id_name}', $caller_id_name, $call_recording_name_download);
									$call_recording_name_download = str_replace('${caller_id_number}', $caller_id_number, $call_recording_name_download);
									$call_recording_name_download = str_replace('${caller_destination}', $caller_destination, $call_recording_name_download);
									$call_recording_name_download = str_replace('${destination_number}', $destination_number, $call_recording_name_download);
									$call_recording_name_download = str_replace('${date}', $call_recording_date, $call_recording_name_download);
									$call_recording_name_download = str_replace('${call_direction}', $call_direction, $call_recording_name_download);
									$call_recording_name_download = str_replace('${year}', $call_recording_year, $call_recording_name_download);
									$call_recording_name_download = str_replace('${month_name}', $call_recording_month_name, $call_recording_name_download);
									$call_recording_name_download = str_replace('${month_number}', $call_recording_month_number, $call_recording_name_download);
									$call_recording_name_download = str_replace('${day}', $call_recording_day, $call_recording_name_download);
									$call_recording_name_download = str_replace('${time}', $call_recording_time, $call_recording_name_download);

									//create a symbolic link with custom name
									$command = 'ln -s ' . $call_recording_path . '/' . $call_recording_name . ' ' . $call_recording_path . '/' . $call_recording_name_download;
									system($command);

									//build the array for all the call recording with the new file name
									$full_recording_paths[] = $call_recording_path . '/' . $call_recording_name_download;
								}
							}

						}
					}
					unset($sql, $rows, $row);
				}

				//compress the recordings
				if (!empty($full_recording_paths) && is_array($full_recording_paths) && @sizeof($full_recording_paths) != 0) {
					header("Content-Type: application/x-zip");
					header("Content-Description: File Transfer");
					header('Content-Disposition: attachment; filename="call_recordings.zip"');
					header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
					header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
					passthru("zip -qj - " . implode(' ', $full_recording_paths));
				}

				//if base64, remove temp recording file
				if (!empty($storage_type) && $storage_type == 'base64' && !empty($row['call_recording_base64'])) {
					foreach ($full_recording_paths as $full_recording_path) {
						@unlink($full_recording_path);
					}
				}

				//remove the symbolic links to the custom file names
				if (!empty($record_name)) {
					foreach ($full_recording_paths as $full_recording_path) {
						@unlink($full_recording_path);
					}
				}

				//end the script
				exit;

			}

		}

	} //method

	/*
	 * range download method (helps safari play audio sources)
	 */
	private function range_download($file) {
		$fp = @fopen($file, 'rb');

		$size   = filesize($file); // File size
		$length = $size;           // Content length
		$start  = 0;               // Start byte
		$end    = $size - 1;       // End byte
		// Now that we've gotten so far without errors we send the accept range header
		/* At the moment we only support single ranges.
		* Multiple ranges requires some more work to ensure it works correctly
		* and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
		*
		* Multirange support annouces itself with:
		* header('Accept-Ranges: bytes');
		*
		* Multirange content must be sent with multipart/byteranges mediatype,
		* (mediatype = mimetype)
		* as well as a boundry header to indicate the various chunks of data.
		*/
		header("Accept-Ranges: 0-" . $length);
		// header('Accept-Ranges: bytes');
		// multipart/byteranges
		// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
		if (isset($_SERVER['HTTP_RANGE'])) {

			$c_start = $start;
			$c_end   = $end;
			// Extract the range string
			[, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);
			// Make sure the client hasn't sent us a multibyte range
			if (strpos($range, ',') !== false) {
				// (?) Shoud this be issued here, or should the first
				// range be used? Or should the header be ignored and
				// we output the whole content?
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			// If the range starts with an '-' we start from the beginning
			// If not, we forward the file pointer
			// And make sure to get the end byte if specified
			if ($range[0] == '-') {
				// The n-number of the last bytes is requested
				$c_start = $size - substr($range, 1);
			} else {
				$range   = explode('-', $range);
				$c_start = $range[0];
				$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
			}
			/* Check the range and make sure it's treated according to the specs.
			* http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
			*/
			// End bytes can not be larger than $end.
			$c_end = ($c_end > $end) ? $end : $c_end;
			// Validate the requested range and return an error if it's not correct.
			if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {

				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1; // Calculate new content length
			fseek($fp, $start);
			header('HTTP/1.1 206 Partial Content');
		}
		// Notify the client the byte range we'll be outputting
		header("Content-Range: bytes $start-$end/$size");
		header("Content-Length: $length");

		// Start buffered download
		$buffer = 1024 * 8;
		while (!feof($fp) && ($p = ftell($fp)) <= $end) {
			if ($p + $buffer > $end) {
				// In case we're only outputtin a chunk, make sure we don't
				// read past the length
				$buffer = $end - $p + 1;
			}
			set_time_limit(0); // Reset time limit for big files
			echo fread($fp, $buffer);
			flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
		}

		fclose($fp);
	}

	/**
	 * Called by the maintenance service to clean out old call recordings
	 *
	 * @param settings $settings
	 *
	 * @return void
	 */
	public static function filesystem_maintenance(settings $settings): void {
		//get the database connection object
		$database = $settings->database();

		//get an associative array of domain_uuid => domain_names
		$domains = maintenance::get_domains($database);

		//loop over each domain
		foreach ($domains as $domain_uuid => $domain_name) {
			//get the settings for this domain
			$domain_settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid]);

			//get the recording location for this domain
			$call_recording_location = $domain_settings->get('switch', 'recordings', '/var/lib/freeswitch/recordings');

			//get the retention days for this domain
			$retention_days = $domain_settings->get('call_recordings', 'filesystem_retention_days', '');

			//ensure retention days are not empty
			if (!empty($retention_days) && is_numeric($retention_days)) {
				$retention_days = intval($retention_days);

				//get list of mp3 and wav files
				$mp3_files = glob("$call_recording_location/$domain_name/archive/*/*/*/*.mp3");
				$wav_files = glob("$call_recording_location/$domain_name/archive/*/*/*/*.wav");

				//combine to single array
				$domain_call_recording_files = array_merge($mp3_files, $wav_files);

				//loop over each call recording mp3 or wav file
				foreach ($domain_call_recording_files as $file) {

					//use the maintenance service class helper function to get the modified date and see if it is older
					if (maintenance_service::days_since_modified($file) > $retention_days) {
						//remove the file when it is older
						if (unlink($file)) {
							//log success
							maintenance_service::log_write(self::class, "Removed $file from call_recordings older than $retention_days days", $domain_uuid);
						} else {
							//log failure
							maintenance_service::log_write(self::class, "Unable to remove $file", $domain_uuid, maintenance_service::LOG_ERROR);
						}
					} else {
						//file is not older - do nothing
					}
				}
			} else {
				//report the retention days is not set correctly
				maintenance_service::log_write(self::class, "Retention days not set or not a valid number", $domain_uuid, maintenance_service::LOG_ERROR);
			}
		}
	}

} //class
