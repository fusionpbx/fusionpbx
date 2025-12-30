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
 * Active Conferences Service
 * Polls conference data and broadcasts via websockets
 */

if (version_compare(PHP_VERSION, '7.1.0', '<')) {
	die("This script requires PHP 7.1.0 or higher. You are running " . PHP_VERSION . "\n");
}

require_once dirname(__DIR__, 4) . '/resources/require.php';

define('SERVICE_NAME', active_conferences_service::get_service_name());

try {
	$active_conferences_service = active_conferences_service::create();
	// Exit using whatever status run returns
	exit($active_conferences_service->run());
} catch (Exception $ex) {
	echo "Error occurred in " . $ex->getFile() . ' (' . $ex->getLine() . '):' . $ex->getMessage();
	// Exit with error code
	exit($ex->getCode());
}
