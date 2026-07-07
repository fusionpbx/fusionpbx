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

	Advanced Call Distribution (ACD)
	Contributor(s):
	BlueCloud <support@blueuc.com>
*/

//------------------------------------------------------------------------------
// Advanced Call Distribution install/upgrade defaults.
//
// Schema (tables), permissions, destinations (so queues are selectable in IVRs,
// time conditions, etc.) and default settings are all declared in app_config.php
// and applied automatically by the FusionPBX app-defaults / upgrade-schema run.
//
// The only thing that must be created per-domain here is the *86 agent
// login/logout feature-code dialplan, which routes to the agent-toggle Lua.
//------------------------------------------------------------------------------

//run once across all domains
	if ($domains_processed == 1) {

		//grant temporary permission so $database->save() may write dialplans during upgrade
			$p = permissions::new();
			$p->add('dialplan_add', 'temp');
			$p->add('dialplan_edit', 'temp');
			$p->add('dialplan_detail_add', 'temp');
			$p->add('dialplan_detail_edit', 'temp');

		//ensure every domain has the *86 ACD agent login/logout feature code
			$sql = "select domain_uuid, domain_name from v_domains ";
			$domains = $database->select($sql, null, 'all');
			if (is_array($domains)) {
				foreach ($domains as $row) {
					$domain_uuid = $row['domain_uuid'];
					$domain_name = $row['domain_name'];

					//skip if this domain already has the feature code
						$sql = "select count(*) from v_dialplans ";
						$sql .= "where domain_uuid = :domain_uuid and dialplan_name = 'acd_agent_toggle' ";
						$parameters['domain_uuid'] = $domain_uuid;
						$exists = $database->select($sql, $parameters, 'column');
						unset($sql, $parameters);
						if (!empty($exists) && (int)$exists > 0) { continue; }

					//build the dialplan
						$dialplan_uuid = uuid();
						$dialplan_xml  = '<extension name="acd_agent_toggle" continue="false" uuid="'.$dialplan_uuid.'">'."\n";
						$dialplan_xml .= '  <condition field="destination_number" expression="^\*86(\d*)$">'."\n";
						$dialplan_xml .= '    <action application="answer" data=""/>'."\n";
						$dialplan_xml .= '    <action application="sleep" data="500"/>'."\n";
						$dialplan_xml .= '    <action application="lua" data="app/acd/acd_agent_toggle.lua"/>'."\n";
						$dialplan_xml .= '  </condition>'."\n";
						$dialplan_xml .= '</extension>';

						$array = [];
						$array['dialplans'][0]['dialplan_uuid']        = $dialplan_uuid;
						$array['dialplans'][0]['domain_uuid']          = $domain_uuid;
						$array['dialplans'][0]['dialplan_name']        = 'acd_agent_toggle';
						$array['dialplans'][0]['dialplan_number']      = '*86';
						$array['dialplans'][0]['dialplan_context']     = $domain_name;
						$array['dialplans'][0]['dialplan_continue']    = 'false';
						$array['dialplans'][0]['dialplan_order']       = '100';
						$array['dialplans'][0]['dialplan_enabled']     = 'true';
						$array['dialplans'][0]['dialplan_description'] = 'Advanced Call Distribution: agent login/logout toggle (*86)';
						$array['dialplans'][0]['dialplan_xml']         = $dialplan_xml;

						$d = 0;
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_uuid']  = uuid();
						$array['dialplans'][0]['dialplan_details'][$d]['domain_uuid']           = $domain_uuid;
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_tag']   = 'condition';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_type']  = 'destination_number';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_data']  = '^\*86(\d*)$';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_group'] = '0';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_order'] = '5';
						$d++;
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_uuid']  = uuid();
						$array['dialplans'][0]['dialplan_details'][$d]['domain_uuid']           = $domain_uuid;
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_tag']   = 'action';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_type']  = 'answer';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_data']  = '';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_group'] = '0';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_order'] = '10';
						$d++;
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_uuid']  = uuid();
						$array['dialplans'][0]['dialplan_details'][$d]['domain_uuid']           = $domain_uuid;
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_tag']   = 'action';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_type']  = 'sleep';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_data']  = '500';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_group'] = '0';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_order'] = '15';
						$d++;
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_uuid']  = uuid();
						$array['dialplans'][0]['dialplan_details'][$d]['domain_uuid']           = $domain_uuid;
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_tag']   = 'action';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_type']  = 'lua';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_data']  = 'app/acd/acd_agent_toggle.lua';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_group'] = '0';
						$array['dialplans'][0]['dialplan_details'][$d]['dialplan_detail_order'] = '20';

					//save
						$database->app_name = acd::app_name;
						$database->app_uuid = acd::app_uuid;
						$database->save($array, false);
						unset($array);
				}
			}
			unset($domains);

		//remove the temporary permissions
			$p->delete('dialplan_add', 'temp');
			$p->delete('dialplan_edit', 'temp');
			$p->delete('dialplan_detail_add', 'temp');
			$p->delete('dialplan_detail_edit', 'temp');

	}

?>
