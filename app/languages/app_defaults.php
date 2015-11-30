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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
	$language_migration['pl'] = 'pl-pl';
	$language_migration['ro'] = 'ro-ro';
	$language_migration['uk'] = 'uk-ua';
	$language_migration['he'] = 'he-il';
	
	//process this only one time
	if ($domains_processed == 1) {
	
		//retrieve default language, if on the migrate list change it
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_category = 'domain'";
		$sql .= "and default_setting_subcategory = 'language'";
		$sql .= "and default_setting_name = 'code'";
		$sql .= "limit 1";
	
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$result = $prep_statement->fetch(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);
	
		if ($result and isset($language_migration[$result['default_setting_value']]) {
			// the default is using the wrong language code, migrating them
			$sql = "update v_default_settings ";
			$sql .= "set default_setting_value = '".$language_migration[$result['default_setting_value']]."' ";
			$sql .= "where default_setting_category = 'domain'";
			$sql .= "and default_setting_subcategory = 'language'";
			$sql .= "and default_setting_name = 'code'";
			$this->dbh->exec(check_sql($sql));
		}
		unset($result, $sql);
	}

	//retrieve default language per domain, if on the migrate list change it
	$sql = "select * from v_domain_settings ";
	$sql .= "where domain_setting_category = 'domain'";
	$sql .= "and domain_setting_subcategory = 'language'";
	$sql .= "and domain_setting_name = 'code'";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$domain_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);
	
	foreach ($domain_settings as $setting) {
		if(isset($language_migration$setting['domain_setting_value']]){
			// the domain is using the wrong language code, migrating them
			$sql = "update v_domain_settings ";
			$sql .= "set domain_setting_value = '".$language_migration[$result['domain_setting_value']]."' ";
			$sql .= "where domain_setting_uuid = '".$setting['domain_setting_uuid']."'";
			$this->dbh->exec(check_sql($sql));
			unset($result, $sql);
		}
	}
	unset($language_migration);
?>