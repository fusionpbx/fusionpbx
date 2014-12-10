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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

$vars = <<<EOD
[
{"var_name":"domain","var_value":"\$\${local_ip_v4}","var_cat":"Domain","var_enabled":"true","var_description":"U2V0cyB0aGUgZGVmYXVsdCBkb21haW4u"},
{"var_name":"domain_name","var_value":"\$\${domain}","var_cat":"Domain","var_enabled":"true","var_description":""},
{"var_name":"sound_prefix","var_value":"\$\${sounds_dir}/en/us/callie","var_cat":"Sound","var_enabled":"true","var_description":"U2V0cyB0aGUgc291bmQgZGlyZWN0b3J5Lg=="},
{"var_name":"hold_music","var_value":"local_stream://default","var_cat":"Music on Hold","var_enabled":"true","var_description":""},
{"var_name":"global_codec_prefs","var_value":"G7221@32000h,G7221@16000h,G722,PCMU,PCMA,GSM","var_cat":"Codecs","var_enabled":"true","var_description":"RzcyMjFAMzIwMDBoLEc3MjIxQDE2MDAwaCxHNzIyLFBDTVUsUENNQSxpTEJDLEdTTSxIMjYzLEgyNjQ="},
{"var_name":"outbound_codec_prefs","var_value":"PCMU,PCMA,GSM","var_cat":"Codecs","var_enabled":"true","var_description":"ZGVmYXVsdDogUENNVSxQQ01BLEdTTQ=="},
{"var_name":"xmpp_client_profile","var_value":"xmppc","var_cat":"Dingaling","var_enabled":"true","var_description":"eG1wcF9jbGllbnRfcHJvZmlsZSBhbmQgeG1wcF9zZXJ2ZXJfcHJvZmlsZSB4bXBwX2NsaWVudF9wcm9maWxlIGNhbiBiZSBhbnkgc3RyaW5nLiB4bXBwX3NlcnZlcl9wcm9maWxlIGlzIGFwcGVuZGVkIHRvICJkaW5nYWxpbmdfIiB0byBmb3JtIHRoZSBkYXRhYmFzZSBuYW1lIGNvbnRhaW5pbmcgdGhlICJzdWJzY3JpcHRpb25zIiB0YWJsZS4gdXNlZCBieTogZGluZ2FsaW5nLmNvbmYueG1sIGVudW0uY29uZi54bWw="},
{"var_name":"xmpp_server_profile","var_value":"xmpps","var_cat":"Dingaling","var_enabled":"true","var_description":""},
{"var_name":"bind_server_ip","var_value":"auto","var_cat":"Dingaling","var_enabled":"true","var_description":"Q2FuIGJlIGFuIGlwIGFkZHJlc3MsIGEgZG5zIG5hbWUsIG9yICJhdXRvIi4gVGhpcyBkZXRlcm1pbmVzIGFuIGlwIGFkZHJlc3MgYXZhaWxhYmxlIG9uIHRoaXMgaG9zdCB0byBiaW5kLiBJZiB5b3UgYXJlIHNlcGFyYXRpbmcgUlRQIGFuZCBTSVAgdHJhZmZpYywgeW91IHdpbGwgd2FudCB0byBoYXZlIHVzZSBkaWZmZXJlbnQgYWRkcmVzc2VzIHdoZXJlIHRoaXMgdmFyaWFibGUgYXBwZWFycy4gVXNlZCBieTogZGluZ2FsaW5nLmNvbmYueG1s"},
{"var_name":"external_rtp_ip","var_value":"\$\${local_ip_v4}","var_cat":"IP Address","var_enabled":"true","var_description":"KElmIHlvdScncmUgZ29pbmcgdG8gbG9hZCB0ZXN0IHRoZW4gcGxlYXNlIGlucHV0IHJlYWwgSVAgYWRkcmVzc2VzIGZvciBleHRlcm5hbF9ydHBfaXAgYW5kIGV4dGVybmFsX3NpcF9pcCkNCg0KQ2FuIGJlIGFuIG9uZSBvZjoNCiAgIGlwIGFkZHJlc3M6ICIxMi4zNC41Ni43OCINCiAgIGEgc3R1biBzZXJ2ZXIgbG9va3VwOiAic3R1bjpzdHVuLnNlcnZlci5jb20iDQogICBhIEROUyBuYW1lOiAiaG9zdDpob3N0LnNlcnZlci5jb20iDQoNCndoZXJlIGZzLm15ZG9tYWluLmNvbSBpcyBhIEROUyBBIHJlY29yZC11c2VmdWwgd2hlbiBmcyBpcyBvbiBhIGR5bmFtaWMgSVAgYWRkcmVzcywgYW5kIHVzZXMgYSBkeW5hbWljIEROUyB1cGRhdGVyLiBJZiB1bnNwZWNpZmllZCwgdGhlIGJpbmRfc2VydmVyX2lwIHZhbHVlIGlzIHVzZWQuIFVzZWQgYnk6IHNvZmlhLmNvbmYueG1sIGRpbmdhbGluZy5jb25mLnhtbA=="},
{"var_name":"external_sip_ip","var_value":"\$\${local_ip_v4}","var_cat":"IP Address","var_enabled":"true","var_description":"VXNlZCBhcyB0aGUgcHVibGljIElQIGFkZHJlc3MgZm9yIFNEUC4NCg0KQ2FuIGJlIGFuIG9uZSBvZjoNCiAgIGlwIGFkZHJlc3M6ICIxMi4zNC41Ni43OCINCiAgIGEgc3R1biBzZXJ2ZXIgbG9va3VwOiAic3R1bjpzdHVuLnNlcnZlci5jb20iDQogICBhIEROUyBuYW1lOiAiaG9zdDpob3N0LnNlcnZlci5jb20iDQoNCndoZXJlIGZzLm15ZG9tYWluLmNvbSBpcyBhIEROUyBBIHJlY29yZC11c2VmdWwgd2hlbiBmcyBpcyBvbiBhIGR5bmFtaWMgSVAgYWRkcmVzcywgYW5kIHVzZXMgYSBkeW5hbWljIEROUyB1cGRhdGVyLiBJZiB1bnNwZWNpZmllZCwgdGhlIGJpbmRfc2VydmVyX2lwIHZhbHVlIGlzIHVzZWQuIFVzZWQgYnk6IHNvZmlhLmNvbmYueG1sIGRpbmdhbGluZy5jb25mLnhtbA=="},
{"var_name":"hangup_on_subscriber_absent","var_value":"true","var_cat":"SIP","var_enabled":"false","var_description":"SGFuZ3VwIG9uIFNVQlNDUklCRVJfQUJTRU5U"},
{"var_name":"hangup_on_call_reject","var_value":"true","var_cat":"SIP","var_enabled":"false","var_description":"SGFuZ3VwIG9uIENBTExfUkVKRUNU"},
{"var_name":"unroll_loops","var_value":"true","var_cat":"SIP","var_enabled":"true","var_description":"VXNlZCB0byB0dXJuIG9uIHNpcCBsb29wYmFjayB1bnJvbGxpbmcu"},
{"var_name":"call_debug","var_value":"false","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"console_loglevel","var_value":"info","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"default_areacode","var_value":"208","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"uk-ring","var_value":"%(400,200,400,450);%(400,2200,400,450)","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"us-ring","var_value":"%(2000, 4000, 440.0, 480.0)","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"pt-ring","var_value":"%(1000, 5000, 400.0, 0.0)","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"fr-ring","var_value":"%(1500, 3500, 440.0, 0.0)","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"rs-ring","var_value":"%(1000, 4000, 425.0, 0.0)","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"it-ring","var_value":"%(1000, 4000, 425.0, 0.0)","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"bong-ring","var_value":"v=-7;%(100,0,941.0,1477.0);v=-7;>=2;+=.1;%(1400,0,350,440)","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"sit","var_value":"%(274,0,913.8);%(274,0,1370.6);%(380,0,1776.7)","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"sip_tls_version","var_value":"tlsv1","var_cat":"SIP","var_enabled":"true","var_description":"U0lQIGFuZCBUTFMgc2V0dGluZ3Mu"},
{"var_name":"internal_auth_calls","var_value":"true","var_cat":"SIP Profile: Internal","var_enabled":"true","var_description":""},
{"var_name":"internal_sip_port","var_value":"5060","var_cat":"SIP Profile: Internal","var_enabled":"true","var_description":""},
{"var_name":"internal_tls_port","var_value":"5061","var_cat":"SIP Profile: Internal","var_enabled":"true","var_description":""},
{"var_name":"internal_ssl_enable","var_value":"false","var_cat":"SIP Profile: Internal","var_enabled":"true","var_description":""},
{"var_name":"internal_ssl_dir","var_value":"\$\${base_dir}/conf/ssl","var_cat":"SIP Profile: Internal","var_enabled":"true","var_description":""},
{"var_name":"external_auth_calls","var_value":"false","var_cat":"SIP Profile: External","var_enabled":"true","var_description":""},
{"var_name":"external_sip_port","var_value":"5080","var_cat":"SIP Profile: External","var_enabled":"true","var_description":""},
{"var_name":"external_tls_port","var_value":"5081","var_cat":"SIP Profile: External","var_enabled":"true","var_description":""},
{"var_name":"external_ssl_enable","var_value":"false","var_cat":"SIP Profile: External","var_enabled":"true","var_description":""},
{"var_name":"external_ssl_dir","var_value":"\$\${base_dir}/conf/ssl","var_cat":"SIP Profile: External","var_enabled":"true","var_description":""},
{"var_name":"use_profile","var_value":"internal","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"default_language","var_value":"en","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"default_dialect","var_value":"us","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"default_voice","var_value":"callie","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"ajax_refresh_rate","var_value":"3000","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"xml_cdr_archive","var_value":"dir","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"ringback","var_value":"\$\${us-ring}","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"transfer_ringback","var_value":"\$\${us-ring}","var_cat":"Defaults","var_enabled":"true","var_description":""},
{"var_name":"record_ext","var_value":"wav","var_cat":"Defaults","var_enabled":"true","var_description":""}
]
EOD;

// Set country depend variables as country code and international direct dialing code (exit code)
	if (!function_exists('set_country_vars')) {
		function set_country_vars($db, $x) {

			$country_list = <<<EOD
[
{"country":"Afghanistan","countrycode":"93","exitcode":"00","isocode":"AF"} 		
,{"country":"Albania","countrycode":"355","exitcode":"00","isocode":"AL"}
,{"country":"Algeria","countrycode":"213","exitcode":"00","isocode":"DZ"}
,{"country":"American Samoa","countrycode":"1","exitcode":"011","isocode":"AS"}
,{"country":"Andorra","countrycode":"376","exitcode":"00","isocode":"AD"}
,{"country":"Angola","countrycode":"244","exitcode":"00","isocode":"AO"}
,{"country":"Anguilla","countrycode":"1","exitcode":"011","isocode":"AI"}
,{"country":"Antigua and Barbuda","countrycode":"1","exitcode":"011","isocode":"AG"}
,{"country":"Argentina","countrycode":"54","exitcode":"00","isocode":"AR"}
,{"country":"Armenia","countrycode":"374","exitcode":"00","isocode":"AM"}
,{"country":"Aruba","countrycode":"297","exitcode":"00","isocode":"AW"}
,{"country":"Ascension","countrycode":"247","exitcode":"00","isocode":"AC"}
,{"country":"Australia","countrycode":"61","exitcode":"0011","isocode":"AU"}
,{"country":"Austria","countrycode":"43","exitcode":"00","isocode":"AT"}
,{"country":"Azerbaijan","countrycode":"994","exitcode":"00","isocode":"AZ"}
,{"country":"Bahamas","countrycode":"1","exitcode":"011","isocode":"BS"}
,{"country":"Bahrain","countrycode":"973","exitcode":"00","isocode":"BH"}
,{"country":"Bangladesh","countrycode":"880","exitcode":"00","isocode":"BD"}
,{"country":"Barbados","countrycode":"1","exitcode":"011","isocode":"BB"}
,{"country":"Belarus","countrycode":"375","exitcode":"810","isocode":"BY"}
,{"country":"Belgium","countrycode":"32","exitcode":"00","isocode":"BE"}
,{"country":"Belize","countrycode":"501","exitcode":"00","isocode":"BZ"}
,{"country":"Benin","countrycode":"229","exitcode":"00","isocode":"BJ"}
,{"country":"Bermuda","countrycode":"1","exitcode":"011","isocode":"BM"}
,{"country":"Bhutan","countrycode":"975","exitcode":"00","isocode":"BT"}
,{"country":"Bolivia","countrycode":"591","exitcode":"00","isocode":"BO"}
,{"country":"Bosnia and Herzegovina","countrycode":"387","exitcode":"00","isocode":"BA"}
,{"country":"Botswana","countrycode":"267","exitcode":"00","isocode":"BW"}
,{"country":"Brazil","countrycode":"55","exitcode":"0014","isocode":"BR"}
,{"country":"British Virgin Islands","countrycode":"1","exitcode":"011","isocode":"VG"}
,{"country":"Brunei","countrycode":"673","exitcode":"00","isocode":"BN"}
,{"country":"Bulgaria","countrycode":"359","exitcode":"00","isocode":"BG"}
,{"country":"Burkina Faso","countrycode":"226","exitcode":"00","isocode":"BF"}
,{"country":"Burundi","countrycode":"257","exitcode":"00","isocode":"BI"}
,{"country":"Cambodia","countrycode":"855","exitcode":"001, 007, 008","isocode":"KH"}
,{"country":"Cameroon","countrycode":"237","exitcode":"00","isocode":"CM"}
,{"country":"Canada","countrycode":"1","exitcode":"011","isocode":"CA"}
,{"country":"Cape Verde","countrycode":"238","exitcode":"00","isocode":"CV"}
,{"country":"Cayman Islands","countrycode":"1","exitcode":"011","isocode":"KY"}
,{"country":"Central African Republic","countrycode":"236","exitcode":"00","isocode":"CF"}
,{"country":"Chad","countrycode":"235","exitcode":"00","isocode":"TD"}
,{"country":"Chile","countrycode":"56","exitcode":"1230","isocode":"CL"}
,{"country":"China","countrycode":"86","exitcode":"00","isocode":"CN"}
,{"country":"Colombia","countrycode":"57","exitcode":"005","isocode":"CO"}
,{"country":"Comoros","countrycode":"269","exitcode":"00","isocode":"KM"}
,{"country":"Congo","countrycode":"242","exitcode":"00","isocode":"CD"}
,{"country":"Cook Islands","countrycode":"682","exitcode":"00","isocode":"CK"}
,{"country":"Costa Rica","countrycode":"506","exitcode":"00","isocode":"CR"}
,{"country":"Croatia","countrycode":"385","exitcode":"00","isocode":"HR"}
,{"country":"Cuba","countrycode":"53","exitcode":"119","isocode":"CU"}
,{"country":"Cyprus","countrycode":"357","exitcode":"00","isocode":"CY"}
,{"country":"Czech Republic","countrycode":"420","exitcode":"00","isocode":"CZ"}
,{"country":"Democratic Republic of Congo","countrycode":"243","exitcode":"00","isocode":"CD"}
,{"country":"Denmark","countrycode":"45","exitcode":"00","isocode":"DK"}
,{"country":"Djibouti","countrycode":"253","exitcode":"00","isocode":"DJ"}
,{"country":"Dominica","countrycode":"1","exitcode":"011","isocode":"DM"}
,{"country":"Dominican Republic","countrycode":"1","exitcode":"011","isocode":"DO"}
,{"country":"East Timor","countrycode":"670","exitcode":"00","isocode":"TL"}
,{"country":"Ecuador","countrycode":"593","exitcode":"00","isocode":"EC"}
,{"country":"Egypt","countrycode":"20","exitcode":"00","isocode":"EG"}
,{"country":"El Salvador","countrycode":"503","exitcode":"00","isocode":"SV"}
,{"country":"Equatorial Guinea","countrycode":"240","exitcode":"00","isocode":"GQ"}
,{"country":"Eritrea","countrycode":"291","exitcode":"00","isocode":"ER"}
,{"country":"Estonia","countrycode":"372","exitcode":"00","isocode":"EE"}
,{"country":"Ethiopia","countrycode":"251","exitcode":"00","isocode":"ET"}
,{"country":"Falkland (Malvinas) Islands","countrycode":"500","exitcode":"00","isocode":"FK"}
,{"country":"Faroe Islands","countrycode":"298","exitcode":"00","isocode":"FO"}
,{"country":"Fiji","countrycode":"679","exitcode":"00","isocode":"FJ"}
,{"country":"Finland","countrycode":"358","exitcode":"00, 990, 994, 999","isocode":"FI"}
,{"country":"France","countrycode":"33","exitcode":"00","isocode":"FR"}
,{"country":"French Guiana","countrycode":"594","exitcode":"00","isocode":"GF"}
,{"country":"French Polynesia","countrycode":"689","exitcode":"00","isocode":"PF"}
,{"country":"Gabon","countrycode":"241","exitcode":"00","isocode":"GA"}
,{"country":"Gambia","countrycode":"220","exitcode":"00","isocode":"GM"}
,{"country":"Georgia","countrycode":"995","exitcode":"00","isocode":"GE"}
,{"country":"Germany","countrycode":"49","exitcode":"00","isocode":"DE"}
,{"country":"Ghana","countrycode":"233","exitcode":"00","isocode":"GH"}
,{"country":"Gibraltar","countrycode":"350","exitcode":"00","isocode":"GI"}
,{"country":"Greece","countrycode":"30","exitcode":"00","isocode":"GR"}
,{"country":"Greenland","countrycode":"299","exitcode":"00","isocode":"GL"}
,{"country":"Grenada","countrycode":"1","exitcode":"011","isocode":"GD"}
,{"country":"Guadeloupe","countrycode":"590","exitcode":"00","isocode":"GP"}
,{"country":"Guam","countrycode":"1","exitcode":"011","isocode":"GU"}
,{"country":"Guatemala","countrycode":"502","exitcode":"00","isocode":"GT"}
,{"country":"Guinea","countrycode":"224","exitcode":"00","isocode":"GN"}
,{"country":"Guinea-Bissau","countrycode":"245","exitcode":"00","isocode":"GW"}
,{"country":"Guyana","countrycode":"592","exitcode":"001","isocode":"GY"}
,{"country":"Haiti","countrycode":"509","exitcode":"00","isocode":"HT"}
,{"country":"Honduras","countrycode":"504","exitcode":"00","isocode":"HN"}
,{"country":"Hong Kong","countrycode":"852","exitcode":"001","isocode":"HK"}
,{"country":"Hungary","countrycode":"36","exitcode":"00","isocode":"HU"}
,{"country":"Iceland","countrycode":"354","exitcode":"00","isocode":"IS"}
,{"country":"India","countrycode":"91","exitcode":"00","isocode":"IN"}
,{"country":"Indonesia","countrycode":"62","exitcode":"001","isocode":"ID"}
,{"country":"Iran","countrycode":"98","exitcode":"00","isocode":"IR"}
,{"country":"Iraq","countrycode":"964","exitcode":"00","isocode":"IQ"}
,{"country":"Ireland","countrycode":"353","exitcode":"00","isocode":"IE"}
,{"country":"Israel","countrycode":"972","exitcode":"00, 012, 013, 014, 018","isocode":"IL"}
,{"country":"Italy","countrycode":"39","exitcode":"00","isocode":"IT"}
,{"country":"Ivory Coast","countrycode":"225","exitcode":"00","isocode":"CI"}
,{"country":"Jamaica","countrycode":"1","exitcode":"011","isocode":"JM"}
,{"country":"Japan","countrycode":"81","exitcode":"010","isocode":"JP"}
,{"country":"Jordan","countrycode":"962","exitcode":"00","isocode":"JO"}
,{"country":"Kazakhstan","countrycode":"7","exitcode":"810","isocode":"KZ"}
,{"country":"Kenya","countrycode":"254","exitcode":"000","isocode":"KE"}
,{"country":"Kiribati","countrycode":"686","exitcode":"00","isocode":"KI"}
,{"country":"Kuwait","countrycode":"965","exitcode":"00","isocode":"KW"}
,{"country":"Kyrgyzstan","countrycode":"996","exitcode":"00","isocode":"KG"}
,{"country":"Laos","countrycode":"856","exitcode":"00","isocode":"LA"}
,{"country":"Latvia","countrycode":"371","exitcode":"00","isocode":"LV"}
,{"country":"Lebanon","countrycode":"961","exitcode":"00","isocode":"LB"}
,{"country":"Lesotho","countrycode":"266","exitcode":"00","isocode":"LS"}
,{"country":"Liberia","countrycode":"231","exitcode":"00","isocode":"LR"}
,{"country":"Libya","countrycode":"218","exitcode":"00","isocode":"LY"}
,{"country":"Liechtenstein","countrycode":"423","exitcode":"00","isocode":"LI"}
,{"country":"Lithuania","countrycode":"370","exitcode":"00","isocode":"LT"}
,{"country":"Luxembourg","countrycode":"352","exitcode":"00","isocode":"LU"}
,{"country":"Macau","countrycode":"853","exitcode":"00","isocode":"MO"}
,{"country":"Macedonia","countrycode":"389","exitcode":"00","isocode":"MK"}
,{"country":"Madagascar","countrycode":"261","exitcode":"00","isocode":"MG"}
,{"country":"Malawi","countrycode":"265","exitcode":"00","isocode":"MW"}
,{"country":"Malaysia","countrycode":"60","exitcode":"00","isocode":"MY"}
,{"country":"Maldives","countrycode":"960","exitcode":"00","isocode":"MV"}
,{"country":"Mali","countrycode":"223","exitcode":"00","isocode":"ML"}
,{"country":"Malta","countrycode":"356","exitcode":"00","isocode":"MT"}
,{"country":"Marshall Islands","countrycode":"692","exitcode":"011","isocode":"MH"}
,{"country":"Martinique","countrycode":"596","exitcode":"00","isocode":"MQ"}
,{"country":"Mauritania","countrycode":"222","exitcode":"00","isocode":"MR"}
,{"country":"Mauritius","countrycode":"230","exitcode":"00","isocode":"MU"}
,{"country":"Mayotte","countrycode":"262","exitcode":"00","isocode":"YT"}
,{"country":"Mexico","countrycode":"52","exitcode":"00","isocode":"MX"}
,{"country":"Micronesia","countrycode":"691","exitcode":"011","isocode":"FM"}
,{"country":"Moldova","countrycode":"373","exitcode":"00","isocode":"MD"}
,{"country":"Monaco","countrycode":"377","exitcode":"00","isocode":"MC"}
,{"country":"Mongolia","countrycode":"976","exitcode":"001","isocode":"MN"}
,{"country":"Montenegro","countrycode":"382","exitcode":"00","isocode":"ME"}
,{"country":"Montserrat","countrycode":"1","exitcode":"011","isocode":"MS"}
,{"country":"Morocco","countrycode":"212","exitcode":"00","isocode":"MA"}
,{"country":"Mozambique","countrycode":"258","exitcode":"00","isocode":"MZ"}
,{"country":"Myanmar","countrycode":"95","exitcode":"00","isocode":"MM"}
,{"country":"Namibia","countrycode":"264","exitcode":"00","isocode":"NA"}
,{"country":"Nauru","countrycode":"674","exitcode":"00","isocode":"NR"}
,{"country":"Nepal","countrycode":"977","exitcode":"00","isocode":"NP"}
,{"country":"Netherlands","countrycode":"31","exitcode":"00","isocode":"NL"}
,{"country":"Netherlands Antilles","countrycode":"599","exitcode":"00","isocode":"AN"}
,{"country":"New Caledonia","countrycode":"687","exitcode":"00","isocode":"NC"}
,{"country":"New Zealand","countrycode":"64","exitcode":"00","isocode":"NZ"}
,{"country":"Nicaragua","countrycode":"505","exitcode":"00","isocode":"NI"}
,{"country":"Niger","countrycode":"227","exitcode":"00","isocode":"NE"}
,{"country":"Nigeria","countrycode":"234","exitcode":"009","isocode":"NG"}
,{"country":"Niue","countrycode":"683","exitcode":"00","isocode":"NU"}
,{"country":"Norfolk Island","countrycode":"6723","exitcode":"00","isocode":"NF"}
,{"country":"North Korea","countrycode":"850","exitcode":"99","isocode":"KP"}
,{"country":"Norway","countrycode":"47","exitcode":"00","isocode":"NO"}
,{"country":"Oman","countrycode":"968","exitcode":"00","isocode":"OM"}
,{"country":"Pakistan","countrycode":"92","exitcode":"00","isocode":"PK"}
,{"country":"Palau","countrycode":"680","exitcode":"011","isocode":"PW"}
,{"country":"Panama","countrycode":"507","exitcode":"00","isocode":"PA"}
,{"country":"Papua New Guinea","countrycode":"675","exitcode":"00","isocode":"PG"}
,{"country":"Paraguay","countrycode":"595","exitcode":"00","isocode":"PY"}
,{"country":"Peru","countrycode":"51","exitcode":"00","isocode":"PE"}
,{"country":"Philippines","countrycode":"63","exitcode":"00","isocode":"PH"}
,{"country":"Poland","countrycode":"48","exitcode":"00","isocode":"PL"}
,{"country":"Portugal","countrycode":"351","exitcode":"00","isocode":"PT"}
,{"country":"Puerto Rico","countrycode":"1","exitcode":"011","isocode":"PR"}
,{"country":"Qatar","countrycode":"974","exitcode":"00","isocode":"QA"}
,{"country":"Reunion","countrycode":"262","exitcode":"00","isocode":"RE"}
,{"country":"Romania","countrycode":"40","exitcode":"00","isocode":"RO"}
,{"country":"Russian Federation","countrycode":"7","exitcode":"810","isocode":"RU"}
,{"country":"Rwanda","countrycode":"250","exitcode":"00","isocode":"RW"}
,{"country":"Saint Helena","countrycode":"290","exitcode":"00","isocode":"SH"}
,{"country":"Saint Kitts and Nevis","countrycode":"1","exitcode":"011","isocode":"KN"}
,{"country":"Saint Lucia","countrycode":"1","exitcode":"011","isocode":"LC"}
,{"country":"Saint Barthelemy","countrycode":"590","exitcode":"00","isocode":"GP"}
,{"country":"Saint Pierre and Miquelon","countrycode":"508","exitcode":"00","isocode":"PM"}
,{"country":"Saint Vincent and the Grenadines","countrycode":"1","exitcode":"011","isocode":"VC"}
,{"country":"Samoa","countrycode":"685","exitcode":"0","isocode":"WS"}
,{"country":"San Marino","countrycode":"378","exitcode":"00","isocode":"SM"}
,{"country":"Sao Tome and Principe","countrycode":"239","exitcode":"00","isocode":"ST"}
,{"country":"Saudi Arabia","countrycode":"966","exitcode":"00","isocode":"SA"}
,{"country":"Senegal","countrycode":"221","exitcode":"00","isocode":"SN"}
,{"country":"Serbia","countrycode":"381","exitcode":"00","isocode":"RS"}
,{"country":"Seychelles","countrycode":"248","exitcode":"00","isocode":"SC"}
,{"country":"Sierra Leone","countrycode":"232","exitcode":"00","isocode":"SL"}
,{"country":"Singapore","countrycode":"65","exitcode":"001, 008","isocode":"SG"}
,{"country":"Slovakia","countrycode":"421","exitcode":"00","isocode":"SK"}
,{"country":"Slovenia","countrycode":"386","exitcode":"00","isocode":"SI"}
,{"country":"Solomon Islands","countrycode":"677","exitcode":"00","isocode":"SB"}
,{"country":"Somalia","countrycode":"252","exitcode":"00","isocode":"SO"}
,{"country":"South Africa","countrycode":"27","exitcode":"00","isocode":"ZA"}
,{"country":"South Korea","countrycode":"82","exitcode":"001, 002","isocode":"KR"}
,{"country":"Spain","countrycode":"34","exitcode":"00","isocode":"ES"}
,{"country":"Sri Lanka","countrycode":"94","exitcode":"00","isocode":"LK"}
,{"country":"Sudan","countrycode":"249","exitcode":"00","isocode":"SD"}
,{"country":"Suriname","countrycode":"597","exitcode":"00","isocode":"SR"}
,{"country":"Swaziland","countrycode":"268","exitcode":"00","isocode":"SZ"}
,{"country":"Sweden","countrycode":"46","exitcode":"00","isocode":"SE"}
,{"country":"Switzerland","countrycode":"41","exitcode":"00","isocode":"CH"}
,{"country":"Syria","countrycode":"963","exitcode":"00","isocode":"SY"}
,{"country":"Taiwan","countrycode":"886","exitcode":"002","isocode":"TW"}
,{"country":"Tajikistan","countrycode":"992","exitcode":"810","isocode":"TJ"}
,{"country":"Tanzania","countrycode":"255","exitcode":"000","isocode":"TZ"}
,{"country":"Thailand","countrycode":"66","exitcode":"001","isocode":"TH"}
,{"country":"Togo","countrycode":"228","exitcode":"00","isocode":"TG"}
,{"country":"Tokelau","countrycode":"690","exitcode":"00","isocode":"TK"}
,{"country":"Tonga","countrycode":"676","exitcode":"00","isocode":"TO"}
,{"country":"Trinidad and Tobago","countrycode":"1","exitcode":"011","isocode":"TT"}
,{"country":"Tunisia","countrycode":"216","exitcode":"00","isocode":"TN"}
,{"country":"Turkey","countrycode":"90","exitcode":"00","isocode":"TR"}
,{"country":"Turkmenistan","countrycode":"993","exitcode":"810","isocode":"TM"}
,{"country":"Turks and Caicos Islands","countrycode":"1","exitcode":"0","isocode":"TC"}
,{"country":"Tuvalu","countrycode":"688","exitcode":"00","isocode":"TV"}
,{"country":"Uganda","countrycode":"256","exitcode":"000","isocode":"UG"}
,{"country":"Ukraine","countrycode":"380","exitcode":"00","isocode":"UA"}
,{"country":"United Arab Emirates","countrycode":"971","exitcode":"00","isocode":"AE"}
,{"country":"United Kingdom","countrycode":"44","exitcode":"00","isocode":"GB"}
,{"country":"United States","countrycode":"1","exitcode":"011","isocode":"US"}
,{"country":"U.S. Virgin Islands","countrycode":"1","exitcode":"011","isocode":"VI"}
,{"country":"Uruguay","countrycode":"598","exitcode":"00","isocode":"UY"}
,{"country":"Uzbekistan","countrycode":"998","exitcode":"8 - wait for dial tone - 10","isocode":"UZ"}
,{"country":"Vanuatu","countrycode":"678","exitcode":"00","isocode":"VU"}
,{"country":"Vatican City","countrycode":"379, 39","exitcode":"00","isocode":"VA"}
,{"country":"Venezuela","countrycode":"58","exitcode":"00","isocode":"VE"}
,{"country":"Vietnam","countrycode":"84","exitcode":"00","isocode":"VN"}
,{"country":"Wallis and Futuna","countrycode":"681","exitcode":"00","isocode":"WF"}
,{"country":"Yemen","countrycode":"967","exitcode":"00","isocode":"YE"}
,{"country":"Zambia","countrycode":"260","exitcode":"00","isocode":"ZM"}
,{"country":"Zimbabwe","countrycode":"263","exitcode":"00","isocode":"ZW"}
]
EOD;

	//		$country_iso=$_SESSION['domain']['country']['iso_code'];

			$sql = "select default_setting_value as value from v_default_settings ";
			$sql .= "where default_setting_name = 'iso_code' ";
			$sql .= "and default_setting_category = 'domain' ";
			$sql .= "and default_setting_subcategory = 'country' ";
			$sql .= "and default_setting_enabled = 'true';";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();

				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);

				if ( count($result)> 0) {
					$country_iso = $result[0]["value"];
				}
			}

			unset($prep_statement, $sql, $result);

			if ( $country_iso===NULL )
				return;

			$countries = json_decode($country_list, true);

			$found = false;
			foreach($countries as $country) {
				if ( $country["isocode"]==$country_iso ) {
					$found = true;
					break;
				}
			}

			if ( !$found )  {
				return;
			}

	// Set default Country ISO code
			$sql = "select count(*) as num_rows from v_vars ";
			$sql .= "where var_name = 'default_country' ";
			$sql .= "and var_cat = 'Defaults' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);

				if ($row['num_rows'] == 0) {
					$sql = "insert into v_vars ";
					$sql .= "(";
					$sql .= "var_uuid, ";
					$sql .= "var_name, ";
					$sql .= "var_value, ";
					$sql .= "var_cat, ";
					$sql .= "var_enabled, ";
					$sql .= "var_order, ";
					$sql .= "var_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'default_country', ";
					$sql .= "'".$country["isocode"]."', ";
					$sql .= "'Defaults', ";
					$sql .= "'true', ";
					$sql .= "'".$x."', ";
					$sql .= "'' ";
					$sql .= ");";
					$db->exec(check_sql($sql));
					unset($sql, $row);
					$x++;				
				}
			}
			unset($prep_statement, $sql);

	// Set default Country code
			$sql = "select count(*) as num_rows from v_vars ";
			$sql .= "where var_name = 'default_countrycode' ";
			$sql .= "and var_cat = 'Defaults' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);

				if ($row['num_rows'] == 0) {
					$sql = "insert into v_vars ";
					$sql .= "(";
					$sql .= "var_uuid, ";
					$sql .= "var_name, ";
					$sql .= "var_value, ";
					$sql .= "var_cat, ";
					$sql .= "var_enabled, ";
					$sql .= "var_order, ";
					$sql .= "var_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'default_countrycode', ";
					$sql .= "'".$country["countrycode"]."', ";
					$sql .= "'Defaults', ";
					$sql .= "'true', ";
					$sql .= "'".$x."', ";
					$sql .= "'' ";
					$sql .= ");";
					$db->exec(check_sql($sql));
					unset($sql, $row);
					$x++;				
				}
			}
			unset($prep_statement, $sql);

	// Set default International Direct Dialing code
			$sql = "select count(*) as num_rows from v_vars ";
			$sql .= "where var_name = 'default_exitcode' ";
			$sql .= "and var_cat = 'Defaults' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);

				if ($row['num_rows'] == 0) {
					$sql = "insert into v_vars ";
					$sql .= "(";
					$sql .= "var_uuid, ";
					$sql .= "var_name, ";
					$sql .= "var_value, ";
					$sql .= "var_cat, ";
					$sql .= "var_enabled, ";
					$sql .= "var_order, ";
					$sql .= "var_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'default_exitcode', ";
					$sql .= "'".$country["exitcode"]."', ";
					$sql .= "'Defaults', ";
					$sql .= "'true', ";
					$sql .= "'".$x."', ";
					$sql .= "'' ";
					$sql .= ");";
					$db->exec(check_sql($sql));
					unset($sql, $row);
					$x++;				
				}
			}
			unset($prep_statement, $sql);

			unset($countries);				
		}
	}

	$x = 1;

