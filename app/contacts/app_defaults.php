<?php

	if ($domains_processed == 1) {

		//create one instance of the database
		$database = new database;

		//populate new phone_label values, phone_type_* values
		$obj = new schema;
		$obj->db_type = $db_type;
		$obj->schema();
		$field_exists = $obj->column_exists($db_name, 'v_contact_phones', 'phone_type'); //check if field exists
		if ($field_exists) {
			//add multi-lingual support
			$language = new text;
			$text = $language->get();

			// populate phone_type_* values
			$sql = "update v_contact_phones set phone_type_voice = '1' ";
			$sql .= "where phone_type = 'home' ";
			$sql .= "or phone_type = 'work' ";
			$sql .= "or phone_type = 'voice' ";
			$sql .= "or phone_type = 'voicemail' ";
			$sql .= "or phone_type = 'cell' ";
			$sql .= "or phone_type = 'pcs' ";
			$database->execute($sql);
			unset($sql);

			$sql = "update v_contact_phones set phone_type_fax = '1' where phone_type = 'fax'";
			$database->execute($sql);
			unset($sql);

			$sql = "update v_contact_phones set phone_type_video = '1' where phone_type = 'video'";
			$database->execute($sql);
			unset($sql);

			$sql = "update v_contact_phones set phone_type_text = '1' where phone_type = 'cell' or phone_type = 'pager'";
			$database->execute($sql);
			unset($sql);

			// migrate phone_type values to phone_label, correct case and make multilingual where appropriate
			$default_phone_types = array('home', 'work', 'pref', 'voice', 'fax', 'msg', 'cell', 'pager', 'modem', 'car', 'isdn', 'video', 'pcs');
			$default_phone_labels = array($text['option-home'],$text['option-work'], 'Pref', 'Voice', $text['option-fax'], $text['option-voicemail'], $text['option-mobile'], $text['option-pager'], 'Modem', 'Car', 'ISDN', 'Video', 'PCS');
			foreach ($default_phone_types as $index => $old) {
				$sql = "update v_contact_phones set phone_label = :phone_label where phone_type = :phone_type ";
				$parameters['phone_label'] = $default_phone_labels[$index]; //new
				$parameters['phone_type'] = $old;
				$database->execute($sql, $parameters);
				unset($sql, $parameters);
			}

			// empty phone_type field to prevent confusion in the future
			$sql = "update v_contact_phones set phone_type is null";
			$database->execute($sql);
			unset($sql);
		}

		//populate primary email from deprecated field in v_contact table
		$field_exists = $obj->column_exists($db_name, 'v_contacts', 'contact_email'); //check if field exists
		if ($field_exists) {
			// get email records
			$sql = "select * from v_contacts where contact_email is not null and contact_email != '' ";
			$result = $database->select($sql);
			unset($sql);

			if ($result !== false) {
				foreach ($result as $row) {
					$array['contact_emails'][0]['contact_email_uuid'] = uuid();
					$array['contact_emails'][0]['domain_uuid'] = $row['domain_uuid'];
					$array['contact_emails'][0]['contact_uuid'] = $row['contact_uuid'];
					$array['contact_emails'][0]['email_primary'] = 1;
					$array['contact_emails'][0]['email_address'] = $row['contact_email'];

					$p = new permissions;
					$p->add('contact_email_add', 'temp');

					$database->app_name = contacts::APP_NAME;
					$database->app_uuid = contacts::APP_UUID;
					$database->save($array, false);
					unset($array);

					$p->delete('contact_email_add', 'temp');

					//verify and remove value from old field
					$sql = "select email_address from v_contact_emails ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and contact_uuid = :contact_uuid ";
					$sql .= "and email_address = :email_address ";
					$parameters['domain_uuid'] = $row['domain_uuid'];
					$parameters['contact_uuid'] = $row['contact_uuid'];
					$parameters['email_address'] = $row['contact_email'];
					$result_2 = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters);

					if ($result_2 !== false) {
						$sql = "update v_contacts set contact_email = null ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and contact_uuid = :contact_uuid ";
						$parameters['domain_uuid'] = $row['domain_uuid'];
						$parameters['contact_uuid'] = $row['contact_uuid'];
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}
					unset($result_2);
				}
			}
			unset($result, $row);
		}

		//populate primary url from deprecated field in v_contact table
		$field_exists = $obj->column_exists($db_name, 'v_contacts', 'contact_url'); //check if field exists
		if ($field_exists) {
			// get email records
			$sql = "select * from v_contacts where contact_url is not null and contact_url != ''";
			$result = $database->select($sql);
			unset($sql);

			if ($result !== false) {
				foreach ($result as $row) {
					$array['contact_urls'][0]['contact_url_uuid'] = uuid();
					$array['contact_urls'][0]['domain_uuid'] = $row['domain_uuid'];
					$array['contact_urls'][0]['contact_uuid'] = $row['contact_uuid'];
					$array['contact_urls'][0]['url_primary'] = 1;
					$array['contact_urls'][0]['url_address'] = $row['contact_url'];

					$p = new permissions;
					$p->add('contact_url_add', 'temp');

					$database->app_name = contacts::APP_NAME;
					$database->app_uuid = contacts::APP_UUID;
					$database->save($array, false);
					unset($array);

					$p->delete('contact_url_add', 'temp');

					//verify and remove value from old field
					$sql = "select url_address from v_contact_urls ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and contact_uuid = :contact_uuid ";
					$sql .= "and url_address = :url_address ";
					$parameters['domain_uuid'] = $row['domain_uuid'];
					$parameters['contact_uuid'] = $row['contact_uuid'];
					$parameters['url_address'] = $row['contact_url'];
					$result_2 = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters);

					if (is_array($result_2) && @sizeof($result_2) != 0) {
						$sql = "update v_contacts set contact_url = '' ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and contact_uuid = :contact_uuid ";
						$parameters['domain_uuid'] = $row['domain_uuid'];
						$parameters['contact_uuid'] = $row['contact_uuid'];
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}
					unset($result_2);
				}
			}
			unset($result, $row);
		}

		//set [name]_primary fields to 0 where null
		$name_tables = array('phones', 'addresses', 'emails', 'urls');
		$name_fields = array('phone', 'address', 'email', 'url');
		foreach ($name_tables as $name_index => $name_table) {
			$sql = "update v_contact_" . $name_table . " set " . $name_fields[$name_index] . "_primary = 0 ";
			$sql .= "where " . $name_fields[$name_index] . "_primary is null ";
			$database->execute($sql);
			unset($sql);
		}
		unset($name_tables, $name_fields, $name_index, $name_table);

		//move the users from the contact groups table into the contact users table
		$sql = "select * from v_contact_groups ";
		$sql .= "where group_uuid in (select user_uuid from v_users) ";
		$result = $database->select($sql, null, 'all');
		if ($result !== false) {
			foreach ($result as &$row) {
				$p = new permissions;
				$p->add('contact_user_add', 'temp');
				$p->add('contact_group_delete', 'temp');

				$array['contact_users'][0]['contact_user_uuid'] = uuid();
				$array['contact_users'][0]['domain_uuid'] = $row["domain_uuid"];
				$array['contact_users'][0]['contact_uuid'] = $row["contact_uuid"];
				$array['contact_users'][0]['user_uuid'] = $row["group_uuid"];

				$database->app_name = contacts::APP_NAME;
				$database->app_uuid = contacts::APP_UUID;
				$database->save($array, false);
				unset($array);

				$array['contact_groups'][0]['contact_group_uuid'] = $row["contact_group_uuid"];

				$database->app_name = contacts::APP_NAME;
				$database->app_uuid = contacts::APP_UUID;
				$database->delete($array);
				unset($array);

				$p->delete('contact_user_add', 'temp');
				$p->delete('contact_group_delete', 'temp');
			}
		}
		unset($sql, $result, $row);
	}
?>
