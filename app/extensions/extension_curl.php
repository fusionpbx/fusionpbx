<?php
    
   
    require_once  '../../resources/classes/database.php';

    $curl = curl_init();
    $err = "";
    $response = "";
    $data = json_encode($_POST['data']);
    $s_type = $_POST['s_type'];
    $a_type = $_POST['a_type'];

    $sql = "select default_setting_value from v_default_settings where default_setting_category = 'server' and default_setting_subcategory = :a_type
            UNION ALL
            select default_setting_value from v_default_settings where default_setting_category = 'server' and default_setting_subcategory = :s_type";
    
    $parameters['a_type'] = $a_type;
    $parameters['s_type'] = $s_type;
    
    $database = new database;

    $result = $database->select($sql, $parameters, 'all');
    unset($sql, $parameters);

    $path = $result[0]['default_setting_value'].$_POST['path'];
    $key = $result[1]['default_setting_value'];
    if(!isset($_POST['action'])){
        
        curl_setopt_array($curl, array(
        CURLOPT_URL => $path,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "content-type: application/json",
            "secret-key: $key"
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);


    }else{
       
		curl_setopt_array($curl, array(
  		CURLOPT_URL => $path,
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => '',
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 0,
  		CURLOPT_FOLLOWLOCATION => true,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => 'GET',
  		CURLOPT_HTTPHEADER =>  array(
            
            "secret-key: $key"
        ),
		));
        $response = curl_exec($curl);
        curl_close($curl);
    }

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        echo  $response;
    }

?>