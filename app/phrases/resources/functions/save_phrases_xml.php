<?php
function save_phrases_xml() {

	//skip saving the xml if the directory is not set
		if (strlen($_SESSION['switch']['phrases']['dir']) == 0) {
			return;
		}

	//declare the global variables
		global $domain_uuid, $config;

	//remove old phrase files for the domain
		$phrase_list = glob($_SESSION['switch']['phrases']['dir']."/*/phrases/".$domain_uuid.".xml");
		foreach ($phrase_list as $phrase_file) {
			unlink($phrase_file);
		}

	//get the list of phrases and write the xml
		$sql = "select * from v_phrases ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and phrase_enabled = 'true' ";
		$sql .= "order by phrase_language asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);

		$prev_language = '';
		if (is_array($result) && @sizeof($result) != 0) {
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
					if (!file_exists($_SESSION['switch']['phrases']['dir']."/".$row['phrase_language']."/phrases/")) {
						mkdir($_SESSION['switch']['phrases']['dir']."/".$row['phrase_language']."/phrases/", 0755);
					}
					$xml_path = $_SESSION['switch']['phrases']['dir']."/".$row['phrase_language']."/phrases/".$domain_uuid.".xml";
					$fout = fopen($xml_path, "w");
					$xml = "<include>\n";
				}

				//build xml
				$xml .= "	<macro name=\"".$row['phrase_uuid']."\">\n";
				$xml .= "		<input pattern=\"(.*)\">\n";
				$xml .= "			<match>\n";

				$sql = "select * from v_phrase_details ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and phrase_uuid = :phrase_uuid ";
				$sql .= "order by phrase_detail_order";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['phrase_uuid'] = $row['phrase_uuid'];
				$database = new database;
				$result_2 = $database->select($sql, $parameters, 'all');
				foreach ($result_2 as &$row_2) {
					$xml .= "				<action function=\"".$row_2['phrase_detail_function']."\" data=\"".$row_2['phrase_detail_data']."\"/>\n";
				}
				unset($sql, $parameters, $result_2, $row_2);
				$xml .= "			</match>\n";
				$xml .= "		</input>\n";
				$xml .= "	</macro>\n";

				$prev_language = $row['phrase_language'];

			}

			if ($fout && $xml) {
				//output xml & close previous file
				$xml .= "</include>\n";

				fwrite($fout, $xml);
				unset($xml);
				fclose($fout);
			}

		}
		unset($result, $row);

	//apply settings
		$_SESSION["reload_xml"] = true;

}

?>