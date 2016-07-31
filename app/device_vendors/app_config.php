<?php

	//application details
		$apps[$x]['name'] = 'Device Vendors';
		$apps[$x]['uuid'] = '4111e520-4ecc-431c-9763-d50bf87dfd50';
		$apps[$x]['category'] = '';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = '';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'device_vendor_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'device_vendor_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'device_vendor_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'device_vendor_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'device_vendor_function_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'device_vendor_function_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'device_vendor_function_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'device_vendor_function_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_device_vendors';
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'device_vendor_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Set the status of the vendor.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the description.';
		$z++;

		$y = 1; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_device_vendor_functions';
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'device_vendor_function_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'device_vendor_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_device_vendor';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'device_vendor_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'label';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the label.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'value';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the value.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Set the status of the function.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the description.';
		$z++;

	//vendor details
		$y=0; //vendor array index
		$z=0; //functions array index
		$vendors[$y]['name'] = "yealink";
		$vendors[$y]['functions'][$z]['label'] = "label-na";
		$vendors[$y]['functions'][$z]['name'] = "na";
		$vendors[$y]['functions'][$z]['value'] = "0";
		$vendors[$y]['functions'][$z]['groups'][] = "admin";
		$vendors[$y]['functions'][$z]['groups'][] = "superadmin";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-line";
		$vendors[$y]['functions'][$z]['name'] = "line";
		$vendors[$y]['functions'][$z]['value'] = "15";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-conference";
		$vendors[$y]['functions'][$z]['name'] = "conference";
		$vendors[$y]['functions'][$z]['value'] = "1";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-forward";
		$vendors[$y]['functions'][$z]['name'] = "forward";
		$vendors[$y]['functions'][$z]['value'] = "2";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-transfer";
		$vendors[$y]['functions'][$z]['name'] = "transfer";
		$vendors[$y]['functions'][$z]['value'] = "3";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-hold";
		$vendors[$y]['functions'][$z]['name'] = "hold";
		$vendors[$y]['functions'][$z]['value'] = "4";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-dnd";
		$vendors[$y]['functions'][$z]['name'] = "dnd";
		$vendors[$y]['functions'][$z]['value'] = "5";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-redial";
		$vendors[$y]['functions'][$z]['name'] = "redial";
		$vendors[$y]['functions'][$z]['value'] = "6";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-call_return";
		$vendors[$y]['functions'][$z]['name'] = "call_return";
		$vendors[$y]['functions'][$z]['value'] = "7";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-sms";
		$vendors[$y]['functions'][$z]['name'] = "sms";
		$vendors[$y]['functions'][$z]['value'] = "8";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-call_pickup";
		$vendors[$y]['functions'][$z]['name'] = "call_pickup";
		$vendors[$y]['functions'][$z]['value'] = "9";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-call_park";
		$vendors[$y]['functions'][$z]['name'] = "call_park";
		$vendors[$y]['functions'][$z]['value'] = "10";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-dtmf";
		$vendors[$y]['functions'][$z]['name'] = "dtmf";
		$vendors[$y]['functions'][$z]['value'] = "11";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-voicemail";
		$vendors[$y]['functions'][$z]['name'] = "voicemail";
		$vendors[$y]['functions'][$z]['value'] = "12";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial";
		$vendors[$y]['functions'][$z]['name'] = "speed_dial";
		$vendors[$y]['functions'][$z]['value'] = "13";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-intercom";
		$vendors[$y]['functions'][$z]['name'] = "intercom";
		$vendors[$y]['functions'][$z]['value'] = "14";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-blf";
		$vendors[$y]['functions'][$z]['name'] = "blf";
		$vendors[$y]['functions'][$z]['value'] = "16";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-url";
		$vendors[$y]['functions'][$z]['name'] = "url";
		$vendors[$y]['functions'][$z]['value'] = "17";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-public_hold";
		$vendors[$y]['functions'][$z]['name'] = "public_hold";
		$vendors[$y]['functions'][$z]['value'] = "19";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-private";
		$vendors[$y]['functions'][$z]['name'] = "private";
		$vendors[$y]['functions'][$z]['value'] = "20";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-shared_line";
		$vendors[$y]['functions'][$z]['name'] = "shared_line";
		$vendors[$y]['functions'][$z]['value'] = "21";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-xml_group";
		$vendors[$y]['functions'][$z]['name'] = "xml_group";
		$vendors[$y]['functions'][$z]['value'] = "22";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-group_pickup";
		$vendors[$y]['functions'][$z]['name'] = "group_pickup";
		$vendors[$y]['functions'][$z]['value'] = "23";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-paging";
		$vendors[$y]['functions'][$z]['name'] = "paging";
		$vendors[$y]['functions'][$z]['value'] = "24";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-record";
		$vendors[$y]['functions'][$z]['name'] = "record";
		$vendors[$y]['functions'][$z]['value'] = "25";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-xml_browser";
		$vendors[$y]['functions'][$z]['name'] = "xml_browser";
		$vendors[$y]['functions'][$z]['value'] = "27";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-history";
		$vendors[$y]['functions'][$z]['name'] = "history";
		$vendors[$y]['functions'][$z]['value'] = "28";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-directory";
		$vendors[$y]['functions'][$z]['name'] = "directory";
		$vendors[$y]['functions'][$z]['value'] = "29";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-menu";
		$vendors[$y]['functions'][$z]['name'] = "menu";
		$vendors[$y]['functions'][$z]['value'] = "30";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-new_sms";
		$vendors[$y]['functions'][$z]['name'] = "new_sms";
		$vendors[$y]['functions'][$z]['value'] = "32";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-status";
		$vendors[$y]['functions'][$z]['name'] = "status";
		$vendors[$y]['functions'][$z]['value'] = "33";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-hot_desking";
		$vendors[$y]['functions'][$z]['name'] = "hot_desking";
		$vendors[$y]['functions'][$z]['value'] = "34";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-url_record";
		$vendors[$y]['functions'][$z]['name'] = "url_record";
		$vendors[$y]['functions'][$z]['value'] = "35";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-ldap";
		$vendors[$y]['functions'][$z]['name'] = "ldap";
		$vendors[$y]['functions'][$z]['value'] = "38";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-blf_list";
		$vendors[$y]['functions'][$z]['name'] = "blf_list";
		$vendors[$y]['functions'][$z]['value'] = "39";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-prefix";
		$vendors[$y]['functions'][$z]['name'] = "prefix";
		$vendors[$y]['functions'][$z]['value'] = "40";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-zero_sp_touch";
		$vendors[$y]['functions'][$z]['name'] = "zero_sp_touch";
		$vendors[$y]['functions'][$z]['value'] = "41";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-acd";
		$vendors[$y]['functions'][$z]['name'] = "acd";
		$vendors[$y]['functions'][$z]['value'] = "42";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-local_phonebook";
		$vendors[$y]['functions'][$z]['name'] = "local_phonebook";
		$vendors[$y]['functions'][$z]['value'] = "43";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-broadsoft_phonebook";
		$vendors[$y]['functions'][$z]['name'] = "broadsoft_phonebook";
		$vendors[$y]['functions'][$z]['value'] = "44";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-local_group";
		$vendors[$y]['functions'][$z]['name'] = "local_group";
		$vendors[$y]['functions'][$z]['value'] = "45";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-broadsoft_group";
		$vendors[$y]['functions'][$z]['name'] = "broadsoft_group";
		$vendors[$y]['functions'][$z]['value'] = "46";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-xml_phonebook";
		$vendors[$y]['functions'][$z]['name'] = "xml_phonebook";
		$vendors[$y]['functions'][$z]['value'] = "47";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-switch_account_up";
		$vendors[$y]['functions'][$z]['name'] = "switch_account_up";
		$vendors[$y]['functions'][$z]['value'] = "48";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-switch_account_down";
		$vendors[$y]['functions'][$z]['name'] = "switch_account_down";
		$vendors[$y]['functions'][$z]['value'] = "49";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-keypad_lock";
		$vendors[$y]['functions'][$z]['name'] = "keypad_lock";
		$vendors[$y]['functions'][$z]['value'] = "50";
		$z++;

		$y++; //vendors array index
		$z=0; //functions array index
		$vendors[$y]['name'] = "snom";
		$vendors[$y]['functions'][$z]['label'] = "label-none";
		$vendors[$y]['functions'][$z]['name'] = "none";
		$vendors[$y]['functions'][$z]['value'] = "none";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-url";
		$vendors[$y]['functions'][$z]['name'] = "action_url";
		$vendors[$y]['functions'][$z]['value'] = "action_url";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-auto_answer";
		$vendors[$y]['functions'][$z]['name'] = "auto_answer";
		$vendors[$y]['functions'][$z]['value'] = "auto_answer";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-blf";
		$vendors[$y]['functions'][$z]['name'] = "blf";
		$vendors[$y]['functions'][$z]['value'] = "blf";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-button";
		$vendors[$y]['functions'][$z]['name'] = "button";
		$vendors[$y]['functions'][$z]['value'] = "button";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-call_agent";
		$vendors[$y]['functions'][$z]['name'] = "call_agent";
		$vendors[$y]['functions'][$z]['value'] = "call_agent";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-conference";
		$vendors[$y]['functions'][$z]['name'] = "conference";
		$vendors[$y]['functions'][$z]['value'] = "conference";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-dtmf";
		$vendors[$y]['functions'][$z]['name'] = "dtmf";
		$vendors[$y]['functions'][$z]['value'] = "dtmf";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-extension";
		$vendors[$y]['functions'][$z]['name'] = "dest";
		$vendors[$y]['functions'][$z]['value'] = "dest";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-redirect";
		$vendors[$y]['functions'][$z]['name'] = "redirect";
		$vendors[$y]['functions'][$z]['value'] = "redirect";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-intercom";
		$vendors[$y]['functions'][$z]['name'] = "icom";
		$vendors[$y]['functions'][$z]['value'] = "icom";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-ivr";
		$vendors[$y]['functions'][$z]['name'] = "ivr";
		$vendors[$y]['functions'][$z]['value'] = "ivr";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-key_event";
		$vendors[$y]['functions'][$z]['name'] = "keyevent";
		$vendors[$y]['functions'][$z]['value'] = "keyevent";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-line";
		$vendors[$y]['functions'][$z]['name'] = "line";
		$vendors[$y]['functions'][$z]['value'] = "line";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-multicast_page";
		$vendors[$y]['functions'][$z]['name'] = "multicast";
		$vendors[$y]['functions'][$z]['value'] = "multicast";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-orbit";
		$vendors[$y]['functions'][$z]['name'] = "orbit";
		$vendors[$y]['functions'][$z]['value'] = "orbit";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-presence";
		$vendors[$y]['functions'][$z]['name'] = "presence";
		$vendors[$y]['functions'][$z]['value'] = "presence";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-p2t";
		$vendors[$y]['functions'][$z]['name'] = "p2t";
		$vendors[$y]['functions'][$z]['value'] = "p2t";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-shared_line";
		$vendors[$y]['functions'][$z]['name'] = "mult";
		$vendors[$y]['functions'][$z]['value'] = "mult";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial";
		$vendors[$y]['functions'][$z]['name'] = "speed";
		$vendors[$y]['functions'][$z]['value'] = "speed";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-transfer";
		$vendors[$y]['functions'][$z]['name'] = "transfer";
		$vendors[$y]['functions'][$z]['value'] = "transfer";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-record";
		$vendors[$y]['functions'][$z]['name'] = "recorder";
		$vendors[$y]['functions'][$z]['value'] = "recorder";
		$z++;

		$y++; //vendors array index
		$z=0; //functions array index
		$vendors[$y]['name'] = "polycom";
		$vendors[$y]['functions'][$z]['label'] = "label-line";
		$vendors[$y]['functions'][$z]['name'] = "line";
		$vendors[$y]['functions'][$z]['value'] = "line";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-automata";
		$vendors[$y]['functions'][$z]['name'] = "automata";
		$vendors[$y]['functions'][$z]['value'] = "automata";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-normal";
		$vendors[$y]['functions'][$z]['name'] = "normal";
		$vendors[$y]['functions'][$z]['value'] = "normal";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-messages";
		$vendors[$y]['functions'][$z]['name'] = "Messages";
		$vendors[$y]['functions'][$z]['value'] = "Messages";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-micmute";
		$vendors[$y]['functions'][$z]['name'] = "MicMute";
		$vendors[$y]['functions'][$z]['value'] = "MicMute";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-redial";
		$vendors[$y]['functions'][$z]['name'] = "Redial";
		$vendors[$y]['functions'][$z]['value'] = "Redial";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-null";
		$vendors[$y]['functions'][$z]['name'] = "Null";
		$vendors[$y]['functions'][$z]['value'] = "Null";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speeddial";
		$vendors[$y]['functions'][$z]['name'] = "SpeedDial";
		$vendors[$y]['functions'][$z]['value'] = "SpeedDial";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speeddialmenu";
		$vendors[$y]['functions'][$z]['name'] = "SpeedDialMenu";
		$vendors[$y]['functions'][$z]['value'] = "SpeedDialMenu";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-url";
		$vendors[$y]['functions'][$z]['name'] = "URL";
		$vendors[$y]['functions'][$z]['value'] = "URL";

		$y++; //vendors array index
		$z=0; //functions array index
		$vendors[$y]['name'] = "aastra";
		$vendors[$y]['functions'][$z]['label'] = "label-blf";
		$vendors[$y]['functions'][$z]['name'] = "blf";
		$vendors[$y]['functions'][$z]['value'] = "blf";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-blf_xfer";
		$vendors[$y]['functions'][$z]['name'] = "blfxfer";
		$vendors[$y]['functions'][$z]['value'] = "blfxfer";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-callers";
		$vendors[$y]['functions'][$z]['name'] = "callers";
		$vendors[$y]['functions'][$z]['value'] = "callers";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-dnd";
		$vendors[$y]['functions'][$z]['name'] = "dnd";
		$vendors[$y]['functions'][$z]['value'] = "dnd";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial";
		$vendors[$y]['functions'][$z]['name'] = "speeddial";
		$vendors[$y]['functions'][$z]['value'] = "speeddial";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-xfer";
		$vendors[$y]['functions'][$z]['name'] = "xfer";
		$vendors[$y]['functions'][$z]['value'] = "xfer";

		$y++; //vendors array index
		$z=0; //functions array index
		$vendors[$y]['name'] = "cisco";
		$vendors[$y]['functions'][$z]['label'] = "label-blf";
		$vendors[$y]['functions'][$z]['name'] = "blf";
		$vendors[$y]['functions'][$z]['value'] = "blf";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-line";
		$vendors[$y]['functions'][$z]['name'] = "line";
		$vendors[$y]['functions'][$z]['value'] = "line";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-disabled";
		$vendors[$y]['functions'][$z]['name'] = "disabled";
		$vendors[$y]['functions'][$z]['value'] = "disabled";

		$y++; //vendors array index
		$z=0; //functions array index
		$vendors[$y]['name'] = "escene";
		$vendors[$y]['functions'][$z]['label'] = "label-blf";
		$vendors[$y]['functions'][$z]['name'] = "blf";
		$vendors[$y]['functions'][$z]['value'] = "1";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-call_park";
		$vendors[$y]['functions'][$z]['name'] = "call_park";
		$vendors[$y]['functions'][$z]['value'] = "7";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-dtmf";
		$vendors[$y]['functions'][$z]['name'] = "dtmf";
		$vendors[$y]['functions'][$z]['value'] = "4";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial";
		$vendors[$y]['functions'][$z]['name'] = "speed_dial";
		$vendors[$y]['functions'][$z]['value'] = "5";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial_prefix";
		$vendors[$y]['functions'][$z]['name'] = "speed_dial_prefix";
		$vendors[$y]['functions'][$z]['value'] = "2";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-intercom";
		$vendors[$y]['functions'][$z]['name'] = "intercom";
		$vendors[$y]['functions'][$z]['value'] = "8";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-pickup";
		$vendors[$y]['functions'][$z]['name'] = "pickup";
		$vendors[$y]['functions'][$z]['value'] = "9";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-broadsoft_group";
		$vendors[$y]['functions'][$z]['name'] = "broadsoft_group";
		$vendors[$y]['functions'][$z]['value'] = "11";
		//BLA type 3 Paging type 6

		$y++; //vendors array index
		$z=0; //functions array index
		$vendors[$y]['name'] = "escene programmable";
		$vendors[$y]['functions'][$z]['label'] = "label-default";
		$vendors[$y]['functions'][$z]['name'] = "default";
		$vendors[$y]['functions'][$z]['value'] = "0";
		$vendors[$y]['functions'][$z]['description'] = "Default";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-redial";
		$vendors[$y]['functions'][$z]['name'] = "redial";
		$vendors[$y]['functions'][$z]['value'] = "1";
		$vendors[$y]['functions'][$z]['description'] = "Redial";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-dnd";
		$vendors[$y]['functions'][$z]['name'] = "dnd";
		$vendors[$y]['functions'][$z]['value'] = "2";
		$vendors[$y]['functions'][$z]['description'] = "DND";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-phone_book";
		$vendors[$y]['functions'][$z]['name'] = "phone_book";
		$vendors[$y]['functions'][$z]['value'] = "3";
		$vendors[$y]['functions'][$z]['description'] = "Contacts";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-ent_phone_book";
		$vendors[$y]['functions'][$z]['name'] = "ent_phone_book";
		$vendors[$y]['functions'][$z]['value'] = "4";
		$vendors[$y]['functions'][$z]['description'] = "Enterprise Phonebook";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-ldap";
		$vendors[$y]['functions'][$z]['name'] = "ldap";
		$vendors[$y]['functions'][$z]['value'] = "5";
		$vendors[$y]['functions'][$z]['description'] = "LDAP";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-directory";
		$vendors[$y]['functions'][$z]['name'] = "directory";
		$vendors[$y]['functions'][$z]['value'] = "6";
		$vendors[$y]['functions'][$z]['description'] = "Directory";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial";
		$vendors[$y]['functions'][$z]['name'] = "speed_dial";
		$vendors[$y]['functions'][$z]['value'] = "7";
		$vendors[$y]['functions'][$z]['description'] = "Speed Dial";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-call_log";
		$vendors[$y]['functions'][$z]['name'] = "call_log";
		$vendors[$y]['functions'][$z]['value'] = "8";
		$vendors[$y]['functions'][$z]['description'] = "Call List";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-missed_calls";
		$vendors[$y]['functions'][$z]['name'] = "missed_calls";
		$vendors[$y]['functions'][$z]['value'] = "9";
		$vendors[$y]['functions'][$z]['description'] = "Missed Calls";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-received_calls";
		$vendors[$y]['functions'][$z]['name'] = "received_calls";
		$vendors[$y]['functions'][$z]['value'] = "10";
		$vendors[$y]['functions'][$z]['description'] = "Received Calls";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-dialed_calls";
		$vendors[$y]['functions'][$z]['name'] = "dialed_calls";
		$vendors[$y]['functions'][$z]['value'] = "11";
		$vendors[$y]['functions'][$z]['description'] = "Dialed Calls";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-menu";
		$vendors[$y]['functions'][$z]['name'] = "menu";
		$vendors[$y]['functions'][$z]['value'] = "12";
		$vendors[$y]['functions'][$z]['description'] = "Menu";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-sms";
		$vendors[$y]['functions'][$z]['name'] = "sms";
		$vendors[$y]['functions'][$z]['value'] = "13";
		$vendors[$y]['functions'][$z]['description'] = "SMS";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-new_sms";
		$vendors[$y]['functions'][$z]['name'] = "new_sms";
		$vendors[$y]['functions'][$z]['value'] = "14";
		$vendors[$y]['functions'][$z]['description'] = "New SMS";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-forward";
		$vendors[$y]['functions'][$z]['name'] = "forward";
		$vendors[$y]['functions'][$z]['value'] = "15";
		$vendors[$y]['functions'][$z]['description'] = "Call Forward";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-status";
		$vendors[$y]['functions'][$z]['name'] = "status";
		$vendors[$y]['functions'][$z]['value'] = "16";
		$vendors[$y]['functions'][$z]['description'] = "View Status";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-enable_account";
		$vendors[$y]['functions'][$z]['name'] = "enable_account";
		$vendors[$y]['functions'][$z]['value'] = "17";
		$vendors[$y]['functions'][$z]['description'] = "Enable/Disable SIP Account";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-xml_browser";
		$vendors[$y]['functions'][$z]['name'] = "xml browser";
		$vendors[$y]['functions'][$z]['value'] = "18";
		$vendors[$y]['functions'][$z]['enabled'] = "false";
		$vendors[$y]['functions'][$z]['description'] = "XML Browser";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-provison_now";
		$vendors[$y]['functions'][$z]['name'] = "provison_now";
		$vendors[$y]['functions'][$z]['value'] = "19";
		$vendors[$y]['functions'][$z]['description'] = "Auto Provison Now";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-hot_desking";
		$vendors[$y]['functions'][$z]['name'] = "hot_desking";
		$vendors[$y]['functions'][$z]['value'] = "20";
		$vendors[$y]['functions'][$z]['description'] = "Hot Desking";
		$z++;

		$y++; //vendors array index
		$z=0; //functions array index
		$vendors[$y]['name'] = "grandstream";
		$vendors[$y]['functions'][$z]['label'] = "label-line";
		$vendors[$y]['functions'][$z]['name'] = "line";
		$vendors[$y]['functions'][$z]['value'] = "line";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-shared_line";
		$vendors[$y]['functions'][$z]['name'] = "shared line";
		$vendors[$y]['functions'][$z]['value'] = "shared line";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial";
		$vendors[$y]['functions'][$z]['name'] = "speed dial";
		$vendors[$y]['functions'][$z]['value'] = "speed dial";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-blf";
		$vendors[$y]['functions'][$z]['name'] = "blf";
		$vendors[$y]['functions'][$z]['value'] = "blf";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-presence_watcher";
		$vendors[$y]['functions'][$z]['name'] = "presence watcher";
		$vendors[$y]['functions'][$z]['value'] = "presence watcher";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-eventlist_blf";
		$vendors[$y]['functions'][$z]['name'] = "eventlist blf";
		$vendors[$y]['functions'][$z]['value'] = "eventlist blf";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial_active";
		$vendors[$y]['functions'][$z]['name'] = "speed dial active";
		$vendors[$y]['functions'][$z]['value'] = "speed dial active";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-dial_dtmf";
		$vendors[$y]['functions'][$z]['name'] = "dial dtmf";
		$vendors[$y]['functions'][$z]['value'] = "dial dtmf";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-voicemail";
		$vendors[$y]['functions'][$z]['name'] = "voicemail";
		$vendors[$y]['functions'][$z]['value'] = "voicemail";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-call_return";
		$vendors[$y]['functions'][$z]['name'] = "call return";
		$vendors[$y]['functions'][$z]['value'] = "call return";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-transfer";
		$vendors[$y]['functions'][$z]['name'] = "transfer";
		$vendors[$y]['functions'][$z]['value'] = "transfer";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-call_park";
		$vendors[$y]['functions'][$z]['name'] = "call park";
		$vendors[$y]['functions'][$z]['value'] = "call park";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-intercom";
		$vendors[$y]['functions'][$z]['name'] = "intercom";
		$vendors[$y]['functions'][$z]['value'] = "intercom";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-ldap_search";
		$vendors[$y]['functions'][$z]['name'] = "ldap search";
		$vendors[$y]['functions'][$z]['value'] = "ldap search";

		$y++; //vendors array index
		$z=0; //functions array index
		$vendors[$y]['name'] = "mitel";
		$vendors[$y]['functions'][$z]['label'] = "label-not_programmed";
		$vendors[$y]['functions'][$z]['name'] = "not_programmed";
		$vendors[$y]['functions'][$z]['value'] = "0";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial";
		$vendors[$y]['functions'][$z]['name'] = "speed_dial";
		$vendors[$y]['functions'][$z]['value'] = "1";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-shared_line";
		$vendors[$y]['functions'][$z]['name'] = "shared_line";
		$vendors[$y]['functions'][$z]['value'] = "5";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-line";
		$vendors[$y]['functions'][$z]['name'] = "line";
		$vendors[$y]['functions'][$z]['value'] = "6";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-call_log";
		$vendors[$y]['functions'][$z]['name'] = "call_log";
		$vendors[$y]['functions'][$z]['value'] = "2";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-phone_book";
		$vendors[$y]['functions'][$z]['name'] = "phone_book";
		$vendors[$y]['functions'][$z]['value'] = "15";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-forward";
		$vendors[$y]['functions'][$z]['name'] = "forward";
		$vendors[$y]['functions'][$z]['value'] = "16";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-dnd";
		$vendors[$y]['functions'][$z]['name'] = "dnd";
		$vendors[$y]['functions'][$z]['value'] = "17";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-advisory_message";
		$vendors[$y]['functions'][$z]['name'] = "advisory_message";
		$vendors[$y]['functions'][$z]['value'] = "3";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-pc_application";
		$vendors[$y]['functions'][$z]['name'] = "pc_application";
		$vendors[$y]['functions'][$z]['value'] = "18";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-headset_on_off";
		$vendors[$y]['functions'][$z]['name'] = "headset_on_off";
		$vendors[$y]['functions'][$z]['value'] = "4";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-rss_feed";
		$vendors[$y]['functions'][$z]['name'] = "rss_feed";
		$vendors[$y]['functions'][$z]['value'] = "19";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-speed_dial_blf";
		$vendors[$y]['functions'][$z]['name'] = "speed_dial_blf";
		$vendors[$y]['functions'][$z]['value'] = "27";
		$z++;
		$vendors[$y]['functions'][$z]['label'] = "label-url";
		$vendors[$y]['functions'][$z]['name'] = "url";
		$vendors[$y]['functions'][$z]['value'] = "19";
		/*
		0 - not programmed
		1 - speed dial
		2 - callLog
		3 - advisoryMsg (on/off)
		4 - headset(on/off)
		5 - shared line
		6 - Line 1
		7 - Line 2
		8 - Line 3
		9 - Line 4
		10 - Line 5
		11 - Line 6
		12 - Line 7
		13 - Line 8
		15 - phonebook
		16 - call forwarding
		17 - do not disturb
		18 - PC Application
		19 - RSS Feed URL / Branding /Notes
		21 - Superkey (5304 set only)
		22 - Redial key (5304 set only)
		23 - Hold key (5304 set only)
		24 - Trans/Conf key (5304 set only)
		25 - Message key (5304 set only)
		26 - Cancel key (5304 set only)
		27 - Speed Dial & BLF

		Mitel web interface shows html_application
		*/

		/*
		echo "<pre>\n";
		foreach ($vendors as $vendor) {
			//print_r($vendor);
			$vendor = $vendor['name'];
			echo "<h1>$vendor</h1>\n";
			$functions = $vendor['functions'];
			foreach ($vendor['functions'] as $type) {
				echo "<hr>\n";
				echo "label: ".$type['label']."\n";
				echo "name: ".$type['name']."\n";
				echo "value: ".$type['value']."\n";
			}
		}
		echo "</pre>\n";
		*/

		/*
		//select
				$device_vendor = 'yealink';
				$device_key_type = '16'; 
				echo "<select class='formfld' name='device_keys[".$x."][device_key_type]' id='key_type_".$x."'>\n";
				echo "	<option value=''></option>\n";
				$previous_vendor = '';
				foreach ($vendors as $vendor) {
					$i = 0;
					foreach ($vendor['functions'] as $type) {
						if ($vendor['name'] != $previous_vendor) {
							if ($i > 0) { echo "	</optgroup>\n"; }
							echo "	<optgroup label='".ucwords($vendor['name'])."'>\n";
						}
						$selected = "";
						if ($device_vendor == $vendor['name'] && $device_key_type == $type['value']) {
							$selected = "selected='selected'";
						}
						echo "		<option value='".$type['value']."' $selected >".$text[$type['label']]."</option>\n";
						$previous_vendor = $vendor['name'];
						$i++;
					}
					echo "	</optgroup>\n";
				}
				echo "</select>\n";
		*/

?>