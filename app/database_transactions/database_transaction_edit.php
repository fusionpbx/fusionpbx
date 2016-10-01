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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('database_transaction_add') || permission_exists('database_transaction_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (isset($_REQUEST["id"])) {
		//$action = "update";
		$database_transaction_uuid = check_str($_REQUEST["id"]);
	}
	//else {
	//	$action = "add";
	//}

//get http post variables and set them to php variables
	/*
	if (count($_POST) > 0) {
		$user_uuid = check_str($_POST["user_uuid"]);
		$app_uuid = check_str($_POST["app_uuid"]);
		$transaction_code = check_str($_POST["transaction_code"]);
		$transaction_address = check_str($_POST["transaction_address"]);
		$transaction_type = check_str($_POST["transaction_type"]);
		$transaction_date = check_str($_POST["transaction_date"]);
		$transaction_old = check_str($_POST["transaction_old"]);
		$transaction_new = check_str($_POST["transaction_new"]);
		$transaction_result = check_str($_POST["transaction_result"]);
	}
	*/

//process the data
	/*
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
	
		$msg = '';
		if ($action == "update") {
			$database_transaction_uuid = check_str($_POST["database_transaction_uuid"]);
		}
	
		//check for all required data
			if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			if (strlen($user_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-user_uuid']."<br>\n"; }
			if (strlen($app_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-app_uuid']."<br>\n"; }
			if (strlen($transaction_code) == 0) { $msg .= $text['message-required']." ".$text['label-transaction_code']."<br>\n"; }
			if (strlen($transaction_address) == 0) { $msg .= $text['message-required']." ".$text['label-transaction_address']."<br>\n"; }
			if (strlen($transaction_type) == 0) { $msg .= $text['message-required']." ".$text['label-transaction_type']."<br>\n"; }
			if (strlen($transaction_date) == 0) { $msg .= $text['message-required']." ".$text['label-transaction_date']."<br>\n"; }
			if (strlen($transaction_old) == 0) { $msg .= $text['message-required']." ".$text['label-transaction_old']."<br>\n"; }
			if (strlen($transaction_new) == 0) { $msg .= $text['message-required']." ".$text['label-transaction_new']."<br>\n"; }
			if (strlen($transaction_result) == 0) { $msg .= $text['message-required']." ".$text['label-transaction_result']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
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
				if ($action == "add" && permission_exists('database_transaction_add')) {
					$sql = "insert into v_database_transactions ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "database_transaction_uuid, ";
					$sql .= "domain_uuid, ";
					$sql .= "user_uuid, ";
					$sql .= "app_uuid, ";
					$sql .= "transaction_code, ";
					$sql .= "transaction_address, ";
					$sql .= "transaction_type, ";
					$sql .= "transaction_date, ";
					$sql .= "transaction_old, ";
					$sql .= "transaction_new, ";
					$sql .= "transaction_result ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'".uuid()."', ";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$user_uuid', ";
					$sql .= "'$app_uuid', ";
					$sql .= "'$transaction_code', ";
					$sql .= "'$transaction_address', ";
					$sql .= "'$transaction_type', ";
					$sql .= "now(), ";
					$sql .= "'$transaction_old', ";
					$sql .= "'$transaction_new', ";
					$sql .= "'$transaction_result' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
	
					$_SESSION["message"] = $text['message-add'];
					header("Location: database_transactions.php");
					return;
	
				} //if ($action == "add")
	
				if ($action == "update" && permission_exists('database_transaction_edit')) {
					$sql = "update v_database_transactions set ";
					$sql .= "domain_uuid = '$domain_uuid', ";
					$sql .= "user_uuid = '$user_uuid', ";
					$sql .= "app_uuid = '$app_uuid', ";
					$sql .= "transaction_code = '$transaction_code', ";
					$sql .= "transaction_address = '$transaction_address', ";
					$sql .= "transaction_type = '$transaction_type', ";
					$sql .= "transaction_date = now(), ";
					$sql .= "transaction_old = '$transaction_old', ";
					$sql .= "transaction_new = '$transaction_new', ";
					$sql .= "transaction_result = '$transaction_result' ";
					$sql .= "where database_transaction_uuid = '$database_transaction_uuid'";
					$sql .= "and domain_uuid = '$domain_uuid' ";
					$db->exec(check_sql($sql));
					unset($sql);
	
					$_SESSION["message"] = $text['message-update'];
					header("Location: database_transactions.php");
					return;
	
				} //if ($action == "update")
			} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)
	*/

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$database_transaction_uuid = check_str($_GET["id"]);

		$sql = "select ";
		$sql .= "t.database_transaction_uuid, d.domain_name, u.username, t.user_uuid, t.app_name, t.app_uuid, ";
		$sql .= "t.transaction_code, t.transaction_address, t.transaction_type, t.transaction_date, ";
		$sql .= "t.transaction_old, t.transaction_new, t.transaction_result ";
		$sql .= "from v_database_transactions as t, v_domains as d, v_users as u ";
		$sql .= "where t.domain_uuid = '$domain_uuid' ";
		$sql .= "and t.database_transaction_uuid = '$database_transaction_uuid' ";
		$sql .= "and t.user_uuid = u.user_uuid ";
		$sql .= "and t.domain_uuid = d.domain_uuid ";

		//$sql = "select *, u.username from v_database_transactions as t, v_users as u ";
		//$sql .= "where domain_uuid = '$domain_uuid' ";
		//$sql .= "t.user_uuid = u.user_uuid ";
		//$sql .= "and database_transaction_uuid = '$database_transaction_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$user_uuid = $row["user_uuid"];
			$app_name = $row["app_name"];
			$app_uuid = $row["app_uuid"];
			$domain_name = $row["domain_name"];
			$username = $row["username"];
			$transaction_code = $row["transaction_code"];
			$transaction_address = $row["transaction_address"];
			//$transaction_type = $row["transaction_type"];
			$transaction_date = $row["transaction_date"];
			$transaction_old = $row["transaction_old"];
			$transaction_new = $row["transaction_new"];
			$transaction_result = $row["transaction_result"];
		}
		unset ($prep_statement);
	}

