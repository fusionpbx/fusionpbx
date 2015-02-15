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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('schema_data_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (strlen($_GET["id"]) > 0) {
	$schema_uuid = check_str($_GET["id"]);
	if (strlen($_GET["data_parent_row_uuid"])>0) {
		$data_parent_row_uuid = $_GET["data_parent_row_uuid"];
	}
	$search_all = strtolower(check_str($_GET["search_all"]));
}

//used for changing the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//used to alternate colors when paging
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-data_view'];

//get the information about the schema by using the id
	$sql = "select * from v_schemas ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and schema_uuid = '$schema_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		$schema_category = $row["schema_category"];
		$schema_label = $row["schema_label"];
		$schema_name = $row["schema_name"];
		$schema_auth = $row["schema_auth"];
		$schema_captcha = $row["schema_captcha"];
		$schema_parent_uuid = $row["schema_parent_uuid"];
		$schema_description = $row["schema_description"];
		break; //limit to 1 row
	}
	unset ($prep_statement);

//get the field information
	$db_field_name_array = array();
	$db_value_array = array();
	$db_names .= "<tr>\n";
	$sql = "select * from v_schema_fields ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and schema_uuid = '$schema_uuid' ";
	$sql .= "order by field_order asc ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result_names = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	$result_count = count($result);
	foreach($result_names as $row) {
		$field_label = $row["field_label"];
		$field_name = $row["field_name"];
		$field_type = $row["field_type"];
		$field_value = $row["field_value"];
		$field_list_hidden = $row["field_list_hidden"];
		$field_column = $row["field_column"];
		$field_required = $row["field_required"];
		$field_order = $row["field_order"];
		$field_order_tab = $row["field_order_tab"];
		$field_description = $row["field_description"];

		$name_array[$field_name]['field_label'] = $row["field_label"];
		$name_array[$field_name]['field_type'] = $row["field_type"];
		$name_array[$field_name]['field_list_hidden'] = $row["field_list_hidden"];
		$name_array[$field_name]['field_column'] = $row["field_column"];
		$name_array[$field_name]['field_required'] = $row["field_required"];
		$name_array[$field_name]['field_order'] = $row["field_order"];
		$name_array[$field_name]['field_order_tab'] = $row["field_order_tab"];
		$name_array[$field_name]['field_description'] = $row["field_description"];
	}
	unset($sql, $prep_statement, $row);
	$fieldcount = count($name_array);

//get the data
	$sql = "";
	$sql .= "select * from v_schema_data ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	if (strlen($search_all) == 0) {
		$sql .= "and schema_uuid = '$schema_uuid' ";
		if (strlen($data_parent_row_uuid) > 0) {
			$sql .= "and data_parent_row_uuid = '$data_parent_row_uuid' ";
		}
	}
	else {
		$sql .= "and data_row_uuid in (";
		$sql .= "select data_row_uuid from v_schema_data \n";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and schema_uuid = '$schema_uuid' ";
		if (strlen($data_parent_row_uuid) == 0) {
			$tmp_digits = preg_replace('{\D}', '', $search_all);
			if (is_numeric($tmp_digits) && strlen($tmp_digits) > 5) {
				if (strlen($tmp_digits) == '11' ) {
					$sql .= "and data_field_value like '%".substr($tmp_digits, -10)."%' \n";
				}
				else {
					$sql .= "and data_field_value like '%$tmp_digits%' \n";
				}
			}
			else {
				$sql .= "and lower(data_field_value) like '%$search_all%' \n";
			}
		}
		else {
			$sql .= "and data_parent_row_uuid = '$data_parent_row_uuid' ";
		}
		$sql .= ")\n";
	}
	$sql .= "limit 20000\n";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result_values = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result_values as $row) {
		//set a php variable
			$field_name = $row[field_name];
			$data_row_uuid = $row[data_row_uuid];

		//restructure the data by setting it the value_array
			$value_array[$data_row_uuid][$field_name] = $row[data_field_value];
			$value_array[$data_row_uuid]['schema_uuid'] = $row["schema_uuid"];
			$value_array[$data_row_uuid]['data_row_uuid'] = $row[data_row_uuid];
			$value_array[$data_row_uuid]['schema_parent_uuid'] = $row[schema_parent_uuid];
			$value_array[$data_row_uuid]['data_parent_row_uuid'] = $row[data_parent_row_uuid];
	}
	$num_rows = count($value_array);

