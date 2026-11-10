<?php
require_once dirname(__DIR__,2).'/resources/require.php';
require_once 'resources/check_auth.php';
require_once __DIR__.'/resources/classes/recording_segment_access.php';
$binary = isset($_GET['binary']);
$can_play = permission_exists('xml_cdr_details') && permission_exists('xml_cdr_recording') && permission_exists('xml_cdr_recording_play');
$can_download = permission_exists('xml_cdr_details') && permission_exists('xml_cdr_recording') && permission_exists('xml_cdr_recording_download');
if (($binary && !$can_download) || (!$binary && !$can_play)) { http_response_code(403); exit; }
$access=new recording_segment_access(database::new());
$path=$access->path($_GET['id']??'',$_SESSION['domain_uuid']??'',permission_exists('call_recording_all') || permission_exists('xml_cdr_all'));
if($path===null||!is_file($path)||!is_readable($path)){http_response_code(404);exit;}
$mime=['wav'=>'audio/wav','mp3'=>'audio/mpeg','ogg'=>'audio/ogg'][strtolower(pathinfo($path,PATHINFO_EXTENSION))]??'application/octet-stream';
header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));if($binary)header('Content-Disposition: attachment; filename="'.basename($path).'"');readfile($path);
