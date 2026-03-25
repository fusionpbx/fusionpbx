#!/usr/bin/env php
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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Tim Fry <tim@fusionpbx.com>
*/

/**
 * Debian/Ubuntu systemd service management:
 *   Copy the operator_panel.service file to /etc/systemd/system/ using the commands:
 *     sudo cp /path/to/operator_panel.service /etc/systemd/system/
 *     sudo systemctl daemon-reload
 * Start/Stop:
 *   systemctl start  operator_panel
 *   systemctl stop   operator_panel
 *   systemctl restart operator_panel
 *   systemctl reload operator_panel
 *
 * Enable on boot:
 *   systemctl enable operator_panel
 *
 * Disable on boot:
 *   systemctl disable operator_panel
 *
 * Non-SystemD:
 *   Normal Daemon Start:
 *     ./operator_panel.php -u www-data -g www-data     # run as www-data user and group as root is prohibited for security reasons
 *   Normal Daemon Stop:
 *     ./operator_panel.php -x
 *
 * Debug Mode (runs in foreground with debug output):
 *   ./operator_panel.php -x                            # exit first if already running the daemon
 *   ./operator_panel.php -d 7 -u www-data -g www-data  # run in debug mode with log level 7 (debug)
 *
 * SystemD Log watching:
 *   journalctl -u operator_panel -f
 */

if (version_compare(PHP_VERSION, '7.1.0', '<')) {
	die("This script requires PHP 7.1.0 or higher. You are running " . PHP_VERSION . "\n");
}

require_once dirname(__DIR__, 4) . '/resources/require.php';

define('SERVICE_NAME', operator_panel_service::get_service_name());

try {
	$service = operator_panel_service::create();
	// Exit using whatever status run() returns
	exit($service->run());
} catch (Exception $ex) {
	echo "Error occurred in " . $ex->getFile() . ' (' . $ex->getLine() . '):' . $ex->getMessage() . "\n";
	exit($ex->getCode());
}
