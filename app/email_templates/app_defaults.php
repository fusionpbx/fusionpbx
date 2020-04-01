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

//if the number of rows is 0 then read the sip profile xml into the database
	if ($domains_processed == 1) {

		//build the array
		$x = 0;
		$array['email_templates'][$x]['email_template_uuid'] = '5256e0aa-10a3-41a9-a7d9-47240823a186';
		$array['email_templates'][$x]['template_language'] = 'de-at';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Sprachnachricht von ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] = "Neue Sprachnachricht<br />\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Nebenstelle \${voicemail_name_formatted}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Anrufer <a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a><br />\n";
		$array['email_templates'][$x]['template_body'] .= "Lä nge \${message_duration}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Nachricht \${message}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '861e6e04-92fe-4bfb-a983-f29b3a5c07cf';
		$array['email_templates'][$x]['template_language'] = 'de-at';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Sprachnachricht von ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] = "Neue Sprachnachricht\n";
		$array['email_templates'][$x]['template_body'] .= "\n";
		$array['email_templates'][$x]['template_body'] .= "Nebenstelle \${voicemail_name_formatted}\n";
		$array['email_templates'][$x]['template_body'] .= "Anrufer \${caller_id_number}\n";
		$array['email_templates'][$x]['template_body'] .= "Lä nge \${message_duration}\n";
		$array['email_templates'][$x]['template_body'] .= "Nachricht \${message}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = 'cb0045f2-6ff1-4ed8-a030-6cec6c65b632';
		$array['email_templates'][$x]['template_language'] = 'de-de';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Sprachnachricht von ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] = "Neue Sprachnachricht<br />\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Nebenstelle \${voicemail_name_formatted}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Anrufer <a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a><br />\n";
		$array['email_templates'][$x]['template_body'] .= "Lä nge \${message_duration}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Nachricht \${message}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = 'f45935f0-7dc1-4b92-9bd7-7b35121a3ca7';
		$array['email_templates'][$x]['template_language'] = 'de-de';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Sprachnachricht von ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] = "Neue Sprachnachricht\n";
		$array['email_templates'][$x]['template_body'] .= "\n";
		$array['email_templates'][$x]['template_body'] .= "Nebenstelle \${voicemail_name_formatted}\n";
		$array['email_templates'][$x]['template_body'] .= "Anrufer \${caller_id_number}\n";
		$array['email_templates'][$x]['template_body'] .= "Lä nge \${message_duration}\n";
		$array['email_templates'][$x]['template_body'] .= "Nachricht \${message}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = '62d1e7ef-c423-4ac6-be9e-c0e2adbbb60d';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Voicemail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "From \${caller_id_name} <a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a><br />\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] .= "To \${voicemail_name_formatted}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Received \${message_date}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Length \${message_duration}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = 'defb880a-e368-4862-b946-a5244871af55';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Voicemail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] = "Voicemail from \${caller_id_name} <\${caller_id_number}>\n";
		$array['email_templates'][$x]['template_body'] .= "\n";
		$array['email_templates'][$x]['template_body'] .= "To \${voicemail_name_formatted}\n";
		$array['email_templates'][$x]['template_body'] .= "Received \${message_date}\n";
		$array['email_templates'][$x]['template_body'] .= "Length \${message_duration}\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = '5d73fb7f-c48a-4752-b5e9-bfe94b4b02d6';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'transcription';
		$array['email_templates'][$x]['template_subject'] = 'Voicemail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "Voicemail from \${caller_id_name} <a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a><br />\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] .= "To \${voicemail_name_formatted}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Received \${message_date}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Length \${message_duration}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Transcription<br />\n";
		$array['email_templates'][$x]['template_body'] .= "\${message_text}\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = 'c5f3ae42-a5af-4bb7-80a3-480cfe90fb49';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'transcription';
		$array['email_templates'][$x]['template_subject'] = 'Voicemail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] = "Voicemail from \${caller_id_name} <\${caller_id_number}>\n";
		$array['email_templates'][$x]['template_body'] .= "\n";
		$array['email_templates'][$x]['template_body'] .= "To \${voicemail_name_formatted}\n";
		$array['email_templates'][$x]['template_body'] .= "Received \${message_date}\n";
		$array['email_templates'][$x]['template_body'] .= "Length \${message_duration}\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}\n";
		$array['email_templates'][$x]['template_body'] .= "\n";
		$array['email_templates'][$x]['template_body'] .= "Transcription\n";
		$array['email_templates'][$x]['template_body'] .= "\${message_text}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = 'fbd0c8ea-6adb-4f8b-92cf-00e9087e3568';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Voicemail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "Voicemail from \${caller_id_name} <a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a><br />\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] .= "To \${voicemail_name_formatted}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Received \${message_date}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Length \${message_duration}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '56bb3416-53fc-4a3d-936d-9e3ba869081d';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Voicemail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] = "Voicemail from \${caller_id_name} <\${caller_id_number}>\n";
		$array['email_templates'][$x]['template_body'] .= "\n";
		$array['email_templates'][$x]['template_body'] .= "To \${voicemail_name_formatted}\n";
		$array['email_templates'][$x]['template_body'] .= "Received \${message_date}\n";
		$array['email_templates'][$x]['template_body'] .= "Length \${message_duration}\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = '233135c9-7e3e-48d6-b6ad-ba1a383c0ac4';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'transcription';
		$array['email_templates'][$x]['template_subject'] = 'Voicemail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "Voicemail from \${caller_id_name} <a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a><br />\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] .= "To \${voicemail_name_formatted}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Received \${message_date}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Length \${message_duration}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Transcription<br />\n";
		$array['email_templates'][$x]['template_body'] .= "\${message_text}\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = 'c8f14f37-4998-41a2-9c7b-7e810c77c570';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'transcription';
		$array['email_templates'][$x]['template_subject'] = 'Voicemail from ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] = "Voicemail from \${caller_id_name} <\${caller_id_number}>\n";
		$array['email_templates'][$x]['template_body'] .= "\n";
		$array['email_templates'][$x]['template_body'] .= "To \${voicemail_name_formatted}\n";
		$array['email_templates'][$x]['template_body'] .= "Received \${message_date}\n";
		$array['email_templates'][$x]['template_body'] .= "Length \${message_duration}\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}\n";
		$array['email_templates'][$x]['template_body'] .= "\n";
		$array['email_templates'][$x]['template_body'] .= "Transcription\n";
		$array['email_templates'][$x]['template_body'] .= "\${message_text}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = '133860ce-175f-4a6f-bfa3-ef7322e80b98';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'missed';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Missed Call from ${caller_id_name} <${caller_id_number}>';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] = "Missed Call from \${caller_id_name} &lt;<a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a>&gt; to \${sip_to_user} ext \${dialed_user}\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '890626c4-907b-44ad-9cf6-02d0b0a2379d';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'missed';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Missed Call from ${caller_id_name} <${caller_id_number}>';
		$array['email_templates'][$x]['template_body'] = "Missed Call from \${caller_id_name} &lt;\${caller_id_number}&gt; to \${sip_to_user} ext \${dialed_user}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = 'eafaf4fe-b21d-47a0-ab2c-5943cb8cb5be';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'missed';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Missed Call from ${caller_id_name} <${caller_id_number}>';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "Missed Call from \${caller_id_name} &lt;<a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a>&gt; to \${sip_to_user} ext \${dialed_user}\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = 'a1b11ded-831f-4b81-8a23-fce866196508';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'missed';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Missed Call from ${caller_id_name} <${caller_id_number}>';
		$array['email_templates'][$x]['template_body'] .= "Missed Call from \${caller_id_name} &lt;\${caller_id_number}&gt; to \${sip_to_user} ext \${dialed_user}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = '14cf1738-2304-4030-b970-a478fda35abc';
		$array['email_templates'][$x]['template_language'] = 'fr-ca';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Messagerie vocale à partir de ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "Messagerie vocale à partir de \${caller_id_name} <a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a><br />\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] .= "À \${voicemail_name_formatted}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Reçu \${message_date}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Longueur \${message_duration}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}<br />\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = 'd3971eb3-757e-4501-8469-9d59738db821';
		$array['email_templates'][$x]['template_language'] = 'fr-ca';
		$array['email_templates'][$x]['template_category'] = 'voicemail';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Messagerie vocale à partir de ${caller_id_name} <${caller_id_number}> ${message_duration}';
		$array['email_templates'][$x]['template_body'] = "Messagerie vocale à partir de \${caller_id_name} <\${caller_id_number}>\n";
		$array['email_templates'][$x]['template_body'] .= "\n";
		$array['email_templates'][$x]['template_body'] .= "À \${voicemail_name_formatted}\n";
		$array['email_templates'][$x]['template_body'] .= "Reçu \${message_date}\n";
		$array['email_templates'][$x]['template_body'] .= "Longueur \${message_duration}\n";
		$array['email_templates'][$x]['template_body'] .= "Message \${message}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = 'ca96d814-cf5e-4dca-91ab-0150c2c6c36a';
		$array['email_templates'][$x]['template_language'] = 'fr-ca';
		$array['email_templates'][$x]['template_category'] = 'missed';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Appel manqué de ${caller_id_name} <${caller_id_number}>';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "Appel manqué de \${caller_id_name} &lt;<a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a>&gt; À \${sip_to_user} ext \${dialed_user}\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '5c57bd40-0479-49ba-945a-c675cd96dc8c';
		$array['email_templates'][$x]['template_language'] = 'fr-ca';
		$array['email_templates'][$x]['template_category'] = 'missed';
		$array['email_templates'][$x]['template_subcategory'] = 'default';
		$array['email_templates'][$x]['template_subject'] = 'Appel manqué de ${caller_id_name} <${caller_id_number}>';
		$array['email_templates'][$x]['template_body'] .= "Appel manqué de \${caller_id_name} &lt;\${caller_id_number}&gt; À \${sip_to_user} ext \${dialed_user}\n";
		$array['email_templates'][$x]['template_type'] = 'text';
		$array['email_templates'][$x]['template_enabled'] = 'false';
		$array['email_templates'][$x]['template_description'] = '';

		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = 'b1eefbfc-c008-4c82-b93f-6a6df237aeaa';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'success_default';
		$array['email_templates'][$x]['template_subject'] = 'Subject, Fax to: ${number_dialed} SENT';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "We are happy to report the fax was sent successfully. It has been attached for your records.\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '48ce4fef-e6bd-4be6-9c76-1590e8498408';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'fail_default';
		$array['email_templates'][$x]['template_subject'] = 'Fax to: ${number_dialed} has Failed';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "We are sorry the fax failed to go through. It has been attached. Please check the number \${number_dialed}, and if it was correct you might consider emailing it instead.\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = 'd64d9a03-affd-4dbf-ba04-5ad5accae4d9';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'fail_busy';
		$array['email_templates'][$x]['template_subject'] = 'Fax to: ${number_dialed} was Busy';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "We tried sending, but the call was busy \${fax_busy_attempts} of those times.\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '29729743-28bc-4f7b-88e1-0bdf6ff33cce';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'fail_invalid';
		$array['email_templates'][$x]['template_subject'] = 'Fax to: ${number_dialed} was Invalid';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "We tried sending, but the number entered was not a working phone number.\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';

		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '297a3e32-125d-4e21-a528-17edc0d50829';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'success_default';
		$array['email_templates'][$x]['template_subject'] = 'Subject, Fax to: ${number_dialed} SENT';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "We are happy to report the fax was sent successfully. It has been attached for your records.\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '420d56d8-6cc5-484a-961e-da02ae0646a5';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'fail_default';
		$array['email_templates'][$x]['template_subject'] = 'Fax to: ${number_dialed} has Failed';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "We are sorry the fax failed to go through. It has been attached. Please check the number \${number_dialed}, and if it was correct you might consider emailing it instead.\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '3899a9f2-96f2-4778-8f77-9962e4bc7ec8';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'fail_busy';
		$array['email_templates'][$x]['template_subject'] = 'Fax to: ${number_dialed} was Busy';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "We tried sending, but the call was busy \${fax_busy_attempts} of those times.\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';
		$x++;
		$array['email_templates'][$x]['email_template_uuid'] = '307499c6-f390-446e-884a-3b5d7554771b';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'fail_invalid';
		$array['email_templates'][$x]['template_subject'] = 'Fax to: ${number_dialed} was Invalid';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "We tried sending, but the number entered was not a working phone number.\n";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = 'html';
		$array['email_templates'][$x]['template_enabled'] = 'true';
		$array['email_templates'][$x]['template_description'] = '';


		//build array of email template uuids
		foreach ($array['email_templates'] as $row) {
			if (is_uuid($row['email_template_uuid'])) {
				$uuids[] = $row['email_template_uuid'];
			}
		}

		//add the email templates to the database
		if (is_array($uuids) && @sizeof($uuids) != 0) {
			$sql = "select * from v_email_templates where ";
			foreach ($uuids as $index => $uuid) {
				$sql_where[] = "email_template_uuid = :email_template_uuid_".$index;
				$parameters['email_template_uuid_'.$index] = $uuid;
			}
			$sql .= implode(' or ', $sql_where);
			$database = new database;
			$email_templates = $database->select($sql, $parameters, 'all');
			unset($sql, $sql_where, $parameters);

			//remove templates that already exist from the array
			foreach ($array['email_templates'] as $index => $row) {
				if (is_array($email_templates) && @sizeof($email_templates) != 0) {
					foreach($email_templates as $email_template) {
						if ($row['email_template_uuid'] == $email_template['email_template_uuid']) {
							unset($array['email_templates'][$index]);
						}
					}
				}
			}
			unset($email_templates, $index);
		}

		//add the missing email templates
		if (is_array($array['email_templates']) && @sizeof($array['email_templates']) != 0) {
			//add the temporary permission
			$p = new permissions;
			$p->add("email_template_add", 'temp');
			$p->add("email_template_edit", 'temp');

			//save the data
			$database = new database;
			$database->app_name = 'email_templates';
			$database->app_uuid = '8173e738-2523-46d5-8943-13883befd2fd';
			$database->save($array);
			//$message = $database->message;

			//remove the temporary permission
			$p->delete("email_template_add", 'temp');
			$p->delete("email_template_edit", 'temp');
		}

		//remove the array
		unset($array);

	}

?>
