<?php


//add fax email templates
	if ($domains_processed == 1) {

		//build the array
		$x = 0;
		$array['email_templates'][$x]['email_template_uuid'] = 'e68ff1d0-aac3-4089-a257-2124a71938bc';
		$array['email_templates'][$x]['template_language'] = 'en-us';
		$array['email_templates'][$x]['template_category'] = 'authentication';
		$array['email_templates'][$x]['template_subcategory'] = 'email';
		$array['email_templates'][$x]['template_subject'] = 'Authentication Code';
		$array['email_templates'][$x]['template_body'] = "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "	<body>\n";
		$array['email_templates'][$x]['template_body'] .= "		<br />\n";
		$array['email_templates'][$x]['template_body'] .= "		<br><strong>Security Code</strong><br><br>\n";
		$array['email_templates'][$x]['template_body'] .= "		Use the following code to verify your identity.<br>\n";
		$array['email_templates'][$x]['template_body'] .= "		Authentication Code: \${auth_code}<br>\n";
		$array['email_templates'][$x]['template_body'] .= "		<br />\n";
		$array['email_templates'][$x]['template_body'] .= "	</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = "html";
		$array['email_templates'][$x]['template_enabled'] = "true";
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = '9a9e3b5f-c439-47da-a901-90dcd340d101';
		$array['email_templates'][$x]['template_language'] = 'en-gb';
		$array['email_templates'][$x]['template_category'] = 'authentication';
		$array['email_templates'][$x]['template_subcategory'] = 'email';
		$array['email_templates'][$x]['template_subject'] = 'Authentication Code';
		$array['email_templates'][$x]['template_body'] = "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "	<body>\n";
		$array['email_templates'][$x]['template_body'] .= "		<br />\n";
		$array['email_templates'][$x]['template_body'] .= "		<br><strong>Security Code</strong><br><br>\n";
		$array['email_templates'][$x]['template_body'] .= "		Use the following code to verify your identity.<br>\n";
		$array['email_templates'][$x]['template_body'] .= "		Authentication Code: \${auth_code}<br>\n";
		$array['email_templates'][$x]['template_body'] .= "		<br />\n";
		$array['email_templates'][$x]['template_body'] .= "	</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = "html";
		$array['email_templates'][$x]['template_enabled'] = "true";
		$x++;

		$array['email_templates'][$x]['email_template_uuid'] = '3595f4b9-8593-41ae-b463-a57b0c23d1af';
		$array['email_templates'][$x]['template_language'] = 'de-de';
		$array['email_templates'][$x]['template_category'] = 'authentication';
		$array['email_templates'][$x]['template_subcategory'] = 'email';
		$array['email_templates'][$x]['template_subject'] = 'Authentifizierungscode';
		$array['email_templates'][$x]['template_body'] = "<html>\n";
		$array['email_templates'][$x]['template_body'] .= "	<body>\n";
		$array['email_templates'][$x]['template_body'] .= "		<br />\n";
		$array['email_templates'][$x]['template_body'] .= "		<br><strong>Sicherheitscode</strong><br><br>\n";
		$array['email_templates'][$x]['template_body'] .= "		Benutzen Sie den folgenden Sicherheitscode um Ihre Identität zu bestätigen.<br>\n";
		$array['email_templates'][$x]['template_body'] .= "		Sicherheitscode: \${auth_code}<br>\n";
		$array['email_templates'][$x]['template_body'] .= "		<br />\n";
		$array['email_templates'][$x]['template_body'] .= "	</body>\n";
		$array['email_templates'][$x]['template_body'] .= "</html>\n";
		$array['email_templates'][$x]['template_type'] = "html";
		$array['email_templates'][$x]['template_enabled'] = "true";
		$x++;

		//build array of email template uuids
		foreach ($array['email_templates'] as $row) {
			if (is_uuid($row['email_template_uuid'])) {
				$uuids[] = $row['email_template_uuid'];
			}
		}

		//add the email templates to the database
		if (!empty($uuids)) {
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
		if (!empty($array['email_templates'])) {
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
		if (!empty($array)) {
			unset($array);
		}

	}

?>