//if there are no variables in the vars table then add them
	if ($domains_processed == 1) {

		$result = json_decode($vars, true);
		foreach($result as $row) {

			$sql = "select count(*) as num_rows from v_vars ";
			$sql .= "where var_name = '".$row['var_name']."' ";
			$sql .= "and var_cat = '".$row['var_cat']."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$row2 = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row2['num_rows'] == 0) {

					$sql = "insert into v_vars ";
					$sql .= "(";
					$sql .= "var_uuid, ";
					$sql .= "var_name, ";
					$sql .= "var_value, ";
					$sql .= "var_cat, ";
					$sql .= "var_enabled, ";
					$sql .= "var_order, ";
					$sql .= "var_description ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'".$row['var_name']."', ";
					$sql .= "'".$row['var_value']."', ";
					$sql .= "'".$row['var_cat']."', ";
					$sql .= "'".$row['var_enabled']."', ";
					$sql .= "'".$x."', ";
					$sql .= "'".$row['var_description']."' ";
					$sql .= ");";
					$db->exec($sql);
					unset($sql);
					$x++;

				}
			}
			unset($prep_statement, $row2);

		}
		unset($result, $row);
	}

//adjust the variables required variables
	if ($domains_processed == 1) {
		//set variables that depend on the number of domains
			if (count($_SESSION['domains']) > 1) {
				//disable the domain and domain_uuid for systems with multiple domains
					$sql = "update v_vars set ";
					$sql .= "var_enabled = 'false' ";
					$sql .= "where (var_name = 'domain' or var_name = 'domain_uuid') ";
					$db->exec(check_sql($sql));
					unset($sql);
			}
			else {
				//set the domain_uuid
					$sql = "select count(*) as num_rows from v_vars ";
					$sql .= "where var_name = 'domain_uuid' ";
					$prep_statement = $db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						if ($row['num_rows'] == 0) {
							$sql = "insert into v_vars ";
							$sql .= "(";
							$sql .= "var_uuid, ";
							$sql .= "var_name, ";
							$sql .= "var_value, ";
							$sql .= "var_cat, ";
							$sql .= "var_enabled, ";
							$sql .= "var_order, ";
							$sql .= "var_description ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".uuid()."', ";
							$sql .= "'domain_uuid', ";
							$sql .= "'".$domain_uuid."', ";
							$sql .= "'Defaults', ";
							$sql .= "'true', ";
							$sql .= "'999', ";
							$sql .= "'' ";
							$sql .= ");";
							$db->exec(check_sql($sql));
							unset($sql);
						}
						unset($prep_statement, $row);
					}
			}

//set country code variables				
			set_country_vars($db, $x);

		//save the vars.xml file
			save_var_xml();
	}
?>