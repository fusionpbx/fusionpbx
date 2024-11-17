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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

function google_get_groups($token) {
	// retrieve groups
	$url = 'https://www.google.com/m8/feeds/groups/default/full?alt=json&v=3.0&oauth_token='.$token;
	$xml_response = curl_file_get_contents($url);
	$records = json_decode($xml_response, true);

	//check for authentication errors
	if ($records['error']['code']) {
		header("Location: contact_auth.php?source=google&target=".substr($_SERVER["PHP_SELF"], strrpos($_SERVER["PHP_SELF"],'/')+1));
		exit;
	}

	//create new array of groups
	foreach($records['feed']['entry'] as $group['number'] => $group) {
		$group_id = substr($group['id']['$t'], strrpos($group['id']['$t'], "/")+1);
		$groups[$group_id]['name'] = ($group['gContact$systemGroup']['id']) ? $group['gContact$systemGroup']['id'] : $group['title']['$t'];
		$groups[$group_id]['count'] = 0;
		unset($group_id);
	}
	unset($group);

	//set account holder info
	$_SESSION['contact_auth']['name'] = $records['feed']['author'][0]['name']['$t'];
	$_SESSION['contact_auth']['email'] = $records['feed']['author'][0]['email']['$t'];

	return $groups;
}
?>