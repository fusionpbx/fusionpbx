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
	Portions created by the Initial Developer are Copyright (C) 2016 - 2018
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
	if (is_uuid($_REQUEST["id"])) {
		$database_transaction_uuid = $_REQUEST["id"];
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$database_transaction_uuid = $_GET["id"];

		$sql = "select ";
		$sql .= "t.database_transaction_uuid, d.domain_name, u.username, t.user_uuid, t.app_name, t.app_uuid, ";
		$sql .= "t.transaction_code, t.transaction_address, t.transaction_type, t.transaction_date, ";
		$sql .= "t.transaction_old, t.transaction_new, t.transaction_result ";
		$sql .= "from v_database_transactions as t, v_domains as d, v_users as u ";
		$sql .= "where t.domain_uuid = :domain_uuid ";
		$sql .= "and t.database_transaction_uuid = :database_transaction_uuid ";
		$sql .= "and t.user_uuid = u.user_uuid ";
		$sql .= "and t.domain_uuid = d.domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['database_transaction_uuid'] = $database_transaction_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$user_uuid = $row["user_uuid"];
			$app_name = $row["app_name"];
			$app_uuid = $row["app_uuid"];
			$domain_name = $row["domain_name"];
			$username = $row["username"];
			$transaction_code = $row["transaction_code"];
			$transaction_address = $row["transaction_address"];
			$transaction_type = $row["transaction_type"];
			$transaction_date = $row["transaction_date"];
			$transaction_old = $row["transaction_old"];
			$transaction_new = $row["transaction_new"];
			$transaction_result = $row["transaction_result"];
		}
		unset($sql, $parameters, $row);
	}

