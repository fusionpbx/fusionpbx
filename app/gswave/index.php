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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('gswave_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//verify the id is as uuid then set as a variable
	if (is_uuid($_GET['id'])) {
		$extension_uuid = $_GET['id'];
	}

//get the extension(s)
	if (permission_exists('extension_edit')) {
		//admin user
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and enabled = 'true' ";
		$sql .= "order by extension asc ";
	}
	else {
		//normal user
		$sql = "select e.* ";
		$sql .= "from v_extensions as e, ";
		$sql .= "v_extension_users as eu ";
		$sql .= "where e.extension_uuid = eu.extension_uuid ";
		$sql .= "and eu.user_uuid = :user_uuid ";
		$sql .= "and e.domain_uuid = :domain_uuid ";
		$sql .= "and e.enabled = 'true' ";
		$sql .= "order by e.extension asc ";
		$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
	}
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	if (is_uuid($extension_uuid) && is_array($extensions) && @sizeof($extensions) != 0) {

		//loop through get selected extension
			if (is_array($extensions) && @sizeof($extensions) != 0) {
				foreach ($extensions as $extension) {
					if ($extension['extension_uuid'] == $extension_uuid) {
						$field = $extension;
						break;
					}
				}
			}

		//get the username
			$username = $field['extension'];
			if (isset($field['number_alias']) && strlen($field['number_alias']) > 0) {
				$username = $field['number_alias'];
			}

		//build the xml
			$xml =  "<?xml version='1.0' encoding='utf-8'?>";
			$xml .= "<AccountConfig version='1'>";
			$xml .= "<Account>";
			$xml .= "<RegisterServer>".$_SESSION['domain_name']."</RegisterServer>";
			//$xml .= "<OutboundServer>".$_SESSION['domain_name']."</OutboundServer>";
			//$xml .= "<SecOutboundServer>".$_SESSION['domain_name']."</SecOutboundServer>";
			$xml .= "<OutboundServer>".$_SESSION['domain_name'].":".$_SESSION['provision']['line_sip_port']['numeric']."</OutboundServer>";
			$xml .= "<SecOutboundServer>".$_SESSION['domain_name'].":".$_SESSION['provision']['line_sip_port']['numeric']."</SecOutboundServer>";
			$xml .= "<UserID>".$username."</UserID>";
			$xml .= "<AuthID>".$username."</AuthID>";
			$xml .= "<AuthPass>".$field['password']."</AuthPass>";
			$xml .= "<AccountName>".$username."</AccountName>";
			$xml .= "<DisplayName>".$username."</DisplayName>";
			$xml .= "<Dialplan>{x+|*x+|*++}</Dialplan>";
			$xml .= "<RandomPort>0</RandomPort>";
			$xml .= "<Voicemail>*97</Voicemail>";
			$xml .= "</Account>";
			$xml .= "</AccountConfig>";

	}

//debian
	//apt install qrencode

//include the header
	$document['title'] = $text['title-gswave'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='get'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-gswave']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo "		<a href='https://play.google.com/store/apps/details?id=com.grandstream.wave' target='_blank'><img src='/app/gswave/resources/images/google_play.png' style='width: auto; height: 30px;' /></a>";
	echo "		<a href='https://itunes.apple.com/us/app/grandstream-wave/id1029274043?ls=1&mt=8' target='_blank'><img src='/app/gswave/resources/images/apple_app_store.png' style='width: auto; height: 30px;' /></a>";
	//echo button::create(['type'=>'button','label'=>'Website','icon'=>'globe','style='margin-left: 15px;','link'=>'http://www.grandstream.com/products/ip-voice-telephony/softphone-app/product/grandstream-wave']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-gswave']."\n";
	echo "<br /><br />\n";

	echo "<div style='text-align: center; white-space: nowrap; margin: 10px 0 40px 0;'>";
	echo $text['label-extension']."<br />\n";
	echo "<select name='id' class='formfld' onchange='this.form.submit();'>\n";
	echo "	<option value='' >".$text['label-select']."...</option>\n";
	if (is_array($extensions) && @sizeof($extensions) != 0) {
		foreach ($extensions as $row) {
			$selected = $row['extension_uuid'] == $extension_uuid ? "selected='selected'" : null;
			echo "	<option value='".escape($row['extension_uuid'])."' ".$selected.">".escape($row['extension'])." ".escape($row['number_alias'])." ".escape($row['description'])."</option>\n";
		}
	}
	echo "</select>\n";

	echo "</form>\n";
	echo "<br>\n";

//stream the file
	if (is_uuid($extension_uuid)) {
		$xml = html_entity_decode( $xml, ENT_QUOTES, 'UTF-8' );
		
		require_once 'resources/qr_code/QRErrorCorrectLevel.php';
		require_once 'resources/qr_code/QRCode.php';
		require_once 'resources/qr_code/QRCodeImage.php';
  
		try {
			$code = new QRCode (- 1, QRErrorCorrectLevel::H);
			$code->addData($xml);
			$code->make();
			
			$img = new QRCodeImage ($code, $width=420, $height=420, $quality=50);
			$img->draw();
			$image = $img->getImage();
			$img->finish();
		}
		catch (Exception $error) {
			echo $error;
		}
	}

//html image
	if (is_uuid($extension_uuid)) {
		echo "<img src=\"data:image/jpeg;base64,".base64_encode($image)."\" style='margin-top: 30px; padding: 5px; background: white; max-width: 100%;'>\n";
	}

	echo "</div>\n";

//add the footer
	require_once "resources/footer.php";

?>
