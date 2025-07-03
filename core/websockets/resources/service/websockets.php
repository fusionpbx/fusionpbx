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
declare(strict_types=1);

if (version_compare(PHP_VERSION, '7.1.0', '<')) {
	die("This script requires PHP 7.1.0 or higher. You are running " . PHP_VERSION . "\n");
}

//
// Only run from the command line
//
if (PHP_SAPI !== 'cli') {
	die('This script can only be run from the command line.');
}

//
// Get the framework files
//
require_once dirname(__DIR__, 4) . '/resources/require.php';

try {

	//
	// Create a web socket service
	//
	$ws_server = websocket_service::create();

	//
	// Exit with status code given by run return value
	//
	exit($ws_server->run());
} catch (Throwable $ex) {

	////////////////////////////////////////////////////
	// Here we catch all exceptions and log the error //
	////////////////////////////////////////////////////
	//
	// Get the error details
	//
	$message = $ex->getMessage();
	$code = $ex->getCode();
	$file = $ex->getFile();
	$line = $ex->getLine();

	//
	// Show user the details
	//
	echo "FATAL ERROR: '$message' (ERROR CODE: $code) FROM $file (Line: $line)\n";
	echo $ex->getTraceAsString() . "\n";

	//
	// Exit with non-zero status code
	//
	exit($ex->getCode());
}
