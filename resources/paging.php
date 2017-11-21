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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

function paging($num_rows, $param, $rows_per_page, $mini = false) {


	//add multi-lingual support
	$language = new text;
	$text = $language->get();

	 //default number of rows per page
	if (strlen($rows_per_page)==0) {
		$rows_per_page = 50;
	}

	// show the first page by default
	$page_number = 0;

	// if $_get['page'] defined, use it as page number
	if(isset($_GET['page']) && is_numeric($_GET['page'])) {
		$page_number = $_GET['page'];
	}

	// counting the offset
	$offset = ($page_number - 1) * $rows_per_page;

	// how many pages we have when using paging?
	$max_page = ceil($num_rows/$rows_per_page);

	// print the link to access each page
	$self = $_SERVER['PHP_SELF'];
	$nav = '';
	for($page = 1; $page <= $max_page; $page++){
		if ($page == $page_number) {
			$nav .= " $page ";   // no need to create a link to current page
		}
		else {
			$nav .= " <a href=\"$self?page=$page\">$page</a> \n";
		}
	}
	if ($page_number > 0) {
        $page = $page_number - 1;
		$prev = "<input class='btn' type='button' value='".$text['button-back']."' alt='".($page+1)."' title='".($page+1)."' onClick=\"window.location = '".$self."?page=$page".$param."';\">\n"; //&#9664;
		$first = "<input class='btn' type='button' value='".$text['button-next']."' onClick=\"window.location = '".$self."?page=1".$param."';\">\n"; //&#9650;
	}
	else {
		$prev = "<input class='btn' type='button' disabled value='".$text['button-back']."' style='opacity: 0.4; -moz-opacity: 0.4; cursor: default;'>\n"; //&#9664;
	}

	if (($page_number + 1) < $max_page) {
        $page = $page_number + 1;
		$next = "<input class='btn' type='button' value='".$text['button-next']."' alt='".($page+1)."' title='".($page+1)."' onClick=\"window.location = '".$self."?page=$page".$param."';\">\n"; //&#9654;
		$last = "<input class='btn' type='button' value='".$text['button-back']."' onClick=\"window.location = '".$self."?page=$max_page".$param."';\">\n"; //&#9660;

	}
	else {
		$last = "<input class='btn' type='button' value='".$text['button-next']."' onClick=\"window.location = '".$self."?page=$max_page".$param."';\">\n"; //&#9660;
		$next = "<input class='btn' type='button' disabled value='".$text['button-back']."' style='opacity: 0.4; -moz-opacity: 0.4; cursor: default;'>\n"; //&#9654;

	}

	$array = array();
	$code = '';
	if ($max_page > 1) {
		//define javascript to include
			$script = "<script>\n".
					"function go(e) {\n".
						"var page_num;\n".
						"page_num = document.getElementById('paging_page_num').value;\n".

						"do_action = false;\n".
						"if (e != null) {\n".
							"// called from a form field keypress event\n".
							"var keyevent;\n".
							"var keychar;\n".

							"if (window.event) { keyevent = e.keyCode; }\n".
							"else if (e.which) { keyevent = e.which; }\n".

							"keychar = keyevent;\n".
							"if (keychar == 13) {\n".
								"do_action = true;\n".
							"}\n".
							"else {\n".
								"keychar;\n".
								"return true;\n".
							"}\n".
						"}\n".
						"else {\n".
							"// called from something else (non-keypress)\n".
							"do_action = true;\n".
						"}\n".

						"if (do_action) {\n".
							"// action to peform when enter is hit\n".
							"if (page_num < 1) { page_num = 1; }\n".
							"if (page_num > ".$max_page.") { page_num = ".$max_page."; }\n".
							"document.location.href = '".$self."?page='+(--page_num)+'".$param."';\n".
						"}\n".
					"}\n".
				"</script>\n";
		//determine size
			$code = ($mini) ? $prev.$next."\n".$script : "<center nowrap>".$prev."&nbsp;&nbsp;&nbsp;<input id='paging_page_num' class='formfld' style='max-width: 50px; min-width: 50px; text-align: center;' type='text' value='".($page_number+1)."' onfocus='this.select();' onkeypress='return go(event);'>&nbsp;&nbsp;<strong>".$max_page."</strong>&nbsp;&nbsp;&nbsp;&nbsp;".$next."</center>\n".$script;
		//add to array
			$array[] = $code;
	}
	else {
		$array[] = "";
	}
	$array[] = $rows_per_page;
	$array[] = $offset;

	return $array;

}

?>
