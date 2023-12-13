<?php

//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";

//check permissions
if (permission_exists('emergency_logs_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//get the http post data
if (!empty($_POST['emergency_logs']) && is_array($_POST['emergency_logs'])) {
	$action = $_POST['action'];
	$search = $_POST['search'];
	$emergency_logs = $_POST['emergency_logs'];
}

//process the http post data by action
if (!empty($action) && !empty($emergency_logs) && is_array($emergency_logs) && @sizeof($emergency_logs) != 0) {

	//validate the token
	$token = new token;
	if (!$token->validate($_SERVER['PHP_SELF'])) {
		message::add($text['message-invalid_token'],'negative');
		header('Location: emergency.php');
		exit;
	}

	//prepare the array
	if (!empty($emergency_logs)) {
		foreach ($emergency_logs as $row) {
			$array['emergency_logs'][$x]['checked'] = $row['checked'];
			$array['emergency_logs'][$x]['emergency_log_uuid'] = $row['emergency_log_uuid'];
			$x++;
		}
	}

	//prepare the database object
	$database = new database;
	$database->app_name = 'emergency_logs';
	$database->app_uuid = 'de63b1ae-7750-11ee-b3a5-005056a27559';

	//send the array to the database class
	if (!empty($action) && $action == 'delete') {
		$database->delete($array);
	}

	//redirect the user
	header('Location: emergency.php'.($search != '' ? '?search='.urlencode($search) : null));
	exit;
}

//get order and order by
$order_by = $_GET["order_by"] ?? null;
$order = $_GET["order"] ?? null;

//define the variables
$search = '';
$show = '';

//add the search variable
if (!empty($_GET["search"])) {
	$search = strtolower($_GET["search"]);
}

//add the show variable
if (!empty($_GET["show"])) {
	$show = $_GET["show"];
}


//get the count
$sql = "select count(log_uuid) ";
$sql .= "from v_emergency_logs ";
if ($show == 'all') {
	$sql .= "where true ";
}
else {
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
}
if (!empty($search)) {
	$sql .= "and ( ";
	$sql .= "	lower(event) like :search ";
	$sql .= ") ";
	$parameters['search'] = '%'.$search.'%';
}
$database = new database;
$num_rows = $database->select($sql, $parameters ?? null, 'column');
unset($sql, $parameters);

//prepare to page the results
$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
$param = !empty($search) ? "&search=".$search : null;
$param .= (!empty($_GET['page']) && $show == 'all' && permission_exists('user_log_all')) ? "&show=all" : null;
$page = !empty($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 0;
list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
$offset = $rows_per_page * $page;

//set the time zone
if (isset($_SESSION['domain']['time_zone']['name'])) {
	$time_zone = $_SESSION['domain']['time_zone']['name'];
}
else {
	$time_zone = date_default_timezone_get();
}

//get the list
$sql = "select * ";
$sql .= "from v_emergency_logs ";
if ($show == 'all') {
	$sql .= "where true ";
}
else {
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
}
if (!empty($search)) {
	$sql .= "and ( ";
	$sql .= "	lower(event) like :search ";
	$sql .= ") ";
	$parameters['search'] = '%'.$search.'%';
}
$sql .= limit_offset($rows_per_page, $offset);

$database = new database;
$emergency_logs = $database->select($sql, $parameters ?? null, 'all');
unset($sql, $parameters);

//create token
$object = new token;
$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
$document['title'] = 'Emergency Logs';
require_once "resources/header.php";

//show the content
echo "<div class='action_bar' id='action_bar'>\n";
echo "	<div class='heading'><b>Emergency Logs (".$num_rows.")</b></div>\n";
echo "	<div class='actions'>\n";
if ($emergency_logs) {
	echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
}
echo 		"<form id='form_search' class='inline' method='get'>\n";
if (permission_exists('emergency_logs_view_all')) {
	if ($show == 'all') {
		echo "<input type='hidden' name='show' value='all'>\n";
	}
	else {
		echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all&search='.$search]);
	}
}
echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
if ($paging_controls_mini != '') {
	echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
}
echo "		</form>\n";
echo "	</div>\n";
echo "	<div style='clear: both;'></div>\n";
echo "</div>\n";

if ($emergency_logs) {
	echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
}

echo $text['title_description-emergency_logs']."\n";
echo "<br /><br />\n";

echo "<table class='list'>\n";
echo "<tr class='list-header'>\n";
echo "<th class='left'>Time</th>\n";
echo "<th class='left'>Date</th>\n";
echo "<th class='left'>Extension</th>\n";
echo "<th class='left'>Event</th>\n";
echo "</tr>\n";

if (!empty($emergency_logs) && is_array($emergency_logs) && @sizeof($emergency_logs) != 0) {
	$x = 0;
	foreach ($emergency_logs as $row) {
		echo "<tr class='list-row'>\n";
		echo "	<td>".escape($row['time'])."</td>\n";
		echo "	<td>".escape($row['date'])."</td>\n";
		echo "	<td>".escape($row['extension'])."</td>\n";
		echo "	<td>".escape($row['event'])."</td>\n";
		echo "</tr>\n";
		$x++;
	}
	unset($emergency_logs);
}

echo "</table>\n";
echo "<br />\n";
echo "<div align='center'>".$paging_controls."</div>\n";
echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
echo "</form>\n";

//include the footer
require_once "resources/footer.php";

?>
