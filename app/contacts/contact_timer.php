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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	if (!permission_exists('contact_time_add')) { echo "access denied"; exit; }

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get contact uuid
	$domain_uuid = $_REQUEST['domain_uuid'];
	$contact_uuid = $_REQUEST['contact_uuid'];

//get posted variables & set time status
	if (is_array($_POST) && @sizeof($_POST) != 0) {
		$contact_time_uuid = $_POST['contact_time_uuid'];
		$contact_uuid = $_POST['contact_uuid'];
		$time_action = $_POST['time_action'];
		$time_description = $_POST['time_description'];

		if ($time_description == 'Description...') { unset($time_description); }

		if ($time_action == 'start') {
			$contact_time_uuid = uuid();
			$array['contact_times'][0]['domain_uuid'] = $domain_uuid;
			$array['contact_times'][0]['contact_time_uuid'] = $contact_time_uuid;
			$array['contact_times'][0]['contact_uuid'] = $contact_uuid;
			$array['contact_times'][0]['user_uuid'] = $_SESSION["user"]["user_uuid"];
			$array['contact_times'][0]['time_start'] = date("Y-m-d H:i:s");
			$array['contact_times'][0]['time_description'] = $time_description;
		}
		if ($time_action == 'stop') {
			$array['contact_times'][0]['contact_time_uuid'] = $contact_time_uuid;
			$array['contact_times'][0]['time_stop'] = date("Y-m-d H:i:s");
			$array['contact_times'][0]['time_description'] = $time_description;
		}

		if (is_array($array) && @sizeof($array) != 0) {
			$database = new database;
			$database->app_name = 'contacts';
			$database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
			$database->save($array);
			unset($array);
		}

		header("Location: contact_timer.php?domain_uuid=".$domain_uuid."&contact_uuid=".$contact_uuid);
	}

//get contact details
	$sql = "select ";
	$sql .= "contact_organization, ";
	$sql .= "contact_name_given, ";
	$sql .= "contact_name_family, ";
	$sql .= "contact_nickname ";
	$sql .= "from v_contacts ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && @sizeof($row) != 0) {
		$contact_organization = $row["contact_organization"];
		$contact_name_given = $row["contact_name_given"];
		$contact_name_family = $row["contact_name_family"];
		$contact_nickname = $row["contact_nickname"];
	}
	else {
		exit;
	}
	unset($sql, $parameters, $row);

//determine timer state and action
	$sql = "select ";
	$sql .= "contact_time_uuid, ";
	$sql .= "time_description ";
	$sql .= "from v_contact_times ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and user_uuid = :user_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "and time_start is not null ";
	$sql .= "and time_stop is null ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && @sizeof($row) != 0) {
		$contact_time_uuid = $row["contact_time_uuid"];
		$time_description = $row["time_description"];
	}
	unset($sql, $parameters, $row);

	$timer_state = is_uuid($contact_time_uuid) ? 'running' : 'stopped';
	$timer_action = $timer_state == 'running' ? 'stop' : 'start';

//determine contact name to display
	if ($contact_nickname != '') {
		$contact = $contact_nickname;
	}
	else if ($contact_name_given != '') {
		$contact = $contact_name_given;
	}
	if ($contact_name_family != '') {
		$contact .= ($contact != '') ? ' '.$contact_name_family : $contact_name_family;
	}
	if ($contact_organization != '') {
		$contact .= ($contact != '') ? ', '.$contact_organization : $contact_organization;
	}

//get the browser version
	$user_agent = http_user_agent();
	$browser_version =  $user_agent['version'];
	$browser_name =  $user_agent['name'];
	$browser_version_array = explode('.', $browser_version);

//set the doctype
	echo ($browser_name != "Internet Explorer") ? "<!DOCTYPE html>\n" : "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";

