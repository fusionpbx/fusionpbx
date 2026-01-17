<?php

if ($domains_processed == 1) {

	//build the array
	$x = 0;
	$array['email_templates'][$x]['email_template_uuid'] = 'e591db34-0f35-47a5-aaa0-23e7285a0d7e';
	$array['email_templates'][$x]['template_language'] = 'en-us';
	$array['email_templates'][$x]['template_category'] = 'call_recordings';
	$array['email_templates'][$x]['template_subcategory'] = 'transcription';
	$array['email_templates'][$x]['template_subject'] = "Caller ID \${caller_id_name} <\${caller_id_number}> \${call_duration}";
	$array['email_templates'][$x]['template_body'] .= "<html>\n";
	$array['email_templates'][$x]['template_body'] .= "	<body>\n";
	$array['email_templates'][$x]['template_body'] .= "	Caller ID \${caller_id_name} <a href=\"tel:\${caller_id_number}\">\${caller_id_number}</a><br />\n";
	$array['email_templates'][$x]['template_body'] .= "	<br />\n";
	$array['email_templates'][$x]['template_body'] .= "		Date \${start_date}<br />\n";
	$array['email_templates'][$x]['template_body'] .= "		Time \${start_time} \${end_time}<br />\n";
	$array['email_templates'][$x]['template_body'] .= "		Length \${length}<br />\n";
	$array['email_templates'][$x]['template_body'] .= "	<br />\n";
	$array['email_templates'][$x]['template_body'] .= "		Transcription<br />\n";
	$array['email_templates'][$x]['template_body'] .= "		\${transcript}\n";
	$array['email_templates'][$x]['template_body'] .= "	<br />\n";
	$array['email_templates'][$x]['template_body'] .= "	<br />\n";
	$array['email_templates'][$x]['template_body'] .= "	</body>\n";
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
		$database->save($array);
		//$message = $database->message;

		//remove the temporary permission
		$p->delete("email_template_add", 'temp');
		$p->delete("email_template_edit", 'temp');
	}

	//remove the array
	unset($array);

}