//create the connection to the memory dbase_add_record
	try {
		$db_memory = new PDO('sqlite::memory:'); //sqlite 3
	}
	catch (PDOException $error) {
		print "error: " . $error->getMessage() . "<br/>";
		die();
	}

//create a memory database and add the fields to the table
	$sql = "CREATE TABLE memory_table ";
	$sql .= "(";
	$sql .= "'id' INTEGER PRIMARY KEY, ";
	$sql .= "'schema_uuid' TEXT, ";
	$sql .= "'data_row_uuid' TEXT, ";
	$sql .= "'schema_parent_uuid' TEXT, ";
	$sql .= "'data_parent_row_uuid' TEXT, ";
	foreach($result_names as $row) {
		if ($row["field_type"] != "label") {
			if ($row["field_name"] != "domain_uuid") {
				//$row["field_label"];
				//$row["field_name"];
				//$row["field_type"];
				if ($row["field_name"] == "number") {
					$sql .= "'".$row["field_name"]."' NUMERIC, ";
				}
				else {
					$sql .= "'".$row["field_name"]."' TEXT, ";
				}
			}
		}
	}
	$sql .= "'domain_uuid' TEXT ";
	$sql .= ");";
	$prep_statement = $db_memory->prepare($sql);
	$prep_statement->execute();
	unset ($prep_statement, $sql);

//list the values from the array
	$x = 0;
	foreach($value_array as $array) {
		//insert the data into the memory table
			$sql = "insert into memory_table ";
			$sql .= "(";
			$sql .= "'schema_uuid', ";
			$sql .= "'data_row_uuid', ";
			$sql .= "'schema_parent_uuid', ";
			$sql .= "'data_parent_row_uuid', ";
			//foreach($array as $key => $value) {
			//	$sql .= "'$key', ";
			foreach($result_names as $row) {
				$field_name = $row["field_name"];
				$sql .= "'$field_name', ";
			}
			$sql .= "'domain_uuid' ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".$array['schema_uuid']."', ";
			$sql .= "'".$array['data_row_uuid']."', ";
			$sql .= "'".$array['schema_parent_uuid']."', ";
			$sql .= "'".$array['data_parent_row_uuid']."', ";
			//foreach($array as $key => $value) {
			//	$sql .= "'$value', ";
			foreach($result_names as $row) {
				$field_name = $row["field_name"];
				$sql .= "'".check_str($array[$field_name])."', ";
			}
			$sql .= "'$domain_uuid' ";
			$sql .= ");";
			//echo "$sql <br /><br />\n";
			$db_memory->exec(check_sql($sql));
			unset($sql);
			unset($array);
		//unset the row of data
			unset($value_array[$x]);
		//increment the value
			$x++;
	}

//set the title and description of the table
	echo "<br />\n";
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' valign='top'><strong>$schema_label</strong><br>\n";
	echo "		$schema_description\n";
	echo "	</td>\n";
	echo "	<td align='right' valign='top'>\n";
	if (strlen($data_parent_row_uuid) == 0) {
		$search_all = str_replace("''", "'", $search_all);
		echo "<form method='GET' name='frm_search' action=''>\n";
		echo "	<input class='formfld' type='text' name='search_all' value=\"$search_all\">\n";
		echo "	<input type='hidden' name='id' value='$schema_uuid'>\n";
		echo "	<input type='hidden' name='data_parent_row_uuid' value='$data_parent_row_uuid'>\n";
		echo "	<input class='btn' type='submit' name='submit' value='".$text['button-search_all']."'>\n";
		echo "</form>\n";
	}
	echo "	</td>\n";
	echo "  </tr>\n";
	echo "</table>\n";
	echo "<br />";

