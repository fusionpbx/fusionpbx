<?php
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

function cryptoJsAesEncrypt($passphrase, $value){
    $salt = openssl_random_pseudo_bytes(8);
    $salted = '';
    $dx = '';
    while (strlen($salted) < 48) {
        $dx = md5($dx.$passphrase.$salt, true);
        $salted .= $dx;
    }
    $key = substr($salted, 0, 32);
    $iv  = substr($salted, 32,16);
    $encrypted_data = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, true, $iv);
    $data = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
    return json_encode($data);
}

$node_1 = $_SESSION['data_base_replication_check']['node_1']['text'];
$node_2 = $_SESSION['data_base_replication_check']['node_2']['text'];
$enc_1 = cryptoJsAesEncrypt(substr(preg_replace("/[^A-Za-z0-9]/",'', $_SESSION['data_base_replication_check']['node_1_password']['text']), 0, 32), substr(base64_encode(hash('sha256', str_replace('_', '.', $node_1), true)), 0, 32));
$enc_2 = cryptoJsAesEncrypt(substr(preg_replace("/[^A-Za-z0-9]/",'', $_SESSION['data_base_replication_check']['node_2_password']['text']), 0, 32), substr(base64_encode(hash('sha256', str_replace('_', '.', $node_2), true)), 0, 32));
unset($node_1, $node_2);

$domain_uuid='';
$h1 = $_REQUEST['h1'];
$h2 = $_REQUEST['h2'];
$type = $_REQUEST['type']; 
$h1 = str_replace('_', '.', $h1);
$h2 = str_replace('_', '.', $h2);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://$h1/core/auto/get_db.php?type=$type"."&".http_build_query(json_decode($enc_1, true)));
unset($enc_1);
curl_setopt($ch, CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
curl_setopt($ch, CURLOPT_TIMEOUT, 1);
curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
$json = curl_exec($ch);
#echo "$h1: $json"; 
curl_close($ch);
if (strlen($json) > 1) {
	$info1 = json_decode($json, true);	
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://$h2/core/auto/get_db.php?type=$type"."&".http_build_query(json_decode($enc_2, true)));
unset($enc_2);
curl_setopt($ch, CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
curl_setopt($ch, CURLOPT_TIMEOUT, 1);
curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
$json = curl_exec($ch);
#echo "$h2: $json";

curl_close($ch);
if (strlen($json) > 1) {
	$info2 = json_decode($json, true);	
}


// print_r($info1);
// print_r($info2);
$sql = "select * from v_default_settings where default_setting_category='api' and default_setting_subcategory='ignoretables' and default_setting_enabled='true'";

#echo $sql;
$prep_statement = $db->prepare($sql);
if ($prep_statement) {
        $prep_statement->execute();
        $result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
        foreach ($result as $row) {
           $csv_string = $row[default_setting_value];
		   break;
        }
}
unset($prep_statement,$result, $row, $sql);
$ignore_tables = explode(',', $csv_string);

foreach ($info1 as $table => $v) {
		if(in_array($table, $ignore_tables)) {
			echo "Ingore $table !!!<br>\n";
			continue;
		}
		echo "compare $h1: $table ...<br>\n";
		$len = count($info1[$table]);
		if (!empty($info2[$table])) {
			for ($i=0;$i<$len;$i++) {
				$info1_table_i_name = $info1[$table][$i]['name'];
				$info1_table_i_type = $info1[$table][$i]['type'];
				$info1_table_i_notnull = $info1[$table][$i]['notnull'];
				
				$info2_table_finded_name = array_filter($info2[$table], function($item) use ($info1_table_i_name) {
					return $item['name'] == $info1_table_i_name;
				});

				$info2_table_finded_type = array_filter($info2[$table], function($item) use ($info1_table_i_type) {
					return $item['type'] == $info1_table_i_type;
				});

				$info2_table_finded_notnull = array_filter($info2[$table], function($item) use ($info1_table_i_notnull) {
					return $item['notnull'] == $info1_table_i_notnull;
				});

				$info2_table_finded_name_per_i = array_pop($info2_table_finded_name)['name'];
				$info2_table_finded_type_per_i = array_pop($info2_table_finded_type)['type'];
				$info2_table_finded_notnull_per_i = array_pop($info2_table_finded_notnull)['notnull'];
				
				if ($info1_table_i_name != $info2_table_finded_name_per_i) {
						echo "Error: [name] $h1:", $info1_table_i_name, " ==> $h2:", $info2_table_finded_name_per_i, "<br>\n";
						continue;
				}
				
				if ($info1_table_i_type != $info2_table_finded_type_per_i) {
						echo "Error: [type] $h1:", $info1_table_i_type, " ==> $h2:", $info2_table_finded_type_per_i, "<br>\n";
						continue;
				}
				
				if ($info1_table_i_notnull != $info2_table_finded_notnull_per_i) {
						echo "Error: [notnull] $h1:", $info1_table_i_notnull, " ==> $h2:", $info2_table_finded_notnull_per_i, "<br>\n";
						continue;
				}
			}
		} else {
			echo "Error: [table] $h1:", $table, " ==> $h2:", $table, "<br>\n";
		}
}

foreach ($info2 as $table => $v) {
		if(in_array($table, $ignore_tables)) {
			echo "Ingore $table !!!<br>\n";
			continue;
		}
		echo "compare $h2: $table ...<br>\n";
		$len = count($info2[$table]);
		if (!empty($info1[$table])) {
			for ($i=0;$i<$len;$i++) {
				$info2_table_i_name = $info2[$table][$i]['name'];
				$info2_table_i_type = $info2[$table][$i]['type'];
				$info2_table_i_notnull = $info2[$table][$i]['notnull'];
				
				$info1_table_finded_name = array_filter($info1[$table], function($item) use ($info2_table_i_name) {
					return $item['name'] == $info2_table_i_name;
				});
				
				$info1_table_finded_type = array_filter($info1[$table], function($item) use ($info2_table_i_type) {
					return $item['type'] == $info2_table_i_type;
				});

				$info1_table_finded_notnull = array_filter($info1[$table], function($item) use ($info2_table_i_notnull) {
					return $item['notnull'] == $info2_table_i_notnull;
				});

				$info1_table_finded_name_per_i = array_pop($info1_table_finded_name)['name'];
				$info1_table_finded_type_per_i = array_pop($info1_table_finded_type)['type'];
				$info1_table_finded_notnull_per_i = array_pop($info1_table_finded_notnull)['notnull'];
				
				if ($info2_table_i_name != $info1_table_finded_name_per_i) {
						echo "Error: [name] $h2:", $info2_table_i_name, " ==> $h1:", $info1_table_finded_name_per_i, "<br>\n";
						continue;
				}
				
				if ($info2_table_i_type != $info1_table_finded_type_per_i) {
						echo "Error: [type] $h2:", $info2_table_i_type, " ==> $h1:", $info1_table_finded_type_per_i, "<br>\n";
						continue;
				}
				
				if ($info2_table_i_notnull != $info1_table_finded_notnull_per_i) {
						echo "Error: [notnull] $h2:", $info2_table_i_notnull, " ==> $h1:", $info1_table_finded_notnull_per_i, "<br>\n";
						continue;
				}
			}
		} else {
			echo "Error: [table] $h2:", $table, " ==> $h1:", $table, "<br>\n";
		}
}
print "Successfully!!!";
?>
