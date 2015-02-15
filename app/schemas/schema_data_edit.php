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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('schema_data_add') || permission_exists('schema_data_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set http get variables to php variables
	$search_all = strtolower(check_str($_GET["search_all"]));
	$schema_uuid = check_str($_GET["schema_uuid"]);
	if (strlen($_GET["data_row_uuid"])>0) { //update
		$data_row_uuid = check_str($_GET["data_row_uuid"]);
		$action = "update";
	}
	else {
		if (strlen($search_all) > 0) {
			$action = "update";
		}
		else {
			$action = "add";
		}
	}
	if (strlen($_GET["id"]) > 0) {
		$schema_uuid = check_str($_GET["id"]);
	}
	if (strlen($_GET["data_parent_row_uuid"])>0) {
		$data_parent_row_uuid = check_str($_GET["data_parent_row_uuid"]);
	}

//get schema information
	$sql = "select * from v_schemas ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and schema_uuid = '$schema_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$schema_category = $row["schema_category"];
		$schema_label = $row["schema_label"];
		$schema_name = $row["schema_name"];
		$schema_auth = $row["schema_auth"];
		$schema_captcha = $row["schema_captcha"];
		$schema_parent_id = $row["schema_parent_id"];
		$schema_description = $row["schema_description"];
		break; //limit to 1 row
	}
	unset ($prep_statement);

