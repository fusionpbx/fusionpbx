<?php 
/*
    Usages for FusionPBX
    Version: 1.0

    The contents of this file are subject to the Mozilla Public License Version
    1.1 (the "License"); you may not use this file except in compliance with
    the License. You may obtain a copy of the License at
    http://www.mozilla.org/MPL/

    Software distributed under the License is distributed on an "AS IS" basis,
    WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
    for the specific language governing rights and limitations under the
    License.

    The Initial Developer of the Original Code is
    Vladimir Vladimirov <w@metastability.ai>
    Portions created by the Initial Developer are Copyright (C) 2022-2025
    the Initial Developer. All Rights Reserved.

    Contributor(s):
    Vladimir Vladimirov <w@metastability.ai>

    The Initial Developer of the Original Code is
    Mark J Crane <markjcrane@fusionpbx.com>
    Portions created by the Initial Developer are Copyright (C) 2008-2025
    the Initial Developer. All Rights Reserved.

    Contributor(s):
    Mark J Crane <markjcrane@fusionpbx.com>
*/

// CLASS WITH DIND EVERYWHERE
// select destination_number as dest, destination_actions as actions, destination_data as data from v_destinations

// select extension_uuid           as uuid, effective_caller_id_name   as name, extension || ' XML ' || domain_name                        as xml      from     v_extensions           as e    left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid
// select ring_group_uuid          as uuid, ring_group_name            as name, ring_group_extension || ' XML ' || domain_name             as xml      from     v_ring_groups          as e    left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid
// select conference_uuid          as uuid, conference_name            as name, conference_extension || ' XML ' || domain_name             as xml      from     v_conferences          as e    left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid
// select conference_center_uuid   as uuid, conference_center_name     as name, conference_center_extension  || ' XML ' || domain_name     as xml      from     v_conference_centers   as e    left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid
// select callback_route_uuid      as uuid, callback_route_name        as name, callback_route_extension  || ' XML ' || domain_name        as xml      from     v_callback_routes      as e    left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid
// select voicemail_uuid           as uuid, voicemail_id               as name, '99' || voicemail_id  || ' XML ' || domain_name            as xml      from     v_voicemails           as e    left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid
// select ivr_menu_uuid            as uuid, ivr_menu_name              as name, ivr_menu_extension  || ' XML ' || domain_name              as xml      from     v_ivr_menus            as e    left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid
// select call_flow_uuid           as uuid, call_flow_name             as name, call_flow_extension  || ' XML ' || domain_name             as xml      from     v_call_flows           as e    left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid
// select call_center_queue_uuid   as uuid, queue_name                 as name, queue_extension  || ' XML ' || domain_name                 as xml      from     v_call_center_queues   as e    left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid


        // IVR MENUS 
        # SELECT 'ivr_menu' AS typecode, e.ivr_menu_uuid AS uuid, e.ivr_menu_name AS name, STRING_AGG(CASE WHEN es.ivr_menu_option_param IS NOT NULL THEN es.ivr_menu_option_param ELSE NULL END, ', ') || CASE WHEN e.ivr_menu_exit_data IS NOT NULL THEN ', ' || e.ivr_menu_exit_data ELSE '' END AS actions FROM v_ivr_menu_options AS es  LEFT JOIN v_ivr_menus AS e ON e.ivr_menu_uuid = es.ivr_menu_uuid GROUP BY e.ivr_menu_uuid, e.ivr_menu_name

        // RING GROUP
        # SELECT 'ring_group' AS typecode, e.ring_group_uuid AS uuid, e.ring_group_name AS name, STRING_AGG(es.destination_number || ' XML ' || dmns.domain_name, ', ') || CASE WHEN e.ring_group_timeout_data IS NOT NULL THEN ', ' || e.ring_group_timeout_data ELSE '' END AS actions FROM v_ring_group_destinations AS es LEFT JOIN v_ring_groups AS e ON e.ring_group_uuid = es.ring_group_uuid LEFT JOIN v_domains AS dmns ON dmns.domain_uuid = e.domain_uuid GROUP BY e.ring_group_uuid, e.ring_group_name;

        // CALL FLOW
        # select 'call_flow' as typecode, e.call_flow_uuid as uuid, e.call_flow_name||' ('||e.call_flow_extension||')' as name, e.call_flow_data||','||e.call_flow_alternate_data as actions from v_call_flows as e
		
		// CALLBACK ROUTE (SMART)
		# select 'callback_route' as typecode, e.callback_route_uuid as uuid, e.callback_route_name||' ('||e.callback_rout  e_extension||')' as name, e.callback_route_app||':'||e.callback_route_data as actions from v_callback_routes as e

		// CALL CENTER QUEUE
		# select 'call_center_queue' as typecode, e.call_center_queue_uuid as uuid, e.queue_name||' ('||e.queue_extension||')' as name, e.queue_timeout_action as actions from v_call_center_queues as e

		// TIME CONDITION
		# SELECT 'time_condition' AS typecode, e.dialplan_uuid AS uuid, e.dialplan_name || ' (' || e.dialplan_number || ')' AS name, STRING_AGG(actions.application || ' ' || actions.data, ', ') AS actions FROM v_dialplans AS e, XMLTABLE('//action' PASSING CAST(e.dialplan_xml AS XML) COLUMNS application TEXT PATH '@application', data TEXT PATH '@data') AS actions WHERE e.app_uuid = '4b821450-926b-175a-af93-a03c441818b1' GROUP BY e.dialplan_uuid, e.dialplan_name, e.dialplan_number

		// DESTINATIONS
		# SELECT 'destination' AS typecode, e.destination_uuid AS uuid, e.destination_number AS name, e.destination_actions AS actions FROM v_destinations AS e


/*
* Usages - Identify the source of usage within various components such as extensions, destinations, IVR menus, ring groups, voicemail, and more.
*/

require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/paging.php";

class usages {

	private $show;
	private $domain_uuid;
	private $user_uuid;
	private $search;
	private $page;
	private $order;
	private $order_by;
	private $actions_list;
	
