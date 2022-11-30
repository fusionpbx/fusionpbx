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

function google_get_contacts($token, $max_results = 50) {
	//global $records;
	global $groups;

	//$url = 'https://www.google.com/m8/feeds/contacts/default/full?max-results='.$max_results.'&oauth_token='.$_SESSION['contact_auth']['token']; // all contacts as xml
	//$url = 'https://www.google.com/m8/feeds/contacts/default/full/78967d550d3fdd99?alt=json&v=3.0&oauth_token='.$_SESSION['contact_auth']['token']; // single contact
	$url = 'https://www.google.com/m8/feeds/contacts/default/full?max-results='.$max_results.'&alt=json&v=3.0&oauth_token='.$token; // all contacts as json
	$xml_response = curl_file_get_contents($url);
	$records = json_decode($xml_response, true);

	//check for authentication errors (logged out of google account, or app access permission revoked, etc)
	if ($records['error']['code']) {
		header("Location: contact_auth.php?source=google&target=".substr($_SERVER["PHP_SELF"], strrpos($_SERVER["PHP_SELF"],'/')+1));
		exit;
	}

	//create new array of contacts
	foreach($records['feed']['entry'] as $contact['number'] => $contact) {
		$contact_id = substr($contact['id']['$t'], strrpos($contact['id']['$t'], "/")+1);
		$contacts[$contact_id]['etag'] = $contact['gd$etag'];
		$contacts[$contact_id]['updated'] = $contact['updated']['$t'];
		$contacts[$contact_id]['name_prefix'] = $contact['gd$name']['gd$namePrefix']['$t'];
		$contacts[$contact_id]['name_given'] = $contact['gd$name']['gd$givenName']['$t'];
		$contacts[$contact_id]['name_middle'] = $contact['gd$name']['gd$additionalName']['$t'];
		$contacts[$contact_id]['name_family'] = $contact['gd$name']['gd$familyName']['$t'];
		$contacts[$contact_id]['name_suffix'] = $contact['gd$name']['gd$nameSuffix']['$t'];
		$contacts[$contact_id]['nickname'] = $contact['gContact$nickname']['$t'];
		$contacts[$contact_id]['title'] = $contact['gd$organization'][0]['gd$orgTitle']['$t'];
		$contacts[$contact_id]['organization'] = $contact['gd$organization'][0]['gd$orgName']['$t'];
		foreach ($contact['gd$email'] as $contact_email['number'] => $contact_email) {
			if ($contact_email['label']) {
				$contact_email_label = $contact_email['label'];
			}
			else {
				$contact_email_label = substr($contact_email['rel'], strpos($contact_email['rel'], "#")+1);
				$contact_email_label = ucwords(str_replace("_", " ", $contact_email_label));
			}
			$contacts[$contact_id]['emails'][$contact_email['number']]['label'] = $contact_email_label;
			$contacts[$contact_id]['emails'][$contact_email['number']]['address'] = $contact_email['address'];
			$contacts[$contact_id]['emails'][$contact_email['number']]['primary'] = ($contact_email['primary']) ? 1 : 0;
		}
		foreach ($contact['gd$phoneNumber'] as $contact_phone['number'] => $contact_phone) {
			if ($contact_phone['label']) {
				$contact_phone_label = $contact_phone['label'];
			}
			else {
				$contact_phone_label = substr($contact_phone['rel'], strpos($contact_phone['rel'], "#")+1);
				$contact_phone_label = ucwords(str_replace("_", " ", $contact_phone_label));
			}
			$contacts[$contact_id]['numbers'][$contact_phone['number']]['label'] = $contact_phone_label;
			$contacts[$contact_id]['numbers'][$contact_phone['number']]['number'] = preg_replace('{\D}', '', $contact_phone['$t']);
		}
		foreach ($contact['gContact$website'] as $contact_website['number'] => $contact_website) {
			$contact_website_label = ($contact_website['label']) ? $contact_website['label'] : ucwords(str_replace("_", " ", $contact_website['rel']));
			$contacts[$contact_id]['urls'][$contact_website['number']]['label'] = $contact_website_label;
			$contacts[$contact_id]['urls'][$contact_website['number']]['url'] = $contact_website['href'];
		}
		foreach ($contact['gd$structuredPostalAddress'] as $contact_address['number'] => $contact_address) {
			if ($contact_address['label']) {
				$contact_address_label = $contact_address['label'];
			}
			else {
				$contact_address_label = substr($contact_address['rel'], strpos($contact_address['rel'], "#")+1);
				$contact_address_label = ucwords(str_replace("_", " ", $contact_address_label));
			}
			$contacts[$contact_id]['addresses'][$contact_address['number']]['label'] = $contact_address_label;
			$contacts[$contact_id]['addresses'][$contact_address['number']]['street'] = $contact_address['gd$street']['$t'];
			$contacts[$contact_id]['addresses'][$contact_address['number']]['extended'] = $contact_address['gd$pobox']['$t'];
			$contacts[$contact_id]['addresses'][$contact_address['number']]['community'] = $contact_address['gd$neighborhood']['$t'];
			$contacts[$contact_id]['addresses'][$contact_address['number']]['locality'] = $contact_address['gd$city']['$t'];
			$contacts[$contact_id]['addresses'][$contact_address['number']]['region'] = $contact_address['gd$region']['$t'];
			$contacts[$contact_id]['addresses'][$contact_address['number']]['postal_code'] = $contact_address['gd$postcode']['$t'];
			$contacts[$contact_id]['addresses'][$contact_address['number']]['country'] = $contact_address['gd$country']['$t'];
		}
		foreach ($contact['gContact$groupMembershipInfo'] as $contact_group['number'] => $contact_group) {
			$contact_group_id = substr($contact_group['href'], strrpos($contact_group['href'], "/")+1);
			$contacts[$contact_id]['groups'][$contact_group_id] = $groups[$contact_group_id]['name'];
		}
		$contacts[$contact_id]['notes'] = $contact['content']['$t'];
	}

	//set account holder info
	$_SESSION['contact_auth']['name'] = $records['feed']['author'][0]['name']['$t'];
	$_SESSION['contact_auth']['email'] = $records['feed']['author'][0]['email']['$t'];

	return $contacts;
}
?>