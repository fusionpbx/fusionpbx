<?php

if ($domains_processed == 1) {

	//replace - with an @ symbol
	$sql = "update v_dialplan_details ";
	$sql .= "set dialplan_detail_data = replace(dialplan_detail_data, '-','@') ";
	$sql .= "where dialplan_detail_type = 'conference' and dialplan_detail_data like '%-%';";
	$database = new database;
	$database->execute($sql);
	unset($sql);

	//add the domain_name as the context
	$sql = "UPDATE v_conferences as c ";
	$sql .= "SET conference_context = ( ";
	$sql .= "	SELECT domain_name FROM v_domains as d ";
	$sql .= "	WHERE d.domain_uuid = c.domain_uuid ";
	$sql .= ") ";
	$sql .= "WHERE conference_context is null; ";
	$database = new database;
	$database->execute($sql);
	unset($sql);

}

?>
