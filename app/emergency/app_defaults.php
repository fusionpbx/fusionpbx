<?php

//if the number of rows is 0 then read the acl xml into the database
	if ($domains_processed == 1) {

		//move the emergency email address to a new default setting in the emergency category
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_uuid = '9317ddfd-6cb1-4294-9c57-4061dde66fe4' ";
		$sql .= "and length(default_setting_value) > 0 ";
		$row = $database->select($sql, null, 'row');
		if (isset($row) && is_array($row) && count($row) != 0) {
			//ensure the new default setting exists before continuing
			$sql = "select count(*) from v_default_settings ";
			$sql .= "where default_setting_uuid = '995d09b6-c37b-4eda-a458-5740b955206f' ";
			$num_rows = $database->select($sql, null, 'column');
			if ($num_rows > 0) {
				//move the values to the new default setting
				$sql = "update v_default_settings set default_setting_value = '".$row['default_setting_value']."' ";
				$sql .= "where default_setting_uuid = '995d09b6-c37b-4eda-a458-5740b955206f' ";
				$database->execute($sql, null);

				//move the values to the new default setting
				$sql = "update v_domain_settings set domain_setting_category = 'emergency', domain_setting_subcategory = 'email_address' ";
				$sql .= "where domain_setting_category = 'dialplan' ";
				$sql .= "and domain_setting_subcategory = 'emergency_email_address' ";
				$database->execute($sql, null);

				//delete the old default setting after the new default setting has been updated
				$sql = "delete from v_default_settings ";
				$sql .= "where default_setting_uuid = '9317ddfd-6cb1-4294-9c57-4061dde66fe4' ";
				$database->execute($sql, null);
			}
		}

	}

?>