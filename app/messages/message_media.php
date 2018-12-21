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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('message_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get media uuid
	$message_media_uuid = $_GET['id'];
	$message_media_source = $_GET['src'];

//get media
	if (is_uuid($message_media_uuid)) {

		$sql = "select message_media_type, message_media_content from v_message_media ";
		$sql .= "where message_media_uuid = '".$message_media_uuid."' ";
		$sql .= "and user_uuid = '".$_SESSION['user_uuid']."' ";
		$sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$media = $prep_statement->fetch(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);

		switch (strtolower($media['message_media_type'])) {
			case 'jpg':
			case 'jpeg': $content_type = 'image/jpg'; break;
			case 'png': $content_type = 'image/png'; break;
			case 'gif': $content_type = 'image/gif'; break;
			case 'aac': $content_type = 'audio/aac'; break;
			case 'wav': $content_type = 'audio/wav'; break;
			case 'mp3': $content_type = 'audio/mpeg'; break;
			case 'mp2': $content_type = 'video/mpeg'; break;
			case 'm4v': $content_type = 'video/mp4'; break;
			case 'pdf': $content_type = 'application/pdf'; break;
			case 'doc': $content_type = 'application/vnd.ms-word'; break;
			case 'docx': $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'; break;
			case 'xls': $content_type = 'application/vnd.ms-excel'; break;
			case 'xlsx': $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; break;
			case 'ppt': $content_type = 'application/vnd.ms-powerpoint'; break;
			case 'pptx': $content_type = 'application/vnd.openxmlformats-officedocument.presentationml.presentation'; break;
			case 'zip': $content_tyep = 'application/zip'; break;
			default: $content_type = 'application/octet-stream'; break;
		}

		header("Content-type: ".$content_type);
		header("Content-Length: ".strlen($media['message_media_content']));
		header("Content-Disposition: attachment; filename=\"".$message_media_source."_".$message_media_uuid.".".strtolower($media['message_media_type'])."\"");
		echo base64_decode($media['message_media_content']);

	}

?>