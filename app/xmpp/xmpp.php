<?php
/* $Id$ */
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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Ken Rice     <krice@tollfreegateway.com>
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('xmpp_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();


require_once "resources/header.php";
$document['title'] = $text['title-xmpp'];

require_once "resources/paging.php";

//connect to event socket
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if ($fp) {
	if (strlen($_GET["a"]) > 0) {
		if ($_GET["a"] == "reload") {
			$cmd = 'api dingaling reload';
			$response = trim(event_socket_request($fp, $cmd));
			$msg = '<strong>Reload:</strong><pre>'.$response.'</pre>';
		}
	}
}

if (!function_exists('switch_dingaling_status')) {
	function switch_dingaling_status($fp, $profile_username, $result_type = 'xml') {
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		$cmd = 'api dingaling status';
		$response = trim(event_socket_request($fp, $cmd));
		$response = explode("\n", $response);
		$x = 0;
		foreach ($response as $row) {
			if ($x > 1) {
				$dingaling = explode("|", $row);
				if ($profile_username == trim($dingaling[0])) {
					return trim($dingaling[1]);
				}
			}
			$x++;
		}
	}
}

//get a list of the xmpp accounts
$sql = "select * from v_xmpp ";
$sql .= "where domain_uuid = '$domain_uuid' ";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$x = 0;
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
foreach ($result as &$row) {
	$profiles_array[$x] = $row;
	$profiles_array[$x]['status'] = switch_dingaling_status($fp, $row['username'].'/talk');
	$x++;
}
unset ($prep_statement);

//include the view
include "profile_list.php";

//include the footer
require_once "resources/footer.php";

?>