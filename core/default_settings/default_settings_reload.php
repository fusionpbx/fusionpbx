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
 Portions created by the Initial Developer are Copyright (C) 2008-2026
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
if (!permission_exists('default_setting_view')) {
	echo "access denied";
	exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//set the variables
$domain_uuid = $_GET['id'] ?? null;

// Set variables from http GET parameters
$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? 'default_setting_category'));
$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
$search = $_GET['search'] ?? '';
$show = $_GET['show'] ?? '';
$default_setting_category = $_GET['default_setting_category'] ?? '';

// Build the query string
$param = [];
if (!empty($_GET['order_by'])) {
	$param['order_by'] = $order_by;
}
if (!empty($_GET['order'])) {
	$param['order'] = $order;
}
if (!empty($search)) {
	$param['search'] = $search;
}
if (!empty($show) && $show == 'all' && permission_exists('stream_all')) {
	$param['show'] = $show;
}
if (!empty($default_setting_category)) {
	$param['default_setting_category'] = $default_setting_category;
}
$query_string = http_build_query($param);

//reload autoloader
$autoload->update();

//reload default settings
settings::clear_cache();

//reset others
$classes_to_clear = array_filter($autoload->get_interface_list('clear_cache'), function ($class) { return $class !== 'settings'; });
foreach ($classes_to_clear as $class_name) {
	$class_name::clear_cache();
}

//reset domains
$domain = new domains();
$domain->set();

//add a message
message::add($text['message-settings_reloaded']);

//redirect the browser
if (is_uuid($domain_uuid)) {
	$location = PROJECT_PATH.'/core/domains/domain_edit.php?id='.$domain_uuid;
}
else {
	$location = 'default_settings.php'.($query_string ? '?'.$query_string : '');
}
header("Location: ".$location);

?>
