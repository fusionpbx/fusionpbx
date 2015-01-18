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
	Portions created by the Initial Developer are Copyright (C) 2008-2010
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//proccess this only one time
if ($domains_processed == 1) {

	//ensure that the language code is set
		$sql = "select count(*) as num_rows from v_default_settings ";
		$sql .= "where default_setting_category = 'domain' ";
		$sql .= "and default_setting_subcategory = 'language' ";
		$sql .= "and default_setting_name = 'code' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				$sql = "insert into v_default_settings ";
				$sql .= "(";
				$sql .= "default_setting_uuid, ";
				$sql .= "default_setting_category, ";
				$sql .= "default_setting_subcategory, ";
				$sql .= "default_setting_name, ";
				$sql .= "default_setting_value, ";
				$sql .= "default_setting_enabled, ";
				$sql .= "default_setting_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".uuid()."', ";
				$sql .= "'domain', ";
				$sql .= "'language', ";
				$sql .= "'code', ";
				$sql .= "'en-us', ";
				$sql .= "'true', ";
				$sql .= "'' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);
			}
			unset($prep_statement, $row);
		}

	//ensure that the default password length and strength are set
		$sql = "select count(*) as num_rows from v_default_settings ";
		$sql .= "where ( ";
		$sql .= "default_setting_category = 'security' ";
		$sql .= "and default_setting_subcategory = 'password_length' ";
		$sql .= "and default_setting_name = 'var' ";
		$sql .= ") or ( ";
		$sql .= "default_setting_category = 'security' ";
		$sql .= "and default_setting_subcategory = 'password_strength' ";
		$sql .= "and default_setting_name = 'var' ";
		$sql .= ") ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				$sql = "insert into v_default_settings ";
				$sql .= "( ";
				$sql .= "default_setting_uuid, ";
				$sql .= "default_setting_category, ";
				$sql .= "default_setting_subcategory, ";
				$sql .= "default_setting_name, ";
				$sql .= "default_setting_value, ";
				$sql .= "default_setting_enabled, ";
				$sql .= "default_setting_description ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "( ";
				$sql .= "'".uuid()."', ";
				$sql .= "'security', ";
				$sql .= "'password_length', ";
				$sql .= "'var', ";
				$sql .= "'10', ";
				$sql .= "'true', ";
				$sql .= "'Sets the default length for system generated passwords.' ";
				$sql .= "), ( ";
				$sql .= "'".uuid()."', ";
				$sql .= "'security', ";
				$sql .= "'password_strength', ";
				$sql .= "'var', ";
				$sql .= "'4', ";
				$sql .= "'true', ";
				$sql .= "'Sets the default strength for system generated passwords.  Valid Options: 1 - Numeric Only, 2 - Include Lower Apha, 3 - Include Upper Alpha, 4 - Include Special Characters' ";
				$sql .= ") ";
				$db->exec(check_sql($sql));
				unset($sql);
			}
			unset($prep_statement, $row);
		}

	//populate the languages table, if necessary
		$sql = "select count(*) as num_rows from v_languages";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				$sql = "insert into v_languages (language, code) values ";
				$sql .= "('Afrikaans', 'af'), ";
				$sql .= "('Albanian', 'sq'), ";
				$sql .= "('Amharic', 'am'), ";
				$sql .= "('Arabic - Algeria', 'ar-dz'), ";
				$sql .= "('Arabic - Bahrain', 'ar-bh'), ";
				$sql .= "('Arabic - Egypt', 'ar-eg'), ";
				$sql .= "('Arabic - Iraq', 'ar-iq'), ";
				$sql .= "('Arabic - Jordan', 'ar-jo'), ";
				$sql .= "('Arabic - Kuwait', 'ar-kw'), ";
				$sql .= "('Arabic - Lebanon', 'ar-lb'), ";
				$sql .= "('Arabic - Libya', 'ar-ly'), ";
				$sql .= "('Arabic - Morocco', 'ar-ma'), ";
				$sql .= "('Arabic - Oman', 'ar-om'), ";
				$sql .= "('Arabic - Qatar', 'ar-qa'), ";
				$sql .= "('Arabic - Saudi Arabia', 'ar-sa'), ";
				$sql .= "('Arabic - Syria', 'ar-sy'), ";
				$sql .= "('Arabic - Tunisia', 'ar-tn'), ";
				$sql .= "('Arabic - United Arab Emirates', 'ar-ae'), ";
				$sql .= "('Arabic - Yemen', 'ar-ye'), ";
				$sql .= "('Armenian', 'hy'), ";
				$sql .= "('Assamese', 'as'), ";
				$sql .= "('Azeri - Cyrillic, Latin', 'az-az'), ";
				$sql .= "('Basque', 'eu'), ";
				$sql .= "('Belarusian', 'be'), ";
				$sql .= "('Bengali - India, Bangladesh', 'bn'), ";
				$sql .= "('Bosnian', 'bs'), ";
				$sql .= "('Bulgarian', 'bg'), ";
				$sql .= "('Burmese', 'my'), ";
				$sql .= "('Catalan', 'ca'), ";
				$sql .= "('Chinese - China', 'zh-cn'), ";
				$sql .= "('Chinese - Hong Kong SAR', 'zh-hk'), ";
				$sql .= "('Chinese - Macau SAR', 'zh-mo'), ";
				$sql .= "('Chinese - Singapore', 'zh-sg'), ";
				$sql .= "('Chinese - Taiwan', 'zh-tw'), ";
				$sql .= "('Croatian', 'hr'), ";
				$sql .= "('Czech', 'cs'), ";
				$sql .= "('Danish', 'da'), ";
				$sql .= "('Divehi, Dhivehi, Maldivian', 'dv'), ";
				$sql .= "('Dutch - Belgium', 'nl-be'), ";
				$sql .= "('Dutch - Netherlands', 'nl-nl'), ";
				$sql .= "('English - Australia', 'en-au'), ";
				$sql .= "('English - Belize', 'en-bz'), ";
				$sql .= "('English - Canada', 'en-ca'), ";
				$sql .= "('English - Caribbean', 'en-cb'), ";
				$sql .= "('English - Great Britain', 'en-gb'), ";
				$sql .= "('English - India', 'en-in'), ";
				$sql .= "('English - Ireland', 'en-ie'), ";
				$sql .= "('English - Jamaica', 'en-jm'), ";
				$sql .= "('English - New Zealand', 'en-nz'), ";
				$sql .= "('English - Phillippines', 'en-ph'), ";
				$sql .= "('English - Southern Africa', 'en-za'), ";
				$sql .= "('English - Trinidad', 'en-tt'), ";
				$sql .= "('English - United States', 'en-us'), ";
				$sql .= "('Estonian', 'et'), ";
				$sql .= "('Faroese', 'fo'), ";
				$sql .= "('Farsi - Persian', 'fa'), ";
				$sql .= "('Finnish', 'fi'), ";
				$sql .= "('French - Belgium', 'fr-be'), ";
				$sql .= "('French - Canada', 'fr-ca'), ";
				$sql .= "('French - France', 'fr-fr'), ";
				$sql .= "('French - Luxembourg', 'fr-lu'), ";
				$sql .= "('French - Switzerland', 'fr-ch'), ";
				$sql .= "('FYRO Macedonia', 'mk'), ";
				$sql .= "('Gaelic - Ireland', 'gd-ie'), ";
				$sql .= "('Gaelic - Scotland', 'gd'), ";
				$sql .= "('German - Austria', 'de-at'), ";
				$sql .= "('German - Germany', 'de-de'), ";
				$sql .= "('German - Liechtenstein', 'de-li'), ";
				$sql .= "('German - Luxembourg', 'de-lu'), ";
				$sql .= "('German - Switzerland', 'de-ch'), ";
				$sql .= "('Greek', 'el'), ";
				$sql .= "('Guarani - Paraguay', 'gn'), ";
				$sql .= "('Gujarati', 'gu'), ";
				$sql .= "('Hebrew', 'he'), ";
				$sql .= "('Hindi', 'hi'), ";
				$sql .= "('Hungarian', 'hu'), ";
				$sql .= "('Icelandic', 'is'), ";
				$sql .= "('Indonesian', 'id'), ";
				$sql .= "('Italian - Italy', 'it-it'), ";
				$sql .= "('Italian - Switzerland', 'it-ch'), ";
				$sql .= "('Japanese', 'ja'), ";
				$sql .= "('Kannada', 'kn'), ";
				$sql .= "('Kashmiri', 'ks'), ";
				$sql .= "('Kazakh', 'kk'), ";
				$sql .= "('Khmer', 'km'), ";
				$sql .= "('Korean', 'ko'), ";
				$sql .= "('Lao', 'lo'), ";
				$sql .= "('Latin', 'la'), ";
				$sql .= "('Latvian', 'lv'), ";
				$sql .= "('Lithuanian', 'lt'), ";
				$sql .= "('Malayalam', 'ml'), ";
				$sql .= "('Malay - Brunei', 'ms-bn'), ";
				$sql .= "('Malay - Malaysia', 'ms-my'), ";
				$sql .= "('Maltese', 'mt'), ";
				$sql .= "('Maori', 'mi'), ";
				$sql .= "('Marathi', 'mr'), ";
				$sql .= "('Nepali', 'ne'), ";
				$sql .= "('Norwegian - Bokml, Nynorsk', 'no-no'), ";
				$sql .= "('Oriya', 'or'), ";
				$sql .= "('Polish', 'pl'), ";
				$sql .= "('Portuguese - Brazil', 'pt-br'), ";
				$sql .= "('Portuguese - Portugal', 'pt-pt'), ";
				$sql .= "('Punjabi', 'pa'), ";
				$sql .= "('Raeto-Romance', 'rm'), ";
				$sql .= "('Romanian - Moldova', 'ro-mo'), ";
				$sql .= "('Romanian - Romania', 'ro'), ";
				$sql .= "('Russian', 'ru'), ";
				$sql .= "('Russian - Moldova', 'ru-mo'), ";
				$sql .= "('Sanskrit', 'sa'), ";
				$sql .= "('Serbian - Cyrillic, Latin', 'sr-sp'), ";
				$sql .= "('Setsuana', 'tn'), ";
				$sql .= "('Sindhi', 'sd'), ";
				$sql .= "('Sinhala, Sinhalese', 'si'), ";
				$sql .= "('Slovak', 'sk'), ";
				$sql .= "('Slovenian', 'sl'), ";
				$sql .= "('Somali', 'so'), ";
				$sql .= "('Sorbian', 'sb'), ";
				$sql .= "('Spanish - Argentina', 'es-ar'), ";
				$sql .= "('Spanish - Bolivia', 'es-bo'), ";
				$sql .= "('Spanish - Chile', 'es-cl'), ";
				$sql .= "('Spanish - Colombia', 'es-co'), ";
				$sql .= "('Spanish - Costa Rica', 'es-cr'), ";
				$sql .= "('Spanish - Dominican Republic', 'es-do'), ";
				$sql .= "('Spanish - Ecuador', 'es-ec'), ";
				$sql .= "('Spanish - El Salvador', 'es-sv'), ";
				$sql .= "('Spanish - Guatemala', 'es-gt'), ";
				$sql .= "('Spanish - Honduras', 'es-hn'), ";
				$sql .= "('Spanish - Mexico', 'es-mx'), ";
				$sql .= "('Spanish - Nicaragua', 'es-ni'), ";
				$sql .= "('Spanish - Panama', 'es-pa'), ";
				$sql .= "('Spanish - Paraguay', 'es-py'), ";
				$sql .= "('Spanish - Peru', 'es-pe'), ";
				$sql .= "('Spanish - Puerto Rico', 'es-pr'), ";
				$sql .= "('Spanish - Spain (Traditional)', 'es-es'), ";
				$sql .= "('Spanish - Uruguay', 'es-uy'), ";
				$sql .= "('Spanish - Venezuela', 'es-ve'), ";
				$sql .= "('Swahili', 'sw'), ";
				$sql .= "('Swedish - Finland', 'sv-fi'), ";
				$sql .= "('Swedish - Sweden', 'sv-se'), ";
				$sql .= "('Tajik', 'tg'), ";
				$sql .= "('Tamil', 'ta'), ";
				$sql .= "('Tatar', 'tt'), ";
				$sql .= "('Telugu', 'te'), ";
				$sql .= "('Thai', 'th'), ";
				$sql .= "('Tibetan', 'bo'), ";
				$sql .= "('Tsonga', 'ts'), ";
				$sql .= "('Turkish', 'tr'), ";
				$sql .= "('Turkmen', 'tk'), ";
				$sql .= "('Ukrainian', 'uk'), ";
				$sql .= "('Urdu', 'ur'), ";
				$sql .= "('Uzbek - Cyrillic, Latin', 'uz-uz'), ";
				$sql .= "('Vietnamese', 'vi'), ";
				$sql .= "('Welsh', 'cy'), ";
				$sql .= "('Xhosa', 'xh'), ";
				$sql .= "('Yiddish', 'yi') ";
				$db->exec(check_sql($sql));
				unset($sql);
			}
			unset($prep_statement, $row);
		}

//set the sip_profiles directory for older installs
	if (isset($_SESSION['switch']['gateways']['dir'])) {
		$orm = new orm;
		$orm->name('default_settings');
		$orm->uuid($_SESSION['switch']['gateways']['uuid']);
		$array['default_setting_category'] = 'switch';
		$array['default_setting_subcategory'] = 'sip_profiles';
		$array['default_setting_name'] = 'dir';
		//$array['default_setting_value'] = '';
		//$array['default_setting_enabled'] = 'true';
		$orm->save($array);
		unset($array);
	}
}

?>