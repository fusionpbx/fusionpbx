<?php
//application details
	$apps[$x]['name'] = "CheckCompareFor2Db";
	$apps[$x]['uuid'] = "7b7b0be6-919c-4ab3-a1c7-beafbe40cb3d";
	$apps[$x]['category'] = "";
	$apps[$x]['subcategory'] = "";
	$apps[$x]['version'] = "1.0";
	$apps[$x]['license'] = "Mozilla Public License 1.1";
	$apps[$x]['url'] = "http://www.fusionpbx.com";
	$apps[$x]['description']['en-us'] = "";
	$apps[$x]['description']['en-gb'] = "";
	$apps[$x]['description']['ar-eg'] = "";
	$apps[$x]['description']['de-at'] = "";
	$apps[$x]['description']['de-ch'] = "";
	$apps[$x]['description']['de-de'] = "";
	$apps[$x]['description']['es-cl'] = "";
	$apps[$x]['description']['es-mx'] = "";
	$apps[$x]['description']['fr-ca'] = "";
	$apps[$x]['description']['fr-fr'] = "";
	$apps[$x]['description']['he-il'] = "";
	$apps[$x]['description']['it-it'] = "";
	$apps[$x]['description']['nl-nl'] = "";
	$apps[$x]['description']['pl-pl'] = "";
	$apps[$x]['description']['pt-br'] = "";
	$apps[$x]['description']['pt-pt'] = "";
	$apps[$x]['description']['ro-ro'] = "";
	$apps[$x]['description']['ru-ru'] = "";
	$apps[$x]['description']['sv-se'] = "";
	$apps[$x]['description']['uk-ua'] = "";
	
	//permission details
	$y=0;
	$apps[$x]['permissions'][$y]['name'] = "check_compare_db";
	$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
	$y++;

	//default settings
	$y=0;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "07710735-4029-46b4-bbdf-741bdc1050e1";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "data_base_replication_check";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "refresh_time";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "numeric";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "600";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "seconds";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "36809425-a1d0-4d3a-9b15-7667016612d6";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "data_base_replication_check";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "node_1";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Node 1 Domain (sub.domain.com)";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "2dd8e2b1-8840-4a9d-b6b6-9be0252371f9";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "data_base_replication_check";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "node_2";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Node 2 Domain (sub.domain.com)";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "5154e96a-7a04-4e37-a38c-3c22464a4061";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "data_base_replication_check";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "node_1_password";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Node 1 Domain Access Password";
	$y++;
	$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "d5234af8-6e6a-4360-9c8a-3ccd87cea8f4";
	$apps[$x]['default_settings'][$y]['default_setting_category'] = "data_base_replication_check";
	$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "node_2_password";
	$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
	$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
	$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "true";
	$apps[$x]['default_settings'][$y]['default_setting_description'] = "Node 2 Domain Access Password";
?>