//process the data submitted to by the html form
	if (count($_POST)>0) { //add
		$schema_uuid = check_str($_POST["schema_uuid"]);
		$schema_name = check_str($_POST["schema_name"]);
		$rcount = check_str($_POST["rcount"]);

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

		$i = 1;
		while($i <= $rcount){
			$field_name = check_str($_POST[$i."field_name"]);
			$data_field_value = check_str($_POST[$i."field_value"]);
			if ($i==1) {
				$unique_temp_id = md5('7k3j2m'.date('r')); //used to find the first item
				$data_row_uuid = $unique_temp_id;
			}
			$sql = "select field_type, field_name from v_schema_fields ";
			$sql .= "where domain_uuid  = '$domain_uuid' ";
			$sql .= "and schema_uuid  = '$schema_uuid' ";
			$sql .= "and field_name = '$field_name' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			while($row = $prep_statement->fetch()){
				$field_type = $row['field_type'];
			}

			if ($field_type == "upload_file" || $field_type == "uploadimage") {
				//print_r($_FILES);
				$upload_temp_dir = $_ENV["TEMP"]."\\";
				ini_set('upload_tmp_dir', $upload_temp_dir);
				//$uploaddir = "";
				if ($field_type == "upload_file") {
					$upload_file = $filedir . $_FILES[$i.'field_value']['name'];
				}
				if ($field_type == "uploadimage") {
					$upload_file = $imagedir . $_FILES[$i.'field_value']['name'];
				}
				//  $_POST[$i."field_name"]
				//print_r($_FILES);
				//echo "upload_file $upload_file<br>\n";
				//echo "upload_temp_dir $upload_temp_dir<br>\n";

				$data_field_value = $_FILES[$i.'field_value']['name'];
				//echo "name $data_field_value<br>\n";
				//echo "field_name $field_name<br>\n";
				//$i."field_value"
				//echo "if (move_uploaded_file(\$_FILES[$i.'field_value']['tmp_name'], $upload_file)) ";
				//if (strlen($_FILES[$i.'field_value']['name'])>0) { //only do the following if there is a file name
					//foreach($_FILES as $file)
					//{
						//[$i.'field_value']
						//print_r($file);
						if($_FILES[$i.'field_value']['error'] == 0 && $_FILES[$i.'field_value']['size'] > 0) {
								if (move_uploaded_file($_FILES[$i.'field_value']['tmp_name'], $upload_file)) {
									//echo $_FILES['userfile']['name'] ." <br>";
									//echo "was successfully uploaded. ";
									//echo "<br><br>";
									//print "<pre>";
									//print_r($_FILES);
									//print "</pre>";
								}
								else {
									//echo "Upload Error.  Here's some debugging info:\n";
									//print "<pre>\n";
									//print_r($_FILES);
									//print "</pre>\n";
									//exit;
								}
						}
					//}
				//}
			} //end if file or image

			if ($action == "add" && permission_exists('schema_data_add')) {
				//get a unique id for the data_row_uuid
					if ($i==1) {
						$data_row_uuid = uuid();
					}

				//insert the field data
					$sql = "insert into v_schema_data ";
					$sql .= "(";
					$sql .= "schema_data_uuid, ";
					$sql .= "domain_uuid, ";
					$sql .= "data_row_uuid, ";
					if(strlen($data_parent_row_uuid)>0) {
						$sql .= "data_parent_row_uuid, ";
					}
					$sql .= "schema_uuid, ";
					if (strlen($schema_parent_id) > 0) {
						$sql .= "schema_parent_id, ";
					}
					$sql .= "field_name, ";
					$sql .= "data_field_value, ";
					$sql .= "data_add_user, ";
					$sql .= "data_add_date ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$data_row_uuid', ";
					if(strlen($data_parent_row_uuid)>0) {
						$sql .= "'$data_parent_row_uuid', ";
					}
					$sql .= "'$schema_uuid', ";
					if (strlen($schema_parent_id) > 0) {
						$sql .= "'$schema_parent_id', ";
					}
					$sql .= "'$field_name', ";
					switch ($name_array[$field_name]['field_type']) {
						case "phone":
							$tmp_phone = preg_replace('{\D}', '', $data_field_value);
							$sql .= "'$tmp_phone', ";
							break;
						case "add_user":
							$sql .= "'".$_SESSION["username"]."', ";
							break;
						case "add_date":
							$sql .= "now(), ";
							break;
						case "mod_user":
							$sql .= "'".$_SESSION["username"]."', ";
							break;
						case "mod_date":
							$sql .= "now(), ";
							break;
						default:
							$sql .= "'$data_field_value', ";
					}
					$sql .= "'".$_SESSION["username"]."', ";
					$sql .= "now() ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					$lastinsertid = $db->lastInsertId($id);
					unset($sql);
			} //end action add

			if ($action == "update" && permission_exists('schema_data_edit')) {
					$data_row_uuid = $_POST["data_row_uuid"];

					$sql_update  = "update v_schema_data set ";
					switch ($name_array[$field_name]['field_type']) {
						case "phone":
							$tmp_phone = preg_replace('{\D}', '', $data_field_value);
							$sql_update .= "data_field_value = '$tmp_phone' ";
							break;
						case "add_user":
							$sql_update .= "data_field_value = '".$_SESSION["username"]."' ";
							break;
						case "add_date":
							$sql_update .= "data_field_value = now() ";
							break;
						case "mod_user":
							$sql_update .= "data_field_value = '".$_SESSION["username"]."' ";
							break;
						case "mod_date":
							$sql_update .= "data_field_value = now() ";
							break;
						default:
							$sql_update .= "data_field_value = '$data_field_value' ";
					}
					$sql_update .= "where domain_uuid = '$domain_uuid' ";
					$sql_update .= "and schema_uuid = '$schema_uuid' ";
					if (strlen($schema_parent_id) > 0) {
						$sql_update .= "and schema_parent_id = '$schema_parent_id' ";
					}
					$sql_update .= "and data_row_uuid = '$data_row_uuid' ";
					if(strlen($data_parent_row_uuid)>0) {
						$sql_update .= "and data_parent_row_uuid = '$data_parent_row_uuid' ";
					}
					$sql_update .= "and field_name = '$field_name' ";
					$count = $db->exec(check_sql($sql_update));
					unset ($sql_update);
					if ($count > 0) {
						//do nothing the update was successfull
					}
					else {
						//no value to update so insert new value
						$sql = "insert into v_schema_data ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "data_row_uuid, ";
						if(strlen($data_parent_row_uuid)>0) {
							$sql .= "data_parent_row_uuid, ";
						}
						$sql .= "schema_uuid, ";
						$sql .= "schema_parent_id, ";
						$sql .= "field_name, ";
						$sql .= "data_field_value, ";
						$sql .= "data_add_user, ";
						$sql .= "data_add_date ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'$domain_uuid', ";
						$sql .= "'$data_row_uuid', ";
						if(strlen($data_parent_row_uuid)>0) {
							$sql .= "'$data_parent_row_uuid', ";
						}
						$sql .= "'$schema_uuid', ";
						$sql .= "'$schema_parent_id', ";
						$sql .= "'$field_name', ";
						switch ($name_array[$field_name]['field_type']) {
							case "phone":
								$tmp_phone = preg_replace('{\D}', '', $data_field_value);
								$sql .= "'$tmp_phone', ";
								break;
							case "add_user":
								$sql .= "'".$_SESSION["username"]."', ";
								break;
							case "add_date":
								$sql .= "now(), ";
								break;
							case "mod_user":
								$sql .= "'".$_SESSION["username"]."', ";
								break;
							case "mod_date":
								$sql .= "now(), ";
								break;
							default:
								$sql .= "'$data_field_value', ";
						}
						$sql .= "'".$_SESSION["username"]."', ";
						$sql .= "now() ";
						$sql .= ")";

						$db->exec(check_sql($sql));
						$lastinsertid = $db->lastInsertId($id);
						unset($sql);
					}
			}
			$i++;
		}

		//redirect user
			if ($action == "add") {
				$_SESSION["message"] = $text['message-add'];
			}
			else if ($action == "update") {
				$_SESSION["message"] = $text['message-update'];
			}

			if (strlen($data_parent_row_uuid) == 0) {
				header("Location: schema_data_edit.php?id=".$schema_uuid."&data_row_uuid=".$data_row_uuid);
			}
			else {
				header("Location: schema_data_edit.php?schema_uuid=".$schema_parent_id."&data_row_uuid=".$data_parent_row_uuid);
			}
			return;
	}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-data'];

