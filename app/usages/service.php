<?php

require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('source_usages') && $_REQUEST['method'] == 'getUsages' && !empty($_REQUEST['case'])) {
    
	$usages = new usages($_REQUEST['show'], $_REQUEST['domain_uuid'], $_REQUEST['search'], $_REQUEST['page'], $_REQUEST['user_uuid']);
    $usage_data = $usages->get($_REQUEST['case']);

    // The Output
    echo json_encode(array("data" => $usage_data), true);

    unset($usages, $usage_data);
}

?>