	public function __construct($show = null, $domain_uuid = null, $search = null, $page = null, $user_uuid = null) {

		//set the show
		if (isset($show)) {
			$this->show = $show;
		} elseif (!empty($_REQUEST['show']) && $_REQUEST['show'] == 'all') {
			$this->show = 'all';
		}

		//set the domain_uuid
		if (!empty($domain_uuid) && is_uuid($domain_uuid)) {
			$this->domain_uuid = $domain_uuid;
		}
		elseif (isset($_SESSION['domain_uuid']) && is_uuid($_SESSION['domain_uuid'])) {
			$this->domain_uuid = $_SESSION['domain_uuid'];
		}

		//set the user_uuid
		if (!empty($user_uuid) && is_uuid($user_uuid)) {
			$this->user_uuid = $user_uuid;
		}
		elseif (isset($_SESSION['user_uuid']) && is_uuid($_SESSION['user_uuid'])) {
			$this->user_uuid = $_SESSION['user_uuid'];
		}

		//set the search
		if (isset($search)) {
			$this->search = $search;
		} elseif (!empty($_REQUEST['search'])) {
			$this->search = strtolower($_REQUEST["search"]);
		} else {
			$this->search = '';
		}

		//set the page
		if (isset($page)) {
			$this->page = $page;
		} elseif (!empty($_REQUEST['page'])) {
			$this->page = $_REQUEST["page"];
		} else {
			$this->page = 0;
		}

		//set the order
		if (!empty($_REQUEST['order'])) {
			$this->order = $_REQUEST["order"];
		}

		//set the order_by
		if (!empty($_REQUEST['order_by'])) {
			$this->order_by = $_REQUEST["order_by"];
		}

		//set the actions
		$this->actionsList = $this->getActions();
	}

	/*
	*	███████╗ ██████╗ ██╗          ██████╗██╗  ██╗██╗██╗     ██████╗ ██████╗ ███████╗███╗   ██╗    ███████╗██╗███╗   ██╗██████╗ ███████╗██████╗ 
	*	██╔════╝██╔═══██╗██║         ██╔════╝██║  ██║██║██║     ██╔══██╗██╔══██╗██╔════╝████╗  ██║    ██╔════╝██║████╗  ██║██╔══██╗██╔════╝██╔══██╗
	*	███████╗██║   ██║██║         ██║     ███████║██║██║     ██║  ██║██████╔╝█████╗  ██╔██╗ ██║    █████╗  ██║██╔██╗ ██║██║  ██║█████╗  ██████╔╝
	*	╚════██║██║▄▄ ██║██║         ██║     ██╔══██║██║██║     ██║  ██║██╔══██╗██╔══╝  ██║╚██╗██║    ██╔══╝  ██║██║╚██╗██║██║  ██║██╔══╝  ██╔══██╗
	*	███████║╚██████╔╝███████╗    ╚██████╗██║  ██║██║███████╗██████╔╝██║  ██║███████╗██║ ╚████║    ██║     ██║██║ ╚████║██████╔╝███████╗██║  ██║
	*	╚══════╝ ╚══▀▀═╝ ╚══════╝     ╚═════╝╚═╝  ╚═╝╚═╝╚══════╝╚═════╝ ╚═╝  ╚═╝╚══════╝╚═╝  ╚═══╝    ╚═╝     ╚═╝╚═╝  ╚═══╝╚═════╝ ╚══════╝╚═╝  ╚═╝
	*/
    function getActions() {
	    $database = new database;
		$sql = " SELECT * FROM (
			SELECT
				'ivr_menu' AS typecode,
				e.ivr_menu_uuid AS uuid,
				e.ivr_menu_name|| ' (' || e.ivr_menu_extension || ')' AS name,
				COALESCE(STRING_AGG(CASE WHEN es.ivr_menu_option_param IS NOT NULL THEN es.ivr_menu_option_param ELSE NULL END, ', '),'') || 
				COALESCE(', ' || e.ivr_menu_exit_data, '') || 
				CASE WHEN e.ivr_menu_greet_long IS NOT NULL THEN ', ' || e.ivr_menu_greet_long ELSE '' END ||
    			CASE WHEN e.ivr_menu_greet_short IS NOT NULL THEN ', ' || e.ivr_menu_greet_short ELSE '' END ||
    			CASE WHEN e.ivr_menu_invalid_sound IS NOT NULL THEN ', ' || e.ivr_menu_invalid_sound ELSE '' END ||
    			CASE WHEN e.ivr_menu_exit_sound IS NOT NULL THEN ', ' || e.ivr_menu_exit_sound ELSE '' END ||
    			CASE WHEN e.ivr_menu_ringback IS NOT NULL THEN ', ' || e.ivr_menu_ringback ELSE '' END AS actions
			FROM v_ivr_menus AS e
			LEFT JOIN v_ivr_menu_options AS es ON e.ivr_menu_uuid = es.ivr_menu_uuid ";
		if ($this->show != "all" || !permission_exists('ivr_menu_all')) {
			$sql.= " WHERE e.domain_uuid = :domain_uuid";
		}
		$sql.="
			GROUP BY e.ivr_menu_uuid, e.ivr_menu_name
		
			UNION ALL
		
			SELECT
				'ring_group' AS typecode,
				e.ring_group_uuid AS uuid,
				e.ring_group_name|| ' (' || e.ring_group_extension || ')' AS name,
				COALESCE(STRING_AGG(es.destination_number || ' XML ' || dmns.domain_name, ', '),'') ||
				COALESCE(', ' || e.ring_group_timeout_data, '') || 
				CASE WHEN e.ring_group_greeting IS NOT NULL THEN ', ' || e.ring_group_greeting ELSE '' END ||
    			CASE WHEN e.ring_group_forward_destination IS NOT NULL THEN ', ' || e.ring_group_forward_destination ELSE '' END ||
    			CASE WHEN e.ring_group_ringback IS NOT NULL THEN ', ' || e.ring_group_ringback ELSE '' END AS actions
			FROM v_ring_groups AS e 
			LEFT JOIN v_ring_group_destinations AS es ON e.ring_group_uuid = es.ring_group_uuid
			LEFT JOIN v_domains AS dmns ON dmns.domain_uuid = e.domain_uuid ";
		if ($this->show != "all" || !permission_exists('ring_group_all')) {
			$sql.= " WHERE e.domain_uuid = :domain_uuid";
		}
		$sql.="
			GROUP BY e.ring_group_uuid, e.ring_group_name
		
			UNION ALL
		
			SELECT
				'call_flow' AS typecode,
				e.call_flow_uuid AS uuid,
				e.call_flow_name || ' (' || e.call_flow_extension || ')' AS name,
				e.call_flow_data || ', ' || e.call_flow_alternate_data ||
				CASE WHEN e.call_flow_sound IS NOT NULL THEN ', ' || e.call_flow_sound ELSE '' END ||
				CASE WHEN e.call_flow_alternate_sound IS NOT NULL THEN ', ' || e.call_flow_alternate_sound ELSE '' END
			AS actions
			FROM v_call_flows AS e";
		if ($this->show != "all" || !permission_exists('call_flow_all')) {
			$sql.= " WHERE e.domain_uuid = :domain_uuid";
		}
		$sql.="
		
			UNION ALL
		
			SELECT
				'callback_route' AS typecode,
				e.callback_route_uuid AS uuid,
				e.callback_route_name || ' (' || e.callback_route_extension || ')' AS name,
				e.callback_route_app || ':' || e.callback_route_data AS actions
			FROM v_callback_routes AS e ";
		if ($this->show != "all" || !permission_exists('callback_routes_view')) {
			$sql.= " WHERE e.domain_uuid = :domain_uuid";
		}
		$sql.="
		
			UNION ALL
		
			SELECT
				'call_center_queue' AS typecode,
				e.call_center_queue_uuid AS uuid,
				e.queue_name || ' (' || e.queue_extension || ')' AS name,
				e.queue_timeout_action || CASE WHEN e.queue_greeting IS NOT NULL THEN ', ' || e.queue_greeting ELSE '' END ||
    			CASE WHEN e.queue_moh_sound IS NOT NULL THEN ', ' || e.queue_moh_sound ELSE '' END ||
    			CASE WHEN e.queue_announce_sound IS NOT NULL THEN ', ' || e.queue_announce_sound ELSE '' END AS actions
			FROM v_call_center_queues AS e ";
		if ($this->show != "all" || !permission_exists('call_center_all')) {
			$sql.= " WHERE e.domain_uuid = :domain_uuid";
		}
		$sql.="
		
			UNION ALL
		
			SELECT
				'time_condition' AS typecode,
				e.dialplan_uuid AS uuid,
				e.dialplan_name || ' (' || e.dialplan_number || ')' AS name,
				COALESCE(STRING_AGG(actions.application || ' ' || actions.data, ', '), '') AS actions
			FROM v_dialplans AS e,
				 XMLTABLE('//action' PASSING CAST(e.dialplan_xml AS XML) COLUMNS application TEXT PATH '@application', data TEXT PATH '@data') AS actions
			WHERE e.app_uuid = '4b821450-926b-175a-af93-a03c441818b1' ";
		if ($this->show != "all" || !permission_exists('time_condition_all')) {
			$sql.= " and e.domain_uuid = :domain_uuid";
		}
		$sql.="
			GROUP BY e.dialplan_uuid, e.dialplan_name, e.dialplan_number
		
			UNION ALL
		
			SELECT
				'destination' AS typecode,
				e.destination_uuid AS uuid,
				e.destination_number AS name,
				CAST(e.destination_actions as text) ||
				CASE WHEN e.destination_hold_music IS NOT NULL THEN '; ' || e.destination_hold_music ELSE '' END
				AS actions
			FROM v_destinations AS e ";
		if ($this->show != "all" || !permission_exists('destination_all')) {
			$sql.= " WHERE e.domain_uuid = :domain_uuid";
		}
		$sql.="
			) AS combined_results";
		if (strpos($sql, ':domain_uuid') !== false) {
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		$result = $database->execute($sql, $parameters);
		unset($database, $sql, $parameters);
		return $result;
	}

