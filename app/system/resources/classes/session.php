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
 * Description of session
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class session {

	/**
	 * Removes old php session files. Called by the maintenance application.
	 * @param settings $settings A settings object
	 * @return void
	 */
	public static function filesystem_maintenance(settings $settings): void {
		$retention_days = $settings->get('session', 'filesystem_retention_days', '');
		if (!empty($retention_days) && is_numeric($retention_days)) {
			//get the session location
			if (session_status() === PHP_SESSION_ACTIVE) {
				//session should not normally be running already in a service
				$session_location = session_save_path();
			} else {
				//session has to be started to get the path
				session_start();
				$session_location = session_save_path();
				session_destroy();
			}
			//loop through all files and check the modified time
			$files = glob($session_location . '/sess_*');
			foreach ($files as $file) {
				if (maintenance_service::days_since_modified($file) > $retention_days) {
					//remove old file
					if (unlink($file)) {
						maintenance_service::log_write(self::class, "Removed old session file $file");
					} else {
						maintenance_service::log_write(self::class, "Unable to remove old session file $file", null, maintenance_service::LOG_ERROR);
					}
				}
			}
		}
	}
}
