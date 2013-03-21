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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('virtual_tables_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if (count($_POST)>0) {
	$virtual_table_uuid = trim($_REQUEST["id"]);
	$data = trim($_POST["data"]);
	$data_delimiter = trim($_POST["data_delimiter"]);
	$data_enclosure = trim($_POST["data_enclosure"]);
}

//define the php class
	class v_virtual_table_fields {
		var $domain_uuid;
		var $virtual_table_uuid;
		var $virtual_field_label;
		var $virtual_field_name;
		var $virtual_field_type;
		var $virtual_field_value;
		var $virtual_field_list_hidden;
		var $virtual_field_column;
		var $virtual_field_required;
		var $virtual_field_order;
		var $virtual_field_order_tab;
		var $virtual_field_description;

		function db_field_exists() {
			global $db;
			$sql = "select count(*) as num_rows from v_virtual_table_fields ";
			$sql .= "where domain_uuid = '$this->domain_uuid' ";
			$sql .= "and virtual_table_uuid ='$this->virtual_table_uuid' ";
			$sql .= "and virtual_field_name = '$this->virtual_field_name' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row['num_rows'] > 0) {
					return true;
				}
				else {
					return false;
				}
			}
		}

		function db_insert() {
			global $db;
			$sql = "insert into v_virtual_table_fields ";
			$sql .= "(";
			$sql .= "virtual_table_field_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "virtual_table_uuid, ";
			$sql .= "virtual_field_label, ";
			$sql .= "virtual_field_name, ";
			$sql .= "virtual_field_type, ";
			$sql .= "virtual_field_value, ";
			$sql .= "virtual_field_list_hidden, ";
			$sql .= "virtual_field_search_by, ";
			$sql .= "virtual_field_column, ";
			$sql .= "virtual_field_required, ";
			$sql .= "virtual_field_order, ";
			$sql .= "virtual_field_order_tab, ";
			$sql .= "virtual_field_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".uuid()."', ";
			$sql .= "'$this->domain_uuid', ";
			$sql .= "'$this->virtual_table_uuid', ";
			$sql .= "'$this->virtual_field_label', ";
			$sql .= "'$this->virtual_field_name', ";
			$sql .= "'$this->virtual_field_type', ";
			$sql .= "'$this->virtual_field_value', ";
			$sql .= "'$this->virtual_field_list_hidden', ";
			$sql .= "'no', ";
			$sql .= "'$this->virtual_field_column', ";
			$sql .= "'$this->virtual_field_required', ";
			$sql .= "'$this->virtual_field_order', ";
			$sql .= "'$this->virtual_field_order_tab', ";
			$sql .= "'$this->virtual_field_description' ";
			$sql .= ")";
			if (!$this->db_field_exists()) { 
				$db->exec(check_sql($sql));
			}
			unset($sql);
		}
	}

	class v_virtual_table_data {
		var $domain_uuid;
		var $virtual_table_uuid;
		var $virtual_data_row_uuid;
		var $virtual_field_name;
		var $virtual_data_field_value;
		var $last_insert_id;
		var $virtual_table_data_uuid;

		function db_unique_id() {
			global $db;
			$sql = "insert into v_virtual_table_data_row_id ";
			$sql .= "(";
			$sql .= "domain_uuid ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$this->domain_uuid' ";
			$sql .= ")";
			$db->exec($sql);
			unset($sql);
			return $db->lastInsertId($id);
		}

		function db_insert() {
			global $db;
			$sql = "insert into v_virtual_table_data ";
			$sql .= "(";
			$sql .= "virtual_table_data_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "virtual_data_row_uuid, ";
			$sql .= "virtual_table_uuid, ";
			$sql .= "virtual_field_name, ";
			$sql .= "virtual_data_field_value, ";
			$sql .= "virtual_data_add_user, ";
			$sql .= "virtual_data_add_date ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".uuid()."', ";
			$sql .= "'$this->domain_uuid', ";
			$sql .= "'$this->virtual_data_row_uuid', ";
			$sql .= "'$this->virtual_table_uuid', ";
			$sql .= "'$this->virtual_field_name', ";
			$sql .= "'$this->virtual_data_field_value', ";
			$sql .= "'".$_SESSION["username"]."', ";
			$sql .= "now() ";
			$sql .= ")";
			$db->exec($sql);
			$this->last_insert_id = $db->lastInsertId($id);
			unset($sql);
		}

		function db_update() {
			global $db;
			$sql  = "update v_virtual_table_data set ";
			$sql .= "virtual_data_row_uuid = '$this->virtual_data_row_uuid', ";
			$sql .= "virtual_field_name = '$this->virtual_field_name', ";
			$sql .= "virtual_data_field_value = '$this->virtual_data_field_value', ";
			$sql .= "virtual_data_add_user = '".$_SESSION["username"]."', ";
			$sql .= "virtual_data_add_date = now() ";
			$sql .= "where domain_uuid = '$this->domain_uuid' ";
			$sql .= "and virtual_table_data_uuid = '$this->virtual_table_data_uuid' ";
			$db->exec($sql);
			unset($sql);
		}
	}

