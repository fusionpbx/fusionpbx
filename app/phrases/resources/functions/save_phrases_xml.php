<?php
function save_phrases_xml() {

	//skip saving the xml if the directory is not set
		if (strlen($_SESSION['switch']['phrases']['dir']) == 0) {
			return;
		}

	//declare the global variables
		global $domain_uuid, $config;

	//connect to the database if not connected
		if (!$db) {
			require_once "resources/classes/database.php";
			$database = new database;
			$database->connect();
			$db = $database->db;
		}

	//remove old phrase files for the domain
		$phrase_list = glob($_SESSION['switch']['phrases']['dir']."/*/phrases/".$domain_uuid.".xml");
		foreach ($phrase_list as $phrase_file) {
			unlink($phrase_file);
		}

	//get the list of phrases and write the xml
		$sql = "select * from v_phrases ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and phrase_enabled = 'true' ";
		$sql .= "order by phrase_language asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);

		$prev_language = '';
		foreach ($result as $row) {

			if ($row['phrase_language'] != $prev_language) {
				if ($prev_language != '') {
					//output xml & close previous file
					$xml .= "</include>\n";
					fwrite($fout, $xml);
					unset($xml);
					fclose($fout);
				}

				//create/open new xml file for writing
				$xml_path = $_SESSION['switch']['phrases']['dir']."/".$row['phrase_language']."/phrases/".$domain_uuid.".xml";
				$fout = fopen($xml_path, "w");
				$xml = "<include>\n";
			}

			//build xml
			$xml .= "	<macro name=\"".$row['phrase_uuid']."\">\n";
			$xml .= "		<input pattern=\"(.*)\">\n";
			$xml .= "			<match>\n";

			$sql2 = "select * from v_phrase_details ";
			$sql2 .= "where domain_uuid = '".$domain_uuid."' ";
			$sql2 .= "and phrase_uuid = '".$row['phrase_uuid']."' ";
			$sql2 .= "order by phrase_detail_order";
			$prep_statement2 = $db->prepare(check_sql($sql2));
			$prep_statement2->execute();
			$result2 = $prep_statement2->fetchAll(PDO::FETCH_ASSOC);
			foreach ($result2 as &$row2) {
				$xml .= "				<action function=\"".$row2['phrase_detail_function']."\" data=\"".$row2['phrase_detail_data']."\"/>\n";
			}
			unset($prep_statement2);
			$xml .= "			</match>\n";
			$xml .= "		</input>\n";
			$xml .= "	</macro>\n";

			$prev_language = $row['phrase_language'];

		}

		//output xml & close previous file
		$xml .= "</include>\n";

		fwrite($fout, $xml);
		unset($xml);
		fclose($fout);

		unset($prep_statement);

	//apply settings
		$_SESSION["reload_xml"] = true;

}
?>