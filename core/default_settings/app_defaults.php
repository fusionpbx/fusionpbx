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

//process this only one time
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
				$sql = "insert into v_languages (language_uuid, language, code) values ";
				$sql .= "('".uuid()."', 'Afrikaans', 'af'), ";
				$sql .= "('".uuid()."', 'Albanian', 'sq'), ";
				$sql .= "('".uuid()."', 'Amharic', 'am'), ";
				$sql .= "('".uuid()."', 'Arabic - Algeria', 'ar-dz'), ";
				$sql .= "('".uuid()."', 'Arabic - Bahrain', 'ar-bh'), ";
				$sql .= "('".uuid()."', 'Arabic - Egypt', 'ar-eg'), ";
				$sql .= "('".uuid()."', 'Arabic - Iraq', 'ar-iq'), ";
				$sql .= "('".uuid()."', 'Arabic - Jordan', 'ar-jo'), ";
				$sql .= "('".uuid()."', 'Arabic - Kuwait', 'ar-kw'), ";
				$sql .= "('".uuid()."', 'Arabic - Lebanon', 'ar-lb'), ";
				$sql .= "('".uuid()."', 'Arabic - Libya', 'ar-ly'), ";
				$sql .= "('".uuid()."', 'Arabic - Morocco', 'ar-ma'), ";
				$sql .= "('".uuid()."', 'Arabic - Oman', 'ar-om'), ";
				$sql .= "('".uuid()."', 'Arabic - Qatar', 'ar-qa'), ";
				$sql .= "('".uuid()."', 'Arabic - Saudi Arabia', 'ar-sa'), ";
				$sql .= "('".uuid()."', 'Arabic - Syria', 'ar-sy'), ";
				$sql .= "('".uuid()."', 'Arabic - Tunisia', 'ar-tn'), ";
				$sql .= "('".uuid()."', 'Arabic - United Arab Emirates', 'ar-ae'), ";
				$sql .= "('".uuid()."', 'Arabic - Yemen', 'ar-ye'), ";
				$sql .= "('".uuid()."', 'Armenian', 'hy'), ";
				$sql .= "('".uuid()."', 'Assamese', 'as'), ";
				$sql .= "('".uuid()."', 'Azeri - Cyrillic, Latin', 'az-az'), ";
				$sql .= "('".uuid()."', 'Basque', 'eu'), ";
				$sql .= "('".uuid()."', 'Belarusian', 'be'), ";
				$sql .= "('".uuid()."', 'Bengali - India, Bangladesh', 'bn'), ";
				$sql .= "('".uuid()."', 'Bosnian', 'bs'), ";
				$sql .= "('".uuid()."', 'Bulgarian', 'bg'), ";
				$sql .= "('".uuid()."', 'Burmese', 'my'), ";
				$sql .= "('".uuid()."', 'Catalan', 'ca'), ";
				$sql .= "('".uuid()."', 'Chinese - China', 'zh-cn'), ";
				$sql .= "('".uuid()."', 'Chinese - Hong Kong SAR', 'zh-hk'), ";
				$sql .= "('".uuid()."', 'Chinese - Macau SAR', 'zh-mo'), ";
				$sql .= "('".uuid()."', 'Chinese - Singapore', 'zh-sg'), ";
				$sql .= "('".uuid()."', 'Chinese - Taiwan', 'zh-tw'), ";
				$sql .= "('".uuid()."', 'Croatian', 'hr'), ";
				$sql .= "('".uuid()."', 'Czech', 'cs'), ";
				$sql .= "('".uuid()."', 'Danish', 'da'), ";
				$sql .= "('".uuid()."', 'Divehi, Dhivehi, Maldivian', 'dv'), ";
				$sql .= "('".uuid()."', 'Dutch - Belgium', 'nl-be'), ";
				$sql .= "('".uuid()."', 'Dutch - Netherlands', 'nl-nl'), ";
				$sql .= "('".uuid()."', 'English - Australia', 'en-au'), ";
				$sql .= "('".uuid()."', 'English - Belize', 'en-bz'), ";
				$sql .= "('".uuid()."', 'English - Canada', 'en-ca'), ";
				$sql .= "('".uuid()."', 'English - Caribbean', 'en-cb'), ";
				$sql .= "('".uuid()."', 'English - Great Britain', 'en-gb'), ";
				$sql .= "('".uuid()."', 'English - India', 'en-in'), ";
				$sql .= "('".uuid()."', 'English - Ireland', 'en-ie'), ";
				$sql .= "('".uuid()."', 'English - Jamaica', 'en-jm'), ";
				$sql .= "('".uuid()."', 'English - New Zealand', 'en-nz'), ";
				$sql .= "('".uuid()."', 'English - Phillippines', 'en-ph'), ";
				$sql .= "('".uuid()."', 'English - Southern Africa', 'en-za'), ";
				$sql .= "('".uuid()."', 'English - Trinidad', 'en-tt'), ";
				$sql .= "('".uuid()."', 'English - United States', 'en-us'), ";
				$sql .= "('".uuid()."', 'Estonian', 'et'), ";
				$sql .= "('".uuid()."', 'Faroese', 'fo'), ";
				$sql .= "('".uuid()."', 'Farsi - Persian', 'fa'), ";
				$sql .= "('".uuid()."', 'Finnish', 'fi'), ";
				$sql .= "('".uuid()."', 'French - Belgium', 'fr-be'), ";
				$sql .= "('".uuid()."', 'French - Canada', 'fr-ca'), ";
				$sql .= "('".uuid()."', 'French - France', 'fr-fr'), ";
				$sql .= "('".uuid()."', 'French - Luxembourg', 'fr-lu'), ";
				$sql .= "('".uuid()."', 'French - Switzerland', 'fr-ch'), ";
				$sql .= "('".uuid()."', 'FYRO Macedonia', 'mk'), ";
				$sql .= "('".uuid()."', 'Gaelic - Ireland', 'gd-ie'), ";
				$sql .= "('".uuid()."', 'Gaelic - Scotland', 'gd'), ";
				$sql .= "('".uuid()."', 'German - Austria', 'de-at'), ";
				$sql .= "('".uuid()."', 'German - Germany', 'de-de'), ";
				$sql .= "('".uuid()."', 'German - Liechtenstein', 'de-li'), ";
				$sql .= "('".uuid()."', 'German - Luxembourg', 'de-lu'), ";
				$sql .= "('".uuid()."', 'German - Switzerland', 'de-ch'), ";
				$sql .= "('".uuid()."', 'Greek', 'el'), ";
				$sql .= "('".uuid()."', 'Guarani - Paraguay', 'gn'), ";
				$sql .= "('".uuid()."', 'Gujarati', 'gu'), ";
				$sql .= "('".uuid()."', 'Hebrew', 'he'), ";
				$sql .= "('".uuid()."', 'Hindi', 'hi'), ";
				$sql .= "('".uuid()."', 'Hungarian', 'hu'), ";
				$sql .= "('".uuid()."', 'Icelandic', 'is'), ";
				$sql .= "('".uuid()."', 'Indonesian', 'id'), ";
				$sql .= "('".uuid()."', 'Italian - Italy', 'it-it'), ";
				$sql .= "('".uuid()."', 'Italian - Switzerland', 'it-ch'), ";
				$sql .= "('".uuid()."', 'Japanese', 'ja'), ";
				$sql .= "('".uuid()."', 'Kannada', 'kn'), ";
				$sql .= "('".uuid()."', 'Kashmiri', 'ks'), ";
				$sql .= "('".uuid()."', 'Kazakh', 'kk'), ";
				$sql .= "('".uuid()."', 'Khmer', 'km'), ";
				$sql .= "('".uuid()."', 'Korean', 'ko'), ";
				$sql .= "('".uuid()."', 'Lao', 'lo'), ";
				$sql .= "('".uuid()."', 'Latin', 'la'), ";
				$sql .= "('".uuid()."', 'Latvian', 'lv'), ";
				$sql .= "('".uuid()."', 'Lithuanian', 'lt'), ";
				$sql .= "('".uuid()."', 'Malayalam', 'ml'), ";
				$sql .= "('".uuid()."', 'Malay - Brunei', 'ms-bn'), ";
				$sql .= "('".uuid()."', 'Malay - Malaysia', 'ms-my'), ";
				$sql .= "('".uuid()."', 'Maltese', 'mt'), ";
				$sql .= "('".uuid()."', 'Maori', 'mi'), ";
				$sql .= "('".uuid()."', 'Marathi', 'mr'), ";
				$sql .= "('".uuid()."', 'Nepali', 'ne'), ";
				$sql .= "('".uuid()."', 'Norwegian - Bokml, Nynorsk', 'no-no'), ";
				$sql .= "('".uuid()."', 'Oriya', 'or'), ";
				$sql .= "('".uuid()."', 'Polish', 'pl'), ";
				$sql .= "('".uuid()."', 'Portuguese - Brazil', 'pt-br'), ";
				$sql .= "('".uuid()."', 'Portuguese - Portugal', 'pt-pt'), ";
				$sql .= "('".uuid()."', 'Punjabi', 'pa'), ";
				$sql .= "('".uuid()."', 'Raeto-Romance', 'rm'), ";
				$sql .= "('".uuid()."', 'Romanian - Moldova', 'ro-mo'), ";
				$sql .= "('".uuid()."', 'Romanian - Romania', 'ro'), ";
				$sql .= "('".uuid()."', 'Russian', 'ru'), ";
				$sql .= "('".uuid()."', 'Russian - Moldova', 'ru-mo'), ";
				$sql .= "('".uuid()."', 'Sanskrit', 'sa'), ";
				$sql .= "('".uuid()."', 'Serbian - Cyrillic, Latin', 'sr-sp'), ";
				$sql .= "('".uuid()."', 'Setsuana', 'tn'), ";
				$sql .= "('".uuid()."', 'Sindhi', 'sd'), ";
				$sql .= "('".uuid()."', 'Sinhala, Sinhalese', 'si'), ";
				$sql .= "('".uuid()."', 'Slovak', 'sk'), ";
				$sql .= "('".uuid()."', 'Slovenian', 'sl'), ";
				$sql .= "('".uuid()."', 'Somali', 'so'), ";
				$sql .= "('".uuid()."', 'Sorbian', 'sb'), ";
				$sql .= "('".uuid()."', 'Spanish - Argentina', 'es-ar'), ";
				$sql .= "('".uuid()."', 'Spanish - Bolivia', 'es-bo'), ";
				$sql .= "('".uuid()."', 'Spanish - Chile', 'es-cl'), ";
				$sql .= "('".uuid()."', 'Spanish - Colombia', 'es-co'), ";
				$sql .= "('".uuid()."', 'Spanish - Costa Rica', 'es-cr'), ";
				$sql .= "('".uuid()."', 'Spanish - Dominican Republic', 'es-do'), ";
				$sql .= "('".uuid()."', 'Spanish - Ecuador', 'es-ec'), ";
				$sql .= "('".uuid()."', 'Spanish - El Salvador', 'es-sv'), ";
				$sql .= "('".uuid()."', 'Spanish - Guatemala', 'es-gt'), ";
				$sql .= "('".uuid()."', 'Spanish - Honduras', 'es-hn'), ";
				$sql .= "('".uuid()."', 'Spanish - Mexico', 'es-mx'), ";
				$sql .= "('".uuid()."', 'Spanish - Nicaragua', 'es-ni'), ";
				$sql .= "('".uuid()."', 'Spanish - Panama', 'es-pa'), ";
				$sql .= "('".uuid()."', 'Spanish - Paraguay', 'es-py'), ";
				$sql .= "('".uuid()."', 'Spanish - Peru', 'es-pe'), ";
				$sql .= "('".uuid()."', 'Spanish - Puerto Rico', 'es-pr'), ";
				$sql .= "('".uuid()."', 'Spanish - Spain (Traditional)', 'es-es'), ";
				$sql .= "('".uuid()."', 'Spanish - Uruguay', 'es-uy'), ";
				$sql .= "('".uuid()."', 'Spanish - Venezuela', 'es-ve'), ";
				$sql .= "('".uuid()."', 'Swahili', 'sw'), ";
				$sql .= "('".uuid()."', 'Swedish - Finland', 'sv-fi'), ";
				$sql .= "('".uuid()."', 'Swedish - Sweden', 'sv-se'), ";
				$sql .= "('".uuid()."', 'Tajik', 'tg'), ";
				$sql .= "('".uuid()."', 'Tamil', 'ta'), ";
				$sql .= "('".uuid()."', 'Tatar', 'tt'), ";
				$sql .= "('".uuid()."', 'Telugu', 'te'), ";
				$sql .= "('".uuid()."', 'Thai', 'th'), ";
				$sql .= "('".uuid()."', 'Tibetan', 'bo'), ";
				$sql .= "('".uuid()."', 'Tsonga', 'ts'), ";
				$sql .= "('".uuid()."', 'Turkish', 'tr'), ";
				$sql .= "('".uuid()."', 'Turkmen', 'tk'), ";
				$sql .= "('".uuid()."', 'Ukrainian', 'uk'), ";
				$sql .= "('".uuid()."', 'Urdu', 'ur'), ";
				$sql .= "('".uuid()."', 'Uzbek - Cyrillic, Latin', 'uz-uz'), ";
				$sql .= "('".uuid()."', 'Vietnamese', 'vi'), ";
				$sql .= "('".uuid()."', 'Welsh', 'cy'), ";
				$sql .= "('".uuid()."', 'Xhosa', 'xh'), ";
				$sql .= "('".uuid()."', 'Yiddish', 'yi') ";
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