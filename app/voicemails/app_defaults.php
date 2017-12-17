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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if the number of rows is 0 then read the sip profile xml into the database
	if ($domains_processed == 1) {

		//add the sip profiles to the database
			$sql = "select count(*) as num_rows from v_email_templates ";
			$sql .= "where template_category = 'email' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row['num_rows'] == 0) {

					//build the array
					$x = 0;
					$array['email_templates'][$x]['email_template_uuid'] = '861e6e04-92fe-4bfb-a983-f29b3a5c07cf';
					$array['email_templates'][$x]['template_language'] = 'de-at';
					$array['email_templates'][$x]['template_category'] = 'email';
					$array['email_templates'][$x]['template_subcategory'] = 'default';
					$array['email_templates'][$x]['template_subject'] = 'Sprachnachricht von ${caller_id_name} <${caller_id_number}> ${message_duration}';
					$array['email_templates'][$x]['template_body'] = 'Neue Sprachnachricht';
					$array['email_templates'][$x]['template_body'] .= '';
					$array['email_templates'][$x]['template_body'] .= 'Nebenstelle ${voicemail_name_formatted}';
					$array['email_templates'][$x]['template_body'] .= 'Anrufer ${caller_id_number}';
					$array['email_templates'][$x]['template_body'] .= 'Lä nge ${message_duration}';
					$array['email_templates'][$x]['template_body'] .= 'Nachricht ${message}';
					$array['email_templates'][$x]['template_enabled'] = 'true';
					$array['email_templates'][$x]['template_description'] = '';
					$x++;
					$array['email_templates'][$x]['email_template_uuid'] = 'f45935f0-7dc1-4b92-9bd7-7b35121a3ca7';
					$array['email_templates'][$x]['template_language'] = 'de-de';
					$array['email_templates'][$x]['template_category'] = 'email';
					$array['email_templates'][$x]['template_subcategory'] = 'default';
					$array['email_templates'][$x]['template_subject'] = 'Sprachnachricht von ${caller_id_name} <${caller_id_number}> ${message_duration}';
					$array['email_templates'][$x]['template_body'] = 'Neue Sprachnachricht';
					$array['email_templates'][$x]['template_body'] .= '';
					$array['email_templates'][$x]['template_body'] .= 'Nebenstelle ${voicemail_name_formatted}';
					$array['email_templates'][$x]['template_body'] .= 'Anrufer ${caller_id_number}';
					$array['email_templates'][$x]['template_body'] .= 'Lä nge ${message_duration}';
					$array['email_templates'][$x]['template_body'] .= 'Nachricht ${message}';
					$array['email_templates'][$x]['template_enabled'] = 'true';
					$array['email_templates'][$x]['template_description'] = '';
					$x++;
					$array['email_templates'][$x]['email_template_uuid'] = 'defb880a-e368-4862-b946-a5244871af55';
					$array['email_templates'][$x]['template_language'] = 'en-gb';
					$array['email_templates'][$x]['template_category'] = 'email';
					$array['email_templates'][$x]['template_subcategory'] = 'default';
					$array['email_templates'][$x]['template_subject'] = 'Voice Mail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
					$array['email_templates'][$x]['template_body'] = 'Voicemail ${caller_id_name} <${caller_id_number}>';
					$array['email_templates'][$x]['template_body'] .= '';
					$array['email_templates'][$x]['template_body'] .= 'To ${voicemail_name_formatted}';
					$array['email_templates'][$x]['template_body'] .= 'Received ${message_date}';
					$array['email_templates'][$x]['template_body'] .= 'Length ${message_duration}';
					$array['email_templates'][$x]['template_body'] .= 'Message ${message}';
					$array['email_templates'][$x]['template_enabled'] = 'true';
					$array['email_templates'][$x]['template_description'] = '';
					$x++;
					$array['email_templates'][$x]['email_template_uuid'] = 'c5f3ae42-a5af-4bb7-80a3-480cfe90fb49';
					$array['email_templates'][$x]['template_language'] = 'en-gb';
					$array['email_templates'][$x]['template_category'] = 'email';
					$array['email_templates'][$x]['template_subcategory'] = 'transcription';
					$array['email_templates'][$x]['template_subject'] = 'Voice Mail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
					$array['email_templates'][$x]['template_body'] = 'Voicemail ${caller_id_name} <${caller_id_number}>';
					$array['email_templates'][$x]['template_body'] .= '';
					$array['email_templates'][$x]['template_body'] .= 'To ${voicemail_name_formatted}';
					$array['email_templates'][$x]['template_body'] .= 'Received ${message_date}';
					$array['email_templates'][$x]['template_body'] .= 'Length ${message_duration}';
					$array['email_templates'][$x]['template_body'] .= 'Message ${message}';
					$array['email_templates'][$x]['template_enabled'] = 'true';
					$array['email_templates'][$x]['template_description'] = '';
					$x++;
					$array['email_templates'][$x]['email_template_uuid'] = '56bb3416-53fc-4a3d-936d-9e3ba869081d';
					$array['email_templates'][$x]['template_language'] = 'en-us';
					$array['email_templates'][$x]['template_category'] = 'email';
					$array['email_templates'][$x]['template_subcategory'] = 'default';
					$array['email_templates'][$x]['template_subject'] = 'Voice Mail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
					$array['email_templates'][$x]['template_body'] = 'Voicemail ${caller_id_name} <${caller_id_number}>';
					$array['email_templates'][$x]['template_body'] .= '';
					$array['email_templates'][$x]['template_body'] .= 'To ${voicemail_name_formatted}';
					$array['email_templates'][$x]['template_body'] .= 'Received ${message_date}';
					$array['email_templates'][$x]['template_body'] .= 'Length ${message_duration}';
					$array['email_templates'][$x]['template_body'] .= 'Message ${message}';
					$array['email_templates'][$x]['template_enabled'] = 'true';
					$array['email_templates'][$x]['template_description'] = '';
					$x++;
					$array['email_templates'][$x]['email_template_uuid'] = 'c8f14f37-4998-41a2-9c7b-7e810c77c570';
					$array['email_templates'][$x]['template_language'] = 'en-us';
					$array['email_templates'][$x]['template_category'] = 'email';
					$array['email_templates'][$x]['template_subcategory'] = 'transcription';
					$array['email_templates'][$x]['template_subject'] = 'Voice Mail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
					$array['email_templates'][$x]['template_body'] = 'Voicemail ${caller_id_name} <${caller_id_number}>';
					$array['email_templates'][$x]['template_body'] .= '';
					$array['email_templates'][$x]['template_body'] .= 'To ${voicemail_name_formatted}';
					$array['email_templates'][$x]['template_body'] .= 'Received ${message_date}';
					$array['email_templates'][$x]['template_body'] .= 'Length ${message_duration}';
					$array['email_templates'][$x]['template_body'] .= 'Message ${message}';
					$array['email_templates'][$x]['template_enabled'] = 'true';
					$array['email_templates'][$x]['template_description'] = '';
					$x++;

					//add the dialplan permission
					$p = new permissions;
					$p->add("email_template_add", 'temp');
					$p->add("email_template_edit", 'temp');

					//save to the data
					$database = new database;
					$database->app_name = 'email_templates';
					$database->app_uuid = '8173e738-2523-46d5-8943-13883befd2fd';
					$database->save($array);
					//$message = $database->message;
					unset($array);
					
					//remove the temporary permission
					$p->delete("email_template_add", 'temp');
					$p->delete("email_template_edit", 'temp');

				} //if ($row['num_rows'] == 0)
			} //if ($prep_statement)
	} //if ($domains_processed == 1)

?>
