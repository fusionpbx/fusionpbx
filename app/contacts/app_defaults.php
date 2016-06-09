<?php

if ($domains_processed == 1) {

	//populate new phone_label values, phone_type_* values
		$obj = new schema;
		$obj->db = $db;
		$obj->db_type = $db_type;
		$obj->schema();
		$field_exists = $obj->column_exists($db_name, 'v_contact_phones', 'phone_type');	//check if field exists
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
			$db->exec(check_sql($sql));
			unset($sql);

			$sql = "update v_contact_phones set phone_type_fax = '1' where phone_type = 'fax'";
			$db->exec(check_sql($sql));
			unset($sql);

			$sql = "update v_contact_phones set phone_type_video = '1' where phone_type = 'video'";
			$db->exec(check_sql($sql));
			unset($sql);

			$sql = "update v_contact_phones set phone_type_text = '1' where phone_type = 'cell' or phone_type = 'pager'";
			$db->exec(check_sql($sql));
			unset($sql);

			// migrate phone_type values to phone_label, correct case and make multilingual where appropriate
			$default_phone_types = array('home','work','pref','voice','fax','msg','cell','pager','modem','car','isdn','video','pcs');
			$default_phone_labels = array($text['option-home'],$text['option-work'],'Pref','Voice',$text['option-fax'],$text['option-voicemail'],$text['option-mobile'],$text['option-pager'],'Modem','Car','ISDN','Video','PCS');
			foreach ($default_phone_types as $index => $old) {
				$new = $default_phone_labels[$index];
				$sql = "update v_contact_phones set phone_label = '".$new."' where phone_type = '".$old."'";
				$db->exec(check_sql($sql));
				unset($sql);
			}

			// empty phone_type field to prevent confusion in the future
			$sql = "update v_contact_phones set phone_type = null";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		unset($obj);

	//populate primary email from deprecated field in v_contact table
		$obj = new schema;
		$obj->db = $db;
		$obj->db_type = $db_type;
		$obj->schema();
		$field_exists = $obj->column_exists($db_name, 'v_contacts', 'contact_email');	//check if field exists
		if ($field_exists) {
			// get email records
			$sql = "select * from v_contacts where contact_email is not null and contact_email != ''";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {
				foreach($result as $row) {
					$sql = "insert into v_contact_emails ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "contact_email_uuid, ";
					$sql .= "email_primary, ";
					$sql .= "email_address";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$row['domain_uuid']."', ";
					$sql .= "'".$row['contact_uuid']."', ";
					$sql .= "'".uuid()."', ";
					$sql .= "1, ";
					$sql .= "'".$row['contact_email']."' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

					//verify and remove value from old field
					$sql2 = "select email_address from v_contact_emails ";
					$sql2 .= "where domain_uuid = '".$row['domain_uuid']."' ";
					$sql2 .= "and contact_uuid = '".$row['contact_uuid']."' ";
					$sql2 .= "and email_address = '".$row['contact_email']."' ";
					$prep_statement2 = $db->prepare(check_sql($sql2));
					$prep_statement2->execute();
					$result2 = $prep_statement2->fetchAll(PDO::FETCH_NAMED);
					$result_count2 = count($result2);
					if ($result_count2 > 0) {
						$sql3 = "update v_contacts set contact_email = '' ";
						$sql3 .= "where domain_uuid = '".$row['domain_uuid']."' ";
						$sql3 .= "and contact_uuid = '".$row['contact_uuid']."' ";
						$prep_statement3 = $db->prepare(check_sql($sql3));
						$prep_statement3->execute();
						unset($sql3, $prep_statement3);
					}
					unset($sql2, $result2, $prep_statement2);
				}
			}
		}
		unset($obj);

	//populate primary url from deprecated field in v_contact table
		$obj = new schema;
		$obj->db = $db;
		$obj->db_type = $db_type;
		$obj->schema();
		$field_exists = $obj->column_exists($db_name, 'v_contacts', 'contact_url');	//check if field exists
		if ($field_exists) {
			// get email records
			$sql = "select * from v_contacts where contact_url is not null and contact_url != ''";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {
				foreach($result as $row) {
					$sql = "insert into v_contact_urls ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "contact_url_uuid, ";
					$sql .= "url_primary, ";
					$sql .= "url_address";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$row['domain_uuid']."', ";
					$sql .= "'".$row['contact_uuid']."', ";
					$sql .= "'".uuid()."', ";
					$sql .= "1, ";
					$sql .= "'".$row['contact_url']."' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

					//verify and remove value from old field
					$sql2 = "select url_address from v_contact_urls ";
					$sql2 .= "where domain_uuid = '".$row['domain_uuid']."' ";
					$sql2 .= "and contact_uuid = '".$row['contact_uuid']."' ";
					$sql2 .= "and url_address = '".$row['contact_url']."' ";
					$prep_statement2 = $db->prepare(check_sql($sql2));
					$prep_statement2->execute();
					$result2 = $prep_statement2->fetchAll(PDO::FETCH_NAMED);
					$result_count2 = count($result2);
					if ($result_count2 > 0) {
						$sql3 = "update v_contacts set contact_url = '' ";
						$sql3 .= "where domain_uuid = '".$row['domain_uuid']."' ";
						$sql3 .= "and contact_uuid = '".$row['contact_uuid']."' ";
						$prep_statement3 = $db->prepare(check_sql($sql3));
						$prep_statement3->execute();
						unset($sql3, $prep_statement3);
					}
					unset($sql2, $result2, $prep_statement2);
				}
			}
		}
		unset($obj);

	//set [name]_primary fields to 0 where null
		$name_tables = array('phones','addresses','emails','urls');
		$name_fields = array('phone','address','email','url');
		foreach ($name_tables as $name_index => $name_table) {
			$sql = "update v_contact_".$name_table." set ".$name_fields[$name_index]."_primary = 0 ";
			$sql .= "where ".$name_fields[$name_index]."_primary is null ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql, $prep_statement);
		}
		unset($name_tables, $name_fields);

	//move the users from the contact groups table into the contact users table
		$sql = "select * from v_contact_groups ";
		$sql .= "where group_uuid in (select user_uuid from v_users) ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$sql = "insert into v_contact_users ";
			$sql .= "( ";
			$sql .= "contact_user_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "contact_uuid, ";
			$sql .= "user_uuid ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "( ";
			$sql .= "'".uuid()."', ";
			$sql .= "'".$row["domain_uuid"]."', ";
			$sql .= "'".$row["contact_uuid"]."', ";
			$sql .= "'".$row["group_uuid"]."' ";
			$sql .= ");";
			//echo $sql."\n";
			$db->exec($sql);
			unset($sql);

			$sql = "delete from v_contact_groups ";
			$sql .= "where contact_group_uuid = '".$row["contact_group_uuid"]."';";
			//echo $sql."\n";
			$db->exec($sql);
			unset($sql);
		}
		unset ($prep_statement);

}

?>