<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once __DIR__ . "/resources/require.php";

//use custom logout destination if set otherwise redirect to the index page
	$logout_destination = $settings->get('login', 'logout_destination', PROJECT_PATH.'/');

//remove remember me token
	setcookie('remember', '', time() - 3600, '/');
	$sql = "update v_user_logs ";
	$sql .= "set remember_selector = null, ";
	$sql .= "remember_validator = null ";
	$sql .= "where user_uuid = :user_uuid ";
	$parameters['user_uuid'] = $_SESSION['user_uuid'];
	$database->execute($sql, $parameters);
	unset($sql, $parameters);

//destroy session
	session_unset();
	session_destroy();

//redirect the user to the logout page
	header("Location: ".$logout_destination);
	exit;