//show the header
	require_once "resources/header.php";

//show the content
	//echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='20%' nowrap='nowrap' valign='top'><b>".$text['title-database_transaction']."</b><br><br></td>\n";
	echo "<td width='80%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='database_transactions.php'\" value='".$text['button-back']."'>";
	//echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table width='350px'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<td valign='top'>\n";
		echo "<table>\n";
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-app_name']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	".$app_name."\n";
		//echo "  <input class='formfld' type='text' name='app_name' maxlength='255' value='$app_name'>\n";
		//echo "<br />\n";
		//echo $text['description-app_uuid']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		/*echo "<tr>\n";
		echo "<th width='10%' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</th>\n";
		echo "<td width='90%' aclass='vtable' align='left'>\n";
		echo "	".$domain_name;
		//echo "  <input class='formfld' type='text' name='domain_name' maxlength='255' value='$domain_name'>\n";
		//echo "	<br />\n";
		//echo "	".$text['description-domain']."\n";
		echo "</td>\n";
		echo "</tr>\n";
		*/
	
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-user_uuid']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  ".$username."\n";
		//echo "  <input class='formfld' type='text' name='username' maxlength='255' value='$username'>\n";
		//echo "<br />\n";
		//echo $text['description-user_uuid']."\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	echo "</td>\n";

	echo "<td valign='top'>\n";
		echo "<table>\n";
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-transaction_code']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	$transaction_code\n";
		//echo "  <input class='formfld' type='text' name='transaction_code' maxlength='255' value='$transaction_code'>\n";
		//echo "<br />\n";
		//echo $text['description-transaction_code']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-transaction_address']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	$transaction_address\n";
		//echo "	<input class='formfld' type='text' name='transaction_address' maxlength='255' value=\"$transaction_address\">\n";
		//echo "<br />\n";
		//echo $text['description-transaction_address']."\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//echo "<tr>\n";
	//echo "<th valign='top' align='left' nowrap='nowrap'>\n";
	//echo "	".$text['label-transaction_type']."\n";
	//echo "</th>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "	<input class='formfld' type='text' name='transaction_type' maxlength='255' value=\"$transaction_type\">\n";
	//echo "<br />\n";
	//echo $text['description-transaction_type']."\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	if ($_REQUEST["debug"] == "true") {
		echo "<table width='350px'  border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-transaction_old']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		//echo "	<input class='formfld' type='text' name='transaction_old' maxlength='255' value=\"$transaction_old\">\n";
		echo "	<textarea name='transaction_old' style='width: 265px; height: 80px;'>$transaction_old</textarea>\n";
		//echo "<br />\n";
		//echo $text['description-transaction_old']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-transaction_new']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		//echo "	<input class='formfld' type='text' name='transaction_new' maxlength='255' value=\"$transaction_new\">\n";
		echo "	<textarea name='transaction_new' style='width: 265px; height: 80px;'>$transaction_new</textarea>\n";
		//echo "<br />\n";
		//echo $text['description-transaction_new']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-transaction_result']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		//echo "	<input class='formfld' type='text' name='transaction_result' maxlength='255' value=\"$transaction_result\">\n";
		echo "	<textarea name='transaction_result' style='width: 265px; height: 80px;'>$transaction_result</textarea>\n";
		//echo "<br />\n";
		//echo $text['description-transaction_result']."\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>";
	}

	//echo "	<tr>\n";
	//echo "		<td colspan='2' align='right'>\n";
	//if ($action == "update") {
	//	echo "				<input type='hidden' name='database_transaction_uuid' value='$database_transaction_uuid'>\n";
	//}
	//echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	//echo "		</td>\n";
	//echo "	</tr>";
	//echo "</table>";
	//echo "</form>";
	//echo "<br /><br />";