//pre-populate the form
	if ($action == "update") {
		//get the field values
			$sql = "";
			$sql .= "select * from v_schema_data ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			if (strlen($search_all) == 0) {
				$sql .= "and schema_uuid = '$schema_uuid' ";
				if (strlen($data_parent_row_uuid) > 0) {
					$sql .= " and data_parent_row_uuid = '$data_parent_row_uuid' ";
				}
			}
			else {
				$sql .= "and data_row_uuid in (";
				$sql .= "select data_row_uuid from v_schema_data \n";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and schema_uuid = '$schema_uuid' ";
				if (strlen($data_parent_row_uuid) > 0) {
					$sql .= " and data_parent_row_uuid = '$data_parent_row_uuid' ";
				}
				else {
					//$sql .= "and data_field_value like '%$search_all%' )\n";
					$tmp_digits = preg_replace('{\D}', '', $search_all);
					if (is_numeric($tmp_digits) && strlen($tmp_digits) > 5) {
						if (strlen($tmp_digits) == '11' ) {
							$sql .= "and data_field_value like '%".substr($tmp_digits, -10)."%' )\n";
						}
						else {
							$sql .= "and data_field_value like '%$tmp_digits%' )\n";
						}
					}
					else {
						$sql .= "and lower(data_field_value) like '%$search_all%' )\n";
					}
				}
			}
			$sql .= "order by data_row_uuid asc ";

			$row_id = '';
			$row_id_found = false;
			$next_row_id_found = false;
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$x=0;
			while($row = $prep_statement->fetch()) {
				//set the last last row id
					if ($x==0) {
						if (strlen($data_row_uuid) == 0) {
							$data_row_uuid = $row['data_row_uuid'];
						}
						$first_data_row_uuid = $row['data_row_uuid'];
					}
				//get the data for the specific row id
					if ($data_row_uuid == $row['data_row_uuid']) {
						//set the data and save it to an array
							$data_row[$row['field_name']] = $row['data_field_value'];
						//set the previous row id
							if ($previous_row_id != $row['data_row_uuid']) {
								$previous_data_row_uuid = $previous_row_id;
								$row_id_found = true;
							}
					}
				//detect a new row id
					if ($previous_row_id != $row['data_row_uuid']) {
						if ($row_id_found) {
							if (!$next_row_id_found) {
								//make sure it is not the current row id
								if ($data_row_uuid != $row['data_row_uuid']) {
									$next_data_row_uuid = $row['data_row_uuid'];
									$next_row_id_found = true;
								}
							}
						}

						//set the last last row id
							$last_data_row_uuid = $row['data_row_uuid'];

						//set the temporary previous row id
							$previous_row_id = $row['data_row_uuid'];

						//set the record number array
							$record_number_array[$row['data_row_uuid']] = $x+1;

						$x++;
					}
			}

			//save the total number of records
				$total_records = $x;

			//set record number
				if (strlen($_GET["n"]) == 0) {
					$n = 1;
				}
				else {
					$n = $_GET["n"];
				}
			unset($sql, $prep_statement, $row);
	}

