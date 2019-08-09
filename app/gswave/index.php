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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('extension_edit')) {
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

//get the extensions
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and enabled = 'true' ";
	$sql .= "order by extension asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the extension
	if (is_uuid($_GET['id'])) {
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and extension_uuid = :extension_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['extension_uuid'] = $extension_uuid;
		$database = new database;
		$extension = $database->select($sql, $parameters, 'all');
		$field = $extension[0];
		unset($sql, $parameters);
	}

//get the username
	$username = $field['extension'];
	if (isset($field['number_alias']) && strlen($field['number_alias']) > 0) {
		$username = $field['number_alias'];
	}

//build the xml
	if (is_uuid($_GET['id'])) {
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

//additional includes
	require_once "resources/header.php";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-gswave']."</b></td>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	//echo "			<input type='button' class='btn' name='' alt='Grandstream Wave' onclick=\"window.location='http://www.grandstream.com/products/ip-voice-telephony/softphone-app/product/grandstream-wave'\" value='Website'>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			<br />".$text['title_description-gswave']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

//show the content
	echo "<form name='frm' id='frm' method='get' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	//echo "<tr>\n";
	//echo "<td width='70%' colspan='2' align='left' valign='top'>\n";
	//echo "	<br />\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' onchange='this.form.submit();' name='id'>\n";
	echo "		<option value=''></option>\n";
	foreach($extensions as $row) {
		if ($row['extension_uuid'] == $extension_uuid) { $selected = "selected='selected'"; } else { $selected = ''; }
		echo "		<option value='".escape($row['extension_uuid'])."' $selected>".escape($row['extension'])." ".escape($row['number_alias'])." ".escape($row['description'])."</option>\n";
	}
	echo "	</select>\n";
	//echo "<br />\n";
	//echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	//echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-message']."</b><br><br></td>\n";
	echo "<td width='70%' colspan='2' align='left' valign='top'>\n";
	echo "	<br />\n";
	echo "	<a href=\"https://play.google.com/store/apps/details?id=com.grandstream.wave\" target=\"_blank\"><img src=\"/app/gswave/resources/images/google_play.png\" style=\"height:71px;\"/></a>";
	echo "	<a href=\"https://itunes.apple.com/us/app/grandstream-wave/id1029274043?ls=1&mt=8\" target=\"_blank\"><img src=\"/app/gswave/resources/images/apple_app_store.png\" style=\"height:71px;\" /></a>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</form>";
	echo "<br />";

//stream the file
	if (is_uuid($_GET['id'])) {
		$include_path = get_include_path();
		$xml = html_entity_decode( $xml, ENT_QUOTES, 'UTF-8' );
		set_include_path ($_SERVER["PROJECT_ROOT"].'/resources/qr_code');
		
		require_once 'QRErrorCorrectLevel.php';
		require_once 'QRCode.php';
		require_once 'QRCodeImage.php';
  
		try {
			$code = new QRCode (- 1, QRErrorCorrectLevel::H);
			$code->addData($xml);
			$code->make();
			
			$img = new QRCodeImage ($code, $width=420, $height=420, $quality=50);
			$img->draw();
			$image = $img->getImage();
			$img->finish();
			
			//if ($image) {
			//  header ( 'Content-Type: image/jpeg' );
			//  header ( 'Content-Length: ' . strlen ( $imgdata ) );
			//  echo $image;
			//}
		}
		catch (Exception $error) {
			echo $error;
		}
	}

//html image
	if (is_uuid($_GET['id'])) {
		echo "<img src=\"data:image/jpeg;base64,". base64_encode($image) ."\">\n";
	}

//add the footer
	set_include_path ($include_path);
	require_once "resources/footer.php";

?>
