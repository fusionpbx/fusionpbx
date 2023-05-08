<?php

//set the include path
	$conf = array_merge(glob("/etc/fusionpbx/config.conf"), glob("/usr/localetc/fusionpbx/config.conf"));
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	include "resources/functions.php";

//show the uuid
	echo uuid();

?>
