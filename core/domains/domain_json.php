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
	Portions created by the Initial Developer are Copyright (C) 2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	
//check permissions
	if (permission_exists('domain_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get posted data
	if (is_array($_POST['search'])) {
		$search = $_POST['search'];
	}

//add the search term
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//validate the token	
	//$token = new token;
	//if (!$token->validate($_SERVER['PHP_SELF'])) {
	//	message::add($text['message-invalid_token'],'negative');
	//	header('Location: /');
	//	exit;
	//}

//include css
	//echo "<link rel='stylesheet' type='text/css' href='/resources/fontawesome/css/all.min.css.php'>\n";

//get the list of domains
	if (permission_exists('domain_all')) {
		$sql = "select * ";
		$sql .= "from v_domains ";
		$sql .= "where true ";
		$sql .= "and domain_enabled = 'true' \n";
		if (isset($search)) {
			$sql .= "	and ( ";
			$sql .= "		lower(domain_name) like :search ";
			$sql .= "		or lower(domain_description) like :search ";
			$sql .= "	) ";
			$parameters['search'] = '%'.$search.'%';
		}
		$sql .= "order by domain_name asc ";
		$sql .= "limit 300 ";
		$database = new database;
		$domains = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the domains
	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/app_config.php") && !is_cli()){
		require_once "app/domains/resources/domains.php";
	}

//debug information
	//print_r($domains);

//show the domains as json
	echo json_encode($domains, true);

?>
