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

if ($domains_processed == 1) {

	$x = 0;
	$array[$x]['default_setting_category'] = 'sms';
	$array[$x]['default_setting_subcategory'] = 'carriers';
	$array[$x]['default_setting_name'] = 'array';
	$array[$x]['default_setting_value'] = 'flowroute';
	$array[$x]['default_setting_enabled'] = 'true';
	$array[$x]['default_setting_description'] = '';
	$x++;
	$array[$x]['default_setting_category'] = 'sms';
	$array[$x]['default_setting_subcategory'] = 'carriers';
	$array[$x]['default_setting_name'] = 'array';
	$array[$x]['default_setting_value'] = 'twilio';
	$array[$x]['default_setting_enabled'] = 'true';
	$array[$x]['default_setting_description'] = '';
	$x++;

}

?>