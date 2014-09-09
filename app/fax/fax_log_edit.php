<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('fax_log_add') || permission_exists('fax_log_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$fax_log_uuid = check_str($_REQUEST["id"]);
		$fax_uuid = check_str($_REQUEST["fax_uuid"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$fax_log_uuid = check_str($_POST["fax_log_uuid"]);
		$fax_success = check_str($_POST["fax_success"]);
		$fax_result_code = check_str($_POST["fax_result_code"]);
		$fax_result_text = check_str($_POST["fax_result_text"]);
		$fax_file = check_str($_POST["fax_file"]);
		$fax_ecm_used = check_str($_POST["fax_ecm_used"]);
		$fax_local_station_id = check_str($_POST["fax_local_station_id"]);
		$fax_document_transferred_pages = check_str($_POST["fax_document_transferred_pages"]);
		$fax_document_total_pages = check_str($_POST["fax_document_total_pages"]);
		$fax_image_resolution = check_str($_POST["fax_image_resolution"]);
		$fax_image_size = check_str($_POST["fax_image_size"]);
		$fax_bad_rows = check_str($_POST["fax_bad_rows"]);
		$fax_transfer_rate = check_str($_POST["fax_transfer_rate"]);
		$fax_retry_attempts = check_str($_POST["fax_retry_attempts"]);
		$fax_retry_limit = check_str($_POST["fax_retry_limit"]);
		$fax_retry_sleep = check_str($_POST["fax_retry_sleep"]);
		$fax_uri = check_str($_POST["fax_uri"]);
		$fax_date = check_str($_POST["fax_date"]);
		$fax_epoch = check_str($_POST["fax_epoch"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$fax_log_uuid = check_str($_POST["fax_log_uuid"]);
	}

	//check for all required data
		if (strlen($fax_log_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-fax_log_uuid']."<br>\n"; }
		if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
		if (strlen($fax_success) == 0) { $msg .= $text['message-required']." ".$text['label-fax_success']."<br>\n"; }
		if (strlen($fax_result_code) == 0) { $msg .= $text['message-required']." ".$text['label-fax_result_code']."<br>\n"; }
		if (strlen($fax_result_text) == 0) { $msg .= $text['message-required']." ".$text['label-fax_result_text']."<br>\n"; }
		if (strlen($fax_file) == 0) { $msg .= $text['message-required']." ".$text['label-fax_file']."<br>\n"; }
		if (strlen($fax_ecm_used) == 0) { $msg .= $text['message-required']." ".$text['label-fax_ecm_used']."<br>\n"; }
		if (strlen($fax_local_station_id) == 0) { $msg .= $text['message-required']." ".$text['label-fax_local_station_id']."<br>\n"; }
		if (strlen($fax_document_transferred_pages) == 0) { $msg .= $text['message-required']." ".$text['label-fax_document_transferred_pages']."<br>\n"; }
		if (strlen($fax_document_total_pages) == 0) { $msg .= $text['message-required']." ".$text['label-fax_document_total_pages']."<br>\n"; }
		if (strlen($fax_image_resolution) == 0) { $msg .= $text['message-required']." ".$text['label-fax_image_resolution']."<br>\n"; }
		if (strlen($fax_image_size) == 0) { $msg .= $text['message-required']." ".$text['label-fax_image_size']."<br>\n"; }
		if (strlen($fax_bad_rows) == 0) { $msg .= $text['message-required']." ".$text['label-fax_bad_rows']."<br>\n"; }
		if (strlen($fax_transfer_rate) == 0) { $msg .= $text['message-required']." ".$text['label-fax_transfer_rate']."<br>\n"; }
		if (strlen($fax_retry_attempts) == 0) { $msg .= $text['message-required']." ".$text['label-fax_retry_attempts']."<br>\n"; }
		if (strlen($fax_retry_limit) == 0) { $msg .= $text['message-required']." ".$text['label-fax_retry_limit']."<br>\n"; }
		if (strlen($fax_retry_sleep) == 0) { $msg .= $text['message-required']." ".$text['label-fax_retry_sleep']."<br>\n"; }
		if (strlen($fax_uri) == 0) { $msg .= $text['message-required']." ".$text['label-fax_uri']."<br>\n"; }
		if (strlen($fax_date) == 0) { $msg .= $text['message-required']." ".$text['label-fax_date']."<br>\n"; }
		if (strlen($fax_epoch) == 0) { $msg .= $text['message-required']." ".$text['label-fax_epoch']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
			return;
		}

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			/*
			if ($action == "add" && permission_exists('fax_log_add')) {
				$sql = "insert into v_fax_logs ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "fax_log_uuid, ";
				$sql .= "fax_log_uuid, ";
				$sql .= "domain_uuid, ";
				$sql .= "fax_success, ";
				$sql .= "fax_result_code, ";
				$sql .= "fax_result_text, ";
				$sql .= "fax_file, ";
				$sql .= "fax_ecm_used, ";
				$sql .= "fax_local_station_id, ";
				$sql .= "fax_document_transferred_pages, ";
				$sql .= "fax_document_total_pages, ";
				$sql .= "fax_image_resolution, ";
				$sql .= "fax_image_size, ";
				$sql .= "fax_bad_rows, ";
				$sql .= "fax_transfer_rate, ";
				$sql .= "fax_retry_attempts, ";
				$sql .= "fax_retry_limit, ";
				$sql .= "fax_retry_sleep, ";
				$sql .= "fax_uri, ";
				$sql .= "fax_date, ";
				$sql .= "fax_epoch ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$fax_log_uuid', ";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$fax_success', ";
				$sql .= "'$fax_result_code', ";
				$sql .= "'$fax_result_text', ";
				$sql .= "'$fax_file', ";
				$sql .= "'$fax_ecm_used', ";
				$sql .= "'$fax_local_station_id', ";
				$sql .= "'$fax_document_transferred_pages', ";
				$sql .= "'$fax_document_total_pages', ";
				$sql .= "'$fax_image_resolution', ";
				$sql .= "'$fax_image_size', ";
				$sql .= "'$fax_bad_rows', ";
				$sql .= "'$fax_transfer_rate', ";
				$sql .= "'$fax_retry_attempts', ";
				$sql .= "'$fax_retry_limit', ";
				$sql .= "'$fax_retry_sleep', ";
				$sql .= "'$fax_uri', ";
				$sql .= "'$fax_date', ";
				$sql .= "'$fax_epoch' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION['message'] = $text['message-add'];
				header('Location: fax_logs.php');
				return;

			} //if ($action == "add")
			*/

			if ($action == "update" && permission_exists('fax_log_edit')) {
				$sql = "update v_fax_logs set ";
				$sql .= "fax_log_uuid = '$fax_log_uuid', ";
				$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "fax_success = '$fax_success', ";
				$sql .= "fax_result_code = '$fax_result_code', ";
				$sql .= "fax_result_text = '$fax_result_text', ";
				$sql .= "fax_file = '$fax_file', ";
				$sql .= "fax_ecm_used = '$fax_ecm_used', ";
				$sql .= "fax_local_station_id = '$fax_local_station_id', ";
				$sql .= "fax_document_transferred_pages = '$fax_document_transferred_pages', ";
				$sql .= "fax_document_total_pages = '$fax_document_total_pages', ";
				$sql .= "fax_image_resolution = '$fax_image_resolution', ";
				$sql .= "fax_image_size = '$fax_image_size', ";
				$sql .= "fax_bad_rows = '$fax_bad_rows', ";
				$sql .= "fax_transfer_rate = '$fax_transfer_rate', ";
				$sql .= "fax_retry_attempts = '$fax_retry_attempts', ";
				$sql .= "fax_retry_limit = '$fax_retry_limit', ";
				$sql .= "fax_retry_sleep = '$fax_retry_sleep', ";
				$sql .= "fax_uri = '$fax_uri', ";
				$sql .= "fax_date = '$fax_date', ";
				$sql .= "fax_epoch = '$fax_epoch' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and fax_log_uuid = '$fax_log_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				$_SESSION['message'] = $text['message-update'];
				header('Location: fax_logs.php');
				return;

			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true") 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$fax_log_uuid = check_str($_GET["id"]);
		$sql = "select * from v_fax_logs ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and fax_log_uuid = '$fax_log_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$fax_log_uuid = $row["fax_log_uuid"];
			$fax_success = $row["fax_success"];
			$fax_result_code = $row["fax_result_code"];
			$fax_result_text = $row["fax_result_text"];
			$fax_file = $row["fax_file"];
			$fax_ecm_used = $row["fax_ecm_used"];
			$fax_local_station_id = $row["fax_local_station_id"];
			$fax_document_transferred_pages = $row["fax_document_transferred_pages"];
			$fax_document_total_pages = $row["fax_document_total_pages"];
			$fax_image_resolution = $row["fax_image_resolution"];
			$fax_image_size = $row["fax_image_size"];
			$fax_bad_rows = $row["fax_bad_rows"];
			$fax_transfer_rate = $row["fax_transfer_rate"];
			$fax_retry_attempts = $row["fax_retry_attempts"];
			$fax_retry_limit = $row["fax_retry_limit"];
			$fax_retry_sleep = $row["fax_retry_sleep"];
			$fax_uri = $row["fax_uri"];
			$fax_date = $row["fax_date"];
			$fax_epoch = $row["fax_epoch"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-fax_log']."</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='fax_logs.php?id=$fax_uuid'\" value='".$text['button-back']."'>";
	//echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	//echo "<tr>\n";
	//echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	//echo "	".$text['label-fax_log_uuid']."\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "  <input class='formfld' type='text' name='fax_log_uuid' maxlength='255' value='$fax_log_uuid'>\n";
	//echo "<br />\n";
	//echo $text['description-fax_log_uuid']."\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_success']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_success' maxlength='255' value='$fax_success'>\n";
	echo "<br />\n";
	echo $text['description-fax_success']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_result_code']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_result_code' maxlength='255' value='$fax_result_code'>\n";
	echo "<br />\n";
	echo $text['description-fax_result_code']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_result_text']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_result_text' maxlength='255' value=\"$fax_result_text\">\n";
	echo "<br />\n";
	echo $text['description-fax_result_text']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_file']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_file' maxlength='255' value=\"$fax_file\">\n";
	echo "<br />\n";
	echo $text['description-fax_file']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_ecm_used']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_ecm_used' maxlength='255' value=\"$fax_ecm_used\">\n";
	echo "<br />\n";
	echo $text['description-fax_ecm_used']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_local_station_id']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_local_station_id' maxlength='255' value=\"$fax_local_station_id\">\n";
	echo "<br />\n";
	echo $text['description-fax_local_station_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_document_transferred_pages']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_document_transferred_pages' maxlength='255' value='$fax_document_transferred_pages'>\n";
	echo "<br />\n";
	echo $text['description-fax_document_transferred_pages']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_document_total_pages']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_document_total_pages' maxlength='255' value='$fax_document_total_pages'>\n";
	echo "<br />\n";
	echo $text['description-fax_document_total_pages']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_image_resolution']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_image_resolution' maxlength='255' value=\"$fax_image_resolution\">\n";
	echo "<br />\n";
	echo $text['description-fax_image_resolution']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_image_size']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_image_size' maxlength='255' value=\"$fax_image_size\">\n";
	echo "<br />\n";
	echo $text['description-fax_image_size']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_bad_rows']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_bad_rows' maxlength='255' value='$fax_bad_rows'>\n";
	echo "<br />\n";
	echo $text['description-fax_bad_rows']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_transfer_rate']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_transfer_rate' maxlength='255' value='$fax_transfer_rate'>\n";
	echo "<br />\n";
	echo $text['description-fax_transfer_rate']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_retry_attempts']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_retry_attempts' maxlength='255' value='$fax_retry_attempts'>\n";
	echo "<br />\n";
	echo $text['description-fax_retry_attempts']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_retry_limit']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_retry_limit' maxlength='255' value='$fax_retry_limit'>\n";
	echo "<br />\n";
	echo $text['description-fax_retry_limit']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_retry_sleep']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_retry_sleep' maxlength='255' value='$fax_retry_sleep'>\n";
	echo "<br />\n";
	echo $text['description-fax_retry_sleep']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_uri']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_uri' maxlength='255' value=\"$fax_uri\">\n";
	echo "<br />\n";
	echo $text['description-fax_uri']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_date']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<br />\n";
	echo $text['description-fax_date']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_epoch']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_epoch' maxlength='255' value='$fax_epoch'>\n";
	echo "<br />\n";
	echo $text['description-fax_epoch']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='fax_log_uuid' value='$fax_log_uuid'>\n";
	}
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "resources/footer.php";
?>