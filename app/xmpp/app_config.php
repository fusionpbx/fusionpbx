<?php
	//application details
		$apps[$x]['name'] = "XMPP Manager";
		$apps[$x]['uuid'] = '740f1c0d-6d82-fcde-3873-0fc9779789ec';
		$apps[$x]['category'] = 'Switch';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en'] = 'Allow User to Open a Flash Phone for his Extension.';

	//menu details
		$apps[$x]['menu'][0]['title']['en'] = 'XMPP Manager';
		$apps[$x]['menu'][0]['uuid'] = '1808365b-0f7c-7555-89d0-31b3d9a75abb';
		$apps[$x]['menu'][0]['parent_uuid'] = 'bc96d773-ee57-0cdd-c3ac-2d91aba61b55';
		$apps[$x]['menu'][0]['category'] = 'internal';
		$apps[$x]['menu'][0]['path'] = '/app/xmpp/v_xmpp.php';
		$apps[$x]['menu'][0]['groups'][] = 'superadmin';

	//permission details
		$apps[$x]['permissions'][0]['name'] = 'xmpp_view';
		$apps[$x]['permissions'][0]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][1]['name'] = 'xmpp_add';
		$apps[$x]['permissions'][1]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][2]['name'] = 'xmpp_edit';
		$apps[$x]['permissions'][2]['groups'][] = 'superadmin';

		$apps[$x]['permissions'][3]['name'] = 'xmpp_delete';
		$apps[$x]['permissions'][3]['groups'][] = 'superadmin';

	//schema details
		$y = 0; //table array index
		$z = 0; //field array index
		$apps[$x]['db'][$y]['table'] = 'v_xmpp';
		$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'id';
		$apps[$x]['db'][$y]['fields'][$z]['name']['deprecated'] = 'xmpp_profile_id';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'serial';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'integer';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'INT NOT NULL AUTO_INCREMENT';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = 'true';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'xmpp_profile_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_domains';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'v_id';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$apps[$x]['db'][$y]['fields'][$z]['deprecated'] = 'true';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'profile_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'username';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'password';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'dialplan';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'context';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'rtp_ip';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'ext_rtp_ip';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'auto_login';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'sasl_type';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'xmpp_server';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'tls_enable';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'usr_rtp_timer';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'default_exten';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'vad';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'in/out/both';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'avatar';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = 'example: /path/to/tiny.jpg';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'candidate_acl';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'local_network_acl';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text'; 
		$apps[$x]['db'][$y]['fields'][$z]['description']['en'] = '';

?>