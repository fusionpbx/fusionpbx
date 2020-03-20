<?php

ob_start('ob_gzhandler');
header('Content-type: text/css; charset: UTF-8');
header('Cache-Control: must-revalidate');
header('Expires: '.gmdate('D, d M Y H:i:s',time()+3600).' GMT');

include_once 'bootstrap-tempusdominus.min.css';

?>