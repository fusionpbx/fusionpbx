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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
	if ($domains_processed == 1) {

		//default settings - change the type from var to text
			$sql = "update v_default_settings ";
			$sql .= "set default_setting_name = 'text' ";
			$sql .= "where default_setting_name = 'var' ";
			$database = new database;
			$database->execute($sql, null);
			unset($sql);

		//set domains with enabled status of empty or null to true
			$sql = "delete from v_default_settings ";
			$sql .= "where (default_setting_category is null and default_setting_subcategory is null) ";
			$sql .= "or (default_setting_category = '' and default_setting_subcategory = '') ";
			$database = new database;
			$database->execute($sql, null);
			unset($sql);

		//populate the languages table, if necessary
			$sql = "select count(*) from v_languages";
			$database = new database;
			$num_rows = $database->select($sql, null, 'column');
			if ($num_rows == 0) {
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
				$sql .= "('".uuid()."', 'Greek - Cyprus', 'el-cy'), ";
				$sql .= "('".uuid()."', 'Greek - Greece', 'el-gr'), ";
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
				$sql .= "('".uuid()."', 'Russian', 'ru-ru'), ";
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
				$database = new database;
				$database->execute($sql, null);
				unset($sql, $parameters);
			}

		//populate the countries table, if necessary
			$sql = "select count(*) from v_countries";
			$database = new database;
			$num_rows = $database->select($sql, null, 'column');
			if ($num_rows == 0) {
				$sql = "insert into v_countries (country_uuid, country, iso_a2, iso_a3, num, country_code) values ";
				$sql .= "('".uuid()."', 'Afghanistan', 'AF', 'AFG', 4, '93'), ";
				$sql .= "('".uuid()."', 'Albania', 'AL', 'ALB', 8, '355'), ";
				$sql .= "('".uuid()."', 'Algeria', 'DZ', 'DZA', 12, '213'), ";
				$sql .= "('".uuid()."', 'American Samoa', 'AS', 'ASM', 16, '1-684'), ";
				$sql .= "('".uuid()."', 'Andorra', 'AD', 'AND', 20, '376'), ";
				$sql .= "('".uuid()."', 'Angola', 'AO', 'AGO', 24, '244'), ";
				$sql .= "('".uuid()."', 'Anguilla', 'AI', 'AIA', 660, '1-264'), ";
				$sql .= "('".uuid()."', 'Antarctica', 'AQ', 'ATA', 10, '672'), ";
				$sql .= "('".uuid()."', 'Antigua and Barbuda', 'AG', 'ATG', 28, '1-268'), ";
				$sql .= "('".uuid()."', 'Argentina', 'AR', 'ARG', 32, '54'), ";
				$sql .= "('".uuid()."', 'Armenia', 'AM', 'ARM', 51, '374'), ";
				$sql .= "('".uuid()."', 'Aruba', 'AW', 'ABW', 533, '297'), ";
				$sql .= "('".uuid()."', 'Australia', 'AU', 'AUS', 36, '61'), ";
				$sql .= "('".uuid()."', 'Austria', 'AT', 'AUT', 40, '43'), ";
				$sql .= "('".uuid()."', 'Azerbaijan', 'AZ', 'AZE', 31, '994'), ";
				$sql .= "('".uuid()."', 'Bahamas', 'BS', 'BHS', 44, '1-242'), ";
				$sql .= "('".uuid()."', 'Bahrain', 'BH', 'BHR', 48, '973'), ";
				$sql .= "('".uuid()."', 'Bangladesh', 'BD', 'BGD', 50, '880'), ";
				$sql .= "('".uuid()."', 'Barbados', 'BB', 'BRB', 52, '1-246'), ";
				$sql .= "('".uuid()."', 'Belarus', 'BY', 'BLR', 112, '375'), ";
				$sql .= "('".uuid()."', 'Belgium', 'BE', 'BEL', 56, '32'), ";
				$sql .= "('".uuid()."', 'Belize', 'BZ', 'BLZ', 84, '501'), ";
				$sql .= "('".uuid()."', 'Benin', 'BJ', 'BEN', 204, '229'), ";
				$sql .= "('".uuid()."', 'Bermuda', 'BM', 'BMU', 60, '1-441'), ";
				$sql .= "('".uuid()."', 'Bhutan', 'BT', 'BTN', 64, '975'), ";
				$sql .= "('".uuid()."', 'Bolivia', 'BO', 'BOL', 68, '591'), ";
				$sql .= "('".uuid()."', 'Bonaire', 'BQ', 'BES', 535, '599'), ";
				$sql .= "('".uuid()."', 'Bosnia and Herzegovina', 'BA', 'BIH', 70, '387'), ";
				$sql .= "('".uuid()."', 'Botswana', 'BW', 'BWA', 72, '267'), ";
				$sql .= "('".uuid()."', 'Bouvet Island', 'BV', 'BVT', 74, '47'), ";
				$sql .= "('".uuid()."', 'Brazil', 'BR', 'BRA', 76, '55'), ";
				$sql .= "('".uuid()."', 'British Indian Ocean Territory', 'IO', 'IOT', 86, '246'), ";
				$sql .= "('".uuid()."', 'Brunei Darussalam', 'BN', 'BRN', 96, '673'), ";
				$sql .= "('".uuid()."', 'Bulgaria', 'BG', 'BGR', 100, '359'), ";
				$sql .= "('".uuid()."', 'Burkina Faso', 'BF', 'BFA', 854, '226'), ";
				$sql .= "('".uuid()."', 'Burundi', 'BI', 'BDI', 108, '257'), ";
				$sql .= "('".uuid()."', 'Cambodia', 'KH', 'KHM', 116, '855'), ";
				$sql .= "('".uuid()."', 'Cameroon', 'CM', 'CMR', 120, '237'), ";
				$sql .= "('".uuid()."', 'Canada', 'CA', 'CAN', 124, '1'), ";
				$sql .= "('".uuid()."', 'Cape Verde', 'CV', 'CPV', 132, '238'), ";
				$sql .= "('".uuid()."', 'Cayman Islands', 'KY', 'CYM', 136, '1-345'), ";
				$sql .= "('".uuid()."', 'Central African Republic', 'CF', 'CAF', 140, '236'), ";
				$sql .= "('".uuid()."', 'Chad', 'TD', 'TCD', 148, '235'), ";
				$sql .= "('".uuid()."', 'Chile', 'CL', 'CHL', 152, '56'), ";
				$sql .= "('".uuid()."', 'China', 'CN', 'CHN', 156, '86'), ";
				$sql .= "('".uuid()."', 'Christmas Island', 'CX', 'CXR', 162, '61'), ";
				$sql .= "('".uuid()."', 'Cocos (Keeling) Islands', 'CC', 'CCK', 166, '61'), ";
				$sql .= "('".uuid()."', 'Colombia', 'CO', 'COL', 170, '57'), ";
				$sql .= "('".uuid()."', 'Comoros', 'KM', 'COM', 174, '269'), ";
				$sql .= "('".uuid()."', 'Congo', 'CG', 'COG', 178, '242'), ";
				$sql .= "('".uuid()."', 'Democratic Republic of the Congo', 'CD', 'COD', 180, '243'), ";
				$sql .= "('".uuid()."', 'Cook Islands', 'CK', 'COK', 184, '682'), ";
				$sql .= "('".uuid()."', 'Costa Rica', 'CR', 'CRI', 188, '506'), ";
				$sql .= "('".uuid()."', 'Croatia', 'HR', 'HRV', 191, '385'), ";
				$sql .= "('".uuid()."', 'Cuba', 'CU', 'CUB', 192, '53'), ";
				$sql .= "('".uuid()."', 'Curaçao', 'CW', 'CUW', 531, '599'), ";
				$sql .= "('".uuid()."', 'Cyprus', 'CY', 'CYP', 196, '357'), ";
				$sql .= "('".uuid()."', 'Czech Republic', 'CZ', 'CZE', 203, '420'), ";
				$sql .= "('".uuid()."', 'Côte d''Ivoire', 'CI', 'CIV', 384, '225'), ";
				$sql .= "('".uuid()."', 'Denmark', 'DK', 'DNK', 208, '45'), ";
				$sql .= "('".uuid()."', 'Djibouti', 'DJ', 'DJI', 262, '253'), ";
				$sql .= "('".uuid()."', 'Dominica', 'DM', 'DMA', 212, '1-767'), ";
				$sql .= "('".uuid()."', 'Dominican Republic', 'DO', 'DOM', 214, '1-809,1-829,1-849'), ";
				$sql .= "('".uuid()."', 'Ecuador', 'EC', 'ECU', 218, '593'), ";
				$sql .= "('".uuid()."', 'Egypt', 'EG', 'EGY', 818, '20'), ";
				$sql .= "('".uuid()."', 'El Salvador', 'SV', 'SLV', 222, '503'), ";
				$sql .= "('".uuid()."', 'Equatorial Guinea', 'GQ', 'GNQ', 226, '240'), ";
				$sql .= "('".uuid()."', 'Eritrea', 'ER', 'ERI', 232, '291'), ";
				$sql .= "('".uuid()."', 'Estonia', 'EE', 'EST', 233, '372'), ";
				$sql .= "('".uuid()."', 'Ethiopia', 'ET', 'ETH', 231, '251'), ";
				$sql .= "('".uuid()."', 'Falkland Islands (Malvinas)', 'FK', 'FLK', 238, '500'), ";
				$sql .= "('".uuid()."', 'Faroe Islands', 'FO', 'FRO', 234, '298'), ";
				$sql .= "('".uuid()."', 'Fiji', 'FJ', 'FJI', 242, '679'), ";
				$sql .= "('".uuid()."', 'Finland', 'FI', 'FIN', 246, '358'), ";
				$sql .= "('".uuid()."', 'France', 'FR', 'FRA', 250, '33'), ";
				$sql .= "('".uuid()."', 'French Guiana', 'GF', 'GUF', 254, '594'), ";
				$sql .= "('".uuid()."', 'French Polynesia', 'PF', 'PYF', 258, '689'), ";
				$sql .= "('".uuid()."', 'French Southern Territories', 'TF', 'ATF', 260, '262'), ";
				$sql .= "('".uuid()."', 'Gabon', 'GA', 'GAB', 266, '241'), ";
				$sql .= "('".uuid()."', 'Gambia', 'GM', 'GMB', 270, '220'), ";
				$sql .= "('".uuid()."', 'Georgia', 'GE', 'GEO', 268, '995'), ";
				$sql .= "('".uuid()."', 'Germany', 'DE', 'DEU', 276, '49'), ";
				$sql .= "('".uuid()."', 'Ghana', 'GH', 'GHA', 288, '233'), ";
				$sql .= "('".uuid()."', 'Gibraltar', 'GI', 'GIB', 292, '350'), ";
				$sql .= "('".uuid()."', 'Greece', 'GR', 'GRC', 300, '30'), ";
				$sql .= "('".uuid()."', 'Greenland', 'GL', 'GRL', 304, '299'), ";
				$sql .= "('".uuid()."', 'Grenada', 'GD', 'GRD', 308, '1-473'), ";
				$sql .= "('".uuid()."', 'Guadeloupe', 'GP', 'GLP', 312, '590'), ";
				$sql .= "('".uuid()."', 'Guam', 'GU', 'GUM', 316, '1-671'), ";
				$sql .= "('".uuid()."', 'Guatemala', 'GT', 'GTM', 320, '502'), ";
				$sql .= "('".uuid()."', 'Guernsey', 'GG', 'GGY', 831, '44'), ";
				$sql .= "('".uuid()."', 'Guinea', 'GN', 'GIN', 324, '224'), ";
				$sql .= "('".uuid()."', 'Guinea-Bissau', 'GW', 'GNB', 624, '245'), ";
				$sql .= "('".uuid()."', 'Guyana', 'GY', 'GUY', 328, '592'), ";
				$sql .= "('".uuid()."', 'Haiti', 'HT', 'HTI', 332, '509'), ";
				$sql .= "('".uuid()."', 'Heard Island and McDonald Islands', 'HM', 'HMD', 334, '672'), ";
				$sql .= "('".uuid()."', 'Holy See (Vatican City State)', 'VA', 'VAT', 336, '379'), ";
				$sql .= "('".uuid()."', 'Honduras', 'HN', 'HND', 340, '504'), ";
				$sql .= "('".uuid()."', 'Hong Kong', 'HK', 'HKG', 344, '852'), ";
				$sql .= "('".uuid()."', 'Hungary', 'HU', 'HUN', 348, '36'), ";
				$sql .= "('".uuid()."', 'Iceland', 'IS', 'ISL', 352, '354'), ";
				$sql .= "('".uuid()."', 'India', 'IN', 'IND', 356, '91'), ";
				$sql .= "('".uuid()."', 'Indonesia', 'ID', 'IDN', 360, '62'), ";
				$sql .= "('".uuid()."', 'Iran, Islamic Republic of', 'IR', 'IRN', 364, '98'), ";
				$sql .= "('".uuid()."', 'Iraq', 'IQ', 'IRQ', 368, '964'), ";
				$sql .= "('".uuid()."', 'Ireland', 'IE', 'IRL', 372, '353'), ";
				$sql .= "('".uuid()."', 'Isle of Man', 'IM', 'IMN', 833, '44'), ";
				$sql .= "('".uuid()."', 'Israel', 'IL', 'ISR', 376, '972'), ";
				$sql .= "('".uuid()."', 'Italy', 'IT', 'ITA', 380, '39'), ";
				$sql .= "('".uuid()."', 'Jamaica', 'JM', 'JAM', 388, '1-876'), ";
				$sql .= "('".uuid()."', 'Japan', 'JP', 'JPN', 392, '81'), ";
				$sql .= "('".uuid()."', 'Jersey', 'JE', 'JEY', 832, '44'), ";
				$sql .= "('".uuid()."', 'Jordan', 'JO', 'JOR', 400, '962'), ";
				$sql .= "('".uuid()."', 'Kazakhstan', 'KZ', 'KAZ', 398, '7'), ";
				$sql .= "('".uuid()."', 'Kenya', 'KE', 'KEN', 404, '254'), ";
				$sql .= "('".uuid()."', 'Kiribati', 'KI', 'KIR', 296, '686'), ";
				$sql .= "('".uuid()."', 'Korea, Democratic People''s Republic of', 'KP', 'PRK', 408, '850'), ";
				$sql .= "('".uuid()."', 'Korea, Republic of', 'KR', 'KOR', 410, '82'), ";
				$sql .= "('".uuid()."', 'Kuwait', 'KW', 'KWT', 414, '965'), ";
				$sql .= "('".uuid()."', 'Kyrgyzstan', 'KG', 'KGZ', 417, '996'), ";
				$sql .= "('".uuid()."', 'Lao People''s Democratic Republic', 'LA', 'LAO', 418, '856'), ";
				$sql .= "('".uuid()."', 'Latvia', 'LV', 'LVA', 428, '371'), ";
				$sql .= "('".uuid()."', 'Lebanon', 'LB', 'LBN', 422, '961'), ";
				$sql .= "('".uuid()."', 'Lesotho', 'LS', 'LSO', 426, '266'), ";
				$sql .= "('".uuid()."', 'Liberia', 'LR', 'LBR', 430, '231'), ";
				$sql .= "('".uuid()."', 'Libya', 'LY', 'LBY', 434, '218'), ";
				$sql .= "('".uuid()."', 'Liechtenstein', 'LI', 'LIE', 438, '423'), ";
				$sql .= "('".uuid()."', 'Lithuania', 'LT', 'LTU', 440, '370'), ";
				$sql .= "('".uuid()."', 'Luxembourg', 'LU', 'LUX', 442, '352'), ";
				$sql .= "('".uuid()."', 'Macao', 'MO', 'MAC', 446, '853'), ";
				$sql .= "('".uuid()."', 'Macedonia, the Former Yugoslav Republic of', 'MK', 'MKD', 807, '389'), ";
				$sql .= "('".uuid()."', 'Madagascar', 'MG', 'MDG', 450, '261'), ";
				$sql .= "('".uuid()."', 'Malawi', 'MW', 'MWI', 454, '265'), ";
				$sql .= "('".uuid()."', 'Malaysia', 'MY', 'MYS', 458, '60'), ";
				$sql .= "('".uuid()."', 'Maldives', 'MV', 'MDV', 462, '960'), ";
				$sql .= "('".uuid()."', 'Mali', 'ML', 'MLI', 466, '223'), ";
				$sql .= "('".uuid()."', 'Malta', 'MT', 'MLT', 470, '356'), ";
				$sql .= "('".uuid()."', 'Marshall Islands', 'MH', 'MHL', 584, '692'), ";
				$sql .= "('".uuid()."', 'Martinique', 'MQ', 'MTQ', 474, '596'), ";
				$sql .= "('".uuid()."', 'Mauritania', 'MR', 'MRT', 478, '222'), ";
				$sql .= "('".uuid()."', 'Mauritius', 'MU', 'MUS', 480, '230'), ";
				$sql .= "('".uuid()."', 'Mayotte', 'YT', 'MYT', 175, '262'), ";
				$sql .= "('".uuid()."', 'Mexico', 'MX', 'MEX', 484, '52'), ";
				$sql .= "('".uuid()."', 'Micronesia, Federated States of', 'FM', 'FSM', 583, '691'), ";
				$sql .= "('".uuid()."', 'Moldova, Republic of', 'MD', 'MDA', 498, '373'), ";
				$sql .= "('".uuid()."', 'Monaco', 'MC', 'MCO', 492, '377'), ";
				$sql .= "('".uuid()."', 'Mongolia', 'MN', 'MNG', 496, '976'), ";
				$sql .= "('".uuid()."', 'Montenegro', 'ME', 'MNE', 499, '382'), ";
				$sql .= "('".uuid()."', 'Montserrat', 'MS', 'MSR', 500, '1-664'), ";
				$sql .= "('".uuid()."', 'Morocco', 'MA', 'MAR', 504, '212'), ";
				$sql .= "('".uuid()."', 'Mozambique', 'MZ', 'MOZ', 508, '258'), ";
				$sql .= "('".uuid()."', 'Myanmar', 'MM', 'MMR', 104, '95'), ";
				$sql .= "('".uuid()."', 'Namibia', 'NA', 'NAM', 516, '264'), ";
				$sql .= "('".uuid()."', 'Nauru', 'NR', 'NRU', 520, '674'), ";
				$sql .= "('".uuid()."', 'Nepal', 'NP', 'NPL', 524, '977'), ";
				$sql .= "('".uuid()."', 'Netherlands', 'NL', 'NLD', 528, '31'), ";
				$sql .= "('".uuid()."', 'New Caledonia', 'NC', 'NCL', 540, '687'), ";
				$sql .= "('".uuid()."', 'New Zealand', 'NZ', 'NZL', 554, '64'), ";
				$sql .= "('".uuid()."', 'Nicaragua', 'NI', 'NIC', 558, '505'), ";
				$sql .= "('".uuid()."', 'Niger', 'NE', 'NER', 562, '227'), ";
				$sql .= "('".uuid()."', 'Nigeria', 'NG', 'NGA', 566, '234'), ";
				$sql .= "('".uuid()."', 'Niue', 'NU', 'NIU', 570, '683'), ";
				$sql .= "('".uuid()."', 'Norfolk Island', 'NF', 'NFK', 574, '672'), ";
				$sql .= "('".uuid()."', 'Northern Mariana Islands', 'MP', 'MNP', 580, '1-670'), ";
				$sql .= "('".uuid()."', 'Norway', 'NO', 'NOR', 578, '47'), ";
				$sql .= "('".uuid()."', 'Oman', 'OM', 'OMN', 512, '968'), ";
				$sql .= "('".uuid()."', 'Pakistan', 'PK', 'PAK', 586, '92'), ";
				$sql .= "('".uuid()."', 'Palau', 'PW', 'PLW', 585, '680'), ";
				$sql .= "('".uuid()."', 'Palestine, State of', 'PS', 'PSE', 275, '970'), ";
				$sql .= "('".uuid()."', 'Panama', 'PA', 'PAN', 591, '507'), ";
				$sql .= "('".uuid()."', 'Papua New Guinea', 'PG', 'PNG', 598, '675'), ";
				$sql .= "('".uuid()."', 'Paraguay', 'PY', 'PRY', 600, '595'), ";
				$sql .= "('".uuid()."', 'Peru', 'PE', 'PER', 604, '51'), ";
				$sql .= "('".uuid()."', 'Philippines', 'PH', 'PHL', 608, '63'), ";
				$sql .= "('".uuid()."', 'Pitcairn', 'PN', 'PCN', 612, '870'), ";
				$sql .= "('".uuid()."', 'Poland', 'PL', 'POL', 616, '48'), ";
				$sql .= "('".uuid()."', 'Portugal', 'PT', 'PRT', 620, '351'), ";
				$sql .= "('".uuid()."', 'Puerto Rico', 'PR', 'PRI', 630, '1'), ";
				$sql .= "('".uuid()."', 'Qatar', 'QA', 'QAT', 634, '974'), ";
				$sql .= "('".uuid()."', 'Romania', 'RO', 'ROU', 642, '40'), ";
				$sql .= "('".uuid()."', 'Russian Federation', 'RU', 'RUS', 643, '7'), ";
				$sql .= "('".uuid()."', 'Rwanda', 'RW', 'RWA', 646, '250'), ";
				$sql .= "('".uuid()."', 'Reunion', 'RE', 'REU', 638, '262'), ";
				$sql .= "('".uuid()."', 'Saint Barthelemy', 'BL', 'BLM', 652, '590'), ";
				$sql .= "('".uuid()."', 'Saint Helena', 'SH', 'SHN', 654, '290'), ";
				$sql .= "('".uuid()."', 'Saint Kitts and Nevis', 'KN', 'KNA', 659, '1-869'), ";
				$sql .= "('".uuid()."', 'Saint Lucia', 'LC', 'LCA', 662, '1-758'), ";
				$sql .= "('".uuid()."', 'Saint Martin (French part)', 'MF', 'MAF', 663, '590'), ";
				$sql .= "('".uuid()."', 'Saint Pierre and Miquelon', 'PM', 'SPM', 666, '508'), ";
				$sql .= "('".uuid()."', 'Saint Vincent and the Grenadines', 'VC', 'VCT', 670, '1-784'), ";
				$sql .= "('".uuid()."', 'Samoa', 'WS', 'WSM', 882, '685'), ";
				$sql .= "('".uuid()."', 'San Marino', 'SM', 'SMR', 674, '378'), ";
				$sql .= "('".uuid()."', 'Sao Tome and Principe', 'ST', 'STP', 678, '239'), ";
				$sql .= "('".uuid()."', 'Saudi Arabia', 'SA', 'SAU', 682, '966'), ";
				$sql .= "('".uuid()."', 'Senegal', 'SN', 'SEN', 686, '221'), ";
				$sql .= "('".uuid()."', 'Serbia', 'RS', 'SRB', 688, '381'), ";
				$sql .= "('".uuid()."', 'Seychelles', 'SC', 'SYC', 690, '248'), ";
				$sql .= "('".uuid()."', 'Sierra Leone', 'SL', 'SLE', 694, '232'), ";
				$sql .= "('".uuid()."', 'Singapore', 'SG', 'SGP', 702, '65'), ";
				$sql .= "('".uuid()."', 'Sint Maarten (Dutch part)', 'SX', 'SXM', 534, '1-721'), ";
				$sql .= "('".uuid()."', 'Slovakia', 'SK', 'SVK', 703, '421'), ";
				$sql .= "('".uuid()."', 'Slovenia', 'SI', 'SVN', 705, '386'), ";
				$sql .= "('".uuid()."', 'Solomon Islands', 'SB', 'SLB', 90, '677'), ";
				$sql .= "('".uuid()."', 'Somalia', 'SO', 'SOM', 706, '252'), ";
				$sql .= "('".uuid()."', 'South Africa', 'ZA', 'ZAF', 710, '27'), ";
				$sql .= "('".uuid()."', 'South Georgia and the South Sandwich Islands', 'GS', 'SGS', 239, '500'), ";
				$sql .= "('".uuid()."', 'South Sudan', 'SS', 'SSD', 728, '211'), ";
				$sql .= "('".uuid()."', 'Spain', 'ES', 'ESP', 724, '34'), ";
				$sql .= "('".uuid()."', 'Sri Lanka', 'LK', 'LKA', 144, '94'), ";
				$sql .= "('".uuid()."', 'Sudan', 'SD', 'SDN', 729, '249'), ";
				$sql .= "('".uuid()."', 'Suriname', 'SR', 'SUR', 740, '597'), ";
				$sql .= "('".uuid()."', 'Svalbard and Jan Mayen', 'SJ', 'SJM', 744, '47'), ";
				$sql .= "('".uuid()."', 'Swaziland', 'SZ', 'SWZ', 748, '268'), ";
				$sql .= "('".uuid()."', 'Sweden', 'SE', 'SWE', 752, '46'), ";
				$sql .= "('".uuid()."', 'Switzerland', 'CH', 'CHE', 756, '41'), ";
				$sql .= "('".uuid()."', 'Syrian Arab Republic', 'SY', 'SYR', 760, '963'), ";
				$sql .= "('".uuid()."', 'Taiwan, Province of China', 'TW', 'TWN', 158, '886'), ";
				$sql .= "('".uuid()."', 'Tajikistan', 'TJ', 'TJK', 762, '992'), ";
				$sql .= "('".uuid()."', 'United Republic of Tanzania', 'TZ', 'TZA', 834, '255'), ";
				$sql .= "('".uuid()."', 'Thailand', 'TH', 'THA', 764, '66'), ";
				$sql .= "('".uuid()."', 'Timor-Leste', 'TL', 'TLS', 626, '670'), ";
				$sql .= "('".uuid()."', 'Togo', 'TG', 'TGO', 768, '228'), ";
				$sql .= "('".uuid()."', 'Tokelau', 'TK', 'TKL', 772, '690'), ";
				$sql .= "('".uuid()."', 'Tonga', 'TO', 'TON', 776, '676'), ";
				$sql .= "('".uuid()."', 'Trinidad and Tobago', 'TT', 'TTO', 780, '1-868'), ";
				$sql .= "('".uuid()."', 'Tunisia', 'TN', 'TUN', 788, '216'), ";
				$sql .= "('".uuid()."', 'Turkey', 'TR', 'TUR', 792, '90'), ";
				$sql .= "('".uuid()."', 'Turkmenistan', 'TM', 'TKM', 795, '993'), ";
				$sql .= "('".uuid()."', 'Turks and Caicos Islands', 'TC', 'TCA', 796, '1-649'), ";
				$sql .= "('".uuid()."', 'Tuvalu', 'TV', 'TUV', 798, '688'), ";
				$sql .= "('".uuid()."', 'Uganda', 'UG', 'UGA', 800, '256'), ";
				$sql .= "('".uuid()."', 'Ukraine', 'UA', 'UKR', 804, '380'), ";
				$sql .= "('".uuid()."', 'United Arab Emirates', 'AE', 'ARE', 784, '971'), ";
				$sql .= "('".uuid()."', 'United Kingdom', 'GB', 'GBR', 826, '44'), ";
				$sql .= "('".uuid()."', 'United States', 'US', 'USA', 840, '1'), ";
				$sql .= "('".uuid()."', 'United States Minor Outlying Islands', 'UM', 'UMI', 581, '1'), ";
				$sql .= "('".uuid()."', 'Uruguay', 'UY', 'URY', 858, '598'), ";
				$sql .= "('".uuid()."', 'Uzbekistan', 'UZ', 'UZB', 860, '998'), ";
				$sql .= "('".uuid()."', 'Vanuatu', 'VU', 'VUT', 548, '678'), ";
				$sql .= "('".uuid()."', 'Venezuela', 'VE', 'VEN', 862, '58'), ";
				$sql .= "('".uuid()."', 'Viet Nam', 'VN', 'VNM', 704, '84'), ";
				$sql .= "('".uuid()."', 'British Virgin Islands', 'VG', 'VGB', 92, '1-284'), ";
				$sql .= "('".uuid()."', 'US Virgin Islands', 'VI', 'VIR', 850, '1-340'), ";
				$sql .= "('".uuid()."', 'Wallis and Futuna', 'WF', 'WLF', 876, '681'), ";
				$sql .= "('".uuid()."', 'Western Sahara', 'EH', 'ESH', 732, '212'), ";
				$sql .= "('".uuid()."', 'Yemen', 'YE', 'YEM', 887, '967'), ";
				$sql .= "('".uuid()."', 'Zambia', 'ZM', 'ZMB', 894, '260'), ";
				$sql .= "('".uuid()."', 'Zimbabwe', 'ZW', 'ZWE', 716, '263'), ";
				$sql .= "('".uuid()."', 'Aland Islands', 'AX', 'ALA', 248, '358') ";
				$database->execute($sql, null);
				unset($sql);
			}

		//update any defaults set to legacy languages
			$language = new text;
			foreach ($language->legacy_map as $language_code => $legacy_code) {
				if(strlen($legacy_code) == 5) {
					continue;
				}
				$sql = "update v_default_settings set default_setting_value = :language_code ";
				$sql .= "where default_setting_value = :legacy_code ";
				$sql .= "and default_setting_name = 'code' ";
				$sql .= "and default_setting_subcategory = 'language' ";
				$sql .= "and default_setting_category = 'domain' ";
				$parameters['language_code'] = $language_code;
				$parameters['legacy_code'] = $legacy_code;
				$database = new database;
				$database->execute($sql, $parameters);
				unset($sql, $parameters);
			}

		//set domain > time_zone to UTC if not set
			$sql = "update v_default_settings set ";
			$sql .= "default_setting_value = 'UTC', ";
			$sql .= "default_setting_enabled = 'true' ";
			$sql .= "where ( ";
			$sql .= "	default_setting_value is null or ";
			$sql .= "	default_setting_value = '' ";
			$sql .= ") ";
			$sql .= "and default_setting_category = 'domain' ";
			$sql .= "and default_setting_subcategory = 'time_zone' ";
			$sql .= "and default_setting_name = 'name' ";
			$database = new database;
			$database->execute($sql);
			unset($sql);

	}

?>
