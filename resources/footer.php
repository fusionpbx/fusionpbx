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

//get the output from the buffer
	$body = $content_from_db.ob_get_contents();
	ob_end_clean(); //clean the buffer

//clear the template
	if ($_SESSION['theme']['cache']['boolean'] == "false") {
		$_SESSION["template_content"] = '';
	}

//set a default template
	if (strlen($_SESSION["template_content"]) == 0) { //build template if session template has no length
		$template_base_path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
		if (strlen($template_rss_sub_category) > 0) {
			//this template was assigned by the content manager
				//get the contents of the template and save it to the template variable
				$template_full_path = $template_base_path.'/'.$template_rss_sub_category.'/template.php';
				if (!file_exists($template_full_path)) {
					$_SESSION['domain']['template']['name'] = 'default';
					$template_full_path = $template_base_path.'/default/template.php';
				}
				$template = file_get_contents($template_full_path);
				$_SESSION["template_content"] = $template;
		}
		else {
			//get the contents of the template and save it to the template variable
				$template_full_path = $template_base_path.'/'.$_SESSION['domain']['template']['name'].'/template.php';
				if (!file_exists($template_full_path)) {
					$_SESSION['domain']['template']['name'] = 'default';
					$template_full_path = $template_base_path.'/default/template.php';
				}
				$template = file_get_contents($template_full_path);
				$_SESSION["template_content"] = $template;
		}
	}

//get the template
	ob_start();
	$template = $_SESSION["template_content"];
	eval('?>' . $template . '<?php ');
	$template = ob_get_contents(); //get the output from the buffer
	ob_end_clean(); //clean the buffer

//prepare the template to display the output
	$custom_head = '';

	if (isset($_SESSION["theme"]["title"]["text"])) {
		if (strlen($_SESSION["theme"]["title"]["text"]) > 0) {
			$document_title = (($document["title"] != '') ? $document["title"]." - " : null).$_SESSION["theme"]["title"]["text"];
		}
		else {
			$document_title = (($document["title"] != '') ? $document["title"]." " : null);
		}
	}
	else {
		if (isset($_SESSION["software_name"])) {
			$document_title = (($document["title"] != '') ? $document["title"]." - " : null).$_SESSION["software_name"];
		}
		else {
			$document_title = (($document["title"] != '') ? $document["title"]." " : null);
		}
	}
	$output = str_replace ("<!--{title}-->", $document_title, $template); //<!--{title}--> defined in each individual page
	$output = str_replace ("<!--{head}-->", $custom_head, $output); //<!--{head}--> defined in each individual page
	if (strlen($v_menu) > 0) {
		$output = str_replace ("<!--{menu}-->", $v_menu, $output); //defined in /resources/menu.php
	}
	else {
		$output = str_replace ("<!--{menu}-->", $_SESSION["menu"], $output); //defined in /resources/menu.php
	}
	$output = str_replace ("<!--{project_path}-->", PROJECT_PATH, $output); //defined in /resources/menu.php

	$pos = strrpos($output, "<!--{body}-->");
	if ($pos === false) {
		$output = $body; //if tag not found just show the body
	}
	else {
		//replace the body
		$output = str_replace ("<!--{body}-->", $body, $output);
	}

//send the output to the browser
	echo $output;
	unset($output);

//$statsauth = "a3az349x2bf3fdfa8dbt7x34fas5X";
//require_once "stats/stat_sadd.php";

?>