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

function paging($num_rows, $param, $rows_per_page, $mini = false, $result_count = 0) {

	//validate the data
	if (!is_numeric($num_rows)) { $num_rows = 0; }
	if (!is_numeric($rows_per_page)) { $rows_per_page = 100; }
	if (!is_numeric($result_count)) { $result_count = 0; }

	// if $_get['page'] defined, use it as page number
	if (isset($_GET['page']) && is_numeric($_GET['page'])) {
		$page_number = $_GET['page'];
	}
	else {
		$page_number = 0;
	}

	//sanitize the parameters
	$sanitized_parameters = '';
	if (isset($param) && strlen($param) > 0) {
		$param_array = explode("&", $param);
		if (is_array($param_array)) {
			foreach($param_array as $row) {
				$param_sub_array = explode("=", $row);
				$key = preg_replace('#[^a-zA-Z0-9_\-]#', '', $param_sub_array['0']);
				$value = urldecode($param_sub_array['1']);
				if ($key == 'order_by' && strlen($value) > 0) {
					//validate order by
					$sanitized_parameters .= "&order_by=". preg_replace('#[^a-zA-Z0-9_\-]#', '', $value);
				}
				else if ($key == 'order' && strlen($value) > 0) {
					//validate order
					switch ($value) {
						case 'asc':
							$sanitized_parameters .= "&order=asc";
							break;
						case 'desc':
							$sanitized_parameters .= "&order=desc";
							break;
					}
				}
				else if (strlen($value) > 0 && is_numeric($value)) {
					$sanitized_parameters .= "&".$key."=".$value;
				}
				else {
					$sanitized_parameters .= "&".$key."=".urlencode($value);
				}
			}
		}
	}

	//how many pages we have when using paging
	if ($num_rows > 0) {
		$max_page = ceil($num_rows/$rows_per_page);
	}
	else {
		$max_page = 1;
	}
	
	//add multi-lingual support
	$language = new text;
	$text = $language->get();

	//print the link to access each page
	$self =  htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
	$nav = '';
	for ($page = 1; $page <= $max_page; $page++){
		if ($page == $page_number) {
			$nav .= " $page ";   // no need to create a link to current page
		}
		else {
			$nav .= " <a href='".$self."?page=".$page."'>".$page."</a> \n";
		}
	}

	if ($page_number > 0) {
		$page = $page_number - 1;
		$prev = button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>'chevron-left','link'=>$self."?page=".$page.$sanitized_parameters,'title'=>$text['label-page'].' '.($page+1)]);
		$first = button::create(['type'=>'button','label'=>$text['button-next'],'icon'=>'chevron-left','link'=>$self."?page=1".$sanitized_parameters]);
	}
	else {
		$prev = button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>'chevron-left','onclick'=>"return false;",'title'=>'','style'=>'opacity: 0.4; -moz-opacity: 0.4; cursor: default;']);
	}

	if (($page_number + 1) < $max_page) {
		$page = $page_number + 1;
		$next = button::create(['type'=>'button','label'=>$text['button-next'],'icon'=>'chevron-right','link'=>$self."?page=".$page.$sanitized_parameters,'title'=>$text['label-page'].' '.($page+1)]);
		$last = button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>'chevron-right','link'=>$self."?page=".$max_page.$sanitized_parameters]);
	}
	else {
		$last = button::create(['type'=>'button','label'=>$text['button-next'],'icon'=>'chevron-right','link'=>$self."?page=".$max_page.$sanitized_parameters]);
		$next = button::create(['type'=>'button','label'=>$text['button-next'],'icon'=>'chevron-right','onclick'=>"return false;",'title'=>'','style'=>'opacity: 0.4; -moz-opacity: 0.4; cursor: default;']);
	}

	//if the result count is less than the rows per page then this is the last page of results
	if ($result_count > 0 and $result_count < $rows_per_page) {
		$next = button::create(['type'=>'button','label'=>(!$mini ? $text['button-next'] : null),'icon'=>'chevron-right','onclick'=>"return false;",'title'=>'','style'=>'opacity: 0.4; -moz-opacity: 0.4; cursor: default;']);
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
							"document.location.href = '".$self."?page='+(--page_num)+'".$sanitized_parameters."';\n".
							"return false;\n".
						"}\n".
					"}\n".
				"</script>\n";
		//determine size
			if ($mini) {
				$code = "<span style='white-space: nowrap;'>".$prev.$next."</span>\n".$script;
			}
			else {
				$code .= "<center style='white-space: nowrap;'>";
				$code .= "	".$prev;
				$code .= "	&nbsp;&nbsp;&nbsp;";
				$code .= "	<input id='paging_page_num' class='formfld' style='max-width: 50px; min-width: 50px; text-align: center;' type='text' value='".($page_number+1)."' onfocus='this.select();' onkeypress='return go(event);'>";
				if ($result_count == 0) {
					$code .= "	&nbsp;&nbsp;<strong>".$max_page."</strong>";
				}
				$code .= "	&nbsp;&nbsp;&nbsp;";
				$code .= "	".$next;
				$code .= "</center>\n".$script;
			}

		//add to array
			$array[] = $code;
	}
	else {
		$array[] = "";
	}
	$array[] = $rows_per_page;

	return $array;

}

?>
