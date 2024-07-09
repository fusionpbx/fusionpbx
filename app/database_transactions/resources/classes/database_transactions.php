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

/**
 * Description of database_transactions
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class database_transactions {

	/**
	 * Removes old entries for in the database database_transactions table
	 * see {@link https://github.com/fusionpbx/fusionpbx-app-maintenance/} FusionPBX Maintenance App
	 * @param settings $settings Settings object
	 * @return void
	 */
	public static function database_maintenance(settings $settings): void {
		//set table name for query
		$table = 'database_transactions';
		//get a database connection
		$database = $settings->database();
		//get a list of domains
		$domains = maintenance::get_domains($database);
		foreach ($domains as $domain_uuid => $domain_name) {
			//get domain settings
			$domain_settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid]);
			//get the retention days for this domain
			$retention_days = $domain_settings->get('database_transactions', 'database_retention_days', '');
			//ensure we have a retention day
			if (!empty($retention_days) && is_numeric($retention_days)) {
				//clear out old records
				$sql = "delete from v_{$table} WHERE transaction_date < NOW() - INTERVAL '{$retention_days} days'"
					. " and domain_uuid = '{$domain_uuid}'";
				$database->execute($sql);
				if ($database->message['code'] === 200) {
					//success
					maintenance_service::log_write(self::class, "Successfully removed database_transaction entries from $domain_name", $domain_uuid);
				} else {
					//failure
					maintenance_service::log_write(self::class, "Unable to remove records for domain $domain_name", $domain_uuid, maintenance_service::LOG_ERROR);
				}
			} else {
				//report an invalid setting
				maintenance_service::log_write(self::class, "Retention days not set or not a numeric value", $domain_uuid, maintenance_service::LOG_ERROR);
			}
		}
		//ensure logs are saved
		maintenance_service::log_flush();
	}
}
