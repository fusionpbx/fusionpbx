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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
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
	if (permission_exists('contact_attachment_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the contact attachment list
	$sql = "select *, length(decode(attachment_content,'base64')) as attachment_size from v_contact_attachments ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by attachment_primary desc, attachment_filename asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_attachments = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_attachments) && @sizeof($contact_attachments) != 0) {

		//styles and attachment layer
			echo "<style>\n";
			echo "	#contact_attachment_layer {\n";
			echo "		z-index: 999999;\n";
			echo "		position: absolute;\n";
			echo "		left: 0px;\n";
			echo "		top: 0px;\n";
			echo "		right: 0px;\n";
			echo "		bottom: 0px;\n";
			echo "		text-align: center;\n";
			echo "		vertical-align: middle;\n";
			echo "	}\n";
			echo "</style>\n";
			echo "<div id='contact_attachment_layer' style='display: none;'></div>\n";

		//display script
			echo "<script>\n";
			echo "	function display_attachment(id) {\n";
			echo "		$('#contact_attachment_layer').load('contact_attachment.php?id=' + id + '&action=display', function(){\n";
			echo "			$('#contact_attachment_layer').fadeIn(200);\n";
			echo "		});\n";
			echo "	}\n";
			echo "</script>\n";

		//show the content
			echo "<div class='grid' style='grid-template-columns: 70px auto 75px;'>\n";
			$x = 0;
			foreach ($contact_attachments as $row) {
				$attachment_type = strtolower(pathinfo($row['attachment_filename'], PATHINFO_EXTENSION));
				$attachment_type_label = $attachment_type == 'jpg' || $attachment_type == 'jpeg' || $attachment_type == 'gif' || $attachment_type == 'png' ? $text['label-image'] : $text['label-file'];
				echo "<div class='box contact-details-label'>".$attachment_type_label."</div>\n";
// 				($row['attachment_primary'] ? "&nbsp;<i class='fas fa-star fa-xs' style='float: right; margin-top: 0.5em; margin-right: -0.5em;' title=\"".$text['label-primary']."\"></i>" : null)."</td>\n";
				echo "<div class='box'>";
				if ($attachment_type == 'jpg' || $attachment_type == 'jpeg' || $attachment_type == 'gif' || $attachment_type == 'png') {
					echo button::create(['type'=>'button','class'=>'link','label'=>escape($row['attachment_filename']),'onclick'=>"display_attachment('".escape($row['contact_attachment_uuid'])."');"]);
				}
				else {
					echo button::create(['type'=>'button','class'=>'link','label'=>escape($row['attachment_filename']),'onclick'=>"window.location='contact_attachment.php?id=".urlencode($row['contact_attachment_uuid'])."&action=download';"]);
				}
				echo "</div>\n";
				echo "<div class='box' style='text-align: right; font-size: 90%;'>".strtoupper(byte_convert($row['attachment_size']))."</div>\n";
				$x++;
			}
			echo "</div>\n";
			unset($contact_attachments);

	}

?>