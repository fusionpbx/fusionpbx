<?php

require_once dirname(__DIR__, 4) . '/resources/require.php';

try {
	// Create the service
	$service = xml_cdr_service::create();

	// Exit using the status run method returns
	exit($service->run());
} catch (Throwable $ex) {
	// Show the details of the error
	echo "Error occurred in " . $ex->getFile() . ' (' . $ex->getLine() . '):' . $ex->getMessage();

	// Exit with error code
	exit($ex->getCode());
}
