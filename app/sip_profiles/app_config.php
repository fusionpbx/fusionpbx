<?php
	//application details
		$apps[$x]['name'] = "SIP Profiles";
		$apps[$x]['uuid'] = "159a8da8-0e8c-a26b-6d5b-19c532b6d470";
		$apps[$x]['category'] = "";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "";
		$apps[$x]['license'] = "Mozilla Public License 1.1";
		$apps[$x]['url'] = "http://www.fusionpbx.com";
		$apps[$x]['description']['en-us'] = "";
		$apps[$x]['description']['es-cl'] = "";
		$apps[$x]['description']['es-mx'] = "";
		$apps[$x]['description']['de-de'] = "";
		$apps[$x]['description']['de-ch'] = "";
		$apps[$x]['description']['de-at'] = "";
		$apps[$x]['description']['fr-fr'] = "";
		$apps[$x]['description']['fr-ca'] = "";
		$apps[$x]['description']['fr-ch'] = "";
		$apps[$x]['description']['pt-pt'] = "";
		$apps[$x]['description']['pt-br'] = "";

	//menu details
		$apps[$x]['menu'][0]['title']['en-us'] = "SIP Profiles";
		$apps[$x]['menu'][0]['title']['es-cl'] = "Perfiles SIP";
		$apps[$x]['menu'][0]['title']['es-mx'] = "";
		$apps[$x]['menu'][0]['title']['de-de'] = "";
		$apps[$x]['menu'][0]['title']['de-ch'] = "";
		$apps[$x]['menu'][0]['title']['de-at'] = "";
		$apps[$x]['menu'][0]['title']['fr-fr'] = "Profiles SIP";
		$apps[$x]['menu'][0]['title']['fr-ca'] = "";
		$apps[$x]['menu'][0]['title']['fr-ch'] = "";
		$apps[$x]['menu'][0]['title']['pt-pt'] = "Perfis SIP";
		$apps[$x]['menu'][0]['title']['pt-br'] = "";
		$apps[$x]['menu'][0]['uuid'] = "47014b1d-13ad-921c-313d-ca42c0424b37";
		$apps[$x]['menu'][0]['parent_uuid'] = "594d99c5-6128-9c88-ca35-4b33392cec0f";
		$apps[$x]['menu'][0]['category'] = "internal";
		$apps[$x]['menu'][0]['path'] = "/app/sip_profiles/sip_profiles.php";
		$apps[$x]['menu'][0]['groups'][] = "superadmin";

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "47014b1d-13ad-921c-313d-ca42c0424b37";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_add";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_setting_view";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_setting_add";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_setting_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_setting_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_setting_view";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_setting_add";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_setting_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "sip_profile_setting_delete";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = "v_sip_profiles";
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_name";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "Enter the SIP Profile name.";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_hostname";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(255)";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_description";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "Enter the description.";
		$z++;

		$y = 1; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = "v_sip_profile_settings";
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_setting_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_setting_name";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "Enter the name.";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_setting_value";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "Enter the value.";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_setting_enabled";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "Choose to enable or disable this.";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "sip_profile_setting_description";
		$apps[$x]['db'][$y]['fields'][$z]['type'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "Enter the description.";
		$z++;
?>
