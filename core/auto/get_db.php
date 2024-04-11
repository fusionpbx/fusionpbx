<?php
require_once dirname(__DIR__, 2) . "/resources/require.php";

// Auth-Part
function cryptoJsAesDecrypt($passphrase, $jsonString){
    $jsondata = json_decode($jsonString, true);
    try {
        $salt = hex2bin($jsondata["s"]);
        $iv  = hex2bin($jsondata["iv"]);
    } catch(Exception $e) { return null; }
    $ct = base64_decode($jsondata["ct"]);
    $concatedPassphrase = $passphrase.$salt;
    $md5 = array();
    $md5[0] = md5($concatedPassphrase, true);
    $result = $md5[0];
    for ($i = 1; $i < 3; $i++) {
        $md5[$i] = md5($md5[$i - 1].$concatedPassphrase, true);
        $result .= $md5[$i];
    }
    $key = substr($result, 0, 32);
    $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
    return json_decode($data, true);
}

$sql = "select default_setting_value from v_default_settings where default_setting_category='data_base_replication_check' and default_setting_subcategory = (select default_setting_subcategory from v_default_settings where default_setting_category='data_base_replication_check' and default_setting_value = '".str_replace('_', '.', $_SERVER['HTTP_HOST'])."') || '_password' order by default_setting_value asc";
$prep_statement = $db->prepare($sql);
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
$password = substr(preg_replace("/[^A-Za-z0-9]/",'', $result[0]['default_setting_value']), 0, 32);
unset($sql, $prep_statement, $prep_statement, $result);
$decryptedData = cryptoJsAesDecrypt($password, json_encode($_REQUEST));
unset($password);

if (substr(preg_replace("/[^A-Za-z0-9]/",'', base64_encode(hash('sha256', str_replace('_', '.', $_SERVER['HTTP_HOST']), true))), 0, 28) == substr(preg_replace("/[^A-Za-z0-9]/",'', $decryptedData), 0, 28)) {
	//access granted
} else {
	exit();
}
//

$domain_uuid='';
$search = $_REQUEST['search'];
$server = $_REQUEST['server'];
$type = $_REQUEST['type'];
if ($type == 'upgrade') {
		$tables = "v_users,v_user_settings,v_user_groups,v_permissions,v_menus,v_menu_languages,v_menu_items,v_menu_item_groups,v_groups,v_group_permissions,v_domains,v_domain_settings,v_default_settings,v_gateways, v_access_controls,v_access_control_nodes";
		$table_csv = "'" . implode("','", explode(',', $tables)) . "'";
}
$sql = "SELECT   tablename,obj_description(relfilenode,'pg_class')  FROM   pg_tables  a, pg_class b
WHERE   
a.tablename = b.relname
and a.tablename   NOT   LIKE   'pg%'

AND a.tablename NOT LIKE 'sql_%'" . ($table_csv ?  " and a.tablename in ($table_csv)" : "") . " order by tablename desc";
#echo $sql;
$prep_statement = $db->prepare($sql);
if ($prep_statement) {
        $prep_statement->execute();
        $result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
        foreach ($result as $row) {
            $info[$row['tablename']] = array();
        }
}

unset($sql, $prep_statement);

foreach($info as $table => $v) {
	$sql = "SELECT col_description(a.attrelid,a.attnum) as comment,format_type(a.atttypid,a.atttypmod) as type,a.attname as name, a.attnotnull as notnull
FROM pg_class as c,pg_attribute as a
where c.relname = '$table' and a.attrelid = c.oid and a.attnum>0 and a.attname not like '...%'";
	#echo $sql;
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as $row) {
			$info[$table][] = $row;
		}
	}
}
echo json_encode($info);
?>
