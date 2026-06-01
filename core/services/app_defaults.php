<?php

// Process this only one time
if ($domains_processed == 1) {

	// Update the service file path for debian services
	$sql = "update v_services \n";
	$sql .= "set service_file = regexp_replace(service_file, 'resources/service/debian-.*\.service', 'resources/service/debian.service') \n";
	$sql .= "where service_file like '%debian-%.service';\n";
	$database->execute($sql);
	unset($sql);

}

?>
