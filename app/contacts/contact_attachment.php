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
	Portions created by the Initial Developer are Copyright (C) 2016-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get attachment uuid
	$contact_attachment_uuid = $_GET['id'];
	$action = $_GET['action'];

//get media
	if (is_uuid($contact_attachment_uuid)) {

		$sql = "select attachment_filename, attachment_content from v_contact_attachments ";
		$sql .= "where contact_attachment_uuid = :contact_attachment_uuid ";
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['contact_attachment_uuid'] = $contact_attachment_uuid;
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$attachment = $database->select($sql, $parameters, 'row');
		unset($sql, $parameters);

		$attachment_type = strtolower(pathinfo($attachment['attachment_filename'], PATHINFO_EXTENSION));

		//determine mime type
		$content_type = 'application/octet-stream'; //set default
		$allowed_attachment_types = json_decode($_SESSION['contacts']['allowed_attachment_types']['text'], true);
		if (is_array($allowed_attachment_types) && sizeof($allowed_attachment_types) != 0) {
			if ($allowed_attachment_types[$attachment_type] != '') {
				$content_type = $allowed_attachment_types[$attachment_type];
			}
		}

		switch ($action) {
			case 'download':
				header("Content-type: ".$content_type."; charset=utf-8");
				header("Content-Disposition: attachment; filename=\"".$attachment['attachment_filename']."\"");
				header("Content-Length: ".strlen(base64_decode($attachment['attachment_content'])));
				echo base64_decode($attachment['attachment_content']);
				break;
			case 'display':
				echo "	<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>\n";
				echo "		<tr>\n";
				echo "			<td align='center' valign='middle'>\n";
				echo "				<img src=\"data:".$content_type.";base64,".$attachment['attachment_content']."\" style='width: auto; max-width: 95%; height: auto; max-height: 800px; box-shadow: 0px 1px 20px #888; background-color: #fff; cursor: pointer;' onclick=\"$('#contact_attachment_layer').fadeOut(200);\" oncontextmenu=\"window.open('contact_attachment.php?id=".$contact_attachment_uuid."&action=download'); return false;\" title=\"".$text['message-click_close_save']."\">\n";
				echo "			</td>\n";
				echo "		</tr>\n";
				echo "	</table>\n";
				break;
		}

	}

?>