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

function paging($num_rows, $param, $rows_per_page) {

	if (strlen($rows_per_page)==0) {
		$rows_per_page = "5"; //default number of rows per page
	}


// by default we show first page
	$pagenum = 0;

	// if $_get['page'] defined, use it as page number
	if(isset($_GET['page'])) {
		$pagenum = $_GET['page'];
	}

	// counting the offset
	$offset = ($pagenum - 1) * $rows_per_page;

	// how many pages we have when using paging?
	$maxpage = ceil($num_rows/$rows_per_page);

	// print the link to access each page
	$self = $_SERVER['PHP_SELF'];
	$nav = '';
	for($page = 1; $page <= $maxpage; $page++){
		if ($page == $pagenum) {
			$nav .= " $page ";   // no need to create a link to current page
		}
		else {
			$nav .= " <a href=\"$self?page=$page\">$page</a> \n";
		}
	}

	if ($pagenum > 0) {
        //echo "currently middepage<br>";
        $page = $pagenum - 1;
		$prev = "<input class='btn' type='button' name='next' value='&#9664;' onClick=\"window.location = '".$self."?page=$page".$param."';\">\n";
		$first = "<input class='btn' type='button' name='last' value='&#9650;' onClick=\"window.location = '".$self."?page=1".$param."';\">\n";

	}
	else {
		//echo "currently on the first page<br>";
		$prev = "<input class='btn' type='button' disabled name='Prev' value='&#9664;'>\n";
		//$first = "<input class='btn' type='button' name='First' value='First'>\n";
	}

	if (($pagenum + 1) < $maxpage) {
        //echo "middle page<br>";
        $page = $pagenum + 1;
		$next = "<input class='btn' type='button' name='next' value='&#9654;' onClick=\"window.location = '".$self."?page=$page".$param."';\">\n";
		$last = "<input class='btn' type='button' name='last' value='&#9660;' onClick=\"window.location = '".$self."?page=$maxpage".$param."';\">\n";

	}
	else {
        //echo "last page<br>";
		$last = "<input class='btn' type='button' name='last' value='&#9660;' onClick=\"window.location = '".$self."?page=$maxpage".$param."';\">\n";
		$next = "<input class='btn' type='button' disabled name='Next' value='&#9654;'>\n";
		//$last = "<input class='btn' type='button' name='Last' value='Last'>\n";

	}

	$returnearray = array();
	if ($maxpage > 1) {
		//$returnearray[] = $first . $prev ." Page $pagenum of $maxpage " . $next . $last;
		$returnearray[] = 	"<center>".$prev ." <input class='btn' type='button' name='middlepage1ofx' value='Page ".($pagenum+1)." of $maxpage' onClick=\"window.location = '".$self."?page=0".$param."';\"> " .$next. "</center>\n";
	}
	else {
		$returnearray[] = "";
	}
	$returnearray[] = $rows_per_page;
	$returnearray[] = $offset;

	return $returnearray;

}
?>
