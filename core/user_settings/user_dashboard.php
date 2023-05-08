<?php

//set the include path
	$conf = array_merge(glob("/etc/fusionpbx/config.conf"), glob("/usr/localetc/fusionpbx/config.conf"));
	set_include_path(parse_ini_file($conf[0])['document.root']);

//start the session
	ini_set("session.cookie_httponly", True);
	if (!isset($_SESSION)) { session_start(); }

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//add the header
	require_once "resources/header.php";

//content
  echo "<br />\n";
  echo "<br />\n";
  echo "<br />\n";
  echo "<br />\n";
  echo "<br />\n";
  echo "<br />\n";
  echo "<br />\n";
  echo "<br />\n";

//add the footer
	require_once "resources/footer.php";

?>
