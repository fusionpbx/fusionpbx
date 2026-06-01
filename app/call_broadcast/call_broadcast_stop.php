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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('call_broadcast_send')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

// Set variables from GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? 'broadcast_name'));
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = $_GET['search'] ?? '';
	$show = $_GET['show'] ?? '';

// Build the query string
	$param = [];
	if (!empty($page)) {
		$param['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$param['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$param['order'] = $order;
	}
	if (!empty($search)) {
		$param['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('call_broadcast_all')) {
		$param['show'] = $show;
	}
	$query_string = http_build_query($param);

//get the html values and set them as variables
	$uuid = trim($_GET["id"]);

	if (is_uuid($uuid)) {
		//show the result
			if (count($_GET) > 0) {
				$fp = event_socket::create();
				if ($fp !== false) {
					$cmd = "sched_del ".$uuid;
					$result = event_socket::api($cmd);
					message::add(htmlentities($result));
				}
			}

		//redirect
			header('Location: call_broadcast_edit.php?id='.$uuid.($query_string ? '&'.$query_string : ''));
			exit;
	}

//default redirect
	header('Location: call_broadcasts.php'.($query_string ? '?'.$query_string : ''));
	exit;

?>
