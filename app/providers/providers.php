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
	Portions created by the Initial Developer are Copyright (C) 2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('dialplan_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the provider
	if (isset($_REQUEST["provider"])) {
		$provider = $_REQUEST["provider"];
		switch ($provider) {
			case 'voicetel':
				break;
			case 'skyetel': 
				break;
			default: 
				unset($provider);
		}
		echo $provider;
		exit;
	}

//skyetel installed
	$sql = "select gateway_uuid from v_gateways ";
	$database = new database;
	$gateways = $database->select($sql, null, 'all');
	$skyetel_installed = false;
	$voicetel_installed = false;
	foreach ($gateways as $row) {
		if ($row['gateway_uuid'] === "22245a48-552c-463a-a723-ce01ebbd69a2") {
			$skyetel_installed = true;
		}
		if ($row['gateway_uuid'] === "d61be0f0-3a4c-434a-b9f6-4fef15e1a634") {
			$voicetel_installed = true;
		}
	}
	unset($sql, $gateways);

?>

<div class="row">
	<div class='col-xs-12'>
		<?php
		echo "<b>".$text['title-providers']."</b>\n";
		echo "<br />\n";
		echo $text['description-providers']."\n";
		?>
	</div>
</div>
<div style='clear: both;'><hr /></div>

<div class="row">
	<div class="col-sm-4" style='padding-top: 0px;'>
		<br /><br />
		<a href='http://skye.tel/fusion-contact' target='_blank'>
			<img src="/app/providers/resources/images/skyetel-logo.png" style="width: 200px;" 
class="center-block img-responsive"><br>
		</a>
	</div>
	<div class="col-sm-8" style='padding-top: 0px;'>
		<?php
		echo "<h2>Skyetel</h2>\n";
		echo "<br />\n";
		echo "<strong>".$text['label-region']."</strong><br />\n";
		echo $text['label-region_skyetel']."\n";
		echo "<br /><br />\n";
		echo "<strong>".$text['label-about']."</strong><br />\n";
		echo $text['label-about_skyetel']."\n";
		echo "<br /><br />\n";
		echo "<strong>".$text['label-features']."</strong><br />\n";
		echo $text['label-features_skyetel']."\n";
		echo "<br /><br />\n";
		echo "<a href='http://skye.tel/fusionpbx-about' target='_blank'><button type=\"button\" class=\"btn btn-success\">".$text['button-website']."</button></a>\n";
		echo "<a href='http://skye.tel/fusion-pricing' target='_blank'><button type=\"button\" class=\"btn btn-success\">".$text['button-pricing']."</button></a>\n";
		echo "<a href='http://skye.tel/fusion-contact' target='_blank'><button type=\"button\" class=\"btn btn-success\">".$text['button-signup']."</button></a>\n";
		if (!$skyetel_installed) {
			echo "<button type=\"button\" onclick=\"window.location='provider_setup.php?provider=skyetel'\" class=\"btn btn-primary\">".$text['button-setup']."</button>\n";
		}
		else {
			echo "<button type=\"button\" onclick=\"window.location='provider_delete.php?provider=skyetel'\" class=\"btn btn-danger\">".$text['button-remove']."</button>\n";
		}
		?>
	</div>
</div>

<div style='clear: both;'><br/><br/><hr /><br/></div>

<div class="row">
	<div class="col-sm-4" style='padding-top: 0px;'>
		<br /><br /><br />
		<a href='http://tiny.cc/voicetel' target='_blank'>
			<img src="/app/providers/resources/images/logo_voicetel.png" style="width: 200px;" 
class="center-block img-responsive"><br>
		</a>
	</div>
	<div class="col-sm-8" style='padding-top: 0px;'>
		<?php
		echo "<h2>VoiceTel</h2>\n";
		echo "<br />\n";
		echo "<strong>".$text['label-region']."</strong><br />\n";
		echo $text['label-region_voicetel']."\n";
		echo "<br /><br />\n";
		echo "<strong>".$text['label-about']."</strong><br />\n";
		echo $text['label-about_voicetel']."\n";
		echo "<br /><br />\n";
		echo "<strong>".$text['label-features']."</strong><br />\n";
		echo $text['label-features_voicetel']."\n";
		echo "<br /><br />\n";
		echo "<a href='http://tiny.cc/voicetel' target='_blank'><button type=\"button\" class=\"btn btn-success\">".$text['button-website']."</button></a>\n";
		echo "<a href='http://tiny.cc/voicetel' target='_blank'><button type=\"button\" class=\"btn btn-success\">".$text['button-signup']."</button></a>\n";
		if (!$voicetel_installed) {
			echo "<button type=\"button\" onclick=\"window.location='provider_setup.php?provider=voicetel'\" class=\"btn btn-primary\">".$text['button-setup']."</button>\n";
		}
		else { 
			echo "<button type=\"button\" onclick=\"window.location='provider_delete.php?provider=voicetel'\" class=\"btn btn-danger\">".$text['button-remove']."</button>\n";
		}
		?>
	</div>

</div>

<div style='clear: both;'><br><br></div>

<?php
require_once "resources/footer.php";
?>
