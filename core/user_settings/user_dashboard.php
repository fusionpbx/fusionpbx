<?php

//start the session
	ini_set("session.cookie_httponly", True);
	if (!isset($_SESSION)) { session_start(); }

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
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
