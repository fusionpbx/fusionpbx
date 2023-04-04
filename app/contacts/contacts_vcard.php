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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
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
if (permission_exists('contact_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if (is_array($_GET) && @sizeof($_GET) != 0) {

	//add multi-lingual support
		$language = new text;
		$text = $language->get();

	//create the vcard object
		require_once "resources/classes/vcard.php";
		$vcard = new vcard();

	//get the contact id
		$contact_uuid = $_GET["id"];

	//get the contact's information
		$sql = "select * from v_contacts ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_uuid = :contact_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_uuid'] = $contact_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$contact_type = $row["contact_type"];
			$contact_organization = $row["contact_organization"];
			$contact_name_given = $row["contact_name_given"];
			$contact_name_family = $row["contact_name_family"];
			$contact_nickname = $row["contact_nickname"];
			$contact_title = $row["contact_title"];
			$contact_role = $row["contact_role"];
			$contact_time_zone = $row["contact_time_zone"];
			$contact_note = $row["contact_note"];
		}
		unset($sql, $parameters, $row);

		$vcard->data['company'] = $contact_organization;
		$vcard->data['first_name'] = $contact_name_given;
		$vcard->data['last_name'] = $contact_name_family;

	//get the contact's primary (and a secondary, if available) email
		$sql = "select email_address from v_contact_emails ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_uuid = :contact_uuid ";
		$sql .= "order by email_primary desc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_uuid'] = $contact_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			$e = 1;
			foreach ($result as &$row) {
				$vcard->data['email'.$e] = $row["email_address"];
				if ($e++ == 2) { break; } //limit to 2 rows
			}
		}
		unset($sql, $parameters, $result, $row);

	//get the contact's primary url
		$sql = "select url_address from v_contact_urls ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_uuid = :contact_uuid ";
		$sql .= "and url_primary = 1 ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_uuid'] = $contact_uuid;
		$database = new database;
		$url_address = $database->select($sql, $parameters, 'column');
		$vcard->data['url'] = $url_address;
		unset($sql, $parameters, $row);


		if ($_GET['type'] == "image" || $_GET['type'] == "html") {
			//don't add this to the QR code at this time
		}
		else {
			$vcard->data['display_name'] = $contact_name_given." ".$contact_name_family;
			$vcard->data['contact_nickname'] = $contact_nickname;
			$vcard->data['contact_title'] = $contact_title;
			$vcard->data['contact_role'] = $contact_role;
			$vcard->data['timezone'] = $contact_time_zone;
			$vcard->data['contact_note'] = $contact_note;
		}

	//get the contact's telephone numbers
		$sql = "select * from v_contact_phones ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and contact_uuid = :contact_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['contact_uuid'] = $contact_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as &$row) {
				$phone_label = $row["phone_label"];
				$phone_number = $row["phone_number"];
				if ($phone_label == $text['option-work']) { $vcard_phone_type = 'work'; }
				else if ($phone_label == $text['option-home']) { $vcard_phone_type = 'home'; }
				else if ($phone_label == $text['option-mobile']) { $vcard_phone_type = 'cell'; }
				else if ($phone_label == $text['option-fax']) { $vcard_phone_type = 'fax'; }
				else if ($phone_label == $text['option-pager']) { $vcard_phone_type = 'pager'; }
				else { $vcard_phone_type = 'voice'; }
				if ($vcard_phone_type != '') {
					$vcard->data[$vcard_phone_type.'_tel'] = $phone_number;
				}
			}
		}
		unset($sql, $parameters, $result, $row);

	//get the contact's addresses
		if ($_GET['type'] == "image" || $_GET['type'] == "html") {
			//don't add this to the QR code at this time
		}
		else {
			$sql = "select * from v_contact_addresses ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and contact_uuid = :contact_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['contact_uuid'] = $contact_uuid;
			$database = new database;
			$result = $database->select($sql, $parameters, 'all');
			if (is_array($result) && @sizeof($result) != 0) {
				foreach ($result as &$row) {
					$address_type = $row["address_type"];
					$address_street = $row["address_street"];
					$address_extended = $row["address_extended"];
					$address_locality = $row["address_locality"];
					$address_region = $row["address_region"];
					$address_postal_code = $row["address_postal_code"];
					$address_country = $row["address_country"];
					$address_latitude = $row["address_latitude"];
					$address_longitude = $row["address_longitude"];
					$address_type = strtolower(trim($address_type));

					$vcard->data[$address_type.'_address'] = $address_street;
					$vcard->data[$address_type.'_extended_address'] = $address_extended;
					$vcard->data[$address_type.'_city'] = $address_locality;
					$vcard->data[$address_type.'_state'] = $address_region;
					$vcard->data[$address_type.'_postal_code'] = $address_postal_code;
					$vcard->data[$address_type.'_country'] = $address_country;
				}
			}
			unset($sql, $parameters, $result, $row);
		}

	//download the vcard
		if ($_GET['type'] == "download") {
			$vcard->download();
		}

	//show the vcard in a text qr code
		if ($_GET['type'] == "text") {
			$vcard->build();
			$content = $vcard->card;
			if ($qr_vcard) {
				$qr_vcard = $content;
			}
			else {
				echo $content;
			}
		}

	//show the vcard in an image qr code
		if ($_GET['type'] == "image" || $_GET['type'] == "html") {
			$vcard->build();
			$content = $vcard->card;

			if (isset($_GET['debug'])) {
				echo "<pre>";
				print_r($vcard->data);
				echo "</pre>";
				exit;
			}

			//include
				require_once $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/qr/qrcode.php";

			//error correction level
				//QR_ERROR_CORRECT_LEVEL_L : $e = 0;
				//QR_ERROR_CORRECT_LEVEL_M : $e = 1;
				//QR_ERROR_CORRECT_LEVEL_Q : $e = 2;
				//QR_ERROR_CORRECT_LEVEL_H : $e = 3;

			//get the qr object
				$qr = QRCode::getMinimumQRCode($content, QR_ERROR_CORRECT_LEVEL_L);
		}

	//show the vcard as an png image
		if ($_GET['type'] == "image") {
			header("Content-type: image/png");
			$im = $qr->createImage(5, 10);
			imagepng($im);
			imagedestroy($im);
		}

	//show the vcard in an html qr code
		if ($_GET['type'] == "html") {
			$qr->make();
			$qr->printHTML();
		}
}

/*
//additional un accounted fields
additional_name
name_prefix
name_suffix
department
work_po_box
home_po_box
home_extended_address
home_address
home_city
home_state
home_postal_code
home_country
pager_tel
contact_email2
photo
birthday
sort_string
*/

?>
