#!/usr/bin/env php
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
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

/**
 * The purpose of this file is to subscribe to the switch events and then notify the web socket service of the event.
 *
 * Requirements:
 *   - PHP 7.1 or higher
 *
 * When an event is received from the switch, it is sent to the web socket service. The web socket service then
 * broadcasts the event to all subscribers that have subscribed to the service that broadcasted the event. The
 * web socket service only has information about who is connected. Each connection to the web socket service
 * is called a subscriber. Subscribers can be either a service or a web client. When a token is created and
 * given to the subscriber class using the "save_token" method, the subscriber is a web client. When the
 * method used is "save_service_token", the subscriber is still a subscriber but now has elevated privileges.
 * Each service can still subscribe to other events from other services just like regular subscribers. But,
 * services have the added ability to broadcast events to other subscribers.
 *
 * Line 1 of this file allows the script to be executable
 */

if (version_compare(PHP_VERSION, '7.1.0', '<')) {
	die("This script requires PHP 7.1.0 or higher. You are running " . PHP_VERSION . "\n");
}

require_once dirname(__DIR__, 4) . '/resources/require.php';

define('SERVICE_NAME', active_calls_service::get_service_name());

try {
	$active_calls_service = active_calls_service::create();
	// Exit using whatever status run returns
	exit($active_calls_service->run());
} catch (Exception $ex) {
	echo "Error occurred in " . $ex->getFile() . ' (' . $ex->getLine() . '):' . $ex->getMessage();
	// Exit with error code
	exit($ex->getCode());
}
