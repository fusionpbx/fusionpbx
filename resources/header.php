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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";

//if reloadxml then run the command
	if (isset($_SESSION["reload_xml"])) {
		if (strlen($_SESSION["reload_xml"]) > 0) {
			if ($_SESSION['apply_settings'] == "true") {
				//show the apply settings prompt
			}
			else {
				//create the event socket connection
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				//reload the access control list this also runs reloadxml
					$response = event_socket_request($fp, 'api reloadxml');
					$_SESSION["reload_xml"] = '';
					unset($_SESSION["reload_xml"]);
					usleep(500);
				//clear the apply settings reminder
					$_SESSION["reload_xml"] = false;
			}
		}
	}

//set the template base directory path
	$template_base_path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';

//check if the template exists if it is missing then use the default
	if (!file_exists($template_base_path.'/'.$_SESSION['domain']['template']['name'].'/template.php')) {
		$_SESSION['domain']['template']['name'] = 'default';
	}

//start the output buffer
	include $template_base_path.'/'.$_SESSION['domain']['template']['name'].'/config.php';

//start the output buffer
	ob_start();

// get the content
	if (isset($_GET["c"])) {
		$content = $_GET["c"]; //link
	}
	else {
		$content = '';
	}

//get the parent id
	$sql = "select * from v_menu_items ";
	$sql .= "where menu_uuid = '".$_SESSION['domain']['menu']['uuid']."' ";
	$sql .= "and menu_item_link = '".$_SERVER["SCRIPT_NAME"]."' ";
	$menu_prep_statement = $db->prepare(check_sql($sql));
	$menu_prep_statement->execute();
	$menu_result = $menu_prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($menu_result as &$menu_row) {
		$_SESSION["menu_item_parent_uuid"] = $menu_row["menu_item_parent_uuid"];
		break;
	}
	unset($menu_prep_statement, $menu_result, $menu_row);

//get the content
	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/content/app_config.php")) {
		$sql = "select * from v_rss ";
		$sql .= "where domain_uuid =:domain_uuid ";
		$sql .= "and rss_category = 'content' ";
		$sql .= "and rss_link =:content ";
		$sql .= "and (length(rss_del_date) = 0 ";
		$sql .= "or rss_del_date is null) ";
		$sql .= "order by rss_order asc ";
		$content_prep_statement = $db->prepare(check_sql($sql));
		$content_prep_statement->bindParam(':domain_uuid', $_SESSION['domain_uuid']);
		if (strlen($content) == 0) {
			$content_prep_statement->bindParam(':content', $_SERVER["PHP_SELF"]);
		}
		else {
			$content_prep_statement->bindParam(':content', $content);
		}
		$content_prep_statement->execute();
		$content_result = $content_prep_statement->fetchAll(PDO::FETCH_NAMED);
		$page["title"] = '';
		foreach($content_result as $content_row) {
			$template_rss_sub_category = $content_row['rss_sub_category'];
			if (strlen($content_row['rss_group']) == 0) {
				//content is public
				$content_from_db = &$content_row['rss_description'];
				if (strlen($content_row['rss_title']) > 0) {
					$page["title"] = $content_row['rss_title'];
				}
			}
			else {
				if (if_group($content_row[rss_group])) { //viewable only to designated group
					$content_from_db = &$content_row[rss_description];
					if (strlen($content_row['rss_title']) > 0) {
						$page["title"] = $content_row['rss_title'];
					}
				}
			}
		} //end foreach
		unset($sql, $content_result, $content_row);
	}

//start the output buffer
	ob_start();

//for translate tool (if available)
	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/translate")) {
		require_once("app/translate/translate_header.php");
	}

?>