	/*
	*	███████╗ ██████╗ ██╗     █╗  ███████╗
	*	██╔════╝██╔═══██╗██║     █║  ██╔════╝
	*	███████╗██║   ██║██║     ╚╝  ███████╗
	*	╚════██║██║▄▄ ██║██║         ╚════██║
	*	███████║╚██████╔╝███████╗    ███████║
	*	╚══════╝ ╚══▀▀═╝ ╚══════╝    ╚══════╝           
	*
	*	Format:
	*		Returned ARRAY format must be: [ { uuid: 'b0993330-70fe-4ccd-8b0f-1f0e97b824d6', xml: '7001 XML test.ftpbx.net' }, { uuid: '...', xml: '...' }, ... ]                           
	*/


	/*
	*	Extension Page List
	*/
	function extension_page() {

		$order_by = $this->order_by ?? 'extension';
		$order = $this->order ?? 'asc';
		$sort = $order_by == 'extension' ? 'natural' : null;

		//get total extension count
		$sql = "select count(*) from v_extensions ";
		$sql .= "where true ";
		if (!($this->show == "all" && permission_exists('extension_all'))) {
			$sql .= "and domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($this->search)) {
			$sql .= "and ( ";
			$sql .= " lower(extension) like :search ";
			$sql .= " or lower(number_alias) like :search ";
			$sql .= " or lower(effective_caller_id_name) like :search ";
			$sql .= " or lower(effective_caller_id_number) like :search ";
			$sql .= " or lower(outbound_caller_id_name) like :search ";
			$sql .= " or lower(outbound_caller_id_number) like :search ";
			$sql .= " or lower(emergency_caller_id_name) like :search ";
			$sql .= " or lower(emergency_caller_id_number) like :search ";
			$sql .= " or lower(directory_first_name) like :search ";
			$sql .= " or lower(directory_last_name) like :search ";
			if (permission_exists("extension_call_group")) {
				$sql .= " or lower(call_group) like :search ";
			}
			$sql .= " or lower(user_context) like :search ";
			$sql .= " or lower(enabled) like :search ";
			// $sql .= " or lower(non_paid) like :search ";
			$sql .= " or lower(description) like :search ";
			$sql .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('extension_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		//get the extensions
		$sql  = "select extension_uuid as uuid, effective_caller_id_name as name, extension || ' XML ' || domain_name as xml ";
		$sql .= "from v_extensions as e ";
		$sql .= "left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid	";
		$sql .= "where true ";
 
		if (!($this->show == "all" && permission_exists('extension_all'))) {
			$sql .= "and e.domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($this->search)) {
			$sql .= "and ( ";
			$sql .= " lower(extension) like :search ";
			$sql .= " or lower(number_alias) like :search ";
			$sql .= " or lower(effective_caller_id_name) like :search ";
			$sql .= " or lower(effective_caller_id_number) like :search ";
			$sql .= " or lower(outbound_caller_id_name) like :search ";
			$sql .= " or lower(outbound_caller_id_number) like :search ";
			$sql .= " or lower(emergency_caller_id_name) like :search ";
			$sql .= " or lower(emergency_caller_id_number) like :search ";
			$sql .= " or lower(directory_first_name) like :search ";
			$sql .= " or lower(directory_last_name) like :search ";
			if (permission_exists("extension_call_group")) {
				$sql .= " or lower(call_group) like :search ";
			}
			$sql .= " or lower(user_context) like :search ";
			$sql .= " or lower(enabled) like :search ";
			// $sql .= " or lower(non_paid) like :search ";
			$sql .= " or lower(description) like :search ";
			$sql .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$sql .= order_by($order_by, $order, null, null, $sort);
		$sql .= limit_offset($rows_per_page, $offset);
		$extensions = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
		return $extensions;
	}

	/*
	*	Ring Group Page List
	*/
	function ring_group_page() {

		$order_by = $this->order_by ?? 'ring_group_name';
		$order = $this->order ?? 'asc';
		$sort = $order_by == 'ring_group_extension' ? 'natural' : null;

		//get filtered ring group count
		if ($this->show == "all" && permission_exists('ring_group_all')) {
			$sql = "select count(*) from v_ring_groups ";
			$sql .= "where true ";
		}
		elseif (permission_exists('ring_group_domain') || permission_exists('ring_group_all')) {
			$sql = "select count(*)  from v_ring_groups ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		else {
			$sql = "select count(*) ";
			$sql .= "from v_ring_groups as r, v_ring_group_users as u ";
			$sql .= "where r.domain_uuid = :domain_uuid ";
			$sql .= "and r.ring_group_uuid = u.ring_group_uuid ";
			$sql .= "and u.user_uuid = :user_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$parameters['user_uuid'] = $this->user_uuid;
		}
		if (!empty($this->search)) {
			$sql .= "and (";
			$sql .= "lower(ring_group_name) like :search ";
			$sql .= "or lower(ring_group_extension) like :search ";
			$sql .= "or lower(ring_group_description) like :search ";
			$sql .= "or lower(ring_group_enabled) like :search ";
			$sql .= "or lower(ring_group_strategy) like :search ";
			$sql .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$database = new database;
		$num_rows = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('ring_group_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		//get the list
		if ($this->show == "all" && permission_exists('ring_group_all')) {
			$sql = "select ring_group_uuid as uuid, ring_group_extension || ' XML ' || domain_name as xml  from v_ring_groups as rng ";
			$sql .= "left join v_domains as dmns on dmns.domain_uuid = rng.domain_uuid	";
			$sql .= "where true ";
		}
		elseif (permission_exists('ring_group_domain') || permission_exists('ring_group_all')) {
			$sql = "select ring_group_uuid as uuid, ring_group_extension || ' XML ' || domain_name as xml  from v_ring_groups as rng ";
			$sql .= "left join v_domains as dmns on dmns.domain_uuid = rng.domain_uuid	";
			$sql .= "where rng.domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		else {
			$sql = "SELECT ring_group_uuid as uuid, ring_group_extension || ' XML ' || domain_name as xml  ";
			$sql .= "FROM v_ring_groups AS r ";
			$sql .= "INNER JOIN v_ring_group_users AS u ON r.ring_group_uuid = u.ring_group_uuid ";
			$sql .= "INNER JOIN v_domains AS dmns ON dmns.domain_uuid = r.domain_uuid ";
			$sql .= "WHERE r.domain_uuid = :domain_uuid ";
			$sql .= "AND u.user_uuid = :user_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$parameters['user_uuid'] = $this->user_uuid;
		}
		if (!empty($this->search)) {
			$sql .= "and (";
			$sql .= "lower(ring_group_name) like :search ";
			$sql .= "or lower(ring_group_extension) like :search ";
			$sql .= "or lower(ring_group_description) like :search ";
			$sql .= "or lower(ring_group_enabled) like :search ";
			$sql .= "or lower(ring_group_strategy) like :search ";
			$sql .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$sql .= ($order_by) ? order_by($order_by, $order) : "order by ring_group_extension asc, ring_group_name asc ";

		$sql .= limit_offset($rows_per_page, $offset);
		$ring_groups = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
		return $ring_groups;
	}

	/*
	*	Ivr Menu Page List
	*/
	function ivr_menu_page() {

		$order_by = $this->order_by ?? '';
		$order = $this->order ?? '';
		$sort = $order_by == 'ivr_menu_extension' ? 'natural' : null;

		//get total ivr_menu count
		$sql = "select count(*) from v_ivr_menus ";
		$sql .= "where true ";
		if ($this->show == "all" && permission_exists('extension_all')) {
			$sql .= "where true ";
		} else {
			$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($this->search)) {
			$sql .= "and (";
			$sql .= "	lower(ivr_menu_name) like :search ";
			$sql .= "	or lower(ivr_menu_extension) like :search ";
			$sql .= "	or lower(ivr_menu_enabled) like :search ";
			$sql .= "	or lower(ivr_menu_description) like :search ";
			$sql .= ")";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('extension_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		//get the ivr_menus
		$sql  = "select ivr_menu_uuid as uuid, ivr_menu_name as name, ivr_menu_extension || ' XML ' || domain_name as xml ";
		$sql .= "from v_ivr_menus as e ";
		$sql .= "left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid	";
		if ($this->show == "all" && permission_exists('extension_all')) {
			$sql .= "where true ";
		} else {
			$sql .= "where (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($this->search)) {
			$sql .= "and (";
			$sql .= "	lower(ivr_menu_name) like :search ";
			$sql .= "	or lower(ivr_menu_extension) like :search ";
			$sql .= "	or lower(ivr_menu_enabled) like :search ";
			$sql .= "	or lower(ivr_menu_description) like :search ";
			$sql .= ")";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$sql .= order_by($order_by, $order, 'ivr_menu_name', 'asc', $sort);
		$sql .= limit_offset($rows_per_page, $offset);
		$ivr_menus = $database->select($sql, $parameters ?? '', 'all');
		unset($sql, $parameters);
		return $ivr_menus;
	}

	/*
	*	 Call Center Queue Page List
	*/
	function call_center_queue_page() {

		$order_by = $this->order_by ?? '';
		$order = $this->order ?? '';
		$sort = $order_by == 'queue_extension' ? 'natural' : null;

		if (!empty($this->search)) {
			$sql_search = " (";
			$sql_search .= "lower(queue_name) like :search ";
			$sql_search .= "or lower(queue_description) like :search ";
			// $sql_search .= "or lower(queue_extension) like :search ";
			$sql_search .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}

		//get total ivr_menu count
		$sql = "select count(*) from v_call_center_queues ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('call_center_all')) {
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($sql_search)) {
			$sql .= "and ".$sql_search;
		}
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('call_center_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		//get the list
		$sql  = "select call_center_queue_uuid as uuid, queue_name as name, queue_extension  || ' XML ' || domain_name as xml from v_call_center_queues as e left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('call_center_all')) {
			$sql .= "and (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($sql_search)) {
			$sql .= "and ".$sql_search;
		}
		$sql .= order_by($order_by, $order, 'queue_name', 'asc', $sort);
		$sql .= limit_offset($rows_per_page, $offset);
		$call_center_queues = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);
		return $call_center_queues;
	}


	/*
	*	 Call Flow Queue Page List
	*/
	function call_flow_page() {

		$order_by = $this->order_by ?? '';
		$order = $this->order ?? '';
		$sort = $order_by == 'call_flow_extension' ? 'natural' : null;

		if (!empty($this->search)) {
			$sql_search = "and (";
			$sql_search .= "lower(call_flow_name) like :search ";
			$sql_search .= "or lower(call_flow_extension) like :search ";
			$sql_search .= "or lower(call_flow_feature_code) like :search ";
			$sql_search .= "or lower(call_flow_context) like :search ";
			$sql_search .= "or lower(call_flow_pin_number) like :search ";
			$sql_search .= "or lower(call_flow_label) like :search ";
			$sql_search .= "or lower(call_flow_alternate_label) like :search ";
			$sql_search .= "or lower(call_flow_description) like :search ";
			$sql_search .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}

		//get total ivr_menu count
		$sql = "select count(*) from v_call_flows ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('call_flow_all')) {
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		$sql .= $sql_search ?? '';
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('call_flow_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		//get the list
		$sql  = "select call_flow_uuid as uuid, call_flow_name as name, call_flow_extension  || ' XML ' || domain_name as xml from v_call_flows as e left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('call_flow_all')) {
			$sql .= "and (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		} 
		$sql .= $sql_search ?? '';
		$sql .= order_by($order_by, $order, 'call_flow_name', 'asc', $sort);
		$sql .= limit_offset($rows_per_page, $offset);
		$call_flows = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);
		return $call_flows;
	}

	/*
	*	 Recordings Page List
	*/
	function recording_page() {

		$order_by = $this->order_by ?? '';
		$order = $this->order ?? '';

		//get total ivr_menu count
		$sql = "select count(*) from v_recordings ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('conference_center_all')) {
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($this->search)) {
			$sql .= "and (";
			$sql .= "	lower(recording_name) like :search ";
			$sql .= "	or lower(recording_filename) like :search ";
			$sql .= "	or lower(recording_description) like :search ";
			$sql .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('recording_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		//get the list
		$sql  = "select recording_uuid as uuid, recording_name as name, recording_filename as xml from v_recordings as e left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('conference_center_all')) {
			$sql .= "and (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($this->search)) {
			$sql .= "and (";
			$sql .= "	lower(recording_name) like :search ";
			$sql .= "	or lower(recording_filename) like :search ";
			$sql .= "	or lower(recording_description) like :search ";
			$sql .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$sql .= order_by($order_by, $order, 'recording_name', 'asc');
		$sql .= limit_offset($rows_per_page, $offset);
		$recordings = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);
		return $recordings;
	}

	/*
	*	 Voicemail Page List
	*/
	function voicemail_page() {

	//set the voicemail uuid array
	if (isset($_SESSION['user']['voicemail'])) {
		foreach ($_SESSION['user']['voicemail'] as $row) {
			if (!empty($row['voicemail_uuid'])) {
				$voicemail_uuids[]['voicemail_uuid'] = $row['voicemail_uuid'];
			}
		}
	}
	else {
		$voicemail = new voicemail;
		$rows = $voicemail->voicemails();
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$voicemail_uuids[]['voicemail_uuid'] = $row['voicemail_uuid'];
			}
		}
		unset($voicemail, $rows, $row);
	}

		$order_by = $this->order_by ?? 'voicemail_id';
		$order = $this->order ?? 'asc';
		$sort = $order_by == 'voicemail_id' ? 'natural' : null;

		if (!empty($this->search)) {
			$sql_search = "and (";
			$sql_search .= "	lower(cast(voicemail_id as text)) like :search ";
			$sql_search .= " 	or lower(voicemail_mail_to) like :search ";
			$sql_search .= " 	or lower(voicemail_local_after_email) like :search ";
			$sql_search .= " 	or lower(voicemail_enabled) like :search ";
			$sql_search .= " 	or lower(voicemail_description) like :search ";
			$sql_search .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}

		//get total ivr_menu count
		$sql = "select count(voicemail_uuid) from v_voicemails ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('voicemail_all')) {
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!permission_exists('voicemail_domain')) {
			if (is_array($voicemail_uuids) && @sizeof($voicemail_uuids) != 0) {
				$sql .= "and (";
				foreach ($voicemail_uuids as $x => $row) {
					$sql_where_or[] = 'voicemail_uuid = :voicemail_uuid_'.$x;
					$parameters['voicemail_uuid_'.$x] = $row['voicemail_uuid'];
				}
				if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
					$sql .= implode(' or ', $sql_where_or);
				}
				$sql .= ")";
			}
			else {
				$sql .= "and voicemail_uuid is null ";
			}
		}
		$sql .= $sql_search ?? '';
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('voicemail_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		$sql = "select voicemail_uuid as uuid, voicemail_id as name, '99' || voicemail_id  || ' XML ' || domain_name as xml from v_voicemails as e left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('voicemail_all')) {
			$sql .= "and (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!permission_exists('voicemail_domain')) {
			if (is_array($voicemail_uuids) && @sizeof($voicemail_uuids) != 0) {
				$sql .= "and (";
				foreach ($voicemail_uuids as $x => $row) {
					$sql_where_or[] = 'voicemail_uuid = :voicemail_uuid_'.$x;
					$parameters['voicemail_uuid_'.$x] = $row['voicemail_uuid'];
				}
				if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
					$sql .= implode(' or ', $sql_where_or);
				}
				$sql .= ")";
			}
			else {
				$sql .= "and voicemail_uuid is null ";
			}
		}
		$sql .= $sql_search ?? '';
		$sql .= order_by($order_by, $order, null, null, $sort);
		$sql .= limit_offset($rows_per_page, $offset);

		$voicemails = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);
		return $voicemails;
	}

	/*
	*	 Callback Route Page List
	*/
	function callback_route_page() {

		$order_by = $this->order_by ?? '';
		$order = $this->order ?? '';


		//get total ivr_menu count
		$sql = "select count(voicemail_uuid) from v_callback_routes ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('callback_route_all')) {
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('callback_route_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		$sql = "select callback_route_uuid as uuid, callback_route_name as name, callback_route_extension  || ' XML ' || domain_name as xml from v_callback_routes as e left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('callback_route_all')) {
			$sql .= "and (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		$sql .= order_by($order_by, $order, null, null, null);
		$sql .= limit_offset($rows_per_page, $offset);

		$callback_routes = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);
		return $callback_routes;
	}

	/*
	*	 Conference Page List
	*/
	function conference_page() {

		$order_by = $this->order_by ?? '';
		$order = $this->order ?? '';
		$sort = $order_by == 'conference_extension' ? 'natural' : null;

		if (!empty($this->search)) {
			$sql_search = "and (";
			$sql_search .= "lower(conference_name) like :search ";
			$sql_search .= "or lower(conference_extension) like :search ";
			$sql_search .= "or lower(conference_pin_number) like :search ";
			$sql_search .= "or lower(conference_description) like :search ";
			$sql_search .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}

		//get total ivr_menu count
		if (permission_exists('conference_view')) {
			//show all extensions
			$sql = "select count(*) from v_conferences ";
			$sql .= "where true ";
			if ($this->show != "all" || !permission_exists('conference_all')) {
				$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
				$parameters['domain_uuid'] = $this->domain_uuid;
			}
		}
		else {
			//show only assigned extensions
			$sql = "select count(*) from v_conferences as c, v_conference_users as u ";
			$sql .= "where c.conference_uuid = u.conference_uuid ";
			$sql .= "and c.domain_uuid = :domain_uuid ";
			$sql .= "and u.user_uuid = :user_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$parameters['user_uuid'] = $this->user_uuid;
		}
		$sql .= $sql_search ?? '';
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('conference_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		//get the list
		if (permission_exists('conference_view')) {
			//show all extensions		
			$sql  = "select conference_uuid as uuid, conference_name as name, conference_extension || ' XML ' || domain_name as xml from v_conferences as e left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid ";
			$sql .= "where true ";
			if ($this->show != "all" || !permission_exists('conference_all')) {
				$sql .= "and (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
				$parameters['domain_uuid'] = $this->domain_uuid;
			} 
		}
		else {
			//show only assigned extensions
			$sql = "select c.conference_uuid as uuid, conference_name as name, conference_extension || ' XML ' || domain_name as xml from v_conferences as c left join v_domains as dmns on dmns.domain_uuid = c.domain_uuid left join v_conference_users as u ";
			$sql .= "on c.conference_uuid = u.conference_uuid ";
			$sql .= "and c.domain_uuid = :domain_uuid ";
			$sql .= "and u.user_uuid = :user_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$parameters['user_uuid'] = $this->user_uuid;
		}
		$sql .= $sql_search ?? '';
		$sql .= order_by($order_by, $order, null, null, $sort);
		$sql .= limit_offset($rows_per_page, $offset);

		$conferences = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);
		return $conferences;
	}

	/*
	*	 Conference Centers Page List
	*/
	function conference_center_page() {

		$order_by = $this->order_by ?? '';
		$order = $this->order ?? '';
		$sort = $order_by == 'conference_center_extension' ? 'natural' : null;

		if (!empty($this->search)) {
			$sql_search = "and ( ";
			$sql_search .= "lower(conference_center_name) like :search ";
			$sql_search .= "or lower(conference_center_extension) like :search ";
			$sql_search .= "or lower(conference_center_greeting) like :search ";
			$sql_search .= "or lower(conference_center_description) like :search ";
			$sql_search .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}

		//show all extensions
		$sql = "select count(*) from v_conference_centers ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('conference_center_all')) {
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		
		$sql .= $sql_search ?? '';
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('conference_center_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		//get the list
		$sql  = "select conference_center_uuid as uuid, conference_center_name as name, conference_center_extension  || ' XML ' || domain_name as xml from v_conference_centers as e left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid ";
		$sql .= "where true ";
		if ($this->show != "all" || !permission_exists('conference_center_all')) {
			$sql .= "and (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		} 
		$sql .= $sql_search ?? '';
		$sql .= order_by($order_by, $order, null, null, $sort);
		$sql .= limit_offset($rows_per_page, $offset);

		$conferences = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);
		return $conferences;
	}

	/*
	*	 Time Conditions Page List
	*/
	function time_condition_page() {

		$order_by = $this->order_by ?? 'dialplan_name';
		$order = $this->order ?? 'asc';
		$sort = $order_by == 'dialplan_number' ? 'natural' : null;

		//show all extensions
		$sql = "select count(dialplan_uuid) from v_dialplans ";
		if ($this->show == "all" && permission_exists('time_condition_all')) {
			$sql .= "where true ";
		} else {
			$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($this->search)) {
			$search = strtolower($search);
			$sql .= "and (";
			$sql .= " 	lower(dialplan_context) like :search ";
			$sql .= " 	or lower(dialplan_name) like :search ";
			$sql .= " 	or lower(dialplan_number) like :search ";
			$sql .= " 	or lower(dialplan_continue) like :search ";
			$sql .= " 	or lower(dialplan_enabled) like :search ";
			$sql .= " 	or lower(dialplan_description) like :search ";
			$sql .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$sql .= "and app_uuid = '4b821450-926b-175a-af93-a03c441818b1' ";
		$sql .= $sql_search ?? null;
		$database = new database;
		$num_rows = $database->select($sql, $parameters ?? null, 'column');

		//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = $this->search ? "&search=".$this->search : null;
		$param = ($this->show == "all" && permission_exists('time_condition_all')) ? "&show=all" : null;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $this->page;

		//get the list
		$sql  = "select dialplan_uuid as uuid, dialplan_name as name, dialplan_number  || ' XML ' || domain_name as xml from v_dialplans as e left join v_domains as dmns on dmns.domain_uuid = e.domain_uuid ";
		if ($this->show == "all" && permission_exists('time_condition_all')) {
			$sql .= "where true ";
		} else {
			$sql .= "where (e.domain_uuid = :domain_uuid or e.domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
		}
		if (!empty($this->search)) {
			$search = strtolower($search);
			$sql .= "and (";
			$sql .= " 	lower(dialplan_context) like :search ";
			$sql .= " 	or lower(dialplan_name) like :search ";
			$sql .= " 	or lower(dialplan_number) like :search ";
			$sql .= " 	or lower(dialplan_continue) like :search ";
			$sql .= " 	or lower(dialplan_enabled) like :search ";
			$sql .= " 	or lower(dialplan_description) like :search ";
			$sql .= ") ";
			$parameters['search'] = '%'.$this->search.'%';
		}
		$sql .= "and e.app_uuid = '4b821450-926b-175a-af93-a03c441818b1' ";
		$sql .= $sql_search ?? '';
		$sql .= order_by($order_by, $order, null, null, $sort);
		$sql .= limit_offset($rows_per_page, $offset);

		$dialplans = $database->select($sql, $parameters ?? null, 'all');
		unset($sql, $parameters);
		return $dialplans;
	}

	/*
	*	███████╗██╗   ██╗███╗   ██╗ ██████╗████████╗██╗ ██████╗ ███╗   ██╗ █╗ ███████╗
	*	██╔════╝██║   ██║████╗  ██║██╔════╝╚══██╔══╝██║██╔═══██╗████╗  ██║ █║ ██╔════╝
	*	█████╗  ██║   ██║██╔██╗ ██║██║        ██║   ██║██║   ██║██╔██╗ ██║ ╚╝ ███████╗
	*	██╔══╝  ██║   ██║██║╚██╗██║██║        ██║   ██║██║   ██║██║╚██╗██║    ╚════██║
	*	██║     ╚██████╔╝██║ ╚████║╚██████╗   ██║   ██║╚██████╔╝██║ ╚████║    ███████║
	*	╚═╝      ╚═════╝ ╚═╝  ╚═══╝ ╚═════╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝    ╚══════╝
	*/																		  

	/*
	*	Find by Name
	*   uuid: 'b0993330-70fe-4ccd-8b0f-1f0e97b824d6', xml: '7001 XML test.ftpbx.net' }
	*	Return List of Array with data: uuid, name(ext/number/code), in_used
	*/
	function findbyXmlName($uuid, $xml) {
		$result = [];
    	$main_uuid = $uuid;
    	$xmlToMatch = $xml;
    	$inUsed = [];
    	$matchCount = 0;
    	foreach ($this->actionsList as $_item) {
    	    $actions = $_item['actions'];
    	    if (!empty($actions) && str_starts_with($actions, '[') && str_ends_with($actions, ']')) {
    	        $decodedActions = json_decode($actions, true);
    	        if (is_array($decodedActions)) {
    	            foreach ($decodedActions as $action) {
    	                if (isset($action['destination_data']) && str_contains($action['destination_data'], $xmlToMatch)) {
    	                    $inUsed[] = [
    	                        'typecode' => $_item['typecode'],
    	                        'uuid' => $_item['uuid'],
    	                        'name' => $_item['name']
    	                    ];
    	                    $matchCount++;
    	                    break;
    	                }
    	            }
    	        }
    	    } elseif (str_contains($actions, $xmlToMatch)) {
    	        $inUsed[] = [
    	            'typecode' => $_item['typecode'],
    	            'uuid' => $_item['uuid'],
    	            'name' => $_item['name']
    	        ];
    	        $matchCount++;
    	    }
    	}
    	$result[] = [
    	    "uuid" => $main_uuid,
    	    "in_used" => $inUsed,
    	    "count" => $matchCount
    	];
		return $result;
	}


	/*
	*	Find by Array of Names
	*   Array format must be: [ { uuid: 'b0993330-70fe-4ccd-8b0f-1f0e97b824d6', xml: '7001 XML test.ftpbx.net' }, { uuid: '...', xml: '...' }, ... ]
	*	Return List of Array with data: uuid, name(ext/number/code), in_used
	*/
	
	function findbyArraysWithNames($array) {
		$result = [];
		foreach ($array as $item) {
    	    $main_uuid = $item['uuid'];
    	    $xmlToMatch = $item['xml'];
    	    $inUsed = [];
    	    $matchCount = 0;
    	    foreach ($this->actionsList as $_item) {
    	        $actions = $_item['actions'];
    	        if (!empty($actions) && str_starts_with($actions, '[') && str_ends_with($actions, ']')) {
    	            $decodedActions = json_decode($actions, true);
    	            if (is_array($decodedActions)) {
    	                foreach ($decodedActions as $action) {
    	                    if (isset($action['destination_data']) && preg_match('/\b' . preg_quote($xmlToMatch, '/') . '\b/', $action['destination_data'])) {
    	                        $inUsed[] = [
    	                            'typecode' => $_item['typecode'],
    	                            'uuid' => $_item['uuid'],
    	                            'name' => $_item['name']
    	                        ];
    	                        $matchCount++;
    	                        break;
    	                    }
    	                }
    	            }
    	        } elseif (preg_match('/\b' . preg_quote($xmlToMatch, '/') . '\b/', $actions)) {
    	            $inUsed[] = [
    	                'typecode' => $_item['typecode'],
    	                'uuid' => $_item['uuid'],
    	                'name' => $_item['name']
    	            ];
    	            $matchCount++;
    	        }
    	    }
    	    $result[] = [
    	        "uuid" => $main_uuid,
    	        "in_used" => $inUsed,
    	        "count" => $matchCount
    	    ];
    	}
		return $result;
	}

	/*
	*	Only Page Usage
	*	Return List of Array with data: url(to click it), Name(ext/number/code), in_used and etc
	*
	*/
	function get($code) {
		if ($_SESSION['in_use'][$code.'s_enabled']['boolean'] != 'true') {
			return [];
		}
		switch ($code) {
			case 'ivr_menu':
				return $this->findbyArraysWithNames($this->ivr_menu_page());
				break;

			case 'extension':
				return $this->findbyArraysWithNames($this->extension_page());
				break;

			case 'ring_group':
				return $this->findbyArraysWithNames($this->ring_group_page());
				break;

			case 'call_center_queue':
				return $this->findbyArraysWithNames($this->call_center_queue_page());
				break;

			case 'call_flow':
				return $this->findbyArraysWithNames($this->call_flow_page());
				break;

			case 'recording':
				return $this->findbyArraysWithNames($this->recording_page());
				break;

			case 'voicemail':
				return $this->findbyArraysWithNames($this->voicemail_page());
				break;

			case 'callback_route':
				return $this->findbyArraysWithNames($this->callback_route_page());
				break;

			case 'conference':
				return $this->findbyArraysWithNames($this->conference_page());
				break;

			case 'conference_center':
				return $this->findbyArraysWithNames($this->conference_center_page());
				break;

			case 'time_condition':
				return $this->findbyArraysWithNames($this->time_condition_page());
				break;

			default:
				return [];
				break;
		}
	}

	function render($typeCode) {
		if ($_SESSION['in_use'][$typeCode.'s_enabled']['boolean'] != 'true') {
			return null;
		}
		$_html  = "<script>";
		$_html .= "    const column_inject_number = 2;";
		$_html .= "    const columnLoading = `<td id='in_used_' class='in_used_'><i class=\"fas fa-spinner spinner_sync\"></i>`;";
		$_html .= "    document.querySelector('.list-header').children[document.querySelector('.list-header').children?.length - column_inject_number].insertAdjacentHTML('beforeBegin', \"<th id='in_used_header'>In Use</th>\");";
		$_html .= "    [...document.querySelectorAll(`td.checkbox input[type=\"hidden\"]`)].map((input) => input.closest('tr').children[input.closest('tr').children?.length - column_inject_number].insertAdjacentHTML('beforeBegin', columnLoading));";
		$_html .= "    function caseEditUrl(item) {";
		$_html .= "        switch (item?.typecode) {";
		$_html .= "            case 'call_center_queue':";
		$_html .= "				return `<a href='".PROJECT_PATH."/app/call_centers/\${item.typecode}_edit.php?id=\${item.uuid}'>\${item.name}</a>`;";
		$_html .= "                break;";
		$_html .= "			default:";
		$_html .= "				return `<a href='".PROJECT_PATH."/app/\${item.typecode}s/\${item.typecode}_edit.php?id=\${item.uuid}'>\${item.name}</a>`;";
		$_html .= "				break;";
		$_html .= "		}";
		$_html .= "    }";
		$_html .= "    ";
		$_html .= "    function capitalizeFirstLetter(string) {";
		$_html .= "        if (!string) return string;";
		$_html .= "        function capitalize(str) {";
		$_html .= "            return str.charAt(0).toUpperCase() + str.slice(1);";
		$_html .= "        }";
		$_html .= "        return string.split('_').map((str) => capitalize(str)).join(' ');";
		$_html .= "    }";
		$_html .= "    function injectUsageData({ data }) {";
		$_html .= "        if (data?.length > 0) {";
		$_html .= "            data?.map((item) => {";
		$_html .= "                const groupedByTypecode = item?.in_used?.reduce((acc, item) => {";
		$_html .= "                    if (!acc[item.typecode]) {";
		$_html .= "                        acc[item.typecode] = []; ";
		$_html .= "                    }";
		$_html .= "                    acc[item.typecode].push(item);";
		$_html .= "                    return acc;";
		$_html .= "                }, {});";
		$_html .= "                const grouped = Object.keys(groupedByTypecode)?.map(key => ({";
		$_html .= "                    typecode: key,";
		$_html .= "                    items: groupedByTypecode[key]";
		$_html .= "                }));";
		$_html .= "                const column = `";
		$_html .= "                <td id='in_used_' class='in_used_'><div class=\"popup-container-info\">";
		$_html .= "                            <span class=\"trigger-popup-info\">";
		$_html .= "                                \${item?.in_used[0]?.name ? ((item?.count == 1 ? caseEditUrl(item.in_used[0]):item?.in_used[0]?.name) + ' ' + (item?.count > 1 ? '['+item?.count+']':'')) : ' - '}";
		$_html .= "                            </span>";
		$_html .= "                            \${item?.count > 1 ? (";
		$_html .= "                                `<div class=\"modal-info\">";
		$_html .= "                                    <div class=\"modal-info-content\">";
		$_html .= "                                        <ul style=\"list-style-type: none; padding: 0; margin: 0;\">";
		$_html .= "                                        \${grouped?.map((item) => {";
		$_html .= "                                                return ('<li style=\"padding: 0px 0 0px 0px;text-align: left;\"><div style=\"font-weight: bold;\">'+capitalizeFirstLetter(item?.typecode)+` [\${item?.items?.length}]:</div></li>`+item?.items?.map((action) => {";
		$_html .= "                                                    return (";
		$_html .= "                                                        '<li class=\"li-url-link-action\">'+caseEditUrl(action)+'</li>'";
		$_html .= "                                                )}";
		$_html .= "                                            ).join(''));";
		$_html .= "                                        }).join('')}";
		$_html .= "                                        </ul>";
		$_html .= "                                    </div>";
		$_html .= "                                </div>`";
		$_html .= "                            ) : ''}";
		$_html .= "                        </div>";
		$_html .= "                    </div>";
		$_html .= "                </td>`;";
		$_html .= "                document.querySelector(`td.checkbox input[type=\"hidden\"][value=\"\${item.uuid}\"]`)?.closest('tr').children[document.querySelector(`td.checkbox input[type=\"hidden\"][value=\"\${item.uuid}\"]`)?.closest('tr').children?.length - column_inject_number - 1].remove();";
		$_html .= "                document.querySelector(`td.checkbox input[type=\"hidden\"][value=\"\${item.uuid}\"]`)?.closest('tr').children[document.querySelector(`td.checkbox input[type=\"hidden\"][value=\"\${item.uuid}\"]`)?.closest('tr').children?.length - column_inject_number].insertAdjacentHTML('beforeBegin', column);";
		$_html .= "            });";
		$_html .= "        }";
		$_html .= "    }";
		$_html .= "	$.ajax({";
		$_html .= "		url: \"/app/usages/service.php?method=getUsages\",";
		$_html .= "		type: \"POST\",";
		$_html .= "		cache: true,";
		$_html .= "		data: {";
		$_html .= "			case: '".$typeCode."',";
		$_html .=            !empty($_REQUEST['show']) ? ('show: "'.$_REQUEST['show'].'",') : null;
		$_html .=            !empty($_REQUEST['domain_uuid']) ? ('domain_uuid: "'.$_REQUEST['domain_uuid'].'",') : null;
		$_html .=            !empty($_REQUEST['user_uuid']) ? ('user_uuid: "'.$_REQUEST['user_uuid'].'",') : null;
		$_html .=            !empty($_REQUEST['search']) ? ('search: "'.$_REQUEST['search'].'",') : null;
		$_html .=            !empty($_REQUEST['page']) ? ('page: "'.$_REQUEST['page'].'",') : null;
		$_html .= "		},";
		$_html .= "		success: async function (response) {";
		$_html .= "			const res = JSON?.parse(response?.replaceAll(\"/\/\", \"\"));";
		$_html .= "            if (res?.data) {";
		$_html .= "                injectUsageData(res);";
		$_html .= "            }";
		$_html .= "		}";
		$_html .= "	});";
		$_html .= "</script>";
		$_html .= "<style>";
		$_html .= ".popup-container-info {";
		$_html .= "    position: relative;";
		$_html .= "    display: inline-block;";
		$_html .= "}";
		$_html .= ".trigger-popup-info {";
		$_html .= "    cursor: pointer;";
		$_html .= "}";
		$_html .= ".modal-info {";
		$_html .= "    display: none;";
		$_html .= "    position: absolute;";
		$_html .= "    bottom: 30%; ";
		$_html .= "    left: 92.5%;";
		$_html .= "    background-color: white;";
		$_html .= "    border: 1px solid #ccc;";
		$_html .= "    border-radius: 5px;";
		$_html .= "    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);";
		$_html .= "    z-index: 100001;";
    	$_html .= "	  max-height: 15rem;";
    	$_html .= "	  overflow-y: auto;";
		$_html .= "}";
		$_html .= ".popup-container-info:hover .modal-info {";
		$_html .= "    display: block;";
		$_html .= "}";
		$_html .= ".modal-info-content {";
		$_html .= "    padding: 5px;";
		$_html .= "    text-align: center;";
		$_html .= "    cursor: default;";
		$_html .= "    color: #111;";
		$_html .= "}";
		$_html .= ".li-url-link-action {";
		$_html .= "    color: #004083;";
		$_html .= "    padding: 2.5px 5px 2.5px 3rem;";
		$_html .= "    text-align: left;";
		$_html .= "    width: max-content;";
		$_html .= "}";
		$_html .= ".li-url-link-action a {";
		$_html .= "    color: #004083 !important;";
		$_html .= "}";
		$_html .= ".li-url-link-action:hover a {";
		$_html .= "    color:rgb(0, 44, 90) !important;";
		$_html .= "    text-decoration: underline;";
		$_html .= "}";
		$_html .= ".spinner_sync {";
		$_html .= "	animation: spinnerSync 1s linear infinite;";
		$_html .= "}";
		$_html .= "@keyframes spinnerSync {";
		$_html .= "	0% {";
		$_html .= "		transform: rotate(0deg);";
		$_html .= "	}";
		$_html .= "	25% {";
		$_html .= "		transform: rotate(90deg);";
		$_html .= "	}";
		$_html .= "	50% {";
		$_html .= "		transform: rotate(180deg);";
		$_html .= "	}";
		$_html .= "	75% {";
		$_html .= "		transform: rotate(270deg);";
		$_html .= "	}";
		$_html .= "	100% {";
		$_html .= "		transform: rotate(360deg);";
		$_html .= "	}";
		$_html .= "}";
		$_html .= "	div.card {";
		$_html .= "		overflow: unset;";
		$_html .= "	}";
		$_html .= "</style>";
		echo $_html;
	}
}

?>