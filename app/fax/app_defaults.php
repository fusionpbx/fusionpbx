 <?php


//add fax email templates
	if ($domains_processed == 1) {

		//build the array
		$x = 0;
		$array['email_templates'][$x]['email_template_uuid'] = 'c3cc981f-3bf5-49d7-bfeb-ed688c788179';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'inbound';
		$array['email_templates'][$x]['template_subject'] = 'FAX Received: ${fax_subject_tag} ${fax_file_name}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] = "<br><strong>Fax Received</strong><br><br>";
		$array['email_templates'][$x]['template_body'] .= "Name: \${fax_file_name}<br>";
		$array['email_templates'][$x]['template_body'] .= "Extension: \${fax_extension}<br>";
		$array['email_templates'][$x]['template_body'] .= "Messages: \${fax_messages} <br>";
		$array['email_templates'][$x]['template_body'] .= "\${fax_file_warning}<br>";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = "html";
		$array['email_templates'][$x]['template_enabled'] = "true";
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = '9817e168-8d02-4b9f-a21b-e867241d68db';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'relay';
		$array['email_templates'][$x]['template_subject'] = 'FAX Received: ${fax_subject_tag} ${fax_file_name}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] = "<br><strong>Fax Received</strong><br><br>";
		$array['email_templates'][$x]['template_body'] .= "Name: \${fax_file_name}<br>";
		$array['email_templates'][$x]['template_body'] .= "Extension: \${fax_extension}<br>";
		$array['email_templates'][$x]['template_body'] .= "Messages: \${fax_messages} <br>";
		$array['email_templates'][$x]['template_body'] .= "\${fax_file_warning}<br>";
		$array['email_templates'][$x]['template_body'] .= "<br>This message arrived successfully from your fax machine, and has been queued for outbound fax delivery. You will be notified later as to the success or failure of this fax.<br>";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = "html";
		$array['email_templates'][$x]['template_enabled'] = "true";
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = 'a70a73d0-e10b-40ee-9a02-308de200ea84';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'inbound';
		$array['email_templates'][$x]['template_subject'] = 'FAX Received: ${fax_subject_tag} ${fax_file_name}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] = "<br><strong>Fax Received</strong><br><br>";
		$array['email_templates'][$x]['template_body'] .= "Name: \${fax_file_name}<br>";
		$array['email_templates'][$x]['template_body'] .= "Extension: \${fax_extension}<br>";
		$array['email_templates'][$x]['template_body'] .= "Messages: \${fax_messages} <br>";
		$array['email_templates'][$x]['template_body'] .= "\${fax_file_warning}<br>";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = "html";
		$array['email_templates'][$x]['template_enabled'] = "true";
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = '819979a1-281c-4c10-b036-3cea084dc42b';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'fax';
		$array['email_templates'][$x]['template_subcategory'] = 'relay';
		$array['email_templates'][$x]['template_subject'] = 'FAX Received: ${fax_subject_tag} ${fax_file_name}';
		$array['email_templates'][$x]['template_body'] .= "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "<body>\n";
		$array['email_templates'][$x]['template_body'] .= "<br />\n";
		$array['email_templates'][$x]['template_body'] = "<br><strong>Fax Received</strong><br><br>";
		$array['email_templates'][$x]['template_body'] .= "Name: \${fax_file_name}<br>";
		$array['email_templates'][$x]['template_body'] .= "Extension: \${fax_extension}<br>";
		$array['email_templates'][$x]['template_body'] .= "Messages: \${fax_messages} <br>";
		$array['email_templates'][$x]['template_body'] .= "\${fax_file_warning}<br>";
		$array['email_templates'][$x]['template_body'] .= "<br>This message arrived successfully from your fax machine, and has been queued for outbound fax delivery. You will be notified later as to the success or failure of this fax.<br>";
		$array['email_templates'][$x]['template_body'] .= "</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = "html";
		$array['email_templates'][$x]['template_enabled'] = "true";

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