//use this when the calendar is needed
	//echo "<script language='javascript' src=\"/resources/calendar_popcalendar.js\"></script>\n";
	//echo "<script language=\"javascript\" src=\"/resources/calendar_lw_layers.js\"></script>\n";
	//echo "<script language=\"javascript\" src=\"/resources/calendar_lw_menu.js\"></script>";

//begin creating the content
	echo "<br />";

//get the title and description of the schema
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td width='50%' valign='top' nowrap='nowrap'>\n";
	echo 	"	<b>$schema_label ";
	if ($action == "add") {
		echo $text['button-add']."\n";
	}
	else {
		echo $text['button-edit']."\n";
	}
	echo "	</b>\n";
	echo "	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
	if ($action == "update" && permission_exists('schema_data_edit')) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-add']."' onclick=\"window.location='schema_data_edit.php?schema_uuid=$schema_uuid'\" value='".$text['button-add']."'>\n";
	}
	echo "			<br />\n";
	echo "			$schema_description\n";
	echo "			<br />\n";
	echo "			<br />\n";
	echo "		</td>\n";

	if (strlen($data_parent_row_uuid) == 0) {
		echo "<td align='center' valign='top' nowrap='nowrap'>\n";

		if ($action == "update" && permission_exists('schema_data_edit')) {
			if (strlen($previous_data_row_uuid) == 0) {
				echo "		<input type='button' class='btn' name='' alt='".$text['button-prev']."' disabled='disabled' value='".$text['button-prev']."'>\n";
			}
			else {
				echo "		<input type='button' class='btn' name='' alt='".$text['button-prev']."' onclick=\"window.location='schema_data_edit.php?schema_uuid=$schema_uuid&data_row_uuid=".$previous_data_row_uuid."&search_all=$search_all&n=".($n-1)."'\" value='".$text['button-prev']." ".$previous_record_id."'>\n";
			}
			echo "		<input type='button' class='btn' name='' alt='".$text['button-prev']."' value='".$record_number_array[$data_row_uuid]." (".$total_records.")'>\n";
			if (strlen($next_data_row_uuid) == 0) {
				echo "		<input type='button' class='btn' name='' alt='".$text['button-next']."' disabled='disabled' value='".$text['button-next']."'>\n";
			}
			else {
				echo "		<input type='button' class='btn' name='' alt='".$text['button-next']."' onclick=\"window.location='schema_data_edit.php?schema_uuid=$schema_uuid&data_row_uuid=".$next_data_row_uuid."&search_all=$search_all&n=".($n+1)."'\" value='".$text['button-next']." ".$next_record_id."'>\n";
			}
		}
		echo "		&nbsp;&nbsp;&nbsp;";
		echo "		&nbsp;&nbsp;&nbsp;";
		echo "		&nbsp;&nbsp;&nbsp;";
		echo "</td>\n";

		echo "<td width='45%' align='right' valign='top' nowrap='nowrap'>\n";
		echo "	<form method='GET' name='frm_search' action='schema_data_edit.php'>\n";
		echo "	<input type='hidden' name='schema_uuid' value='$schema_uuid'>\n";
		//echo "	<input type='hidden' name='id' value='$schema_uuid'>\n";
		//echo "	<input type='hidden' name='data_parent_row_uuid' value='$data_parent_row_uuid'>\n";
		//echo "	<input type='hidden' name='data_row_uuid' value='$first_data_row_uuid'>\n";
		echo "	<input class='formfld' type='text' name='search_all' value='$search_all'>\n";
		echo "	<input class='btn' type='submit' name='submit' value='".$text['button-search_all']."'>\n";
		echo "	<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='schema_data_view.php?id=$schema_uuid'\" value='".$text['button-back']."'>\n";
		echo "	</form>\n";
		echo "</td>\n";
	}
	else {
		echo "	<td width='50%' align='right'>\n";
		//echo "		<input type='button' class='btn' name='' alt='".$text['button-prev']."' onclick=\"window.location='schema_data_edit.php?schema_uuid=$schema_parent_id&data_row_uuid=$data_parent_row_uuid'\" value='".$text['button-prev']."'>\n";
		//echo "		<input type='button' class='btn' name='' alt='".$text['button-next']."' onclick=\"window.location='schema_data_edit.php?schema_uuid=$schema_parent_id&data_row_uuid=$data_parent_row_uuid'\" value='".$text['button-next']."'>\n";
		echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='schema_data_edit.php?schema_uuid=$schema_parent_id&data_row_uuid=$data_parent_row_uuid'\" value='".$text['button-back']."'>\n";
		echo "	</td>\n";
	}
	echo "  </tr>\n";
	echo "</table>\n";