//built in str_getcsv requires PHP 5.3 or higher, this function can be used to reproduct the functionality but requirs PHP 5.1.0 or higher 
	if(!function_exists('str_getcsv')) {
		function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
			$fp = fopen("php://memory", 'r+');
			fputs($fp, $input);
			rewind($fp);
			$data = fgetcsv($fp, null, $delimiter, $enclosure); // $escape only got added in 5.3.0
			fclose($fp);
			return $data;
		}
	}

	//POST to PHP variables
		if (count($_POST)>0) {

			//show the header
				require_once "includes/header.php";

			echo "<div align='center'>\n";
			echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>Import Results</b></td>\n";
			echo "<td width='70%' align='right' valign='top'>\n";
			echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='virtual_tables_import.php?id=$virtual_table_uuid'\" value='Back'>\n";
			echo "	<br /><br />\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "	<tr>\n";
			echo "		<td colspan='2' align='left'>\n";

			//import data
				if (strlen($data) > 0) {
					$line_array = explode("\n",$data);
					$name_array = explode(",",$line_array[0]);
					$x = 0;
					$db->beginTransaction();
					foreach($name_array as $key => $val) {
						$virtual_field_label = trim($val);
						$virtual_field_name = trim($val);
						$virtual_field_name = str_replace(" ", "_", $virtual_field_name);
						$virtual_field_name = str_replace("-", "_", $virtual_field_name);
						$virtual_field_name = strtolower($virtual_field_name);

						$fields = new v_virtual_table_fields;
						$fields->domain_uuid = $domain_uuid;
						$fields->virtual_table_uuid = $virtual_table_uuid;
						$fields->virtual_field_label = $virtual_field_label;
						$fields->virtual_field_name = $virtual_field_name;
						$fields->virtual_field_type = 'text';
						$fields->virtual_field_value = '';
						$fields->virtual_field_list_hidden = 'show';
						$fields->virtual_field_column = '1';
						$fields->virtual_field_required = 'yes';
						$fields->virtual_field_order = $x;
						$fields->virtual_field_order_tab = $x;
						$fields->virtual_field_description = $virtual_field_label;
						$fields->db_insert();
						unset($fields);
						$x++;
					}

					foreach($line_array as $key => $line) {
						if ($key > 0) {
							$value_array = str_getcsv($line, $data_delimiter, $data_enclosure);
							$x=0;
							foreach($value_array as $key => $val) {

								$virtual_field_label = trim($name_array[$x]);
								$virtual_field_name = trim($name_array[$x]);
								$virtual_field_name = str_replace(" ", "_", $virtual_field_name);
								$virtual_field_name = str_replace("-", "_", $virtual_field_name);
								$virtual_field_name = strtolower($virtual_field_name);

								$virtual_field_value = trim($val);

								$data = new v_virtual_table_data;
								$data->domain_uuid = $domain_uuid;
								$data->virtual_table_uuid = $virtual_table_uuid;
								if ($x == 0) {
									$virtual_data_row_uuid = uuid();
									//echo "id: ".$virtual_data_row_uuid."<br />\n";
								}
								$data->virtual_data_row_uuid = $virtual_data_row_uuid;
								$data->virtual_field_name = $virtual_field_name;
								$data->virtual_data_field_value = $virtual_field_value;
								$data->db_insert();
								unset($data);

								echo "<strong>$virtual_field_name:</strong> $virtual_field_value<br/>\n";
								$x++;
							}
							echo "<hr size='1' />\n";
						}
					}
					$db->commit();
				} //if (strlen($data) > 0)

			echo "		</td>\n";
			echo "	</tr>";
			echo "	</table>";
			echo "</div>\n";

			//show the footer
				require_once "includes/footer.php";

			exit;
		}


//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr>\n";
	echo "<td width='30%' nowrap='nowrap' align='left' valign='top'><b>Import</b></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	//echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='virtual_tables_import.php?id=$virtual_table_uuid'\" value='Back'>\n";
	echo "	<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Data:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <textarea name='data' id='data' rows='7' class='txt' wrap='off'>$data</textarea\n";
	echo "<br />\n";
	echo "Copy and paste the comma delimitted data into the text area to begin the import.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Delimiter:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' style='width:40px;' name='data_delimiter'>\n";
	echo "    <option value=','>,</option>\n";
	echo "    <option value='|'>|</option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo "Select the delimiter.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Enclosure:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' style='width:40px;' name='data_enclosure'>\n";
	echo "    <option value='\"'>\"</option>\n";
	echo "    <option value=''></option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo "Select the enclosure.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='submit' name='import' class='btn' value='Import'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

	echo "</form>";

require_once "includes/footer.php";
?>
