<?php

class grandstream {
	public static $name  = 'grandstream';
	public static $title = 'Grandstream';
	public static $memory_key_functions = Array(
		Array('line',               'label-line'),
		Array('shared line',        'label-shared_line'      ),
		Array('speed dial',         'label-speed_dial'       ),
		Array('blf',                'label-blf'              ),
		Array('presence watcher',   'label-presence_watcher' ),
		Array('eventlist blf',      'label-eventlist_blf'    ),
		Array('speed dial active',  'label-speed_dial_active'),
		Array('dial dtmf',          'label-dial_dtmf'        ),
		Array('voicemail',          'label-voicemail'        ),
		Array('call return',        'label-call_return'      ),
		Array('transfer',           'label-transfer'         ),
		Array('call park',          'label-call_park'        ),
		Array('intercom',           'label-intercom'         ),
		Array('ldap search',        'label-ldap_search'      ),
	);
};