//prepare for paging the results
	require_once "resources/paging.php";
	$rows_per_page = 100;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	if (strlen($schema_parent_uuid) > 0) {
		$param = "&id=$schema_parent_uuid&data_row_uuid=$data_row_uuid";
	}
	else {
		$param = "&id=$schema_uuid&data_row_uuid=$data_row_uuid";
	}
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//list the data in the database
	$sql = "select * from memory_table \n";
	$sql .= "where domain_uuid = '$domain_uuid' \n";
	$sql .= "limit $rows_per_page offset $offset \n";
	//$sql .= "order by field_order asc \n";
	$prep_statement = $db_memory->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);

//begin the list
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	foreach($result_names as $row) {
		if ($row['field_list_hidden'] != "hide") {
			echo "<th valign='top' nowrap>&nbsp; ".$row['field_label']." &nbsp;</th>\n";
		}
	}
	echo "<td class='list_control_icons'>";
	if (permission_exists('schema_data_add')) {
		echo "<a href='schema_data_edit.php?schema_uuid=".$schema_uuid."&data_parent_row_uuid=$data_parent_row_uuid' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	$db_values = '';
	$x = 0;
	foreach ($result as &$row) {
		echo "<tr>\n";
		foreach($result_names as $row2) {
			$field_name = $row2[field_name];

			//get the values from the array and set as php variables
				$field_label = $name_array[$field_name]['field_label'];
				$field_type = $name_array[$field_name]['field_type'];
				$field_list_hidden = $name_array[$field_name]['field_list_hidden'];
				$field_column = $name_array[$field_name]['field_column'];
				$field_required = $name_array[$field_name]['field_required'];
				$field_order = $name_array[$field_name]['field_order'];
				$field_order_tab = $name_array[$field_name]['field_order_tab'];
				$field_description = $name_array[$field_name]['field_description'];

			if ($field_list_hidden != "hide") {
				switch ($field_type) {
					case "textarea":
						$tmp_value = str_replace("\n", "<br />\n", $row[$field_name]);
						echo "<td valign='top' class='".$row_style[$c]."'>".$tmp_value."&nbsp;</td>\n";
						unset($tmp_value);
						break;
					case "email":
						echo "<td valign='top' class='".$row_style[$c]."'><a href='mailto:".$row[$field_name]."'>".$row[$field_name]."</a>&nbsp;</td>\n";
						break;
					case "phone":
						$tmp_phone = $row[$field_name];
						$tmp_phone = format_phone($tmp_phone);
						echo "<td valign='top' class='".$row_style[$c]."'>".$tmp_phone."&nbsp;</td>\n";
						break;
					case "url":
						$url = $row[$field_name];
						if (substr($url,0,4) != "http") {
							$url = 'http://'.$url;
						}
						echo "<td valign='top' class='".$row_style[$c]."'><a href='".$url."' target='_blank'>".$row[$field_name]."</a>&nbsp;</td>\n";
						break;
					default:
						echo "<td valign='top' class='".$row_style[$c]."'>".$row[$field_name]."&nbsp;</td>\n";
						break;
				}
			}
		}

		echo "<td class='list_control_icons'>";
		if (permission_exists('schema_data_edit')) {
			if (strlen($data_parent_row_uuid) == 0) {
				echo "<a href='schema_data_edit.php?schema_uuid=".$row["schema_uuid"]."&data_parent_row_uuid=$data_parent_row_uuid&data_row_uuid=".$row['data_row_uuid']."&search_all=$search_all' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			else {
				echo "<a href='schema_data_edit.php?schema_uuid=".$row["schema_uuid"]."&data_parent_row_uuid=$data_parent_row_uuid&data_row_uuid=".$row['data_row_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
		}
		if (permission_exists('schema_delete')) {
			echo"<a href='schema_delete.php?data_row_uuid=".$row['data_row_uuid']."&data_parent_row_uuid=$data_parent_row_uuid&schema_uuid=".$schema_uuid."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
		}
		echo "</td>\n";

		echo "</tr>\n";
		if ($c==0) { $c=1; } else { $c=0; }
	}

//show the paging tools and final add button
	echo "<tr>\n";
	echo "<td colspan='999' align='left'>\n";
	echo "	<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('schema_data_add')) {
		echo "<a href='schema_data_edit.php?schema_uuid=".$schema_uuid."&data_parent_row_uuid=$data_parent_row_uuid' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>\n";
    echo "</div>";

//show the header
    echo "<br><br>";
    require_once "resources/footer.php";

?>