//begin the table that will hold the html form
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='10'>\n";

//determine if a file should be uploaded
	$sql = "SELECT * FROM v_schema_fields ";
	$sql .= "where domain_uuid  = '$domain_uuid ' ";
	$sql .= "and schema_uuid  = '$schema_uuid ' ";
	$sql .= "and field_type = 'uploadimage' ";
	$sql .= "or domain_uuid  = '$domain_uuid ' ";
	$sql .= "and schema_uuid  = '$schema_uuid ' ";
	$sql .= "and field_type = 'upload_file' ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	if (count($prep_statement->fetchAll(PDO::FETCH_NAMED)) > 0) {
		echo "<form method='post' name='frm' enctype='multipart/form-data' action=''>\n";
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"104857600\" />\n";
	}
	else {
		echo "<form method='post' name='frm' action=''>\n";
	}

//get the fields and then display them
	$sql = "select * from v_schema_fields ";
	$sql .= "where domain_uuid  = '$domain_uuid' ";
	$sql .= "and schema_uuid  = '$schema_uuid' ";
	$sql .= "order by field_column asc, field_order asc ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);

	echo "<input type='hidden' name='rcount' value='$result_count'>\n";
	echo "<input type='hidden' name='schema_uuid' value='$schema_uuid'>\n";

	if ($result_count == 0) { //no results
		echo "<tr><td class='vncell'>&nbsp;</td></tr>\n";
	}
	else { //received results
		$x=1;
		$field_column_previous = '';
		$column_schema_cell_status = '';
		foreach($result as $row) {
			//handle more than one column
				$field_column = $row[field_column];
				//echo "<!--[column: $field_column]-->\n";
				if ($field_column != $field_column_previous) {
					$column_schema_cell_status = 'open';
					//do the following except for the first time through the loop
						if ($x != 1) {
							//close the table
								echo "</td>\n";
								echo "</tr>\n";
								echo "</table>\n";
							//close the row
								echo "</td>\n";
						}
					//open a new row
						echo "<td valign='top'>\n";
					//start a table in the new row
						echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				}

			//display the fields
					if ($row['field_type'] != "hidden"){
						switch ($row['field_type']) {
						case "add_user":
							break;
						case "add_date":
							break;
						case "mod_user":
							break;
						case "mod_date":
							break;
						default:
							echo "<tr>\n";
							if ($row['field_type'] == "label") {
								echo "<td valign='bottom' align='left' class='' style='padding-top:10px;padding-bottom:7px;padding-right:5px;padding-left:0px;' nowrap='nowrap'>\n";
								echo "	<strong>".$row['field_label']."</strong>\n";
								echo "</td>\n";
							}
							else {
								if ($row['field_required'] == "yes") {
									echo "<td valign='top' align='left' class='vncellreq' style='padding-top:3px;' nowrap='nowrap'>\n";
								}
								else {
									echo "<td valign='top' align='left' class='vncell' style='padding-top:3px;' nowrap='nowrap'>\n";
								}
								echo "".$row['field_label']."\n";
								echo "</td>\n";
							}
						}
					}
					switch ($row['field_type']) {
						case "checkbox":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							if (strlen($data_row[$row['field_name']])>0) {
								echo "<input tabindex='".$row['field_order_tab']."' class='' type='checkbox' name='".$x."field_value' maxlength='50' value=\"".$row['field_value']."\" checked='checked'/>\n";
							}
							else {
								echo "<input tabindex='".$row['field_order_tab']."' class='' type='checkbox' name='".$x."field_value' maxlength='50' value=\"".$row['field_value']."\" />\n";
							}
							echo "</td>\n";
							break;
						case "text":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%' type='text' name='".$x."field_value' maxlength='50' value=\"".$data_row[$row['field_name']]."\">\n";
							echo "</td>\n";
							break;
						case "email":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='text' name='".$x."field_value' maxlength='50' value=\"".$data_row[$row['field_name']]."\">\n";
							echo "</td>\n";
							break;
						case "label":
							break;
						case "password":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%' type='password' name='".$x."field_value' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"".$data_row[$row['field_name']]."\">\n";
							echo "</td>\n";
							break;
						case "pin_number":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value=\"".$row['field_name']."\">\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%' type='password' name='".$x."field_value' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"".$data_row[$row['field_name']]."\">\n";
							echo "</td>\n";
							break;
						case "hidden":
							echo "<input type='hidden' name='".$x."field_name' value=\"".$row['field_name']."\">\n";
							echo "<input type='hidden' name='".$x."field_value' value=\"".$data_row[$row['field_name']]."\">\n";
							break;
						case "url":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value=\"".$row['field_name']."\">\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='text' name='".$x."field_value' maxlength='50' value='".$data_row[$row['field_name']]."'>\n";
							echo "</td>\n";
							break;
						case "date":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value=\"".$row['field_name']."\">\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='text' name='".$x."field_value' maxlength='50' value='".$data_row[$row['field_name']]."'>\n";

							//echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							//echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
							//echo "<tr>";
							//echo "<td valign='top'><input tabindex='".$row['field_order_tab']."' name='".$x."field_value' readonly class='formfld' style='width:90%'  value='".$data_row[$row['field_name']]."' type='text' class='frm' onclick='popUpCalendar(this, this, \"mm/dd/yyyy\");'></td>\n";
							//echo "<td valign='middle' width='20' align='right'><img src='/images/icon_calendar.gif' onclick='popUpCalendar(this, frm.".$x."field_value, \"mm/dd/yyyy\");'></td>	\n";
							//echo "</tr>";
							//echo "</table>";
							//echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='text' name='".$x."field_value' maxlength='50' value='".$data_row[$row['field_name']]."'>\n";
							echo "</td>\n";
							break;
						case "truefalse":
							//checkbox
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value=\"".$row['field_name']."\">\n";
							echo "<table border='0'>\n";
							echo "<tr>\n";
							switch ($row['field_name']) {
								case "true":
									echo "<td>".$text['option-true']."</td><td width='50'><input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='checkbox' name='".$x."field_value' checked='checked' value='true' /></td>\n";
									echo "<td>".$text['option-false']."</td><td><input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='checkbox' name='".$x."field_value' value='false'></td>\n";
									break;
								case "false":
									echo "<td>".$text['option-true']."</td><td width='50'><input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='checkbox' name='".$x."field_value' value='true' /></td>\n";
									echo "<td>".$text['option-false']."</td><td><input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='checkbox' name='".$x."field_value' checked='checked' value='false' /></td>\n";
									break;
								default:
									echo "<td>".$text['option-true']."</td><td width='50'><input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='checkbox' name='".$x."field_value' value='true' /></td>\n";
									echo "<td>".$text['option-false']."</td><td><input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='checkbox' name='".$x."field_value' value='false' /></td>\n";
							}

							echo "</tr>\n";
							echo "</table>\n";
							echo "</td>\n";
							break;
						case "textarea":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<textarea tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  name='".$x."field_value' rows='4'>".$data_row[$row['field_name']]."</textarea>\n";
							echo "</td>\n";
							break;
						case "radiobutton":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name=\"".$x."field_name\" value=\"".$row['field_name']."\">\n";

							$sqlselect = "SELECT data_types_name, data_types_value ";
							$sqlselect .= "FROM v_schema_name_values ";
							$sqlselect .= "where domain_uuid = '".$domain_uuid."' ";
							$sqlselect .= "and schema_field_uuid = '".$row["schema_field_uuid"]."' ";
							$prep_statement_2 = $db->prepare($sqlselect);
							$prep_statement_2->execute();
							$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
							$result_count2 = count($result2);

							echo "<table>";
							if ($result_count > 0) {
								foreach($result2 as $row2) {
										echo "<tr><td>".$row2["data_types_name"]."</td><td><input tabindex='".$row['field_order_tab']."' type='radio' name='".$x."field_value' value='".$row2["data_types_select_value"]."'";
										if ($row2["data_types_value"] == $data_row[$row['field_name']]) { echo " checked>"; } else { echo ">"; }
										echo "</td></tr>";
								} //end foreach
							} //end if results
							unset($sqlselect, $result2, $result_count2);
							echo "</table>";
							//echo "</select>\n";
							echo "</td>\n";
							break;
						case "select":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";

							$sqlselect = "SELECT data_types_name, data_types_value ";
							$sqlselect .= "FROM v_schema_name_values ";
							$sqlselect .= "where domain_uuid = '".$domain_uuid."' ";
							$sqlselect .= "and schema_field_uuid = '".$row["schema_field_uuid"]."' ";
							$prep_statement_2 = $db->prepare($sqlselect);
							$prep_statement_2->execute();
							$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
							$result_count2 = count($result2);

							echo "<select tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  name='".$x."field_value'>\n";
							echo "<option value=''></option>\n";
							if ($result_count > 0) {
								foreach($result2 as $row2) {
										echo "<option value=\"" . $row2["data_types_value"] . "\"";
										if (strtolower($row2["data_types_value"]) == strtolower($data_row[$row['field_name']])) { echo " selected='selected' "; }
										echo ">" . $row2["data_types_name"] . "</option>\n";
								} //end foreach
							} //end if results
							unset($sqlselect, $result2, $result_count2);
							echo "</select>\n";
							echo "</td>\n";
							break;
						case "ipv4":
							//max 15
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='text' name='".$x."field_value' maxlength='15' value=\"".$data_row[$row['field_name']]."\">\n";
							echo "</td>\n";
							break;
						case "ipv6":
							//maximum number of characters 39
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='text' name='".$x."field_value' maxlength='39' value=\"".$data_row[$row['field_name']]."\">\n";
							echo "</td>\n";
							break;
						case "phone":
							$tmp_phone = $data_row[$row['field_name']];
							$tmp_phone = format_phone($tmp_phone);
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='text' name='".$x."field_value' maxlength='20' value=\"".$tmp_phone."\">\n";
							echo "</td>\n";
							break;
						case "money":
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='text' name=".$x."field_value' maxlength='255' value=\"".$data_row[$row['field_name']]."\">\n";
							echo "</td>\n";
							break;
						case "add_user":
							//echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input type='hidden' name='".$x."field_value' maxlength='255' value=\"".$data_row[$row['field_name']]."\">\n";
							//echo "</td>\n";
							break;
						case "add_date":
							//echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input type='hidden' name='".$x."field_value' maxlength='255' value=\"".$data_row[$row['field_name']]."\">\n";
							//echo "</td>\n";
							break;
						case "mod_user":
							//echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input type='hidden' name='".$x."field_value' maxlength='255' value=\"".$data_row[$row['field_name']]."\">\n";
							//echo "</td>\n";
							break;
						case "mod_date":
							//echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input type='hidden' name='".$x."field_value' maxlength='255' value=\"".$data_row[$row['field_name']]."\">\n";
							//echo "</td>\n";
							break;
						case "uploadimage":
							if (strlen($data_row[$row['field_name']]) > 0) {
								echo "<td valign='top' align='left' class='vtable'>\n";
								echo "<script type=\"text/javascript\">\n";
								echo $row['field_name']." = \"\<input type=\'hidden\' name=\'".$x."field_name\' value=\'".$row['field_name']."\'>\\n\";\n";
								echo $row['field_name']." += \"\<input tabindex='".$row['field_order_tab']."' class=\'formfld fileinput\' type=\'file\' name='".$x."field_value\' value=\'".$data_row[$row['field_name']]."\'>\\n\";\n";
								echo "</script>\n";

								echo "<div id='".$row['field_name']."id'>";
								echo "<table border='0' width='100%'>";
								echo "<tr>";
								echo "<td align='left'>";
									echo "".$data_row[$row['field_name']]."";
								echo "</td>";
								echo "<td align='right'>";
									echo "<input tabindex='".$row['field_order_tab']."' type='button' class='btn' title='delete' onclick=\"document.getElementById('".$row['field_name']."id').innerHTML=".$row['field_name']."\" value='x'>\n";
									//echo "<input type='button' class='btn' title='delete' onclick=\"addField('".$row['field_name']."id','".$x."field_name', 'hidden', '".$row['field_name']."',1);addField('".$row['field_name']."id','".$x."field_value', 'file', '',1);//'".$row['field_name']."'\" value='x'>\n";
								echo "</td>";
								echo "</tr>";
								echo "<tr>";
								echo "<td colspan='2' align='center'>";
								if (file_exists($imagetempdir.$data_row[$row['field_name']])) {
									echo "<img src='/images/cache/".$data_row[$row['field_name']]."'>";
								}
								else {
									echo "<img src='imagelo.php?max=125&img=".$data_row[$row['field_name']]."'>";
								}
								echo "</td>";
								echo "</tr>";

								echo "</table>";
								echo "<div>";
								echo "</td>\n";
							}
							else {
								echo "<td valign='top' align='left' class='vtable'>\n";
								echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
								echo "<input tabindex='".$row['field_order_tab']."' class='formfld fileinput' style='width:90%'  type='file' name='".$x."field_value' value=\"".$data_row[$row['field_name']]."\">\n";
								echo "</td>\n";
							}
							break;
						case "upload_file":
							if (strlen($data_row[$row['field_name']]) > 0) {
								echo "<td valign='top' align='left' class='vtable'>\n";
								echo "<script type=\"text/javascript\">\n";
								echo $row['field_name']." = \"<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\";\n";
								echo $row['field_name']." += \"<input tabindex='".$row['field_order_tab']."' class='formfld fileinput' style='width:90%'  type='file' name='".$x."field_value' value='".$data_row[$row['field_name']]."'>\";\n";
								echo "</script>\n";

								echo "<span id='".$row['field_name']."'>";
								echo "<table width='100%'>";
								echo "<tr>";
								echo "<td>";
								echo "<a href='download.php?f=".$data_row[$row['field_name']]."'>".$data_row[$row['field_name']]."</a>";
								echo "</td>";
								echo "<td align='right'>";
									echo "<input tabindex='".$row['field_order_tab']."' type='button' class='btn' title='".$text['button-delete']."' onclick=\"document.getElementById('".$row['field_name']."').innerHTML=".$row['field_name']."\" value='x'>\n";
								echo "</td>";
								echo "</tr>";
								echo "</table>";
								echo "<span>";
								echo "</td>\n";
							}
							else {
								echo "<td valign='top' align='left' class='vtable'>\n";
								echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
								echo "<input tabindex='".$row['field_order_tab']."' class='formfld fileinput' style='width:90%'  type='file' name='".$x."field_value' value=\"".$data_row[$row['field_name']]."\">\n";
								echo "</td>\n";
							}

							break;
						default:
							echo "<td valign='top' align='left' class='vtable'>\n";
							echo "<input type='hidden' name='".$x."field_name' value='".$row['field_name']."'>\n";
							echo "<input tabindex='".$row['field_order_tab']."' class='formfld' style='width:90%'  type='text' style='' name='".$x."field_value' maxlength='255' value=\"".$data_row[$row['field_name']]."\">\n";
							echo "</td>\n";
						}
					if ($row['field_type'] != "hidden"){
						echo "</tr>\n";
					}

			//set the current value to the previous value
				$field_column_previous = $field_column;

			$x++;

		} //end foreach
		unset($sql, $result, $row_count);

		if ($column_schema_cell_status == 'open') {
			$column_schema_cell_status = 'closed';
		}
	} //end if results

	echo "	<tr>\n";
	echo "		<td colspan='999' align='right'>\n";
		if ($action == "add" && permission_exists('schema_data_add')) {
			echo "			<input type='submit' class='btn' name='submit' value='".$text['button-save']."'>\n";
		}
		if ($action == "update" && permission_exists('schema_data_edit')) {
			echo "			<input type='hidden' name='data_row_uuid' value='$data_row_uuid'>\n";
			echo "			<input type='submit' tabindex='9999999' class='btn' name='submit' value='".$text['button-save']."'>\n";
		}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "	</td>\n";
	echo "	</tr>\n";
	echo "</form>\n";

	if ($action == "update" && permission_exists('schema_data_edit')) {
		//get the child schema_uuid and use it to show the list of data
			$sql = "select * from v_schemas ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and schema_parent_uuid = '$schema_uuid' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				echo "<tr class='border'>\n";
				echo "	<td colspan='999' align=\"left\">\n";
				echo "		<br>";
				$_GET["id"] = $row["schema_uuid"];
				$schema_label = $row["schema_label"];
				$_GET["data_parent_row_uuid"] = $data_row_uuid;

				//show button
				//echo "<input type='button' class='btn' name='' alt='".$schema_label."' onclick=\"window.location='schema_data_view.php?id=".$row["schema_uuid"]."&data_parent_row_uuid=".$data_row_uuid."'\" value='".$schema_label."'>\n";

				//show list
				require_once "schema_data_view.php";
				echo "	</td>";
				echo "	</tr>";
			}
	}
	echo "</table>\n";

require_once "resources/footer.php";
?>
