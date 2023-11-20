<?php
$recording_id='';
$domain_id='';
if(isset($_POST['recording_id'])){
    $recording_id=$_POST['recording_id'];
}
if(isset($_POST['domain_id'])){
    $domain_id=$_POST['domain_id'];
}
if(!empty($recording_id)&&!empty($domain_id)){
    require_once "../../../resources/classes/database.php";
    require 'classes/aws/aws-autoloader.php';
    
    
                                
    function getS3Setting($domain_id){
    
        $config=[];
    
        $sql = "select * from v_domain_settings ";
        $sql .= "where domain_setting_category = 'aws' ";
        $sql .= "and domain_uuid = '".$domain_id."' \n";
        // $parameters['domain_uuid'] = $domain_id;
        //$parameters['domain_uuid'] = $domain_uuid;
        $database = new database;
        $row = $database->select($sql);
        // $row = $database->select($sql);
    
    
        if (is_array($row) && count($row)>0) {
            $config['driver']='s3';
            $config['url']='';
            $config['endpoint']='';
            $config['region']='us-west-2';
            $config['use_path_style_endpoint']=false;
        
                foreach($row as $conf){
                    $config[getCredentialKey($conf['domain_setting_subcategory'])]=trim($conf['domain_setting_value']);
                }

        }  else {
            $config['driver']='s3';
                $config['url']='';
                $config['endpoint']='';
                $config['region']='us-west-2';
                $config['use_path_style_endpoint']=false;
            
                $config=getDefaultS3Configuration();
                
            }
            unset ($sql, $parameters, $row);
            
            $setting['default']='s3';
            $setting['disks']['s3']=$config;
            return $config;
    
      }
       function getDefaultS3Configuration(){
    
        $sql = "select * from v_default_settings ";
        $sql .= "where default_setting_category = 'aws' ";
        $database = new database;
        $default_credentials = $database->select($sql);
        
        $config=[];
        foreach($default_credentials as $d_conf){
            $config[getCredentialKey($d_conf['default_setting_subcategory'])]=$d_conf['default_setting_value'];
        }
        return $config;
    }
    function getCredentialKey($string){
       switch($string){
        case 'region':
            return 'region';
        case 'secret_key':
            return 'secret';
        case 'bucket_name':
            return 'bucket';
        case 'access_key':
            return 'key';
        default:
            return $string;
       }
    }

    //escape user data
	function escape($string) {
		if (is_array($string)) {
			return false;
		}
		elseif (isset($string) && strlen($string)) {
			return htmlentities($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		else {
			return false;
		}
		//return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	}

    $setting=getS3Setting($domain_id);
    $s3 = new \Aws\S3\S3Client([
    'region'  => $setting['region'],
    'version' => 'latest',
    'credentials' => [
        'key'    => $setting['key'],
        'secret' => $setting['secret']
    ]
    ]);

    $database = new database;

    // Check v_xml_cdr table first. New way
    $sql='Select record_path, record_name from v_xml_cdr where xml_cdr_uuid = :xml_cdr_uuid';
    $parameters['xml_cdr_uuid'] = $recording_id;
    $rec = $database->select($sql, $parameters, 'row');

    if (is_array($rec) && $rec['record_path'] == 'S3') {
        $response = $s3->doesObjectExist($setting['bucket'],$rec['record_name']);

        if($response){	
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $setting['bucket'],
                'Key'    => $rec['record_name']
            ]);
            $request = $s3->createPresignedRequest($cmd, '+60 minutes');
            $file_path = (string) $request->getUri();
        } else {
            echo json_encode(['success'=>false]);
            exit;
        }

        unset ($sql, $parameters, $rec);
        echo json_encode(['success'=>true,'path'=>$file_path]);
        exit;

    } 

    // Check archive_recording table if not found in v_xml_cdr. Old way
    $sql='Select * from archive_recording where xml_cdr_uuid = :xml_cdr_uuid';
    $parameters['xml_cdr_uuid'] = $recording_id;
    $rec = $database->select($sql, $parameters, 'row');

    if (is_array($rec)) {
        $response = $s3->doesObjectExist($setting['bucket'], $rec['object_key']);

        if($response){									
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $setting['bucket'],
                'Key'    => $rec['object_key']
            ]);
            $request = $s3->createPresignedRequest($cmd, '+60 minutes');
            $file_path = (string) $request->getUri();
        } else {
            echo json_encode(['success'=>false]);
            exit;
        }

        unset ($sql, $parameters, $rec);
        echo json_encode(['success'=>true,'path'=>$file_path]);
        exit;

    } 
    
    // If not found in both tables, provide a default file path
    $file_path='download.php?id='.escape($recording_id).'&t=record';
    
    unset ($sql, $parameters, $rec);
    
    echo json_encode(['success'=>true,'path'=>$file_path]);
    exit;
    
} else {
    echo json_encode(['success'=>false]);
    exit;

}

?>
