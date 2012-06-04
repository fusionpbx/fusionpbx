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

//remove external from the end of the gateway path
	if (substr($v_gateways_dir, -8) == "external") {
		//$v_gateways_dir = substr($v_gateways_dir, 0, (strlen($v_gateways_dir)-9));
		//$sql = "update v_domain_settings set ";
		//$sql .= "v_gateways_dir = '$v_gateways_dir' ";
		//$sql .= "where domain_uuid = '$domain_uuid'";
		//$db->exec($sql);
		//unset($sql);
	}

?>