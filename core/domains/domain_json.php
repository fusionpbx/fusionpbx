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
	Portions created by the Initial Developer are Copyright (C) 2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('domain_view')) {
		echo "access denied";
		exit;
	}

//get posted data
	if (!empty($_POST['search'])) {
		$search = is_string($_POST['search'] ?? null) ? $_POST['search'] : '';
	}

//add the search term
	if (!empty($_GET["search"])) {
		$search = lower_case(is_string($_GET["search"] ?? null) ? $_GET["search"] : '');
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
	if (permission_exists('domain_all') || permission_exists('domain_select')) {

		//a user without domain_all is limited to their own domain and the child domains below
		//it in the parent child tree. the same restriction is used by the domains list in
		//core/domains/domains.php and by the domain change check in resources/require.php.
		$user_domain_uuid = $_SESSION['user']['domain_uuid'] ?? '';
		$restrict_domains = !permission_exists('domain_all') && is_uuid($user_domain_uuid);

		$sql = "select * ";
		$sql .= "from v_domains ";
		$sql .= "where true ";
		$sql .= "and domain_enabled = true \n";
		if ($restrict_domains) {
			if ($database->select("select domain_uuid from view_domains limit 1 ", null, 'column') !== false) {
				//the view holds the parent child relationship, a domain is included when the
				//user's domain appears anywhere in its path which gives their own domain and
				//every domain below it
				$sql .= "and domain_uuid in (select domain_uuid from view_domains where :user_domain_uuid = any(domain_uuid_path)) \n";
			}
			else {
				//when the view has not been created yet only the user's own domain is included
				$sql .= "and domain_uuid = :user_domain_uuid \n";
			}
			$parameters['user_domain_uuid'] = $user_domain_uuid;
		}
		if (isset($search)) {
			$sql .= "	and ( ";
			$sql .= "		lower(domain_name) like :search ";
			$sql .= "		or lower(domain_description) like :search ";
			$sql .= "	) ";
			$search = str_replace("*", "%", $search);
			if (strstr($search, '%')) {
				$parameters['search'] = $search;
			}
			else {
				$parameters['search'] = '%'.$search.'%';
			}
		}
		$sql .= "order by domain_name asc ";
		$domains = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);
	}

//get the domains
	if (file_exists(dirname(__DIR__, 2)."/app/domains/app_config.php") && !is_cli()){
		require_once "app/domains/resources/domains.php";
	}

//debug information
	//print_r($domains);

//show the domains as json
	echo json_encode($domains, true);

?>