//define the array _difference function
	//this adds old and new values to the array
	function array_difference($array1, $array2) {
		$difference = array();
		foreach($array1 as $key => $value) {
			if(is_array($array2[$key])) {
				$difference[$key] = array_difference($array1[$key], $array2[$key]);
			}
			else {
			  	$difference[$key]['old'] = $value;
			}
		}
		foreach($array2 as $key => $value) {
			if(is_array($value)) {
				$difference[$key] = array_difference($array1[$key], $array2[$key]);
			}
			else {
				$difference[$key]['new'] = $value;
			}
		}
		return $difference;
	}

//show the content from the difference array as a list
	function show_difference($array) {
		//loop through the array
			foreach($array as $key => $value) {
				if(is_array($value) && !isset($value['old']) && !isset($value['new'])) {
					if (!is_numeric($key)) {
						//get the table name
							$_SESSION['name'] = $key;
					}
					else {
						//get the row id
							$_SESSION['row'] = $key;
					}
					$array = show_difference($value);
				}
				else {
					//determine if the value has changed
						if (strval($value['old']) == strval($value['new']) && isset($value['old'])) {
							$color = "#000000";
						}
						else {
							$color = "#ff0000";
						}
					//set the table header
						if ($_SESSION['previous_name'] != $_SESSION['name'] || $_SESSION['previous_row'] != $_SESSION['row']) {
							echo str_replace("<th>name</th>","<th>".$_SESSION['name']."</th>",$_SESSION['table_header']);
							//echo $_SESSION['table_header'];
						}
						$_SESSION['previous_name'] = $_SESSION['name'];
						$_SESSION['previous_row'] = $_SESSION['row'];
					//set the variables
						$old = $value['old'];
						$new = $value['new'];
						if (is_null($old)) { $old = "null"; }
						if (is_null($new)) { $new = "null"; }
					//show the results
						echo "<tr style='color: $color;'>\n";
						//echo "	<td class=\"vtable\" style='color: $color;'>".$_SESSION['name']."</td>\n";
						//echo "	<td class=\"vtable\" style='color: $color; text-align: center;'>".$_SESSION['row']."</td>\n";
						echo "	<td class=\"vtable\" style='color: $color;'>$key</td>\n";
						echo "	<td class=\"vtable\" style='color: $color;'>".$old."</td>\n";
						echo "	<td class=\"vtable\" style='color: $color;'>".$new."</td>";
						echo "</tr>\n";
					//echo "</table>\n";
				}
			}
	}

//decode the json to arrays
	$before = json_decode($transaction_old, true);
	$after = json_decode($transaction_new, true);

//unset the sessions
	unset($_SESSION['previous_name']);
	unset($_SESSION['previous_row']);

//create the table header
	$array = array_difference($before, $after, 1);
	$table_header = "<tr><td colspan='5'>&nbsp;</td></tr>\n";
	$table_header .= "<tr>\n";
	//$table_header .= "	<th>Table</th>\n";
	//$table_header .= "	<th>Row</th>\n";
	$table_header .= "	<th>name</th>\n";
	$table_header .= "	<th>old</th>\n";
	$table_header .= "	<th>new</th>\n";
	$table_header .= "</tr>\n";
	$_SESSION['table_header'] = $table_header;

//show the difference
	echo "<table border='0' cellpadding='3'>\n";
	show_difference($array);
	echo "</table>\n";
	
	echo "<br />\n";
	echo "<br />\n";

//include the footer
	require_once "resources/footer.php";

?>