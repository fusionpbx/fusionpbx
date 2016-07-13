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

	public static function provision_key_type($firmware, $key_category, $key_type){
		if ($key_category == "line") {
			switch ($key_type) {
				case "line":              $key_type  = "0";  break;
				case "shared line":       $key_type  = "1";  break;
				case "speed dial":        $key_type  = "10"; break;
				case "blf":               $key_type  = "11"; break;
				case "presence watcher":  $key_type  = "12"; break;
				case "eventlist blf":     $key_type  = "13"; break;
				case "speed dial active": $key_type  = "14"; break;
				case "dial dtmf":         $key_type  = "15"; break;
				case "voicemail":         $key_type  = "16"; break;
				case "call return":       $key_type  = "17"; break;
				case "transfer":          $key_type  = "18"; break;
				case "call park":         $key_type  = "19"; break;
				case "intercom":          $key_type  = "20"; break;
				case "ldap search":       $key_type  = "21"; break;
				default: $key_type = $key_type; /*warning unknown/unsupported key type*/
			}
		}
		if ($key_category == "memory" || $key_category == "expansion") {
			switch ($key_type) {
				case "speed dial":        $key_type  = "0";  break;
				case "blf":               $key_type  = "1";  break;
				case "presence watcher":  $key_type  = "2";  break;
				case "eventlist blf":     $key_type  = "3";  break;
				case "speed dial active": $key_type  = "4";  break;
				case "dial dtmf":         $key_type  = "5";  break;
				case "voicemail":         $key_type  = "6";  break;
				case "call return":       $key_type  = "7";  break;
				case "transfer":          $key_type  = "8";  break;
				case "call park":         $key_type  = "9";  break;
				case "intercom":          $key_type  = "10"; break;
				case "ldap search":       $key_type  = "11"; break;
				default: $key_type = $key_type; /*warning unknown/unsupported key type*/
			}
		}
		return $key_type;
	}
};
