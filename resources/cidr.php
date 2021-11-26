<?php

//check the domain cidr range 
	if (isset($_SESSION['domain']["cidr"]) && !defined('STDIN')) {
		$found = false;
		foreach($_SESSION['domain']["cidr"] as $cidr) {
			if (check_cidr($cidr, $_SERVER['REMOTE_ADDR'])) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			echo "access denied";
			exit;
		}
	}
 
 ?>