//get the type if not provided
	if (strlen($transaction_type) == 0) {
		if ($transaction_old == null || $transaction_old == "null") {
			$transaction_type = 'add';
		}
		else {
			$transaction_type = 'update';
		}
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='20%' nowrap='nowrap' valign='top'><b>".$text['title-database_transaction']."</b><br><br></td>\n";
	echo "		<td width='80%' align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='database_transactions.php'\" value='".$text['button-back']."'>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table width='400'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<td valign='top'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<th valign='top' align='left' nowrap='nowrap'>\n";
	echo "				".$text['label-app_name']."\n";
	echo "			</th>\n";
	echo "			<td class='vtable' align='left'>\n";
	echo "				".$app_name."\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<th valign='top' align='left' nowrap='nowrap'>\n";
	echo "				".$text['label-user_uuid']."\n";
	echo "			</th>\n";
	echo "			<td class='vtable' align='left'>\n";
	echo "  			".escape($username)."\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";

	echo "<td valign='top'>\n";
	echo "	<table border='0'>\n";
	echo "		<tr>\n";
	echo "			<th valign='top' align='left' nowrap='nowrap'>\n";
	echo "				".$text['label-transaction_code']."\n";
	echo "			</th>\n";
	echo "			<td class='vtable' align='left'>\n";
	echo "				".escape($transaction_code)."\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<th valign='top' align='left' nowrap='nowrap'>\n";
	echo "				".$text['label-transaction_address']."\n";
	echo "			</th>\n";
	echo "			<td class='vtable' align='left'>\n";
	echo "				".escape($transaction_address)."\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";

	echo "<td valign='top'>\n";
	echo "	<table border='0'>\n";
	echo "		<tr>\n";
	echo "			<th valign='top' align='left' nowrap='nowrap'>\n";
	echo "				".$text['label-transaction_type']."\n";
	echo "			</th>\n";
	echo "			<td class='vtable' align='left'>\n";
	echo "				".escape($transaction_type)."\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "		<tr>\n";
	echo "			<th width='10%' valign='top' align='left' nowrap='nowrap'>\n";
	echo "				".$text['label-domain']."\n";
	echo "			</th>\n";
	echo "			<td width='90%' aclass='vtable' align='left'>\n";
	echo "				".escape($domain_name);
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";

	if ($_REQUEST["debug"] == "true") {
		echo "<table width='350px'  border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-transaction_old']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<textarea name='transaction_old' style='width: 265px; height: 80px;'>".escape($transaction_old)."</textarea>\n";
		echo "</td>\n";
		echo "</tr>\n";
	
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-transaction_new']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<textarea name='transaction_new' style='width: 265px; height: 80px;'>".escape($transaction_new)."</textarea>\n";
		echo "</td>\n";
		echo "</tr>\n";
	
		echo "<tr>\n";
		echo "<th valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-transaction_result']."\n";
		echo "</th>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<textarea name='transaction_result' style='width: 265px; height: 80px;'>".escape($transaction_result)."</textarea>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>";
	}

//define the array _difference function
	//this adds old and new values to the array
	function array_difference($array1, $array2) {
		$array = array();
		if (is_array($array1)) {
			foreach($array1 as $key => $value) {
				if (is_array($array2[$key])) {
					$array[$key] = array_difference($array1[$key], $array2[$key]);
				}
				else {
				  	$array[$key]['old'] = $value;
				}
			}
		}
		if (is_array($array2)) {
			foreach($array2 as $key => $value) {
				if (is_array($value)) {
					$array[$key] = array_difference($array1[$key], $array2[$key]);
				}
				else {
					$array[$key]['new'] = $value;
				}
			}
		}
		return $array;
	}

//show the content from the difference array as a list
	function show_difference($array) {

		//loop through the array
			foreach ($array as $key => $value) {
				if (is_array($value) && !isset($value['old']) && !isset($value['new'])) {
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
					//set the variables
						$old = $value['old'];
						$new = $value['new'];
						if (is_null($old)) { $old = ''; }
						if (is_null($new)) { $new = ''; }
					//determine if the value has changed
						if (strval($old) == strval($new) && isset($old)) {
							$color = "#000000";
						}
						else {
							$color = "#ff0000";
						}
					//set the table header
						if ($_SESSION['previous_name'] !== $_SESSION['name'] || $_SESSION['previous_row'] !== $_SESSION['row']) {
							echo str_replace("<th>name</th>","<th>".$_SESSION['name']."</th>",$_SESSION['table_header']);
							//echo $_SESSION['table_header'];
						}
						$_SESSION['previous_name'] = $_SESSION['name'];
						$_SESSION['previous_row'] = $_SESSION['row'];
					//show the results
						echo "<tr style='color: $color;'>\n";
						echo "	<td class=\"vtable\" style='color: $color;'>".$key."</td>\n";
						echo "	<td class=\"vtable\" style='color: $color;'>".$old."</td>\n";
						echo "	<td class=\"vtable\" style='color: $color;'>".$new."</td>";
						echo "</tr>\n";
					//echo "</table>\n";
				}
				unset($key,$old,$new,$value);
			}
	}

//decode the json to arrays
	$before = json_decode($transaction_old, true);
	$after = json_decode($transaction_new, true);

//unset the sessions
	unset($_SESSION['previous_name']);
	unset($_SESSION['previous_row']);

//show the add
	if ($transaction_type == "add") {

		//multiple dimensional array into a 2 dimensional array
		if (is_array($after)) {
			$x = 0;
			foreach($after as $key => $value) {
				$id = 0;
				foreach($value as $row) {
					$sub_id = 0;
					foreach($row as $sub_key => $val) {
						if (is_array($val)) {
							foreach($val as $sub_row) {
								foreach($sub_row as $k => $v) {
									$array[$x]['schema'] = $sub_key;
									$array[$x]['row'] = $sub_id;
									$array[$x]['name'] = $k;
									$array[$x]['value'] = htmlentities($v);
									$x++;
								}
								$sub_id++;
							}
						}
						else {
							$array[$x]['schema'] = $key;
							$array[$x]['row'] = $id;
							$array[$x]['name'] = $sub_key;
							$array[$x]['value'] = htmlentities($val);
							$x++;
						}
					}
					$id++;
				}
			}
		}

		echo "<table border='0'>\n";
		foreach($array as $row) {
			if ($row['schema'] !== $previous_schema || $row['row'] !== $previous_row) {
				echo "<tr><td colspan='4'>&nbsp;</td></tr>\n";
				echo "<tr>\n";
				echo "	<th>".$row['schema']."</th>\n";
				echo "	<th>value</th>\n";
				echo "</tr>\n";
			}
			echo "<tr>\n";
			//echo "	<td>".$row['schema']."</td>\n";
			//echo "	<td>".$row['row']."</td>\n";
			echo "	<td class=\"vtable\" style='color: #000000;'>".$row['name']."</td>\n";
			echo "	<td class=\"vtable\" style='color: #ff0000;'>".$row['value']."</td>\n";
			echo "</tr>\n";

			$previous_schema = $row['schema'];
			$previous_row = $row['row'];
		}
		echo "</table>\n";

		/*
		if (is_array($after)) {
			//create the table header
				$array = array_difference(null, $after, 1);
				$table_header = "<tr><td colspan='5'>&nbsp;</td></tr>\n";
				$table_header .= "<tr>\n";
				$table_header .= "	<th>name</th>\n";
				$table_header .= "	<th>&nbsp;</th>\n";
				$table_header .= "	<th>new</th>\n";
				$table_header .= "</tr>\n";
				$_SESSION['table_header'] = $table_header;

			//show the difference
				echo "<table border='0' cellpadding='3'>\n";
				show_difference($array);
				echo "</table>\n";
		}
		*/
	}

//show the update
	if ($transaction_type == "update") {
		if (count($before) > 0 && count($after) > 0) {

			//create the table header
				$array = array_difference($before, $after, 1);
				$table_header = "<tr><td colspan='5'>&nbsp;</td></tr>\n";
				$table_header .= "<tr>\n";
				$table_header .= "	<th>name</th>\n";
				$table_header .= "	<th>old</th>\n";
				$table_header .= "	<th>new</th>\n";
				$table_header .= "</tr>\n";
				$_SESSION['table_header'] = $table_header;
			
			//show the difference
				echo "<table border='0'>\n";
				show_difference($array);
				echo "</table>\n";
		}
	}

//show the delete
	if ($transaction_type == "delete") {
		//echo "<h3>Record Deleted</h3><br />\n";
		echo "<br />\n";
		echo "<pre>\n";
		print_r($before);
		echo "</pre>\n";
	}

//add a few lines at the end
	echo "<br />\n";
	echo "<br />\n";

//include the footer
	require_once "resources/footer.php";

?>