?>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
<head>
	<title><?php echo $text['label-time_timer']; ?>: <?php echo $contact; ?></title>
	<style>
		body {
			color: #5f5f5f;
			font-size: 12px;
			font-family: arial;
			margin: 0;
			padding: 15px;
			}

		b {
			color: #952424;
			font-size: 15px;
			font-family: arial;
			}

		a {
			color: #004083;
			width: 100%;
			}

		a:hover {
			color: #5082ca;
			}

		form {
			margin: 0;
			}

		input.btn, input.button {
			font-family: Candara, Calibri, Segoe, "Segoe UI", Optima, Arial, sans-serif;
			padding: 2px 6px 3px 6px;
			color: #fff;
			font-weight: bold;
			cursor: pointer;
			font-size: 11px;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
			-khtml-border-radius: 3px;
			border-radius: 3px;
			background-image: -moz-linear-gradient(top, #524f59 25%, #000 64%);
			background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0.25, #524f59), color-stop(0.64, #000));
			border: 1px solid #26242a;
			background-color: #000;
			text-align: center;
			text-transform: uppercase;
			text-shadow: 0px 0px 1px rgba(0, 0, 0, 0.85);
			opacity: 0.9;
			-moz-opacity: 0.9;
			}

		input.btn:hover, input.button:hover, img.list_control_icon:hover {
			box-shadow: 0 0 5px #cddaf0;
			-webkit-box-shadow: 0 0 5px #cddaf0;
			-moz-box-shadow: 0 0 5px #cddaf0;
			opacity: 1.0;
			-moz-opacity: 1.0;
			cursor: pointer;
			}

		input.txt, textarea.txt, select.txt, .formfld {
			font-family: arial;
			font-size: 12px;
			color: #000;
			text-align: left;
			padding: 5px;
			border: 1px solid #c0c0c0;
			background-color: #fff;
			box-shadow: 0 0 3px #cddaf0 inset;
			-moz-box-shadow: 0 0 3px #cddaf0 inset;
			-webkit-box-shadow: 0 0 3px #cddaf0 inset;
			border-radius: 3px;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
			}

		input.txt, .formfld {
			transition: width 0.25s;
			-moz-transition: width 0.25s;
			-webkit-transition: width 0.25s;
			max-width: 500px;
			}

		input.txt:focus, .formfld:focus {
			-webkit-box-shadow: 0 0 5px #cddaf0;
			-moz-box-shadow: 0 0 5px #cddaf0;
			box-shadow: 0 0 5px #cddaf0;
			}

		td {
			color: #5f5f5f;
			font-size: 12px;
			font-family: arial;
			}

		.vncell {
			border-bottom: 1px solid #fff;
			background-color: #e5e9f0;
			padding: 8px;
			text-align: right;
			color: #000;
			-moz-border-radius: 4px;
			-webkit-border-radius: 4px;
			border-radius: 4px;
			border-right: 3px solid #e5e9f0;
			}

		DIV.timer_running {
			vertical-align: middle;
			padding-top: 7px;
			line-height: 50px;
			width: 100%;
			height: 53px;
			text-align: center;
			background-color: #2C9DE8;
			font-size: 50px;
			color: #FFFFFF;
			/*-webkit-text-shadow: 0px 0px 5px #000;*/
			/*-moz-text-shadow: 0px 0px 5px #000;*/
			/*text-shadow: 0px 0px 5px #000;*/
			font-weight: bold;
			letter-spacing: -0.05em;
			font-family: "Courier New",Courier,"Lucida Sans Typewriter","Lucida Typewriter",monospace;
			-moz-border-radius: 4px;
			-webkit-border-radius: 4px;
			border-radius: 4px;
			}

		DIV.timer_stopped {
			vertical-align: middle;
			padding-top: 7px;
			line-height: 50px;
			width: 100%;
			height: 53px;
			text-align: center;
			background-color: #2C9DE8;
			font-size: 50px;
			color: #FFFFFF;
			/*-webkit-text-shadow: 0px 0px 5px #000;*/
			/*-moz-text-shadow: 0px 0px 5px #000;*/
			/*text-shadow: 0px 0px 5px #000;*/
			font-weight: bold;
			letter-spacing: -0.05em;
			font-family: "Courier New",Courier,"Lucida Sans Typewriter","Lucida Typewriter",monospace;
			-moz-border-radius: 4px;
			-webkit-border-radius: 4px;
			border-radius: 4px;
			}

	</style>

	<script language='JavaScript' type='text/javascript' src='<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-3.6.1.min.js'></script>
	<script src='https://code.jquery.com/jquery-migrate-3.1.0.js'></script>
	<script type="text/javascript">
		$(document).ready(function(){
			//ajax for refresh
			var refresh = 1500;
			var source_url = 'contact_timer_inc.php?domain_uuid=<?php echo escape($domain_uuid); ?>&contact_uuid=<?php echo escape($contact_uuid); ?>&contact_time_uuid=<?php echo escape($contact_time_uuid); ?>';

			var ajax_get = function () {
				$.ajax({
					url: source_url, success: function(response){
						$("#ajax_reponse").html(response);
					}
				});
				setTimeout(ajax_get, refresh);
			};
			<?php if ($timer_state == 'running') { ?>
				ajax_get();
			<?php } ?>
		});

	//set window title to time when timer is running
		function set_title(title_text) {
			window.document.title = title_text;
		}

	</script>
</head>
<body>
	<img src='resources/images/icon_timer.png' style='width: 24px; height: 24px; border: none; margin-left: 15px;' alt="<?php echo $text['label-time_timer']; ?>" align='right'>
	<b><?php echo $text['label-time_timer']; ?></b>
	<br><br>
	<?php echo $text['description_timer']; ?>
	<br><br>
	<strong><a href="javascript:void(0);" onclick="window.opener.location.href='contact_edit.php?id=<?php echo escape($contact_uuid); ?>';"><?php echo escape($contact); ?></a></strong>
	<br><br>
	<div id='ajax_reponse' class='timer_<?php echo escape($timer_state);?>'>00:00:00</div>
	<br>
	<form name='frm' id='frm' method='post'>
	<input type='hidden' name='domain_uuid' value="<?php echo escape($domain_uuid); ?>">
	<input type='hidden' name='contact_time_uuid' value="<?php echo escape($contact_time_uuid); ?>">
	<input type='hidden' name='contact_uuid' value="<?php echo escape($contact_uuid); ?>">
	<input type='hidden' name='time_action' value="<?php echo escape($timer_action); ?>">
	<table cellpadding='0' cellspacing='0' border='0' style='width: 100%;'>
		<tr>
			<td class='vncell' style='text-align: center; border: none; padding: 0 !important; padding-top: 10px !important;'>
				<?php echo $text['label-description']; ?><br>
				<textarea name='time_description' id='timer_description' class='formfld' style='width: calc(100% - 30px); height: 50px; margin: 5px 10px 10px;'><?php echo escape($time_description); ?></textarea>
			</td>
		</tr>
	</table>
	<br>
	<center>
	<?php if ($timer_state == 'running') { ?>
		<input type='submit' class='btn' value="<?php echo $text['button-stop']; ?>">
	<?php } else if ($timer_state == 'stopped') { ?>
		<input type='submit' class='btn' value="<?php echo $text['button-start']; ?>">
	<?php } ?>
	</center>
	</form>
	<?php if ($timer_state == 'stopped') { ?><script>$('#timer_description').trigger('focus');</script><?php } ?>
</body>
</html>
