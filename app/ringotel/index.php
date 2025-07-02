<?php
/*
    Ringotel Integration for FusionPBX
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

require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";
require_once "resources/header.php";

if (permission_exists("ringotel") || if_group("superadmin")) {
	//access granted
} else {
	echo "access denied";
	exit;
}

$language = new text;
$text = $language->get();

// for softphonepage
function getLessThan30($str, $prefix)
{
	$lengthDomainNamePlusPrefix = strlen($str . $prefix);
	if ($lengthDomainNamePlusPrefix > 30) {
		$new_text = preg_replace('/([b-df-hj-np-tv-z])([b-df-hj-np-tv-z])/i', '$1-$2', $str);
		$exploded = explode('-', $new_text);
		array_pop($exploded);
		$Next = implode($exploded);
		return getLessThan30($Next, $prefix);
	} else {
		return $str;
	}
}

//additional includes
require_once "resources/paging.php";

echo "<script language='JavaScript' type='text/javascript' src='" . PROJECT_PATH . "/resources/javascript/qrcode/qrcode.min.js'></script>\n";
echo "<script language='JavaScript' type='text/javascript' src='" . PROJECT_PATH . "/resources/javascript/html-to-image.min.js'></script>\n";
echo "<script language='JavaScript' type='text/javascript' src='" . PROJECT_PATH . "/resources/javascript/multiselect-dropdown.js'></script>\n";

// ORG INIT ERROR
echo '	<div id="not_exist_organization_note" class="alert alert-warning alert-dismissible fade show" style="display: none;" role="alert">	';
echo '	  <strong>You don\'t have an organization account.</strong> You should check it. If you already have, just try refresh page.';
echo '	  <button type="button" class="close" data-dismiss="alert" aria-label="Close"> ';
echo '	    <span aria-hidden="true">&times;</span> ';
echo '	  </button> ';
echo '	</div> ';

// LOADING INIT
echo '	<div id="init_loading" class="justify-content-center" style="display: flex;padding: 8rem;font-size: large;vertical-align: middle;flex-direction: row;align-items: center;align-content: center;flex-wrap: nowrap;">	';
echo ' 		<span class="spinner-grow spinner-grow-sm" role="status" style="width: 2rem;height: 2rem;" aria-hidden="true"></span>	';
echo ' 		<span>Organization loading...</span>';
echo '	</div>	';

// ORGANIZATION INIT ERROR
echo '	<div id="not_exist_organization" class="jumbotron" style="display: none;background-color: white; border: 0px solid #b5b5b5;">	';

echo '	  <p class="lead">Organization</p>	';
echo '	  <hr>	';
echo '	  <p>Use Admin Shell to create your own connection for organization.</p>	';
echo '	  <p class="lead">	';
echo '		<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#createOrgModal">';
echo '			<span id="create_org_text">Create</span>';
echo '			<span id="create_org_loading" style="display: none;">';
echo ' 				<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo ' 				Loading...	';
echo '			</span>';
echo '		</button>';
echo '	  </p>	';
echo '	</div>	';

// ORGANIZATION INIT SUCCESSFUL

echo '<nav id="organizations" style="display:none;"> ';
echo '  <div class="nav nav-pills" id="nav-tab" role="tablist"> ';
echo '    	<a class="nav-item nav-link active" id="nav-org-tab" style="padding: 0.5rem 1rem 0.5rem 0.2rem;cursor: default;"';
// echo '			data-toggle="tab" href="#nav-org" role="tab" aria-controls="nav-org" aria-selected="true"';
echo '			>';
echo '			<h4 class="card-title" style="font-weight: 400;padding: 0.5rem 0rem 0rem 0rem;">Organization</h4>';
echo '		</a> ';

// icon for back to ORG
echo '    	<a class="nav-item nav-link active" id="nav-org-tab-back" style="padding: 0.5rem 1rem 0.5rem 0.2rem;display:none;">';
echo '			<h4 class="card-title" style="font-weight: 400;padding: 0.5rem 0rem 0rem 0rem;"><i class="fa fa-angle-left" aria-hidden="true"></i></h4>';
echo '		</a> ';

echo '    	<a class="nav-item nav-link" id="nav-integration-tab" style="cursor: pointer;" style="padding: 0.5rem 1rem 0.5rem 0.2rem;">';
// echo '			data-toggle="tab" href="#nav-integration" role="tab" aria-controls="nav-integration" aria-selected="false" ';
echo '				';
echo '			<h5 class="card-title" style="font-weight: 400;padding: 0.65rem 0rem 0rem 0rem;border-bottom: 1px solid #80808045;">Integration</h5>';
echo '		</a> ';
echo '  </div> ';
echo '</nav> ';
echo '<div class="tab-content" id="nav-tabContent"> ';
echo '  <div class="tab-pane show active" id="nav-org" role="tabpanel" aria-labelledby="nav-org-tab">';
/////////////////////////////////////////////////////////////////////////
////// ENTRY POINT FOR ORGANIZATION, CONNECTIONS, USERS DOM ELEMENTS ////
/////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


// echo ' <h4 class="card-title" style="font-weight: 400; padding: 0.5rem 0.2rem;display:none;" id="organizations">Organization</h4>';
echo '	 <div id="exist_organization" style="display: none;margin-top: 16px;">';
echo '		<div class="card" style="width: 20rem;">';
echo '	   		<div class="card-body card-body-p">';
echo '				<div style="display: flex; flex-direction: row;align-items: center; justify-content: space-between;">';
echo '					<h5 class="card-title" id="organization_name" style="font-weight: 200;font-size: 14pt;overflow: hidden;text-overflow: ellipsis;text-wrap: nowrap;align-items: center;align-items: center;color: #007bff;margin: 0rem;"></h5>';
echo '					<div id="packageid_switcher_container">';
echo '					</div>';
echo '					<div style="position: absolute;bottom: 0;right: 0;padding: 1rem;">';
echo '						<a id="delete_organization" style="cursor: pointer;margin-bottom: 0rem;padding: 0.45rem;"><i class="fa fa-trash" aria-hidden="true"></i></a>';
echo '						<span id="deleting_org_loading" style="display: none;">';
echo '	 						<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo '	 						Deleting...	';
echo '						</span>';
echo '					</div>';
echo '				</div>';
echo '	     		<p class="card-text" id="organization_domain" style="font-family: monospace;font-weight: 700;"></p>';
echo '				<p class="card-text"><small class="text-muted" id="organization_created"></small></p>';
echo '	   		</div>';
echo '		</div>';
echo '	 </div>';


// CONNECTION CONTAINER
echo '	<div id="connection_module" style="display:none;">';
echo '	 	<div style="display: flex; padding: 1rem 0rem;justify-content: flex-end;flex-direction: row;justify-content: space-between;">';
echo '			<h4 class="card-title" style="font-weight: 400; padding: 0.5rem 0.2rem;margin: 0;" id="connections">Connections</h4>';
echo '     		<button class="btn btn-primary" style="color: #007bff;background-color: transparent;border-color: transparent;font-weight: bold;" data-toggle="modal" data-target="#createConnectionModal">';
echo '     			<span id="create_connect_text" class="create_connect_text">+ Add Connection</span>';
echo '				<span id="create_connect_loading" class="create_connect_loading" style="display: none;">';
echo ' 					<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo ' 					Loading...	';
echo '				</span>';
echo '			</button>';
echo '		</div>';

echo '		<div id="group_exist_connections" style="display: none;">';
echo '			<div id="group_exist_connections_container" style="display: flex; flex-direction: row;flex-direction: row;flex-wrap: wrap;gap: 10px;">';
// here we receive the connections
echo '			</div>';
echo '		</div>';
echo ' </div>';


// PARKS
echo '	<div id="parks_module" style="display: none;">';
echo '		<div style="display: flex; padding: 1rem 0rem;flex-direction: row;justify-content: space-between;">';
echo '			<h4 class="card-title" style="font-weight: 400; padding: 0.5rem 0.2rem;margin: 0;" id="parks">Parking Slots <span id="parks_count"></span></h4>';
echo '			<div style="margin-left: auto;">';
echo '			<button id="parks_save" class="btn btn-outline-primary parks_save" style="text-overflow: ellipsis;white-space: nowrap;border: 0px;padding: 2px 10px;margin: 10px;align-items: center;display: none;bottom: 10px;right: 130px;;font-size: 12pt;font-weight: 600;"> ';
echo '				Update Visual Call Park  ';
echo '			</button> ';
echo '			<button id="parks_close_updating" class="btn btn-outline-primary parks_close_updating" style="text-overflow: ellipsis;white-space: nowrap;display: none;border: 0px;padding: 2px 10px;margin: 10px;align-items: center;bottom: 10px;right: 40px;font-size: 12pt;font-weight: 600;color: gray;"> ';
echo '				Close ';
echo '			</button> ';
echo '			<span id="parks_save_loading" style="text-overflow: ellipsis;white-space: nowrap;border: 0px;padding: 2px 10px;margin: 10px;align-items: center;display: none;bottom: 10px;right: 100px;font-size: 12pt;font-weight: 600;"> ';
echo '				<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span> ';
echo '				Loading...	 ';
echo '			</span> ';
echo '			<button id="parks_update" class="btn btn-outline-secondary parks_update" style="text-overflow: ellipsis;white-space: nowrap;border: 0px;padding: 2px 10px;margin: 10px;align-items: center;display: flex;bottom: 10px;right: 100px;font-size: 12pt;font-weight: 600;"> ';
echo '				Edit ';
echo '			</button> ';
echo '			</div>';
echo '     		<button data-toggle="modal" data-target="#createParksModal" id="create_parks_modal_button" class="btn btn-primary" style="text-overflow: ellipsis;white-space: nowrap;align-items: center;color: #007bff;background-color: transparent;border-color: transparent;font-weight: bold;">';
echo '     			<span id="create_parks_modal_text">+ Add Parks</span>';
echo '				<span id="create_parks_modal_loading" style="display: none;">';
echo ' 					<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo ' 					Loading...	';
echo '				</span>';
echo '			</button>';
echo '		</div>';
echo '		<div id="group_exist_parks" style="display: none;">';
echo '			<div id="group_exist_parks_list" style="flex-direction: row;display: flex;flex-wrap: wrap;gap: 10px;">';
// here we receive the connections
echo '			</div>';
echo '		</div>';
echo '</div>';

// USERS
echo '		<div id="extension_module" style="display: none;">';

// Head of line with header and buttons
echo '			<div style="display: flex; padding: 1rem 0rem;flex-direction: row;justify-content: space-between;">';
echo '				<h4 class="card-title" style="font-weight: 400; padding: 0.5rem 0.2rem;margin: 0;" id="users">Users';
echo '					<span id="users_count">';
echo '					</span>';
echo '				</h4>';
echo '				<div class="reSyncAllNames" style="margin-left: auto;display: flex;align-items: center;color: #2196f3;cursor: pointer;padding: 8px;opacity: 0.75;" alt="Resync Names">';
echo '					<svg fill="#007bff" width="15px" height="15px" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">';
echo '						<path d="M370.72 133.28C339.458 104.008 298.888 87.962 255.848 88c-77.458.068-144.328 53.178-162.791 126.85-1.344 5.363-6.122 9.15-11.651 9.15H24.103c-7.498 0-13.194-6.807-11.807-14.176C33.933 94.924 134.813 8 256 8c66.448 0 126.791 26.136 171.315 68.685L463.03 40.97C478.149 25.851 504 36.559 504 57.941V192c0 13.255-10.745 24-24 24H345.941c-21.382 0-32.09-25.851-16.971-40.971l41.75-41.749zM32 296h134.059c21.382 0 32.09 25.851 16.971 40.971l-41.75 41.75c31.262 29.273 71.835 45.319 114.876 45.28 77.418-.07 144.315-53.144 162.787-126.849 1.344-5.363 6.122-9.15 11.651-9.15h57.304c7.498 0 13.194 6.807 11.807 14.176C478.067 417.076 377.187 504 256 504c-66.448 0-126.791-26.136-171.315-68.685L48.97 471.03C33.851 486.149 8 475.441 8 454.059V320c0-13.255 10.745-24 24-24z"/>';
echo '					</svg>';
echo '					<div id="resync_names" style="padding: 5px 10px;font-weight: 700;font-size: 10pt;width: 146px;">';
echo '						re-Sync Extensions';
echo '					</div>';
echo '				</div>';
echo '				<div class="reSyncAllPassword" style="display: flex;align-items: center;color: #2196f3;cursor: pointer;padding: 8px;opacity: 0.75;" alt="Resync Password">';
echo '					<svg fill="#007bff" width="15px" height="15px" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">';
echo '						<path d="M370.72 133.28C339.458 104.008 298.888 87.962 255.848 88c-77.458.068-144.328 53.178-162.791 126.85-1.344 5.363-6.122 9.15-11.651 9.15H24.103c-7.498 0-13.194-6.807-11.807-14.176C33.933 94.924 134.813 8 256 8c66.448 0 126.791 26.136 171.315 68.685L463.03 40.97C478.149 25.851 504 36.559 504 57.941V192c0 13.255-10.745 24-24 24H345.941c-21.382 0-32.09-25.851-16.971-40.971l41.75-41.749zM32 296h134.059c21.382 0 32.09 25.851 16.971 40.971l-41.75 41.75c31.262 29.273 71.835 45.319 114.876 45.28 77.418-.07 144.315-53.144 162.787-126.849 1.344-5.363 6.122-9.15 11.651-9.15h57.304c7.498 0 13.194 6.807 11.807 14.176C478.067 417.076 377.187 504 256 504c-66.448 0-126.791-26.136-171.315-68.685L48.97 471.03C33.851 486.149 8 475.441 8 454.059V320c0-13.255 10.745-24 24-24z"/>';
echo '					</svg>';
echo '					<div id="resync_password" style="padding: 5px 10px;font-weight: 700;font-size: 10pt;width: 143px;">';
echo '						re-Sync Passwords';
echo '					</div>';
echo '				</div>';
echo '	     		<button data-toggle="modal" data-target="#createUserModal" id="create_users_button" class="btn btn-primary create_users_button" style="width: 161px;color: #007bff;background-color: transparent;border-color: transparent;font-weight: bold;">';
echo '	     			<span id="create_users_text" class="create_users_text">+ Add Extensions</span>';
echo '					<span id="create_users_loading" class="create_users_loading" style="display: none;">';
echo '	 					<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo '	 					Loading...	';
echo '					</span>';
echo '				</button>';
echo '			</div>';

// Users
echo '			<div id="group_exist_users" style="display: none;">';
echo '				<div id="group_exist_users_list" style="flex-direction: row;display: flex;flex-wrap: wrap;gap: 10px;">';
// here we receive the connections
echo '				</div>';
echo '			</div>';

// Extensions
echo '			<div id="group_exist_extensions" style="display: none;">';
echo '				<div style="display: flex; padding: 1rem 0rem;flex-direction: row;justify-content: space-between;">';
echo '					<h4 class="card-title" style="font-weight: 400; padding: 0.5rem 0.2rem;margin: 0;" id="users">Extensions <span id="extensions_count"></span></h4>';
echo '				</div>';
echo '			<div id="group_exist_extensions_list" style="flex-direction: row;display: flex;flex-wrap: wrap;gap: 10px;">';
// here we receive the connections
echo '			</div>';
echo '		</div>';



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

echo '		</div> ';
echo '		</div> ';
echo '  	<div class="tab-pane show active" id="nav-integration" role="tabpanel" aria-labelledby="nav-integration-tab" style="margin: 10px 0px;display: none;">';



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////	INTEGRATION	////////////////////////////////// INTEGRATION ////////////////////////////////// INTGRATION /////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




// INTEGRATION INIT STATE



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
echo '		</div> ';
echo '</div>';

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////
/// MODAL FOR CREATING ORGANIZATION ///
///////////////////////////////////////
echo '	<div class="modal hide fade" id="createOrgModal" tabindex="-1" role="dialog" aria-labelledby="createOrgModalLabel" aria-hidden="true" style="display: none;">';
echo '	  <div class="modal-dialog modal-dialog-centered" role="document">';
echo '	    <div class="modal-content" style="width: max-content;">';
echo '	      <div class="modal-header">';
echo '	        <h5 class="modal-title" id="createOrgModalLabel">Create Organization</h5>';
echo '	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
echo '	          <span aria-hidden="true">&times;</span>';
echo '	        </button>';
echo '	      </div>';
echo '	      <div class="modal-body" style="width: max-content;">';
echo '			<div class="input-group mb-3" style="flex-direction: row;width: 100%;">';
echo '			  <div class="input-group-prepend" style="width: 100%;">';
echo '			    <span class="input-group-text" id="basic-addon1">Organization name</span>';
echo '				<span class="input-group-text" style="color: #000000;background-color: white;width: 100%;">' . $_SESSION['domain_name'] . '</span>';
echo '			  </div>';
echo '			</div>';
echo '			<div class="input-group mb-3" style="flex-direction: row;">';
echo '			  <div class="input-group-prepend">';
echo '			    <span class="input-group-text" id="basic-addon1">Domain name</span>';
echo '			  </div>';
$default_domain_unique_name = getLessThan30(explode('.', $_SESSION['domain_name'])[0], isset($_SESSION['ringotel']['domain_name_postfix']['text']) ? ('-'.$_SESSION['ringotel']['domain_name_postfix']['text']) : '-ringotel');
echo '			  <input type="text" class="form-control" id="domain_unique_name" placeholder="Unique Organization Domain" aria-label="Unique Organization Domain" value=' . $default_domain_unique_name . '>';
echo '			  <div class="input-group-append">';
echo '			    <span class="input-group-text" id="basic-addon2">'.(isset($_SESSION['ringotel']['domain_name_postfix']['text']) ? ('-'.$_SESSION['ringotel']['domain_name_postfix']['text']) : '-ringotel').'</span>';
echo '			</div>';
echo '	      </div>';
echo '	      <div class="modal-footer">';
echo '	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
echo '	    	<button id="create_organization" class="btn btn-primary" role="button" style="color: white;width: 9rem;">';
echo '				<span id="create_org_text">Create</span>';
echo '				<span id="create_org_loading" style="display: none;">';
echo ' 					<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo ' 					Loading...	';
echo '				</span>';
echo '			</button>	';
echo '	      </div>';
echo '	    </div>';
echo '	  </div>';
echo '	</div>';
echo '	</div>';

/////////////////////////////////////
/// MODAL FOR CREATING CONNECTION ///
/////////////////////////////////////
echo '	<div class="modal hide fade" id="createConnectionModal" tabindex="-1" role="dialog" aria-labelledby="createConnectionModalLabel" aria-hidden="true" style="display: none;">';
echo '	  <div class="modal-dialog modal-dialog-centered" role="document">';
echo '	    <div class="modal-content">';
echo '	      <div class="modal-header">';
echo '	        <h5 class="modal-title" id="createConnectionModalLabel">Create Connection</h5>';
echo '	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
echo '	          <span aria-hidden="true">&times;</span>';
echo '	        </button>';
echo '	      </div>';
echo '	      <div class="modal-body" style="width: auto;">';
echo '			<div class="input-group mb-3">';
echo '			  <div class="input-group-prepend">';
echo '			    <div class="input-group-text" id="basic-addon1">Domain or IP address</div>';
echo '			  </div>';
echo '			  <input type="text" class="form-control" id="connection_domain" placeholder="Domain or IP address" aria-label="Domain or IP address" value=' . $_SESSION['domain_name'] . ':' . (isset($_SESSION['ringotel']['ringotel_organization_port']['text']) ? $_SESSION['ringotel']['ringotel_organization_port']['text'] : '5070') . '>';
echo '			</div>';
echo '			<div class="input-group mb-3">';
echo '			  <div class="input-group-prepend">';
echo '			    <div class="input-group-text">Max registrations</div>';
echo '			  </div>';
echo '			  <input type="text" class="form-control" id="maxregs" placeholder="MaxRegs" aria-label="MaxRegs" value="1">';
echo '			</div>';
echo '	      </div>';
echo '	      <div class="modal-footer">';
echo '	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
// button for creating connection
echo '	    	<button id="create_connect_button" class="btn btn-primary" role="button" style="color: white;width: 9rem;">';
echo '				<span id="create_connect_text" class="create_connect_text">Create</span>';
echo '				<span id="create_connect_loading" class="create_connect_loading" style="display: none;">';
echo ' 					<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo ' 					Loading...	';
echo '				</span>';
echo '			</button>	';

echo '	      </div>';
echo '	    </div>';
echo '	  </div>';
echo '	</div>';
echo '	</div>';

///////////////////////////////
/// MODAL FOR CREATING USER ///
///////////////////////////////
echo '	<div class="modal hide fade" id="createUserModal" tabindex="-1" role="dialog" aria-labelledby="createUserModalLabel" aria-hidden="true" style="display: none;">';
echo '	  <div class="modal-dialog modal-dialog-centered" role="document">';
echo '	    <div class="modal-content" style="width: auto;">';
echo '	      <div class="modal-header">';
echo '	        <h5 class="modal-title" id="createUserModalLabel">Create Users</h5>';
echo '	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
echo '	          <span aria-hidden="true">&times;</span>';
echo '	        </button>';
echo '	      </div>';

// Get List Of Extensions
$sql = "    select * from v_extensions  ";
$sql .= "    where domain_uuid = :domain_uuid ";
$sql .= "    order by extension::int asc ";
$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$db = new database;
$extensions = $db->select($sql, $parameters);
unset($sql, $db, $parameters);

echo '		  <div id="extensions" style="display: flex;flex-direction: row;">';

////////////////////////////////// left table for exist extensions /////////////////////////////////
echo '		  	<div id="exist_extensions" style="display:flex;">';
echo '				<table class="table table-hover" style="overflow-y: scroll;display: table-caption;height: 50vh;width: max-content;">';
echo '				  <thead>';
echo '				    <tr>';
echo '				      <th scope="col">';
echo '						  <input type="checkbox" aria-label="Select all" id="extensions_select_all" style="transform: scale(1.25);margin-bottom: 0.2rem;">';
echo '					  </th>';
echo '				      <th style="font-size: unset;vertical-align: middle;" scope="col">Extension</th>';
echo '				      <th style="font-size: unset;vertical-align: middle;" scope="col">Effective CID Name</th>';
echo '				      <th style="font-size: unset;vertical-align: middle;" scope="col">Outbound CID Number</th>';
echo '				      <th style="font-size: unset;vertical-align: middle;" scope="col">Email</th>';
echo '				      <th style="font-size: unset;vertical-align: middle;" scope="col">Activate</th>';
echo '				    </tr>';
echo '				  </thead>';
echo '				  <tbody id="table_exists_extensions">';

// foreach ($extensions as $row) {
// 	// Line-start
// 	echo '				    <tr id="extension_line_' . $row['extension'] . '">';
// 	echo '				      <th style="vertical-align: middle;display: flex;flex-direction: column;border: 0;" scope="row">';
// 	echo '						<input name="create" class="extension_column" id="extension_' . $row['extension_uuid'] . '" data-uuid="' . $row['extension_uuid'] . '" type="checkbox" aria-label="select" style="transform: scale(1.25);margin-bottom: 0.2rem;">';
// 	echo '					  </th>';
// 	echo '				      <td style="vertical-align: middle;font-size: 11pt;">' . $row['extension'] . '</td>';
// 	echo '				      <td style="vertical-align: middle;font-size: 11pt;">' . $row['effective_caller_id_name'] . '</td>';
// 	echo '				      <td style="vertical-align: middle;font-size: 11pt;">' . $row['effective_caller_id_number'] . '</td>';
// 	echo '				      <td style="vertical-align: middle;font-size: 11pt;padding: 0.5rem;">';
// 	echo '						<input name="email" class="form-control ext_email" id="ext_email_' . $row['extension_uuid'] . '" data-uuid="' . $row['extension_uuid'] . '" type="email" style="line-height: 1rem;height: 1.75rem;padding: 0.5rem;" placeholder="provide the email..."></input>';
// 	echo '					  </td>';
// 	echo '				      <td style="vertical-align: middle;display: flex;justify-content: center;">';
// 	echo '						<input name="active" class="ext_activate" id="ext_activate_' . $row['extension_uuid'] . '" data-uuid="' . $row['extension_uuid'] . '" type="checkbox" style="transform: scale(1.25);margin-bottom: 0.2rem;"></input>';
// 	echo '					  </td>';
// 	echo '				    </tr>';
// 	/// Line-end
// }

echo '				  </tbody>';
echo '				</table>';
echo '		  	</div>';
///////////////////////////////////////////////////////////////////////////////////////////////////

// /////////////////////////////// right table for ringotel extensions ///////////////////////////////
// echo '		  	<div id="ringotel_extensions" style="display:flex;">';
// echo '				<table class="table table-hover" style="overflow-y: scroll;display: table-caption;height: 50vh;width: max-content;">';
// echo '				  <thead>';
// echo '				    <tr>';
// echo '				      <th scope="col">';
// echo '						  <input type="checkbox" aria-label="Select all" id="right_extensions_select_all">';
// echo '					  </th>';
// echo '				      <th scope="col">Extension</th>';
// echo '				      <th scope="col">Effective CID Name</th>';
// echo '				      <th scope="col">Outbound CID Number</th>';
// echo '				    </tr>';
// echo '				  </thead>';
// echo '				  <tbody>';
// // Line-start
// echo '				    <tr>';
// echo '				      <th scope="row" style="display: flex;flex-direction: column;border: 0;">';
// echo '						 <input type="checkbox" aria-label="select" class="extension_right_column">';
// echo '					  </th>';
// echo '				      <td>' . $row['extension'] . '</td>';
// echo '				      <td>' . $row['effective_caller_id_name'] . '</td>';
// echo '				      <td>' . $row['effective_caller_id_number'] . '</td>';
// echo '				    </tr>';
// /// Line-end
// echo '				  </tbody>';
// echo '				</table>';
// echo '		  	</div>';
// ////////////////////////////////////////////////////////////////////////////////////////////////////

echo '		</div>';
echo '	      <div class="modal-footer">';

echo '			<div class="input-group">';
echo '				<div class="input-group-prepend">';
echo '					<span class="input-group-text" id="basic-addon1">';
echo '						Select Connection:';
echo '					</span>';
echo '				</div>';
echo '				<select id="users_selector_branch" class="form-control">';
echo '				</select>';
echo '			</div>';
// button for close
echo '	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
// button for creating user
echo '	    	<button id="create_extensions_button" class="btn btn-primary" role="button" style="color: white;width: 20rem;height: 2.375rem;">';
echo '				<span id="create_extensions_text" class="create_extensions_text">Create</span>';
echo '				<span id="create_extensions_loading" class="create_extensions_loading" style="display: none;">';
echo ' 					<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo ' 					Loading...	';
echo '				</span>';
echo '			</button>	';

echo '	      </div>';
echo '	    </div>';
echo '	  </div>';
echo '	</div>';
echo '	</div>';


/////////////////////////////////////
/// MODAL FOR CREATE PARKS ///
/////////////////////////////////////
echo '	<div class="modal hide fade" id="createParksModal" tabindex="-1" role="dialog" aria-labelledby="createParksModalLabel" aria-hidden="true" style="display: none;">';
echo '	  <div class="modal-dialog modal-dialog-centered" role="document" id="createParksModal_modal_body">';
echo '	    <div class="modal-content">';
echo '	      <div class="modal-header">';
echo '	        <h5 class="modal-title" id="createParksModalLabel">Create Parks</h5>';
echo '	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
echo '	          <span aria-hidden="true">&times;</span>';
echo '	        </button>';
echo '	      </div>';
echo '	      <div class="modal-body" style="width: auto;" id="modal_body_createParks">';
echo '			<div class="input-group" style="padding-bottom: 1rem;">';
echo '				<div class="input-group-prepend">';
echo '					<span class="input-group-text" id="basic-addon1">';
echo '						Select Connection:';
echo '					</span>';
echo '				</div>';
echo '				<select id="users_selector_branch_for_parks" class="form-control">';
echo '				</select>';
echo '			</div>';
echo '			<div class="input-group mb-3">';
echo '			  <div class="input-group-prepend">';
echo '			    <div class="input-group-text" id="basic-addon1">From</div>';
echo '			  </div>';
echo '			  <input type="number" class="form-control" id="from_park_number" placeholder="...">';
echo '			  <div class="input-group-prepend">';
echo '			    <div class="input-group-text" id="basic-addon1">To</div>';
echo '			  </div>';
echo '			  <input type="number" class="form-control" id="to_park_number" placeholder="...">';
echo '			</div>';
echo '	      </div>';
echo '	      <div class="modal-footer">';
echo '	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
// button for create Parks user
echo '	    	<button id="create_parks_button" class="btn btn-primary" role="button" style="color: white;width: 9rem;height: 2.375rem;">';
echo '				<span id="create_parks_text" 	class="create_parks_text">Create</span>';
echo '				<span id="create_parks_loading" class="create_parks_loading" style="display: none;">';
echo ' 					<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo ' 					Loading...	';
echo '				</span>';
echo '			</button>	';
echo '	      </div>';
echo '	    </div>';
echo '	  </div>';
echo '	</div>';
echo '	</div>';


/////////////////////////////////////
/// MODAL FOR ACTIVATING ///
/////////////////////////////////////
echo '	<div class="modal hide fade" id="createActivationModal" tabindex="-1" role="dialog" aria-labelledby="createActivationModalLabel" aria-hidden="true" style="display: none;">';
echo '	  <div class="modal-dialog modal-dialog-centered" role="document" id="createActivationModal_modal_body">';
echo '	    <div class="modal-content">';
echo '	      <div class="modal-header">';
echo '	        <h5 class="modal-title" id="createActivationModalLabel">Activating User</h5>';
echo '	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
echo '	          <span aria-hidden="true">&times;</span>';
echo '	        </button>';
echo '	      </div>';
echo '	      <div class="modal-body" style="width: auto;" id="modal_body_activateUser">';
echo '			<div class="input-group mb-3">';
echo '			  <div class="input-group-prepend">';
echo '			    <div class="input-group-text" id="basic-addon1">Email</div>';
echo '			  </div>';
echo '			  <input type="text" class="form-control" id="email_for_user" placeholder="Email for user">';
echo '			</div>';
echo '	      </div>';
echo '	      <div class="modal-footer">';
echo '	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
// button for activate user
echo '	    	<button id="create_user_activate_button" class="btn btn-primary" role="button" style="color: white;width: 9rem;height: 2.375rem;">';
echo '				<span id="create_user_activate_text" class="create_user_activate_text">Activate</span>';
echo '				<span id="create_user_activate_loading" class="create_user_activate_loading" style="display: none;">';
echo ' 					<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo ' 					Loading...	';
echo '				</span>';
echo '			</button>	';
echo '	      </div>';
echo '	    </div>';
echo '	  </div>';
echo '	</div>';
echo '	</div>';

/////////////////////////////////////
/// MODAL FOR RESET PASSWORD ///
/////////////////////////////////////
echo '	<div class="modal hide fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true" style="display: none;">';
echo '	  <div class="modal-dialog modal-dialog-centered" role="document" id="resetPasswordModal_modal_body">';
echo '	    <div class="modal-content">';
echo '	      <div class="modal-header">';
echo '	        <h5 class="modal-title" id="resetPasswordModalLabel">Reset User Password</h5>';
echo '	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
echo '	          <span aria-hidden="true">&times;</span>';
echo '	        </button>';
echo '	      </div>';
echo '	      <div class="modal-body" style="width: auto;" id="modal_body_resetPassword">';
echo '			<div class="input-group mb-3">';
echo '			  <div class="input-group-prepend">';
echo '			    <div class="input-group-text" id="basic-addon1">Email</div>';
echo '			  </div>';
echo '			  <input type="text" class="form-control" id="email_for_reset_password" placeholder="Email for user">';
echo '			</div>';
echo '	      </div>';
echo '	      <div class="modal-footer">';
echo '	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
// button for reset user password
echo '	    	<button id="reset_user_password_button" class="btn btn-primary" role="button" style="color: white;width: 9rem;">';
echo '				<span id="reset_user_password_text" class="reset_user_password_text">Reset</span>';
echo '				<span id="reset_user_password_loading" class="reset_user_password_loading" style="display: none;">';
echo ' 					<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	';
echo ' 					Loading...	';
echo '				</span>';
echo '			</button>	';
echo '	      </div>';
echo '	    </div>';
echo '	  </div>';
echo '	</div>';
echo '	</div>';


/////////////////////////////////////
/// Organization Package Error
/////////////////////////////////////
echo '	<div id="package_org_popup" style="-webkit-transition: all 0.5s;transition: all 0.5s;position: absolute;display: block;width: 14rem;opacity:0;" class="toast" role="alert" aria-live="assertive" aria-atomic="true">';
echo '	  <div class="toast-header" style="color: #bb1a1a;">';
echo '		<i class="fa fa-exclamation-circle" aria-hidden="true" style="padding: 0rem 0.5rem 0rem 0rem;color: #cf0000;"></i>';
echo '	    <strong class="mr-auto">Error</strong>';
echo '	    <button id="package_org_popup_close" type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">';
echo '	      <span aria-hidden="true">&times;</span>';
echo '	    </button>';
echo '	  </div>';
echo '	  <div id="package_org_popup_text" class="toast-body">';
echo '	  </div>';
echo '	</div>';
/////////////////////////////////////

include "resources/footer.php";

echo '<style>';
echo '	.card-body-p {';
echo '		padding: 0.65rem 1rem 0.25rem 1rem !important;';
echo '	}';
echo '	.input:checked + .slider {';
echo '		background-color: #2196f3;';
echo '	}';
echo '	.nav-pills .nav-link.active,.nav-pills .show>.nav-link  { ';
echo '			box-shadow: 0 0 0 #007bff; ';
// echo '			animation: pulse 2s infinite; ';
echo '				padding: 0.5rem 1rem 0.5rem 0.2rem;';
echo '	} ';
echo '	@-webkit-keyframes pulse { ';
echo '		0% { ';
echo '			-webkit-box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4); ';
echo '		} ';
echo '		70% { ';
echo '			-webkit-box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); ';
echo '		} ';
echo '		100% { ';
echo '			-webkit-box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); ';
echo '		} ';
echo '	} ';
echo '	@keyframes pulse { ';
echo '		0% { ';
echo '		  	-moz-box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4); ';
echo '		  	box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4); ';
echo '		} ';
echo '		70% { ';
echo '			-moz-box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); ';
echo '			box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); ';
echo '		} ';
echo '		100% { ';
echo '			-moz-box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); ';
echo '			box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); ';
echo '		} ';
echo '	} ';
echo '	@-webkit-keyframes sync {	';
echo '		0% {transform: rotate(0deg);}	';
echo '		50% {transform: rotate(180deg);}	';
echo '		100% {transform: rotate(360deg);}	';
echo '	}	';
echo '	@keyframes sync {	';
echo '		0% {transform: rotate(0deg);}	';
echo '		50% {transform: rotate(180deg);}	';
echo '		100% {transform: rotate(360deg);}	';
echo '	}	';
echo '	@-webkit-keyframes plug {	';
echo '		0% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		18% {transform: translateY(-7px);filter: contrast(0.5);color:#2196f3}	';
echo '		22% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		25% {transform: translateY(-3px);filter: contrast(0.5);color:#2196f3}	';
echo '		34% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		65% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		80% {transform: translateY(-8px);filter: contrast(0.5);color:#2196f3}	';
echo '		95% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		100% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '	}	';
echo '	@keyframes plug {	';
echo '		0% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		18% {transform: translateY(-7px);filter: contrast(0.5);color:#2196f3}	';
echo '		22% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		25% {transform: translateY(-3px);filter: contrast(0.5);color:#2196f3}	';
echo '		34% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		65% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		80% {transform: translateY(-8px);filter: contrast(0.5);color:#2196f3}	';
echo '		95% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '		100% {transform: translateY(0px);filter: contrast(1);color:#c31919}	';
echo '	}	';
echo '	.nav-pills .nav-link {	';
echo '	    border-radius: .25rem	';
echo '	}	';
echo '	.nav-pills .nav-link.active,.nav-pills .show>.nav-link {	';
echo '	    color: #5f5f5f;	';
echo '	    background-color: transparent;	';
echo '	}	';
echo '	@media (min-width: 880px) {	';
echo '		#main_content {	';
echo '			max-width: 100vw;	';
echo '		}	';
echo '	}	';
echo '	@media (min-width: 975px) {	';
echo '		#main_content {	';
echo '			max-width: 95vw;	';
echo '		}	';
echo '	}	';
echo '	@media (min-width: 1170px) {	';
echo '		#main_content {	';
echo '			max-width: 80vw;	';
echo '		}	';
echo '	}	';
echo '	@media (min-width: 1300px) {	';
echo '		#main_content {	';
echo '			max-width: 70vw;	';
echo '		}	';
echo '	}	';
echo '	@media (min-width: 1500px) {	';
echo '		#main_content {	';
echo '			max-width: 70vw;	';
echo '		}	';
echo '	}	';
echo '	@media (min-width: 2000px) {	';
echo '		#main_content {	';
echo '			max-width: 60vw;	';
echo '		}	';
echo '	}	';
echo '	#packageid_switcher_container {';
echo '		display: flex; ';
echo '		flex-direction: row; ';
echo '		justify-content: center; ';
echo '		align-items: center; ';
echo '	}';
echo '	.packageid_switcher {';
echo '		background: #808080;';
echo '		opacity: 0.2;';
echo '		width: 46.67px;';
echo '		-webkit-transition: all 0.5s;transition: all 0.5s;font-weight: 400;color: #fff;padding: 2px 6px;border-radius: 4px;right: auto;top: -4px;font-size: 16px;height: 22px;line-height: 22px;text-align: center;display: inline-block;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;cursor: pointer;';
echo '	}';
echo '	#packageid_switcher_container .active {';
echo '		background: #2196f3;';
echo '		opacity: 1;';
echo '	}';
echo '	#packageid_switcher_container:hover {';
echo ' 		.packageid_switcher {';
echo '			opacity: 0.5;';
echo '			background: #2196f3;';
echo '		}';
echo '	}';
echo '	#packageid_switcher_container .active:hover {';
echo ' 		.packageid_switcher {';
echo '			opacity: 0.5;';
echo '			background: #808080;';
echo '		}';
echo '	}';
echo '	.dropdown-toggle::after {';
echo '		content: none;';
echo '	}';
echo '	.paddingBottomEc {';
echo '		padding-bottom: 15px;';
echo '		padding-right: 5px;';
echo '		padding-left: 5px;';
echo '	}';
echo '	.connection_name_ec { ';
echo '		grid-area: connection_name_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	} ';
echo '	.protocol_ec { ';
echo '		grid-area: protocol_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	} ';
echo '	.address_ec { ';
echo '		grid-area: address_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	} ';
echo '	.sip_port_ec { ';
echo '		grid-area: sip_port_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	} ';
echo '	.country_ec { ';
echo '		grid-area: country_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	} ';
echo '	.r_inbound_numbers_ec { ';
echo '		grid-area: r_inbound_numbers_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	} ';
echo '	.sips_options_ec {';
echo '		grid-area: sips_options_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	}';
echo '	.maxreg_ec {';
echo '		grid-area: maxreg_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	}';
echo '	.multitenant_ec {';
echo '		grid-area: multitenant_ec;';
echo '		align-self: end;';
echo '		justify-self: center;';
echo '		padding: 0;';
echo '	}';
echo '	.main_settings_edit_connection { ';
echo '		display: grid; ';
echo '		grid-template-columns: 2; ';
echo '		grid-template-rows: 5; ';
echo '		grid-template-areas:  ';
echo '		  "connection_name_ec protocol_ec" ';
echo '		  "sips_options_ec sips_options_ec" ';
echo '		  "address_ec sip_port_ec" ';
echo '		  "country_ec r_inbound_numbers_ec" ';
echo '		  "maxreg_ec multitenant_ec"; ';
echo '	} ';
echo '	.user_name_ec {';
echo '		grid-area: user_name_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	}';
echo '	.email_ec {';
echo '		grid-area: email_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	}';
echo '	.extension_ec {';
echo '		grid-area: extension_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	}';
echo '	.sip_password_ec {';
echo '		grid-area: sip_password_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	}';
echo '	.sip_username_ec {';
echo '		grid-area: sip_username_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	}';
echo '	.auth_name_ec {';
echo '		grid-area: auth_name_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	}';
echo '	.mobile_ec {';
echo '		grid-area: mobile_ec; ';
echo '		justify-self: stretch; ';
echo '		align-self: center; ';
echo '	}';
echo '	.main_settings_edit_user { ';
echo '		display: grid; ';
echo '		grid-template-columns: auto auto; ';
echo '		grid-template-rows: auto; ';
echo '		grid-template-areas:  ';
echo '		  "user_name_ec email_ec" ';
echo '		  "extension_ec sip_password_ec" ';
echo '		  "sip_username_ec auth_name_ec" ';
echo '		  "mobile_ec null"; ';
echo '	} ';

echo '	@keyframes aware { ';
echo '		0% { ';
echo '			transform: translateY(0px); ';
echo '			filter: saturate(1.0); ';
echo '			filter: blur(1px); ';
echo '			transform: rotate(0deg); ';
echo '		} ';
echo '		14.6% { ';
echo '			transform: rotate(1deg); ';
echo '		} ';
echo '		22.7% { ';
echo '			transform: rotate(0deg); ';
echo '		} ';
echo '		29.7% { ';
echo '			transform: rotate(-1deg); ';
echo '		} ';
echo '		34.0% { ';
echo '			transform: rotate(0deg); ';
echo '		} ';
echo '		40% { ';
echo '			transform: translateY(1px); ';
echo '			filter: saturate(1.5); ';
echo '			filter: blur(0.2px); ';
echo '		} ';
echo '		50% { ';
echo '			transform: translateY(0px); ';
echo '			filter: saturate(1.2); ';
echo '		} ';
echo '		100% { ';
echo '			transform: translateY(0px); ';
echo '			filter: saturate(1.0); ';
echo '			filter: blur(0px); ';
echo '		} ';
echo '	} ';
echo '	.shaked {';
echo '		animation: aware 1s infinite;';
echo '		box-shadow: rgba(50, 50, 93, 0.25) 0px 6px 12px -2px, rgba(0, 0, 0, 0.3) 0px 3px 7px -3px;';
echo '	}';
echo '	.attention {';
echo '		animation: pulse 1s infinite;';
echo '	}';
echo '	.user_card:hover {';
echo '		-moz-box-shadow: rgba(136, 165, 191, 0.48) 6px 2px 16px 0px, rgba(255, 255, 255, 0.8) -6px -2px 16px 0px;';
echo '		box-shadow: rgba(136, 165, 191, 0.48) 6px 2px 16px 0px, rgba(255, 255, 255, 0.8) -6px -2px 16px 0px;';
echo '		-webkit-transition: all 0.25s;';
echo '		transition: all 0.25s;';
echo '		';
echo '	}';
echo '	.active-option {	';
echo '		opacity: 0.5;	';
echo '	}					';
echo '	.edit_user:hover, .delete_user:hover {';
echo '	    box-shadow: inset rgb(63 63 63 / 20%) 0px 0px 15px 15px;';
echo '	    z-index: 1;';
echo '	}';
echo '	div.card {';
echo '		position: relative;';
echo '    	display: -ms-flexbox;';
echo '    	display: flex;';
echo '    	-ms-flex-direction: column;';
echo '    	flex-direction: column;';
echo '    	min-width: 0;';
echo '    	word-wrap: break-word;';
echo '    	background-color: #fff;';
echo '    	background-clip: border-box;';
echo '    	border: 1px solid rgba(0, 0, 0, .125);';
echo '    	border-radius: .25rem;';
echo '		overflow-x: unset;';
echo '	}';
echo "	.switch { /* container */";
echo "		position: relative;";
echo "		display: inline-block;";
echo "		width: 50px;";
echo "		height: 28px;";
echo "		margin: 1px;";
echo "		-moz-border-radius: 3px 3px 3px 3px;";
echo "		-webkit-border-radius: 3px 3px 3px 3px;";
echo "		-khtml-border-radius: 3px 3px 3px 3px;";
echo "		border-radius: 3px 3px 3px 3px;";
echo "	}";
echo "	.switch > input {";
echo "		display: none;";
echo "	}";
echo "	.slider {";
echo "		position: absolute;";
echo "		cursor: pointer;";
echo "		top: 0;";
echo "		left: 0;";
echo "		right: 0;";
echo "		bottom: 0;";
echo "		background: #c0c0c0;";
echo "		-moz-border-radius: 3px 3px 3px 3px;";
echo "		-webkit-border-radius: 3px 3px 3px 3px;";
echo "		-khtml-border-radius: 3px 3px 3px 3px;";
echo "		border-radius: 3px 3px 3px 3px;";
echo "		-webkit-transition: .2s;";
echo "		transition: .2s;";
echo "		}";
echo "	.slider:before {";
echo "		position: absolute;";
echo "		text-align: center;";
echo "		padding-top: 3px;";
echo "		content: 'O';";
echo "		color: #c0c0c0;";
echo "		height: 24px;";
echo "		width: 24px;";
echo "		top: 2px;";
echo "		left: 2px;";
echo "		bottom: 2px;";
echo "		background: #ffffff;";
echo "		-moz-border-radius: 3px 3px 3px 3px;";
echo "		-webkit-border-radius: 3px 3px 3px 3px;";
echo "		-khtml-border-radius: 3px 3px 3px 3px;";
echo "		border-radius: 3px 3px 3px 3px;";
echo "		-webkit-transition: .2s;";
echo "		transition: .2s;";
echo "	}";
echo "	input:checked + .slider {";
echo "		background: #2e82d0;";
echo "	}";
echo "	input:focus + .slider {";
echo "	}";
echo "	input:checked + .slider:before {";
echo "		text-align: center;";
echo "		padding-top: 2px;";
echo "		content: '|';";
echo "		color: #2e82d0;";
echo "		-webkit-transform: translateX(22px);";
echo "		-ms-transform: translateX(22px);";
echo "		transform: translateX(22px);";
echo "	}";
echo '</style>';

?>

<script>
	////// Edit User MODAL //////
	const modalEditUser = ({ id, accountid, domain, name, email, extension, sip_password, sip_username, auth_name, mobile, created }) => {
		return (
			`
			<div class="modal hide fade" id="editUserModal_${id}" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel_${id}" aria-hidden="true" style="display: none;">
				  <div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						  <div class="modal-header">
							<h5 class="modal-title" id="editUserModalLabel_${id}">Edit User</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								  <span aria-hidden="true">&times;</span>
							</button>
						  </div>

							  <div class="modal-body" style="width: auto;">

								<form id="modal_form_edit_user_${id}">
									<div id="main_settings_edit_user_${id}" class="main_settings_edit_user">

										<div id="user_name_ec_${id}" class="user_name_ec paddingBottomEc">
											<div id="user_name_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${name ? '' : 'opacity: 0;'}">
												Display name
											</div>
											<input id="user_name_ec_input_${id}" name="user_name_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control user_name_ec_input"  placeholder="Display name" aria-label="Display name" value="${name || ''}">
										</div>

										<div id="email_ec_${id}" class="email_ec paddingBottomEc">
											<div id="email_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${email ? '' : 'opacity: 0;'}">
												User email
											</div>
											<input id="email_ec_input_${id}" name="email_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control email_ec_input"  placeholder="User email" aria-label="User email" value="${email || ''}">
										</div>

										<div id="extension_ec_${id}" class="extension_ec paddingBottomEc">
											<div id="extension_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${extension ? '' : 'opacity: 0;'}">
												PBX extension
											</div>
											  <input id="extension_ec_input_${id}" name="extension_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control extension_ec_input" placeholder="PBX extension" aria-label="PBX extension" value="${extension || ''}">
										</div>

										<div id="sip_password_ec_${id}" class="sip_password_ec paddingBottomEc">
											<div id="sip_password_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${sip_password ? '' : 'opacity: 0;'}">
												SIP password
											</div>
											  <input id="sip_password_ec_input_${id}" name="sip_password_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="password" class="form-control sip_password_ec_input" placeholder="SIP password" aria-label="SIP password" value="${sip_password || ''}">
										</div>

										<div id="sip_username_ec_${id}" class="sip_username_ec paddingBottomEc">
											<div id="sip_username_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${sip_username ? '' : 'opacity: 0;'}">
												SIP username
											</div>
											  <input id="sip_username_ec_input_${id}" name="sip_username_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control sip_username_ec_input" placeholder="SIP username" aria-label="SIP username" value="${sip_username || ''}">
										</div>

										<div id="auth_name_ec_${id}" class="auth_name_ec paddingBottomEc">
											<div id="auth_name_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${auth_name ? '' : 'opacity: 0;'}">
												Authorization name
											</div>
											  <input id="auth_name_ec_input_${id}" name="auth_name_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control auth_name_ec_input" placeholder="Authorization name" aria-label="Authorization name" value="${auth_name || ''}">
										</div>

										<div id="mobile_ec_${id}" class="mobile_ec paddingBottomEc">
											<div id="mobile_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${mobile ? '' : 'opacity: 0;'}">
												Mobile
											</div>
											  <input id="mobile_ec_input_${id}" name="mobile_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control mobile_ec_input" placeholder="Mobile" aria-label="Mobile" value="${mobile || ''}">
										</div>

									</div>

								</form>

							  </div>

							  <div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-dismiss="modal">
									Close
								</button>
								<button id="edit_user_button_${id}" class="btn btn-primary edit_user_button" role="button" style="color: white;width: 9rem;">
									<span id="edit_user_text_${id}" class="edit_user_text">
										Save
									</span>
									<span id="edit_user_loading_${id}" class="edit_user_loading" style="display: none;">
										<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	
										Saving...	
									</span>
								</button>	
							  </div>
						</div>
				  </div>
			</div>
			`
		)
	};


	const modalEditUsersFn = () => {
		// Display Name
		$(`.user_name_ec_input`).on('keyup', (function (el) {
			const id = el.target.id.split('_').pop();
			if (el.target.value.trim()) {
				$(`#user_name_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#user_name_ec_input_text_${id}`).css('opacity', 0);
			}
		}));

		// User Email
		$(`.email_ec_input`).on('keyup', (function (el) {
			const id = el.target.id.split('_').pop();
			if (el.target.value.trim()) {
				$(`#email_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#email_ec_input_text_${id}`).css('opacity', 0);
			}
		}));

		// Extension
		$(`.extension_ec_input`).on('keyup', (function (el) {
			const id = el.target.id.split('_').pop();
			if (el.target.value.trim()) {
				$(`#extension_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#extension_ec_input_text_${id}`).css('opacity', 0);
			}
		}));

		// SIP Password
		$(`.sip_password_ec_input`).on('keyup', (function (el) {
			const id = el.target.id.split('_').pop();
			if (el.target.value.trim()) {
				$(`#sip_password_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#sip_password_ec_input_text_${id}`).css('opacity', 0);
			}
		}));

		// SIP Username
		$(`.sip_username_ec_input`).on('keyup', (function (el) {
			const id = el.target.id.split('_').pop();
			if (el.target.value.trim()) {
				$(`#sip_username_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#sip_username_ec_input_text_${id}`).css('opacity', 0);
			}
		}));

		// Authorization name
		$(`.auth_name_ec_input`).on('keyup', (function (el) {
			const id = el.target.id.split('_').pop();
			if (el.target.value.trim()) {
				$(`#auth_name_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#auth_name_ec_input_text_${id}`).css('opacity', 0);
			}
		}));

		// Mobile
		$(`.mobile_ec_input`).on('keyup', (function (el) {
			const id = el.target.id.split('_').pop();
			if (el.target.value.trim()) {
				$(`#mobile_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#mobile_ec_input_text_${id}`).css('opacity', 0);
			}
		}));

		// SAVE 
		$(`.edit_user_button`).on('click', (function (el) {
			const id = el.target.id.split('_').pop();
			const orgid = $('#delete_organization').attr('data-account');

			// form field value
			const name = $('#user_name_ec_input_' + id).val();
			const email = $('#email_ec_input_' + id).val();
			const extension = $('#extension_ec_input_' + id).val();
			const password = $('#sip_password_ec_input_' + id).val();
			const username = $('#sip_username_ec_input_' + id).val();
			const authname = $('#auth_name_ec_input_' + id).val();
			const mobile = $('#mobile_ec_input_' + id).val();

			const data = {
				orgid,
				id,
				name,
				email,
				extension,
				password,
				username,
				authname,
				mobile
			};

			// console.log('--> [edit_user_button] --> data', data);

			saveEditedUser(id, data);
		}));
	};

	// Save Edited User/Extension 
	const saveEditedUser = (id, data) => {
		const orgid = $('#delete_organization').attr('data-account');
		$('#edit_user_button_' + id).attr('disabled', true);
		$('#edit_user_text_' + id).slideUp(300);

		setTimeout(() => {
			$('#edit_user_loading_' + id).slideDown(300);
			$.ajax({
				url: "/app/ringotel/service.php?method=updateUser",
				type: "post",
				cache: true,
				data,
				success: async function (response) {
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					// console.log('[updateUser --------> ', result);
					$('#edit_user_loading_' + id).slideUp(300);
					// Skip the modal with user editing
					$('#editUserModal_' + id).click();
					$('#options_user_' + id).click();
					$('#user_card_' + id).css('opacity', 0.5);
					setTimeout(() => {
						$('#edit_user_text_' + id).slideDown(300);
						$('#edit_user_button_' + id).attr('disabled', false);

						// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
						getUsersWithUpdateElements();
					}, 300);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		}, 300);
	};

	////// Connection MODAL //////
	const modalEditConnection = ({ id, accountid, name, domain, country, address, created, provision: { protocol, inboundFormat, noverify, nosrtp, multitenant, maxregs } }) => {
		const address_only = address.split(':')[0];
		const port_only = address.split(':')[1];
		return (
			`
			<div class="modal hide fade" id="editConnectionModal_${id}" tabindex="-1" role="dialog" aria-labelledby="editConnectionModalLabel_${id}" aria-hidden="true" style="display: none;">
				  <div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						  <div class="modal-header">
							<h5 class="modal-title" id="editConnectionModalLabel_${id}">Edit Connection</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								  <span aria-hidden="true">&times;</span>
							</button>
						  </div>

							  <div class="modal-body" style="width: auto;">

								<form id="modal_form_edit_connection_${id}">
									<div id="main_settings_edit_connection_${id}" class="main_settings_edit_connection">
										<div id="connection_name_ec" class="connection_name_ec paddingBottomEc">
											<div id="connection_name_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${name ? '' : 'opacity: 0;'}">
												Connection Name
											</div>
											<input id="connection_name_ec_input_${id}" name="connection_name_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control"  placeholder="Connection Name" aria-label="Connection Name" value="${name}">
										</div>
										<div id="protocol_ec_${id}" class="protocol_ec paddingBottomEc">
											<div id="protocol_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${protocol ? '' : 'opacity: 0;'}">
												Protocol
											</div>
											  <select id="protocol_ec_input_${id}" name="protocol_ec_input" data-noverify="${noverify}" data-nosrtp="${nosrtp}" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control" placeholder="Protocol" aria-label="Protocol" value="${protocol}">
												  <option value="sip">SIP (UDP)</option>
												  <option value="sip-tcp">SIP (TCP)</option>
												  <option value="sips">SIP (TLS/SRTP)</option>
												  <option value="DNS-NAPTR">DNS-NAPTR (Beta)</option>
											</select>
										</div>
										${(protocol === 'sips' || protocol === 'DNS-NAPTR') ?
				`
											<div id="sips_options_ec_${id}" class="paddingBottomEc">
												<div style="display:flex;flex-direction:row;align-items: center;height: 25px;">
													<input id="noverify_ec_${id}" name="noverify_ec" style="transform: scale(1.25);" type="checkbox" aria-label="Do not verify server certificate" value="${noverify}">
													<label for="noverify_ec_${id}" style="text-align: center;height: 25px;-ms-flex-wrap:nowrap;flex-wrap:nowrap;margin: 0.7rem 0.45rem 0.5rem 0.45rem;font-size: 12pt;">Do not verify server certificate</label>
												</div>
												<div style="display:flex;flex-direction:row;align-items: center;height: 25px;">
													<input id="nosrtp_ec_${id}" name="nosrtp_ec" style="transform: scale(1.25);" type="checkbox" aria-label="Disable SRTP" value="${nosrtp}">
													<label for="nosrtp_ec_${id}" style="text-align: center;height: 25px;-ms-flex-wrap:nowrap;flex-wrap:nowrap;margin: 0.7rem 0.45rem 0.5rem 0.45rem;font-size: 12pt;">Disable SRTP</label>
												</div>
											</div>
											`
				: ''
			}
										<div id="address_ec_${id}" class="address_ec paddingBottomEc">
											<div id="address_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${address_only ? '' : 'opacity: 0;'}">
												Domain or IP Address
											</div>
											  <input id="address_ec_input_${id}" name="address_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control" placeholder="Domain or IP Address" aria-label="Domain or IP Address" value="${address_only}">
										</div>
										<div id="sip_port_ec_${id}" class="sip_port_ec paddingBottomEc">
											<div id="sip_port_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${port_only ? '' : 'opacity: 0;'}">
												SIP Port
											</div>
											  <input id="sip_port_ec_input_${id}" name="sip_port_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control" placeholder="SIP Port" aria-label="SIP Port" value="${port_only}">
										</div>
										<div id="country_ec_${id}" class="country_ec paddingBottomEc">
											<div id="country_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${country ? '' : 'opacity: 0;'}">
												Country
											</div>
											  <input id="country_ec_input_${id}" name="country_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control" disabled="true"placeholder="Country" aria-label="Country" value="${country}">
										</div>
										<div id="r_inbound_numbers_ec_${id}" class="r_inbound_numbers_ec paddingBottomEc">
											<div id="r_inbound_numbers_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;">
												Reformat inbound numbers
											</div>
											<select id="r_inbound_numbers_ec_input_${id}" name="r_inbound_numbers_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control" placeholder="Reformat inbound numbers" aria-label="Reformat inbound numbers" value="${inboundFormat}">
												<option value="">Disabled</option>
												<option value="e164">+E.164</option>
												<option value="national">National (strip + and country code)</option>
											</select>
										</div>
										<div id="maxreg_ec_${id}" class="maxreg_ec paddingBottomEc">
											<div id="maxreg_ec_input_text_${id}" style="transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;${country ? '' : 'opacity: 0;'}">
												Max registrations
											</div>
											  <input id="maxreg_ec_input_${id}" name="maxreg_ec_input" style="border: 0;border-bottom: 1px solid #80808063;border-radius: 0;" type="text" class="form-control" placeholder="Max registrations" aria-label="Max registrations" value="${maxregs}">
										</div>
										<div class="multitenant_ec paddingBottomEc">
											<div style="display:flex;flex-direction:row;align-items: center;height: 25px;padding: 2rem 0px;">
												<input id="multitenant_ec_${id}" name="noverify_ec" style="transform: scale(1.25);margin: 4px;" type="checkbox" aria-label="Multi-tenant mode" value="${multitenant}">
												<label for="multitenant_ec_${id}" style="text-align: center;height: 25px;-ms-flex-wrap:nowrap;flex-wrap:nowrap;margin: 0.7rem 0.45rem 0.5rem 0.45rem;font-size: 12pt;">Multi-tenant mode</label>
											</div>
										</div>
									</div>
								</form>

							  </div>

							  <div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-dismiss="modal">
									Close
								</button>
								<button id="edit_connect_button_${id}" class="btn btn-primary edit_connect_button" role="button" style="color: white;width: 9rem;">
									<span id="edit_connect_text_${id}" class="edit_connect_text">
										Save
									</span>
									<span id="edit_connect_loading_${id}" class="edit_connect_loading" style="display: none;">
										<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	
										Saving...	
									</span>
								</button>	
							  </div>
						</div>
				  </div>
			</div>
			`
		)
	};

	const updateConnection = (id, data) => {
		const orgid = $('#delete_organization').attr('data-account');
		$('#edit_connect_button_' + id).attr('disabled', true);
		$('#edit_connect_text_' + id).slideUp(300);
		$('#editConnectModal_button_' + id).css('pointer-events', 'none');
		$('#delete_connect_' + id).css('pointer-events', 'none');

		setTimeout(() => {
			$('#edit_connect_loading_' + id).slideDown(300);
			$.ajax({
				url: "/app/ringotel/service.php?method=updateBranchWithUpdatedSettings",
				type: "post",
				cache: true,
				data,
				success: async function (response) {
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					// console.log('[updateConnection --------> ', result);
					$('#edit_connect_loading_' + id).slideUp(300);
					$('#editConnectionModal_' + id).click();
					$('#connect_card_' + id).css('opacity', 0.5);
					setTimeout(() => {
						$('#edit_connect_text_' + id).slideDown(300);
						$('#edit_connect_button_' + id).attr('disabled', false);
						getConnections(orgid);
					}, 300);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		}, 300);
	};

	const modalEditConnectionFn = (id) => {
		// @param per connection _${id}


		// Connection Name
		$(`#connection_name_ec_input_${id}`).on('keyup', (function (el) {
			if (el.target.value.trim()) {
				$(`#connection_name_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#connection_name_ec_input_text_${id}`).css('opacity', 0);
			}
		}));
		// Protocol
		$(`#protocol_ec_input_${id}`).on('change', (function (el) {
			// console.log('el.target.value', el.target.value);
			const noverify = el.target.getAttribute('data-noverify');
			const nosrtp = el.target.getAttribute('data-nosrtp');
			if (el.target.value.trim()) {
				$(`#protocol_ec_input_text_${id}`).css('opacity', 1);
				if (el.target.value === 'sips' || el.target.value === 'DNS-NAPTR') {
					const inject = `
						<div id="sips_options_ec_${id}" class="paddingBottomEc" style="display: none;">
							<div style="display:flex;flex-direction:row;align-items: center;height: 25px;">
								<input id="noverify_ec_${id}" name="noverify_ec" style="transform: scale(1.25);" type="checkbox" aria-label="Do not verify server certificate" value="${noverify}">
								<label for="noverify_ec" style="text-align: center;height: 25px;-ms-flex-wrap:nowrap;flex-wrap:nowrap;margin: 0.7rem 0.45rem 0.5rem 0.45rem;font-size: 12pt;">Do not verify server certificate</label>
							</div>
							<div style="display:flex;flex-direction:row;align-items: center;height: 25px;">
								<input id="nosrtp_ec_${id}" name="nosrtp_ec" style="transform: scale(1.25);" type="checkbox" aria-label="Disable SRTP" value="${nosrtp}" name="Disable SRTP">
								<label for="nosrtp_ec" style="text-align: center;height: 25px;-ms-flex-wrap:nowrap;flex-wrap:nowrap;margin: 0.7rem 0.45rem 0.5rem 0.45rem;font-size: 12pt;">Disable SRTP</label>
							</div>
						</div>
					`;
					if (!$(`#sips_options_ec_${id}`)[0]) {
						$(`#protocol_ec_${id}`).after(inject);
					};
					setTimeout(() => {
						$(`#sips_options_ec_${id}`).slideDown(300);
					}, 100);
				} else {
					$(`#sips_options_ec_${id}`).slideUp(300);
					setTimeout(() => {
						$(`#sips_options_ec_${id}`).remove();
					}, 300);
				}
			} else {
				$(`#protocol_ec_input_text_${id}`).css('opacity', 0);
			}
		}));
		// Address Only
		$(`#address_ec_input_${id}`).on('keyup', (function (el) {
			if (el.target.value.trim()) {
				$(`#address_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#address_ec_input_text_${id}`).css('opacity', 0);
			}
		}));
		// SIP Port
		$(`#sip_port_ec_input_${id}`).on('keyup', (function (el) {
			if (el.target.value.trim()) {
				$(`#sip_port_ec_input_text_${id}`).css('opacity', 1);
			} else {
				$(`#sip_port_ec_input_text_${id}`).css('opacity', 0);
			}
		}));

		// SAVE 
		$(`#edit_connect_button_${id}`).on('click', (function () {
			const formData = $(`#modal_form_edit_connection_${id}`).serializeArray();
			// console.log('--> [edit_connect_button] --> data', formData);

			const orgid = $('#delete_organization').attr('data-account');

			// form field value
			const name = $('#connection_name_ec_input_' + id).val();
			const protocol = $('#protocol_ec_input_' + id).val();
			const address = $('#address_ec_input_' + id).val();
			const port = $('#sip_port_ec_input_' + id).val();
			const country = $('#country_ec_input_' + id).val();
			const inboundFormat = $('#r_inbound_numbers_ec_input_' + id).val();
			const maxregs = $('#maxreg_ec_input_' + id).val();
			const multitenant = $('#multitenant_ec_' + id)[0].checked;

			const data = {
				orgid,
				id,
				name,
				protocol,
				address,
				port,
				country,
				inboundFormat,
				maxregs,
				multitenant
			};

			if (protocol === 'sips' || protocol === 'DNS-NAPTR') {
				const noverify = $('#noverify_ec_' + id)[0].checked;		// addintional
				const nosrtp = $('#nosrtp_ec_' + id)[0].checked;			// addintional
				data.noverify = noverify;
				data.nosrtp = nosrtp;
			}

			// console.log('--> [edit_connect_button] --> data', data);

			updateConnection(id, data);
		}));
	};

	const eventSwitchProFn = (orgid) => {
		// Disable  event and reinit it
		$('#checkbox-switcher-for-integrations-input').off('click');
		// Switch the Essential/PRO Version
		$('#checkbox-switcher-for-integrations-input').on('click', (async (el) => {
			const spinner = `<div class="spinner-border" id="packageid_switcher_container_spinner" role="status" style="margin-top: 1px;font-size: 10pt;width: 1rem;height: 1rem;"><span class="sr-only">Loading...</span></div>`;

			$('#packageid_switcher_container').prepend(spinner);

			const hasClassActive = $('#checkbox-switcher-for-integrations-input').hasClass('active');
			const packageid = hasClassActive ? 1 : 2;
			const result = await switchOrganizationMode({ orgid, packageid });

			// console.log('[switchOrganizationMode] result, packageid: ', result, packageid);

			if (result?.switch?.error) {
				$('#packageid_switcher_container_spinner').remove();
				const offset = $('#checkbox-switcher-for-integrations-input').offset();
				// console.log('[switchOrganizationMode] offset', offset);
				$('#package_org_popup').offset({ top: offset?.top || 300, left: (offset?.left || 300) + 100 });
				$('#package_org_popup').css('display', '');
				$('#package_org_popup').css('opacity', 1);
				$('#package_org_popup_text').text(result?.switch?.error.message);

				// re-Init the switcher
				$('#packageid_switcher_container').html(`<label class="switch" id="checkbox-switcher-for-integrations" style="margin: 0;transform: scale(0.65);"><input id="checkbox-switcher-for-integrations-input" type="checkbox" checked class="active"></input><div class="slider round"></div></label><div>PRO</div>`);
				eventSwitchProFn(orgid);

			} else if (Array.isArray(result[0]) && result[0].length === 0) {
				$('#packageid_switcher_container_spinner').remove();
				// Show Package Alert
				if (packageid == 1) {
					const package_alert = `<div id="not_exist_integration_package_note" class="alert alert-warning alert-dismissible fade show" style="" role="alert"><strong>Upgrade to PRO.</strong><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button></div>`;
					$('#nav-integration').prepend(package_alert);
				}
				if (packageid == 2) {
					$('#not_exist_integration_package_note').remove();
				}

				if ($('#checkbox-switcher-for-integrations-input').hasClass('active')) {
					$('#checkbox-switcher-for-integrations-input').removeClass('active');
				} else {
					$('#checkbox-switcher-for-integrations-input').addClass('active');
				}
			};
		}));
	}

	///// PARKS SAVE UPDATE DELETE
	////
	///
	// 

	// PARKS CLOSE
	const eventParkClose = () => {
		$('.parks_close_updating').off('click');
		$('.parks_close_updating').on('click', (function () {
			// Hide the delete button per Park
			$('.delete_user[data-type-code=PARK]').fadeOut(300);
			// Hide the options window per Park
			$('.user_card_options[data-type-code=PARK]').fadeOut(300);
			// Hide the options button per Park
			$('.options_user[data-type-code=PARK]').fadeOut(300);
			// Show the Close Parks button
			$('.parks_close_updating').animate({ width: 'toggle' }, 300);
			// Show the Save Parks button
			$('.parks_save').animate({ width: 'toggle' }, 300);
			// Hide the Add Parks
			$('#create_parks_modal_button').animate({ width: 'toggle' }, 300);
			setTimeout(() => {
				$('.parks_update').animate({ width: 'toggle' }, 300);
				// Disable aware animation
				$('.user_card[data-type-code=PARK]').removeClass('shaked');
			}, 300);
		}));
	}

	// PARKS EDIT EVENT 
	const eventParksEdit = () => {
		// clear event listeners
		$('.parks_update').off('click');
		// Bind Event Listener [Edit Parks]
		$(".parks_update").on('click', (function (el) {
			// Show the Save Parks button
			$('.parks_update').animate({ width: 'toggle' }, 300);
			setTimeout(() => {
				// Enable aware animation
				$('.user_card[data-type-code=PARK]').addClass('shaked');
				// Show the delete button per Park
				$('.delete_user[data-type-code=PARK]').fadeIn(300);
				// Show the options window per Park
				$('.user_card_options[data-type-code=PARK]').fadeIn(300);
				// Show the options button per Park
				$('.options_user[data-type-code=PARK]').fadeIn(300);
				// Show the Close Parks button
				$('.parks_close_updating').animate({ width: 'toggle' }, 300);
				// Show the Save Parks button
				$('.parks_save').animate({ width: 'toggle' }, 300);
				// Hide the Add Parks
				$('#create_parks_modal_button').animate({ width: 'toggle' }, 300);
				// Reinit the event listeners
				eventParkClose();
			}, 300);


		}));
	}

	// PARKS SAVE EVENT
	const eventParksSave = () => {
		// clear event listeners
		$('.parks_save').off('click');
		// Bind Event Listener [Edit Parks]
		$(".parks_save").on('click', (function (el) {
			// Disable Save Parks Button
			$('#parks_save').attr('disable', true);
			// Clear Style
			$('#parks_save').removeClass('attention');
			// Parks Number List
			const orgid = $('#delete_organization').attr('data-account');

			const parks_numbers_list = [...$('.user_card[data-type-code=PARK]')]?.map((park) => { return { park: park?.getAttribute('data-number'), connection: park?.getAttribute('data-branch-id'), connectionName: park?.getAttribute('data-branch-name') } });

			// Groupe the Parks
			const object_array = parks_numbers_list?.reduce(function (rv, x) {
				(rv[x['connection']] = rv[x['connection']] || []).push(x);
				return rv;
			}, {});

			// Convert to Array
			const connects_array = Object.keys(object_array)?.map((key) => {
				return { [key]: object_array[key] };
			});

			// console.log('parks_numbers_list', parks_numbers_list);

			// Async Request per connect
			Array.isArray(connects_array) &&

				Promise.all(connects_array.map(async (item) => {
					// console.log(' -----> item', item);
					const branchid = Object.keys(item)[0];
					const park_array = Object.values(item)[0]?.map(({ park }) => park);
					const branchname = Object.values(item)[0]?.map(({ connectionName }) => connectionName)[0];
					const from_park_number = Math.min(...park_array);
					const to_park_number = Math.max(...park_array);
					const data = {
						orgid,
						id: branchid,
						name: branchname,
						from_park_number,
						to_park_number,
						park_array
					};
					console.table(' -------> data', data);
					await $.ajax({
						url: "/app/ringotel/service.php?method=updateParksWithUpdatedSettings",
						type: "get",
						cache: true,
						data,
						success: function (response) {
							const res = JSON.parse(response.replaceAll("\\", ""));
							// console.log(' ----------------> [createParks] result', Array.isArray(res) && res?.length === 0);
						},
						error: function (jqXHR, textStatus, errorThrown) {
							// console.log(textStatus, errorThrown);
						}
					});
				})).then((res) => {
					// console.log(' ----------------> Promise.all [createParks]');
					// Enable aware animation
					$('.user_card[data-type-code=PARK]').addClass('shaked');
					// Show the delete button per Park
					$('.delete_user[data-type-code=PARK]').fadeIn(300);
					// Show the options button per Park
					$('.user_card_options[data-type-code=PARK]').fadeIn(300);
					// Show the options button per Park
					$('.options_user[data-type-code=PARK]').fadeIn(300);
					// Show the Close Parks button
					$('.parks_close_updating').animate({ width: 'toggle' }, 300);
					// Show the Save Parks button
					$('.parks_save').animate({ width: 'toggle' }, 300);
					// Hide the Add Parks
					$('#create_parks_modal_button').animate({ width: 'toggle' }, 300);
					// Reinit the event listeners
					eventParkClose();
					setTimeout(() => {
						// Show the Save Parks button
						$('.parks_update').animate({ width: 'toggle' }, 300);
						// Enable Save Parks Button
						$('#parks_save').attr('disable', false);
						// load Branches (Connections)
						getConnections(orgid);
					}, 300);
				});
		}));
	}


	// Modal For Editing User/Extension Settings



	//
	///
	////
	/////
	////// ///// ////// ///// ////


	//
	///
	////
	/////
	/////////////////////////////////////
	/////////// INTEGRATION /////////////
	/////////////////////////////////////

	// Func Valid Cheker
	const functValidChecker = () => {
		const users = $('#manage_numbers_users').val();
		if ($('#manage_numbers_phone_number').hasClass('alert-danger')) {
			$('#manage_numbers_phone_number').removeClass('alert-danger');
		}
		if ($('#multiselect_dropdown').hasClass('alert-danger')) {
			$('#multiselect_dropdown').removeClass('alert-danger');
		}
		if (users?.length > 0) {
			$('#manage_numbers_activate_button').attr('disabled', false);
		}
	};

	// Create Numbers Configuration
	const eventSaveIntegratedUsers = () => {
		// Phone Number Required Field
		$('#manage_numbers_phone_number').on('change', (function (el) {
			if (el.target?.value?.trim()) {
				functValidChecker();
			}
		}));

		// Users Required Field
		$('#manage_numbers_users').on('change', (function () {
			functValidChecker();
		}));

		// Save Button
		$('#manage_numbers_activate_button').on('click', (function () {
			const name = $('#manage_numbers_friendly_name').val().trim();
			const number = $('#manage_numbers_phone_number').val().trim();
			const users = $('#manage_numbers_users').val();
			if (users?.length === 0 || !number) {
				!number && $('#manage_numbers_phone_number').addClass('alert-danger');
				if (users?.length === 0) {
					$('#multiselect_dropdown').addClass('alert-danger');
					$('#manage_numbers_users').addClass('alert-danger');
				}
				$('#manage_numbers_activate_button').attr('disabled', true);
			} else {
				$('#manage_numbers_activate_button').attr('disabled', true);
				$('#manage_numbers_activate_text').slideUp(300);
				$('#manage_numbers_activate_loading').slideDown(300);
				const orgid = $('#delete_organization').attr('data-account');
				const data = { orgid, name, number, users };
				// console.log('[manage_numbers_activate_button] data', data);

				// Create Ajax Funciton
				$.ajax({
					url: "/app/ringotel/service.php?method=createSMSTrunk",
					type: "post",
					cache: true,
					data,
					success: async function (response) {
						const { result } = JSON.parse(response.replaceAll("\\", ""));
						// console.log('[createSMSTrunk --------> ', result);

						// re-Update Trunk
						const parksUserExtensions = await getUsersWithUpdateElements(); // Get Users Again For Updating DOM and For SMS Trunk
						getSMSTrunk(parksUserExtensions); // Get Numbers Configuration

						$('#manage_numbers_activate_button').attr('disabled', false);
						$('#manage_numbers_activate_text').slideDown(300);
						$('#manage_numbers_activate_loading').slideUp(300);
						$('#manageNumbersModal').click();
					},
					error: function (jqXHR, textStatus, errorThrown) {
						// console.log(textStatus, errorThrown);
					}
				});
			}
		}));
	}

	const eventIntegrationCreate = () => {
		$('#integration_create').off('click');
		// Create Integration Functional
		$('#integration_create').on('click', () => {
			$('#integration_create').attr('disabled', true);
			$('#create_inter_text').fadeOut(300);
			const profileid = $('#delete_organization').attr('data-account');
			setTimeout(() => {
				$('#create_inter_loading').fadeIn();
				$.ajax({
					url: "/app/ringotel/service.php?method=createIntegration",
					type: "get",
					cache: true,
					data: { profileid },
					success: function (response) {
						const { result } = JSON.parse(response.replaceAll("\\", ""));
						// console.log(' ------------ [createIntegration]', result);
						if (result?.status === 200 && result?.state === 1) {
							getIntegration();
						} else {
							const not_exist_integration_note = notExistIntegrationNoteElement("Integration is available in PRO version.");
							$('#nav-integration').prepend(not_exist_integration_note);
							$('#create_inter_loading').fadeOut(300);
							setTimeout(() => {
								$('#create_inter_text').fadeIn(300);
								$('#integration_create').attr('disabled', false);
							}, 300);
						}
					},
					error: function (jqXHR, textStatus, errorThrown) {
						// console.log(textStatus, errorThrown);
					}
				});
			}, 300);
		});
	}

	const IntegrationElement = (data) => {
		return `<div class="card" id="integration_service" style="width: 25rem; display:none;transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;">
				  <img src="${data.logo}" class="card-img-top" alt="${data.id}" style="width: 2rem;left: 0;margin: 0.8rem 1.2rem 1rem;">
				  <div class="card-body card-body-p" style="padding: 0 1.25rem 1.25rem 1.25rem;">
					<h5 class="card-title" style="font-size: 17pt;font-weight: 700;display: flex;align-items: center;">
						${data.name}
						<button id="manageNumbersModalDisable" class="btn btn-outline-danger" style="font-size: 9pt;padding: 0px 10px;margin: 0px 10px;height: 20px;align-items: center;display: flex;">Disable</button>
					</h5>
					<p class="card-text" style="font-size: 11pt;">Add numbers configured on Bandwidth and allocate users who will be able to send/recieve SMS.</p>
					<button id="manageNumbersModal_button" class="btn btn-primary" data-toggle="modal" data-target="#manageNumbersModal">Manage numbers</button>
				  </div>
				</div>
				`;
	}

	const integrationServiceContainerElement = () => {
		return `<div id="integration_service_container" style="display:none;"><div>`
	}

	const MembersIntegrationModalelement = () => {
		return `<div class="modal hide fade" id="manageNumbersModal" tabindex="-1" role="dialog" aria-labelledby="manageNumbersModalLabel" aria-hidden="true" style="display: none;">
					  <div class="modal-dialog modal-dialog-centered" role="document" id="manageNumbersModal_modal_body">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title" id="manageNumbersModalLabel">Manage Numbers</h5>
									<button type="button" class="close" data-dismiss="modal" aria-label="Close">
										  <span aria-hidden="true">&times;</span>
									</button>
								</div>
								<div class="modal-body" style="width: auto;" id="modal_body_manageNumbers">

									<div class="input-group mb-3">
										  <div class="input-group-prepend">
												<div class="input-group-text" id="basic-addon1" style="width: 150px;justify-content: end;">
												Friendly name
											</div>
										  </div>
										  <input type="text" class="form-control" name="manage_numbers_friendly_name" id="manage_numbers_friendly_name">
									</div>

									<div class="input-group mb-3">
											  <div class="input-group-prepend">
													<div class="input-group-text" id="basic-addon1" style="width: 150px;justify-content: end;">
													Phone number
												</div>
											  </div>

											<select class="form-control" name="manage_numbers_phone_number" id="manage_numbers_phone_number" placeholder="a Bandwidth phone number">
												echo '<option value=""></option>';
												<?php
												unset($sql);
												// Get List Of Destinations
												$sql = "    select dest.destination_number from v_destinations as dest  ";
												$sql .= "    where domain_uuid = :domain_uuid ";
												$sql .= "    and destination_type = :destination_type ";
												$sql .= "    order by dest.destination_number asc ";
												$parameters['destination_type'] = 'inbound';
												$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
												$db = new database;
												$destinations = $db->select($sql, $parameters);
												unset($sql, $db, $parameters);
												foreach ($destinations as $dest) {
													echo '<option value="' . $dest['destination_number'] . '">' . $dest['destination_number'] . '</option>';
												}
												?>
											</select>
									</div>

									<div class="input-group mb-3" style="-ms-flex-wrap:nowrap;flex-wrap:nowrap;">
											<div class="input-group-prepend">
												<div class="input-group-text" id="basic-addon1" style="width: 150px;justify-content: end;">
													Users*
												</div>
											</div>
											<select style="height: 44px;overflow: hidden;" id="manage_numbers_users" name="manage_numbers_users" multiple multiselect-hide-x="true" type="text" class="form-control" placeholder="Who will be sending and receiving SMS via this phone number">
											</select>
									</div>
									<span id="integrations_users_select_all" style="cursor: pointer;position: absolute;right: 16px;margin-right: 16px;margin-top: -8px;border-radius: 20px;padding: 0px 6px;border: gray solid 1px;color: white;background: gray;">
										Select All
									</span>
								</div>
								<div class="modal-footer">

									<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
									<button id="manage_numbers_activate_button" class="btn btn-primary" role="button" style="color: white;width: 9rem;height: 2.375rem;">
										<span id="manage_numbers_activate_text" class="manage_numbers_activate_text">Save</span>
										<span id="manage_numbers_activate_loading" class="manage_numbers_activate_loading" style="display: none;">
										<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	
											Loading...	
										</span>
									</button>

								  </div>
							</div>
					  </div>
				</div>`;
	}

	// Templater for GET SMS TRUNK
	const elementSMSTrunk = (data, parksUserExtensions) => {
		// console.log('[elementSMSTrunk --------> data', data);
		// console.log('[elementSMSTrunk --------> parksUserExtensions', parksUserExtensions);

		const users_elements = data?.users.map((id) => {
			const user = parksUserExtensions?.filter((item) => item.id === id)[0];
			return (
				`<div data-id="${user?.id}" style="background: #a1a1a1;padding: 0px 10px;font-size: 11pt;border-radius: 10px;color: white;margin: 5px 7px;">${user?.name} (${user?.extension})</div>`
			);
		}).join('');

		return `<div id="sms_trunk_${data.id}" class="card mb-3" style="height: 232px;max-width: 100%;margin: 10px 0px;box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 6px -1px, rgba(0, 0, 0, 0.06) 0px 2px 4px -1px;">
					  <div class="row no-gutters">
						<div class="col-md-5">
							<div class="card-body card-body-p">
								<h5    	id="table_sms_trunk_name_h5_${data.id}" class="card-title" style="font-size: 20pt;font-weight: 700;">${data?.name || '-'}</h5>
								<input 	id="table_sms_trunk_name_input_${data.id}" class="card-title" style="font-size: 20pt;font-weight: 700;display:none;font-size: 20pt;font-weight: 700;margin: 0;padding: 0px 10px;line-height: normal;border-radius: 10px;border: 1px solid #8080804f;" value="${data?.name || ''}"/>
								<table 	id="table_sms_trunk_${data.id}"    data-id="${data.id}" class="table table table-hover table-borderless" style="margin-bottom: 1rem;">
								  <tbody>
									<tr>
										<td style="font-size: 12pt;padding: 0.25rem 0.75rem;">Number:</td>
										<td id="table_sms_trunk_number_td_${data.id}" name="table_sms_trunk_number_td" class="table_sms_trunk_number_td" style="font-size: 12pt;width: 120px;padding: 0.25rem 0.75rem;">
											${data?.number || '-'}
										</td>
										<td style="font-size: 12pt;width: 120px;padding: 0.25rem 0.75rem;display:none;">
											<input type="number" id="table_sms_trunk_number_input_${data.id}" name="table_sms_trunk_number_input" class="table_sms_trunk_number_input" style="padding: 0;margin: 0;width: inherit;border: 1px solid #837e7e52;padding: 0px 4px;border-radius: 20px;" value="${data?.number?.replace('+1', '') || ''}"/>
										</td>
									</tr>
									<tr>
										  <td style="font-size: 12pt;padding: 0.25rem 0.75rem;">Country:</td>
										  <td style="font-size: 12pt;width: 120px;padding: 0.25rem 0.75rem;">${data?.country || '-'}</td>
									</tr>
									<tr>
										  <td style="font-size: 12pt;padding: 0.25rem 0.75rem;">Reformat outbound:</td>
										  <td style="font-size: 12pt;width: 120px;padding: 0.25rem 0.75rem;">${data?.outboundFormat || '-'}</td>
									</tr>
									<tr>
										  <td style="font-size: 12pt;padding: 0.25rem 0.75rem;">Reformat inbound:</td>
										  <td style="font-size: 12pt;width: 120px;padding: 0.25rem 0.75rem;">${data?.inboundFormat || '-'}</td>
									</tr>
									<tr>
										  <td style="font-size: 12pt;padding: 0.25rem 0.75rem;">10DLC Opt-Out:</td>
										  <td style="font-size: 12pt;width: 120px;padding: 0.25rem 0.75rem;">${data?.optout?.autoreply && data?.optout?.keyword ? 'Enabled' : 'Disabled'}</td>
									</tr>
								  </tbody>
								</table>
							</div>
						</div>
						<div class="col-md-7">
							  <div class="card-body card-body-p">
									<h5 class="card-title">Users</h5>
									<div id="table_sms_trunk_users_${data.id}" class="table_sms_trunk_users" data-id="${data.id}" style="display: flex;flex-wrap: wrap;">
										${users_elements}
									</div>
									<select id="table_sms_trunk_manage_users_${data.id}" style="height: 125px; overflow: hidden; display: none;" name="table_sms_trunk_manage_users" multiple="" multiselect-hide-x="true" type="text" class="form-control" placeholder="Who will be sending and receiving SMS via this phone number"></select>
							  </div>
						</div>
					  </div>

					<button id="sms_trunk_save_${data.id}" class="btn btn-outline-primary sms_trunk_save" data-id="${data.id}" 		style="border: 0px;padding: 2px 10px;margin: 10px;align-items: center;display: none;position: absolute;bottom: 10px;right: 130px;;font-size: 10pt;font-weight: 600;">
						Save
					</button>

					<button id="sms_trunk_close_updating_${data.id}" class="btn btn-outline-primary sms_trunk_close_updating" data-id="${data.id}" 		style="display: none;border: 0px;padding: 2px 10px;margin: 10px;align-items: center;position: absolute;bottom: 10px;right: 40px;font-size: 10pt;font-weight: 600;color: gray;">
						Close
					</button>
					  
					<span id="sms_trunk_save_loading_${data.id}" style="border: 0px;padding: 2px 10px;margin: 10px;align-items: center;display: none;position: absolute;bottom: 10px;right: 100px;font-size: 10pt;font-weight: 600;">
						<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
						Loading...	
					</span>
					  
					<button id="sms_trunk_update_${data.id}" class="btn btn-outline-secondary sms_trunk_update" data-id="${data.id}" 	style="border: 0px;padding: 2px 10px;margin: 10px;align-items: center;display: flex;position: absolute;bottom: 10px;right: 100px;font-size: 10pt;font-weight: 600;">
						Edit
					</button>
					  
					<button id="sms_trunk_delete_${data.id}" class="btn btn-outline-danger sms_trunk_delete" data-id="${data.id}" data-number="${data?.number?.replace('+1', '') || ''}"	style="border: 0px;padding: 2px 10px;margin: 10px;align-items: center;display: flex;position: absolute;bottom: 10px;right: 10px;font-size: 10pt;font-weight: 600;">
						Delete
					</button>
				</div>`;
	}

	// SMS TRUNK SAVE EVENT 
	const eventSMSTrunkSave = () => {
		// Disable sms trunk update
		$('.sms_trunk_save').off('click');
		// Enable sms trunk update
		$('.sms_trunk_save').on('click', (function (e) {
			const id = e.target.getAttribute('data-id');
			const orgid = $('#delete_organization').attr('data-account');
			// console.log('----> update sms trunk @id, @orgid ', id, orgid);

			// Hide the save button
			$('#sms_trunk_save_' + id).fadeOut(300);
			$('#sms_trunk_close_updating_' + id).fadeOut(300);

			// Disable Inputs
			$('#table_sms_trunk_name_input_' + id).attr('disabled', true);
			$('#table_sms_trunk_number_input_' + id).attr('disabled', true);
			$('#table_sms_trunk_users_' + id).parent().children('.multiselect-dropdown').css('opacity', '0.5');
			$('#table_sms_trunk_users_' + id).parent().children('.multiselect-dropdown').css('pointer-events', 'none');

			// Get params for updating
			const name = $('#table_sms_trunk_name_input_' + id).val();
			const number = $('#table_sms_trunk_number_input_' + id).val();
			const users = $('#table_sms_trunk_manage_users_' + id).val();

			// console.log('----> update sms trunk @name, @number, @users ', name, number, users);

			const data = {
				orgid,
				id,
				name,
				number,
				users
			};
			// console.log('[--------------eventSMSTrunkSave-------------] data', data);

			setTimeout(() => {
				// Show the save loading
				$('#sms_trunk_save_loading_' + id).fadeIn(300);
				// Create Ajax Funciton
				$.ajax({
					url: "/app/ringotel/service.php?method=updateSMSTrunk",
					type: "post",
					cache: true,
					data,
					success: async function (response) {

						// re-Update Trunk
						const parksUserExtensions = await getUsersWithUpdateElements(); // Get Users Again For Updating DOM and For SMS Trunk
						getSMSTrunk(parksUserExtensions); // Get Numbers Configuration

						$('#manage_numbers_activate_button').attr('disabled', false);
						$('#manage_numbers_activate_text').slideDown(300);
						$('#manage_numbers_activate_loading').slideUp(300);
						$('#manageNumbersModal').click();

						setTimeout(() => {
							// Hide Loading
							$('#sms_trunk_save_loading_' + id).fadeOut(300);
							// AFTER Successful Ajax Request
							$('#sms_trunk_delete_' + id).fadeIn(300);
							$('#sms_trunk_update_' + id).fadeIn(300);
							$('#sms_trunk_save_' + id).fadeOut(300);
							$('#sms_trunk_close_updating_' + id).fadeOut(300);
						}, 300);
					},
					error: function (jqXHR, textStatus, errorThrown) {
						// console.log(textStatus, errorThrown);
					}
				});
			}, 300);
		}));
	};

	// SMS TRUNK CLOSE EVENT
	const eventSMSTrunkClose = () => {
		// Disable sms trunk update
		$('.sms_trunk_close_updating').off('click');
		// Enable sms trunk update
		$('.sms_trunk_close_updating').on('click', (function (e) {
			const id = e.target.getAttribute('data-id');
			const orgid = $('#delete_organization').attr('data-account');
			// console.log('----> close updating sms trunk @id, @orgid ', id, orgid);

			// Show delete and Update
			$('#sms_trunk_delete_' + id).fadeIn(300);
			$('#sms_trunk_update_' + id).fadeIn(300);
			// Hide Save and Close
			$('#sms_trunk_save_' + id).fadeOut(300);
			$('#sms_trunk_close_updating_' + id).fadeOut(300);

			// Hide exist Input and Select elements
			$('#table_sms_trunk_name_input_' + id).slideUp(300);											// Name input-Field
			// $('#table_sms_trunk_number_input_'+id).parent().slideUp(300);									// Number input-Field (it is a parent because the input is inside of <td> tag)
			$('#table_sms_trunk_manage_users_' + id).parent().children('#multiselect_dropdown').slideUp(300); // Users multi-Selector

			// Switch to Text fields
			setTimeout(() => {
				$('#table_sms_trunk_name_h5_' + id).slideDown(300); 											// Name text-field
				// $('#table_sms_trunk_number_td_'+id).slideDown(300);											// Number text-field
				$('#table_sms_trunk_users_' + id).slideDown(300); 											// Users text-field
				$('#table_sms_trunk_manage_users_' + id).parent().children('#multiselect_dropdown').remove(); // [clean] Users multi-selector
			}, 300);
		}));
	};


	// SMS TRUNK UPDATE EVENT
	const eventSMSTrunkUpdate = (users) => {
		// Disable sms trunk update
		$('.sms_trunk_update').off('click');
		// Enable sms trunk update
		$('.sms_trunk_update').on('click', (function (e) {
			const sms_trunk_id = e.target.getAttribute('data-id');
			// console.log('----> update sms trunk @sms_trunk_id, @orgid ', sms_trunk_id);
			// Hide Update and Delete
			$('#sms_trunk_delete_' + sms_trunk_id).fadeOut(300);
			$('#sms_trunk_update_' + sms_trunk_id).fadeOut(300);
			// Show Save and Close
			$('#sms_trunk_save_' + sms_trunk_id).fadeIn(300);
			$('#sms_trunk_close_updating_' + sms_trunk_id).fadeIn(300);

			// Enable Inputs
			$('#table_sms_trunk_name_input_' + sms_trunk_id).attr('disabled', false);
			// $('#table_sms_trunk_number_input_'+sms_trunk_id).attr('disabled', false);
			$('#table_sms_trunk_users_' + sms_trunk_id).parent().children('.multiselect-dropdown').css('opacity', '1');
			$('#table_sms_trunk_users_' + sms_trunk_id).parent().children('.multiselect-dropdown').css('pointer-events', '');

			// Switch to editable field
			$('#table_sms_trunk_name_h5_' + sms_trunk_id).slideUp(300); // Name field
			// $('#table_sms_trunk_number_td_'+sms_trunk_id).slideUp(300); // Number field
			$('#table_sms_trunk_users_' + sms_trunk_id).slideUp(300); // Users Field

			// Add Users to Select List for SMS Trunk Update
			$('#table_sms_trunk_manage_users_' + sms_trunk_id).html(users?.map((item) => {
				return `<option value="${item?.id}">${item?.name} (${item?.extension})</option>`; // Add options to select field
			}));

			setTimeout(() => {
				// Check exist values
				const sms_trunks_users_ids = [...$('#table_sms_trunk_users_' + sms_trunk_id).children('div')].map((item) => item.getAttribute('data-id'));
				// Set Up Cheked State for Users Selector
				$('#table_sms_trunk_manage_users_' + sms_trunk_id).val(sms_trunks_users_ids);

				// [SHOW] Switch to editable field
				/**/$('#table_sms_trunk_name_input_' + sms_trunk_id).slideDown(300); /// Name field
				// /**/$('#table_sms_trunk_number_input_'+sms_trunk_id).parent().slideDown(300); /// Number field
				/**/$('#table_sms_trunk_manage_users_' + sms_trunk_id).parent().children('#multiselect_dropdown').remove(); /// Clear Select Field for Add Users Manage Members
				/**/MultiSelectDropDownBindSelectors({ id: 'table_sms_trunk_manage_users_' + sms_trunk_id, style: { height: '125px' } }); /// Multi Values For Inputs (Just For Select Fields)
			}, 300);
		}));
	};

	// SMS TRUNK DELETE EVENT
	const eventSMSTrunkDelete = () => {
		// Disable sms trunk delete event
		$('.sms_trunk_delete').off('click');
		// Enable sms trunk delete event
		$('.sms_trunk_delete').on('click', (function (e) {
			$('#manageNumbersModal_button').attr('disabled', true);
			const sms_trunk_id = e.target.getAttribute('data-id');
			const sms_trunk_number = e.target.getAttribute('data-number');
			const orgid = $('#delete_organization').attr('data-account');
			// console.log('sms_trunk_id', sms_trunk_id);

			$('#sms_trunk_delete_' + sms_trunk_id).attr('disabled', true);

			$.ajax({
				url: "/app/ringotel/service.php?method=deleteSMSTrunk",
				type: "post",
				cache: true,
				data: { orgid, id: sms_trunk_id },
				success: function (response) {
					// console.log('[eventSMSTrunkDelete] response', response);
					// getIntegration();
					$('#sms_trunk_' + sms_trunk_id).slideUp(300);

					// console.log('[eventSMSTrunkDelete ---------> sms_trunk_number', sms_trunk_number);
					// show number in options if we are managing numbers
					sms_trunk_number && $('#manage_numbers_phone_number').children(`option[value=${sms_trunk_number.replace('+1', '')}]`)?.slideDown();

					setTimeout(() => {
						// disable Button
						$('#manageNumbersModal_button').attr('disabled', false);
					}, 300);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		}));
	};

	// Get Numbers Configuration
	const getSMSTrunk = (parksUserExtensions) => {
		// disable Button
		$('#manageNumbersModal_button').attr('disabled', true);
		// get smsTrunk
		const orgid = $('#delete_organization').attr('data-account');
		$.ajax({
			url: "/app/ringotel/service.php?method=getSMSTrunk",
			type: "post",
			cache: true,
			data: { orgid },
			success: function (response) {
				const { result } = JSON.parse(response.replaceAll("\\", ""));
				// console.log('[getSMSTrunk --------> ', result);
				if (result?.length > 0) {
					// get List of Users to compare their id's
					$('#integration_service').css('width', '100%');
					result.map((item, k) => {
						const elementSMSTrunk_ = elementSMSTrunk(item, parksUserExtensions);
						if (k === 0) {
							$('#integration_service_container').html(elementSMSTrunk_);
						} else {
							$('#integration_service_container').append(elementSMSTrunk_);
						}
					});

					// clear manage options accesible numbers
					const exist_number = result.map(({ number }) => {
						number && $('#manage_numbers_phone_number').children(`option[value=${number?.replace('+1', '')}]`)?.slideUp();
					});

					const regexp = /[\+*]/; // Parks param detect
					const users = parksUserExtensions.filter((ext) => (!ext.extension.match(regexp) && ext.status === 1)).sort((a, b) => parseInt(a.extension) - parseInt(b.extension));

					// Reload Event Listeners
					eventSMSTrunkDelete();
					eventSMSTrunkUpdate(users);
					eventSMSTrunkSave();
					eventSMSTrunkClose();
				};
				setTimeout(() => {
					$('#manageNumbersModal_button').attr('disabled', false);
				}, 300);
			},
			error: function (jqXHR, textStatus, errorThrown) {
				// console.log(textStatus, errorThrown);
			}
		});
	}

	const eventDisableIntegrationFunc = () => {
		$('#manageNumbersModalDisable').off('click');
		// Disable Integration functionality
		$('#manageNumbersModalDisable').on('click', (function () {
			$('#manageNumbersModalDisable').attr('disabled', true);
			$('#manageNumbersModal_button').attr('disabled', true);
			$('#integration_service_container').slideUp();
			const profileid = $('#delete_organization').attr('data-account');
			$.ajax({
				url: "/app/ringotel/service.php?method=deleteIntegration",
				type: "get",
				cache: true,
				data: { profileid },
				success: function (response) {
					// console.log('--------------> [deleteIntegration]', response);
					getIntegration();
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		}));
	}

	// Template for not exist integration note
	const notExistIntegrationNoteElement = (message) => {
		return `<div id="not_exist_integration_note" class="alert alert-warning alert-dismissible fade show" style="" role="alert">		  <strong>${message || 'You don\'t have activated users.'}</strong>	  <button type="button" class="close" data-dismiss="alert" aria-label="Close"> 	    <span aria-hidden="true">×</span> 	  </button> 	</div>`;
	}

	// Template for not exist integration note
	const notExistIntegrationElement = () => {
		return `<div id="not_exist_integration" class="jumbotron" style="background-color: white; border: 0px solid #b5b5b5;padding: 2rem 2rem;">		  <hr>		  <p style="font-size: 14pt;">Allow sending and receiving SMS/MMS via Bandwidth for users.</p>		  <p class="lead">			<button type="button" class="btn btn-primary btn-lg" id="integration_create" style="padding: 0.15rem 1rem;">			<span id="create_inter_text">Create</span>			<span id="create_inter_loading" style="display: none;"> 				<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>	 				Loading...				</span>		</button>	  </p>		</div>`;
	}

	// get Integrations uses from getOrganization function
	const getIntegration = (parameters) => {
		const orgid = $('#delete_organization').attr('data-account');
		// console.log('[getIntegration] ------------> orgid', orgid);
		$.ajax({
			url: "/app/ringotel/service.php?method=getIntegration",
			type: "get",
			cache: true,
			data: { orgid },
			success: async function (response) {
				const { result, error } = JSON.parse(response.replaceAll("\\", ""));
				if (Array.isArray(result) && result[0]?.id === "Bandwidth" && result[0]?.state === 1) {
					// Button Form Create Actions
					$('#create_inter_loading').fadeOut(300);

					// Show Integrations Form
					$('#not_exist_integration').slideUp(300);

					// Exist Elemets
					// const not_exist_integration_note = notExistIntegrationNoteElement();
					const modal_member_numbers = MembersIntegrationModalelement();
					const service_form = IntegrationElement(result[0]);
					const integration_service_container = integrationServiceContainerElement();

					// Replace All Elements
					// $('#nav-integration').html(not_exist_integration_note);
					$('#nav-integration').html(modal_member_numbers);
					$('#nav-integration').append(service_form);
					$('#nav-integration').append(integration_service_container);

					// Event "Integration Create"
					eventIntegrationCreate();

					// Enable Button
					$('#manage_numbers_activate_button').attr('disabled', false);

					// Handle Hide Event For Modal
					$('#manageNumbersModal').on("hidden.bs.modal", function () {
						$('#manage_numbers_friendly_name').val('');
						$('#manage_numbers_phone_number').val('');
					});

					// Select All Functionality
					$('#integrations_users_select_all').on('click', (function () {
						$('#multiselect_dropdown_list_manage_numbers_users').children().click();
						$('#multiselect_dropdown_list_wrapper_manage_numbers_users').hide();
					}));

					// Add Event-Listener
					eventSaveIntegratedUsers();
					eventDisableIntegrationFunc(); // Disable Integration functionality

					// Get Users Again For Updating DOM and For SMS Trunk
					const parksUserExtensions = await getUsersWithUpdateElements();
					// Get Numbers Configuration
					getSMSTrunk(parksUserExtensions);

					// Button Form Create Actions and show the Service Form 
					setTimeout(() => {
						$('#create_inter_text').fadeIn(300);
						$('#integration_service').slideDown(300);
						$('#integration_service_container').slideDown(300);

						// Enable Button
						$('#manageNumbersModalDisable').attr('disabled', false);
						$('#manageNumbersModal_button').attr('disabled', false);
					}, 300);

				} else {
					$('#integration_service').slideUp(300);
					// Show Not Exists
					// const not_exist_integration_note = notExistIntegrationNoteElement(error?.message);
					const not_exist_integration = notExistIntegrationElement();
					// $('#nav-integration').html(not_exist_integration_note);
					$('#nav-integration').html(not_exist_integration);
					setTimeout(() => {
						$('#not_exist_integration').slideDown(300);
						// $('#not_exist_integration_note').slideDown(300);
						// Event "Integration Create"
						eventIntegrationCreate();
					}, 300);
				}

				$('#integration_create').attr('disabled', false);
			},
			error: function (jqXHR, textStatus, errorThrown) {
				// console.log(textStatus, errorThrown);
				const not_exist_integration = notExistIntegrationElement();
				const not_exist_integration_note = notExistIntegrationNoteElement();
				$('#nav-integration').html(not_exist_integration_note);
				$('#nav-integration').append(not_exist_integration);

			}
		});
	}
	////
	///
	//

	let calledUserStateChecker = false;

	const detachUserEvent = () => {
		// clear event listeners
		$('.detachUser').off('click');
		// Bind Event Listener [Delete User] 
		$(".detachUser").on('click', (function (el) {
			const id = el.currentTarget.getAttribute('data-id');
			const userid = el.currentTarget.getAttribute('data-userid');
			const branchid = el.currentTarget.getAttribute('data-branch');
			const orgid = $('#delete_organization').attr('data-account');
			const data = {
				id,
				userid,
				orgid,
			};
			$.ajax({
				url: "/app/ringotel/service.php?method=detachUser",
				type: "post",
				cache: true,
				data,
				success: function (response) {
					// const { result } = JSON.parse(response.replaceAll("\\", ""));
					// // console.log('[createExtensions] result', result);
					// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
					getUsersWithUpdateElements(orgid, branchid);
					setTimeout(() => {

					}, 300);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		}));
	};

	const ExistsExtensionTemplate = ({ extension, extension_uuid, effective_caller_id_name, effective_caller_id_number }) => {
		return (`
			<tr id="extension_line_${extension}">
			  <th style="vertical-align: middle;display: flex;flex-direction: column;border: 0;" scope="row">
				<input name="create" class="extension_column" id="extension_${extension_uuid}" data-uuid="${extension_uuid}" type="checkbox" aria-label="select" style="transform: scale(1.25);margin-bottom: 0.2rem;">
			  </th>
			  <td style="vertical-align: middle;font-size: 11pt;">${extension}</td>
			  <td style="vertical-align: middle;font-size: 11pt;">${effective_caller_id_name}</td>
			  <td style="vertical-align: middle;font-size: 11pt;">${effective_caller_id_number}</td>
			  <td style="vertical-align: middle;font-size: 11pt;padding: 0.5rem;">
				<input name="email" class="form-control ext_email" id="ext_email_${extension_uuid}" data-uuid="${extension_uuid}" type="email" style="line-height: 1rem;height: 1.75rem;padding: 0.5rem;" placeholder="provide the email..."></input>
			  </td>
			  <td style="vertical-align: middle;display: flex;justify-content: center;">
				<input name="active" class="ext_activate" id="ext_activate_${extension_uuid}" data-uuid="${extension_uuid}" type="checkbox" style="transform: scale(1.25);margin-bottom: 0.2rem;"></input>
			  </td>
			</tr>`
		)
	}

	const getStatusElementForTemplate = (code) => {
		let html = '';
		switch (code) {
			case 0:
				html = '<span style="color: #bbb;"><i style="padding: 0rem 0.5rem 0rem 0rem;" class="fa fa-circle" aria-hidden="true"></i>Offline</span>'; 					// #bbb
				break;
			case 1:
				html = '<span style="color: #2196f3;"><i style="padding: 0rem 0.5rem 0rem 0rem;" class="fa fa-circle" aria-hidden="true"></i>Online</span>'; 				// #2196f3
				break;
			case 2:
				html = '<span style="color: #f87c00;"><i style="padding: 0rem 0.5rem 0rem 0rem;" class="fa fa-circle" aria-hidden="true"></i>Available</span>'; 			// #f87c00
				break;
			case 3:
				html = '<span style="color: #f44336;"><i style="padding: 0rem 0.5rem 0rem 0rem;" class="fa fa-circle" aria-hidden="true"></i>DND</span>'; 					// #f44336
				break;
			case 5:
				html = '<span style="color: #2196f380;"><i style="padding: 0rem 0.5rem 0rem 0rem;" class="fa fa-circle" aria-hidden="true"></i><span style="color: #555;">Available on PBX</span></span>'; 	// #2196f380
				break;
			case 8:
				html = '<span style="color: darkred;"><i style="padding: 0rem 0.5rem 0rem 0rem;" class="fa fa-circle" aria-hidden="true"></i>Busy</span>'; 					// darkred
				break;
		}
		return html;
	};

	const UserTemplate = ({ id, branchid, name, domain, extension, created, state, status, trunkstate, ...other }, TYPE_CODE, orgid) => {
		let extensionExists = other?.extension_exists;
		let stateElement = getStatusElementForTemplate(state);
		let statusElement = '';
		switch (status) {
			case -2:
				statusElement = ''; // "nothing" - it's only for Park's
				break;
			case -1:
				statusElement = `
								<span class="reSyncPassword" style="color: #2196f3;cursor: pointer;padding: 8px;opacity: 0.75;" alt="Resync Password" data-id="${id}" data-userid="${other?.userid || ''}" data-branch="${branchid}" data-extension="${extension}">
									<svg fill="#000000" width="15px" height="15px" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M370.72 133.28C339.458 104.008 298.888 87.962 255.848 88c-77.458.068-144.328 53.178-162.791 126.85-1.344 5.363-6.122 9.15-11.651 9.15H24.103c-7.498 0-13.194-6.807-11.807-14.176C33.933 94.924 134.813 8 256 8c66.448 0 126.791 26.136 171.315 68.685L463.03 40.97C478.149 25.851 504 36.559 504 57.941V192c0 13.255-10.745 24-24 24H345.941c-21.382 0-32.09-25.851-16.971-40.971l41.75-41.749zM32 296h134.059c21.382 0 32.09 25.851 16.971 40.971l-41.75 41.75c31.262 29.273 71.835 45.319 114.876 45.28 77.418-.07 144.315-53.144 162.787-126.849 1.344-5.363 6.122-9.15 11.651-9.15h57.304c7.498 0 13.194 6.807 11.807 14.176C478.067 417.076 377.187 504 256 504c-66.448 0-126.791-26.136-171.315-68.685L48.97 471.03C33.851 486.149 8 475.441 8 454.059V320c0-13.255 10.745-24 24-24z"/></svg>
								</span>
								<span class="activateUser" data-toggle="modal" data-target="#createActivationModal" style="padding: 4px;color:#2196f3; cursor:pointer;" alt="Activate User" data-id="${id}" data-branch="${branchid}" data-extension="${extension}"><i class="fa fa-plug" aria-hidden="true"></i></span>`;
				break;
			case 1:
				statusElement = `
								<span class="reSyncPassword" style="color: #2196f3;cursor: pointer;padding: 8px;;opacity: 0.75;" alt="Resync Password" data-id="${id}" data-userid="${other?.userid || ''}" data-branch="${branchid}" data-extension="${extension}">
									<svg fill="#000000" width="15px" height="15px" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M370.72 133.28C339.458 104.008 298.888 87.962 255.848 88c-77.458.068-144.328 53.178-162.791 126.85-1.344 5.363-6.122 9.15-11.651 9.15H24.103c-7.498 0-13.194-6.807-11.807-14.176C33.933 94.924 134.813 8 256 8c66.448 0 126.791 26.136 171.315 68.685L463.03 40.97C478.149 25.851 504 36.559 504 57.941V192c0 13.255-10.745 24-24 24H345.941c-21.382 0-32.09-25.851-16.971-40.971l41.75-41.749zM32 296h134.059c21.382 0 32.09 25.851 16.971 40.971l-41.75 41.75c31.262 29.273 71.835 45.319 114.876 45.28 77.418-.07 144.315-53.144 162.787-126.849 1.344-5.363 6.122-9.15 11.651-9.15h57.304c7.498 0 13.194 6.807 11.807 14.176C478.067 417.076 377.187 504 256 504c-66.448 0-126.791-26.136-171.315-68.685L48.97 471.03C33.851 486.149 8 475.441 8 454.059V320c0-13.255 10.745-24 24-24z"/></svg>
								</span>
								<span class="resetPassword" data-toggle="modal" data-target="#resetPasswordModal" data-id="${id}" data-branch="${branchid}" data-extension="${extension}">
								  <button type="button" class="btn btn-secondary" data-toggle="tooltip" data-placement="right" title="Reset password" style="
										margin: 0;
										padding:  0;
										background: transparent;
										border:  none;
										color: #393939;
										padding: 8px;
									">
									<i class="fa fa-key" aria-hidden="true" style="padding-bottom: 3px;color: #e3ba1b;"></i>
								  </button>
								</span>
								<span class="deactivateUser" style="padding-left: 8px;padding-right: 4px;color: #2196f3;cursor: pointer;" alt="Deactivate User" data-id="${id}" data-userid="${other?.userid || ''}" data-branch="${branchid}" data-extension="${extension}">
									<i class="fa fa-plug" aria-hidden="true" style="color:#c31919;"></i>
								</span>
								`; // "Deactivate" | "Editing" | "reSync Password"
				break;
			case 2:
				statusElement = `<span class="detachUser" alt="Remove from user" style="cursor:pointer;" data-id="${id}" data-userid="${other?.userid || ''}" data-branch="${branchid}"><i class="fa fa-eraser" aria-hidden="true"></i></span>`;
				break;
		}

		const branchname = $('#group_exist_connections_container').children(`[data-id=${branchid}]`).attr('data-name');

		let templateUserEditModal = '';
		if (TYPE_CODE != "PARK") {
			const userModalData = {
				id,
				accountid: orgid,
				domain,
				name,
				email: other?.info?.email || '',
				extension,
				auth_name: other?.authname || '',
				sip_password: other?.password || '',
				sip_username: other?.username || '',
				mobile: other?.mobile || other?.info?.mobile || '',
				created
			};
			templateUserEditModal = modalEditUser(userModalData);
		}

		return (`
			<div class="card user_card" style="width: 14rem;${extensionExists ? '' : 'filter: opacity(0.65) blur(0.45px);border: 1px solid rgb(183 0 0 / 26%);'}"  id="user_card_${id}" data-id="${id}" data-branch-id="${branchid}" data-branch-name="${branchname}" data-type-code="${TYPE_CODE}" data-number="${extension.replace('park+*', '')}" data-extension-exist="${extensionExists ? true : ''}">
				<div class="card-body card-body-p" style="padding: 1rem 1rem 0.5rem 1rem;">
					<div style="display: flex; flex-direction: row;align-items: center;justify-content: space-between;">
						<h5 class="card-title" id="user_name" style="overflow: hidden;text-overflow: ellipsis;text-wrap: nowrap;font-weight: 200;font-size: 12.5pt;align-items: center;color: #007bff;margin: 0rem;">
							${name || extension}
						</h5>
						<div id="user_card_options_${id}" data="${id}" data-account="${branchid}" data-type-code="${TYPE_CODE}" class="user_card_options" style="position: absolute;right: -2rem;top: 2.5rem;background: white;box-shadow: rgba(0, 0, 0, 0.15) 0px 0px 15px 3px;z-index: 1;border-radius: 2px;display:none;">
							<div style="display: flex;flex-direction: column;align-items: start;justify-content: space-between;">
								${TYPE_CODE != "PARK" ? `
									<button id="edit_user_${id}" data="${id}" data-account="${branchid}" data-type-code="${TYPE_CODE}" data-toggle="modal" data-target="#editUserModal_${id}" class="edit_user" style="border: none;cursor: pointer;margin-bottom: 0rem;padding: 0.45rem;width: 100%;">
										<i id="edit_user_icon_${id}" class="fa fa-edit" aria-hidden="true"></i>
										<span style="padding: 0px 5px 0px 2px;">Edit</span>
									</button>
									
								` : ''}
								<a id="delete_user_${id}" data="${id}" data-account="${branchid}" data-type-code="${TYPE_CODE}" class="delete_user" style="cursor: pointer;margin-bottom: 0rem;padding: 0.45rem;width: 100%;color:#cb0000;">
									<i class="fa fa-trash" aria-hidden="true" style="padding: 0px 4px 0px 1px;"></i>
									<span padding: 0px 5px;>Delete</span>
								</a>
								<span id="deleting_user_loading_${id}" style="display: none;margin-bottom: 0rem;padding: 0rem 0rem;">
									 <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
									 Deleting...
								</span>
							</div>
						</div>
						<a id="options_user_${id}" data="${id}" data-account="${branchid}" data-type-code="${TYPE_CODE}" class="options_user" style="cursor: pointer;margin-bottom: 0rem;padding: 0.25rem;">
							<i id="options_user_icon_${id}" class="fa fa-bars" aria-hidden="true"></i>
						</a>
					</div>
					<div>
						<p class="card-title" id="user_name" style="font-weight: 500;align-items: center;margin-bottom: 0.25rem;">
							${domain}
						</p>
						<div style='display:flex;flex-direction: row;'>
							<p class="card-text" id="user_domain" style="font-family: monospace;font-weight: 200;margin-bottom: 0rem;font-size: 10pt;">
								${extension}
							</p>
							${extensionExists ? '' : '<div style="bottom: 0;right: 0;font-size: 8pt;padding:  0.25rem;font-weight: bold;filter: none !important;">(Not Exist)</div>'}
						</div>
					<div style="display: flex;flex-direction: row;justify-content: space-between;font-size: 11pt;font-weight: 700;">
						<div id='user_state_${id}' style='vertical-align: middle;justify-content: center;align-items: center;display: flex;'>
							${stateElement}
						</div>
						<div id='user_status_${id}' style="font-size: 17pt;display:flex;align-items: center;">
							${statusElement}
						</div>
					</div>
					<p class="card-text">
						<small class="text-muted" id="user_created">
							${'Created: ' + new Date(created).toLocaleDateString()}
						</small>
					</p>
				</div>
				${TYPE_CODE != "PARK" ?
				`<div id="modal_edit_user_entry_${id}" class="modal_edit_user_entry">${templateUserEditModal}</div>`
				: ''}
			</div>`
		)
	}

	const optionalUsersEvent = () => {
		// clear event listeners
		$('.options_user').off('click');
		// Bind Event Listener [Options User] 
		$('.options_user').on('click', (function (e) {
			const id = e.currentTarget.id.split('_').pop();
			$('.user_card_options').slideUp(50);
			if ($('#options_user_' + id).hasClass('active-option')) {
				$('#options_user_' + id).removeClass('active-option');
				$('#user_card_options_' + id).slideUp(100);
			} else {
				// console.log('options_user', id);
				$('#options_user_' + id).addClass('active-option');
				$('#user_card_options_' + id).slideDown(100);
			}
		}));
	}

	const editUserEvent = () => {
		// clear event listeners
		$('.edit_user').off('click');
		// Bind Event Listener [Edit User] 
		$(".edit_user").on('click', (function (e) {
			const id = e.currentTarget.id.split('_').pop();

			// console.log('edit_user', id);

			$('#editUserModal_' + id).click();
		}));
	}

	const deleteUsersEvent = () => {
		// clear event listeners
		$('.delete_user').off('click');
		// Bind Event Listener [Delete User] 
		$(".delete_user").on('click', (function (el) {
			// fade out text
			$('.create_extensions_text').fadeOut(300);
			// get options from element
			const userId = el.currentTarget.getAttribute('data');
			const orgid = $('#delete_organization').attr('data-account');
			const TYPE_CODE = $(el.currentTarget).attr('data-type-code');
			$('#create_connect_button').attr('disabled', true);
			$('#create_users_button').attr('disabled', true);
			$('#delete_user_' + userId).fadeOut(300);
			setTimeout(() => $('#deleting_user_loading_' + userId).fadeIn(), 300);
			// DELETE USER
			$.ajax({
				url: "/app/ringotel/service.php?method=deleteUser",
				type: "get",
				cache: true,
				data: {
					id: userId,
					orgid: orgid
				},
				success: function (response) {
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					$('#create_user_loading').fadeOut();

					// Decrease counting
					switch (TYPE_CODE) {
						case 'PARK':
							$('#parks_count').text($('#parks_count').text() - 1);
							break;
						case 'USER':
							$('#users_count').text($('#users_count').text() - 1);
							break;
						case 'EXTENSION':
							$('#extensions_count').text($('#extensions_count').text() - 1);
							break;
					}

					// get users list per connect
					$('.delete_connect').map((k, item) => {
						const orgid = item.getAttribute("data-account");
						const branchid = item.getAttribute("data");

						// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
						if (TYPE_CODE !== 'PARK') {
							getUsersWithUpdateElements(orgid, branchid);
						} else {
							$('#parks_save').addClass('attention');
						}
					});

					// clear exists element card
					$('#user_card_' + userId).fadeOut(300);
					setTimeout(() => {
						// remove exists element card
						$('#user_card_' + userId).remove();
					}, 300);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		}));
	};

	const reInitActivateForExtEvent = () => {
		// clear events for ext_activate
		$('.ext_activate').off('click');
		// set activate for extension 
		$('.ext_activate').on('click', ((e) => {
			const uuid = e.target.getAttribute('data-uuid');
			if ($('#ext_email_' + uuid).val().trim()) {
				if ($('#ext_email_' + uuid).hasClass('alert-danger')) {
					$('#ext_email_' + uuid).removeClass('alert-danger');
				}
			} else {
				$('#ext_activate_').attr('checked', false);
				$('#ext_activate_' + uuid)[0].checked = false;
				$('#ext_email_' + uuid).addClass('alert-danger');
			}
		}));
	};

	const createListExistExtensions = () => {
		const extensionsPHP = <?php echo json_encode($extensions) ?>;
		// console.log('[extensionsPHP]', extensionsPHP);
		const extensionsPHPListTemplated = extensionsPHP?.map((ext) => {
			return ExistsExtensionTemplate({
				extension: ext?.extension,
				extension_uuid: ext?.extension_uuid,
				effective_caller_id_name: ext?.effective_caller_id_name,
				effective_caller_id_number: ext?.effective_caller_id_number
			});
		});
		$('#table_exists_extensions').html(extensionsPHPListTemplated);
		// re-init activate extension event
		// reInitActivateForExtEvent();
	};

	const getUsers = (orgid, branchid, type) => {
		if (type !== 'HIDE') {
			$('#create_users_text').fadeOut(300);
			$('#create_users_button').attr('disabled', true);
		}

		return new Promise((resolve, reject) => {
			setTimeout(() => {
				$('.create_extensions_loading').fadeOut(300);
				$('#create_users_loading').fadeIn();
				return $.ajax({
					url: "/app/ringotel/service.php?method=getUsers",
					type: "get",
					cache: true,
					data: {
						orgid,
						// branchid
					}
				}).then((response) => {
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					// Clear List Of "Create Users" Extensions
					result.map((ext) => {
						$('#extension_line_' + ext.extension).remove();
					});
					if (type !== 'HIDE') {
						$('#create_users_loading').fadeOut(300);
					}
					resolve(result);
				});
			}, 300);
		});
	};

	const getUsersWithUpdateElements = async () => {
		// Disable Buttons
		$('#create_connect_button').attr('disabled', true);
		$('#create_users_button').attr('disabled', true);

		// List of Exist Extension Reset 
		createListExistExtensions();
		// get orgid 
		const orgid = $('#delete_organization').attr('data-account');
		// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
		const parksUserExtensions = await getUsers(orgid);
		// console.log('[getUsersWithUpdateElements] ------------> parksUserExtensions', parksUserExtensions);

		// MAP ALL DATA FROM ALL BRANCHES
		const regexp = /[\+*]/; // Parks param detect
		const parks = parksUserExtensions.filter((ext) => ext.extension.match(regexp));
		const users = parksUserExtensions.filter((ext) => (!ext.extension.match(regexp) && ext.status === 1)).sort((a, b) => parseInt(a.extension) - parseInt(b.extension));
		const extensions = parksUserExtensions.filter((ext) => (!ext.extension.match(regexp) && ext.status !== 1)).sort((a, b) => parseInt(a.extension) - parseInt(b.extension));

		const parks_count = parks?.length;
		const users_count = users?.length;
		const extensions_count = extensions?.length;

		// clear exists elements of users data
		clearParksUsersExtensions();

		// elements of parks
		if (Array.isArray(parks) && parks_count > 0) {
			const ParksElements = parks.map((param) => UserTemplate(param, 'PARK', orgid));
			$('#group_exist_parks_list').html(ParksElements);
			// skip the delete functional
			// it'll be available only if the user push the edit button
			$('.delete_user[data-type-code=PARK]').css('display', 'none');
			$('.user_card_options[data-type-code=PARK]').css('display', 'none');
			$('.options_user[data-type-code=PARK]').css('display', 'none');

			// event listeners for elements
			eventParksSave();
			eventParksEdit();
			detachUserEvent();
			activateUserEvent();
			deactivateUserEvent();
		}

		// elements of users
		if (Array.isArray(users) && users_count > 0) {
			const UsersElements = users.map((param) => UserTemplate(param, 'USER', orgid));
			$('#group_exist_users_list').html(UsersElements);

			// event listeners for elements
			detachUserEvent();
			activateUserEvent();
			deactivateUserEvent();
			reSyncPasswordEvent();
			reSyncAllNamesEvent();
			reSyncAllPasswordEvent();
			resetUserPasswordEvent();

			// Add Users to Select List for Integration
			$('#manage_numbers_users').html(users?.map((item) => {
				return `<option value="${item?.id}">${item?.name} (${item?.extension})</option>`;
			}));

			// Skip Alert Note
			$('#not_exist_integration_note').slideUp();

			// Clear Select Field for Add Users Manage Members
			$('#manage_numbers_users').parent().children('#multiselect_dropdown').remove();

			// Multi Values For Inputs (Just For Select Fields)
			MultiSelectDropDownBindSelectors({ id: 'manage_numbers_users' });

			$('#manage_numbers_activate_button').attr('disabled', false);
		} else {
			// Show Alert Note
			$('#not_exist_integration_note').fadeIn(300);

			// Cleanusers in Select List of integration
			$('#manage_numbers_users').html('');

			// Manage Numbers Clear
			$('#multiselect_dropdown').remove();
			$('#manage_numbers_users').show();
		}

		// elements of users
		if (Array.isArray(extensions) && extensions_count > 0) {
			const ExtensionsElements = extensions.map((param) => UserTemplate(param, 'EXTENSION', orgid));
			$('#group_exist_extensions_list').html(ExtensionsElements);

			// event listeners for elements
			detachUserEvent();
			activateUserEvent();
			reSyncPasswordEvent();
			reSyncAllNamesEvent();
			reSyncAllPasswordEvent();
			deactivateUserEvent();
		}

		// Users Created Modal - create users elements inside Button
		$('#create_extensions_loading').fadeOut(300);

		// show block with users
		setTimeout(() => {
			$('#create_users_button').attr('disabled', false);
			$('#create_users_text').fadeIn(300);

			// Users Created Modal - create users elements inside Button
			$('#create_extensions_text').fadeIn(300);

			// console.log('[		Fade IN		users list ]');
			if (Array.isArray(parks) && parks?.length > 0) {
				$('#group_exist_parks').fadeIn(300);
			}
			if (Array.isArray(users) && users?.length > 0) {
				$('#group_exist_users').fadeIn(300);
			}

			if (Array.isArray(extensions) && extensions?.length > 0) {
				$('#group_exist_extensions').fadeIn(300);
			}
			if ((Array.isArray(parks) && parks?.length > 0) ||
				(Array.isArray(users) && users?.length > 0) ||
				(Array.isArray(extensions) && extensions?.length > 0)) {
				// functional for cards
				deleteUsersEvent();
				optionalUsersEvent();
				editUserEvent();
				modalEditUsersFn();

				// Cheker User Status
				if (!calledUserStateChecker) {
					checkStateUsers();
				}
			}

			// Counting all Parks, Users and Extensions
			counting(parks_count, users_count, extensions_count);

			// Enable Buttons
			$('#create_connect_button').attr('disabled', false);
			$('#create_users_button').attr('disabled', false);

		}, 300);
		return parksUserExtensions;
	};

	const ConnectionTemplate = ({ id, accountid, name, domain, address, created }, modalEdit) => {
		return (`
		 <div class="card connection" style="width: 18rem;" id="connect_card_${id}" data-id="${id}" data-name="${name}">
			   <div class="card-body card-body-p" style="padding: 1rem 1rem 0.5rem 1rem;">
				<div style="display: flex; flex-direction: row;align-items: center;justify-content: space-between;">
					<h5 class="card-title" id="connect_name" style="font-weight: 200;font-size: 14pt;align-items: center;color: #007bff;margin: 0rem;overflow: hidden;text-overflow: ellipsis;text-wrap: nowrap;">${name}</h5>
					<span id="editConnectModal_button_${id}" class="btn btn-outline-secondary" data-toggle="modal" data-target="#editConnectionModal_${id}" style="cursor:pointer;opacity: 0.8;position: absolute;bottom: 0;right: 0;margin: 20px;padding: 0px;border: 0;line-height: normal;">
						<svg width="15px" height="15px" viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
							<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
								<g id="Dribbble-Light-Preview" transform="translate(-99.000000, -400.000000)" fill="#000000">
									<g id="icons" transform="translate(56.000000, 160.000000)">
										<path d="M61.9,258.010643 L45.1,258.010643 L45.1,242.095788 L53.5,242.095788 L53.5,240.106431 L43,240.106431 L43,260 L64,260 L64,250.053215 L61.9,250.053215 L61.9,258.010643 Z M49.3,249.949769 L59.63095,240 L64,244.114985 L53.3341,254.031929 L49.3,254.031929 L49.3,249.949769 Z" id="edit-[#1479]">
										</path>
									</g>
								</g>
							</g>
						</svg>
					</span>
					<a id="delete_connect_${id}" data="${id}" data-account="${accountid}" class="delete_connect" style="cursor: pointer;margin-bottom: 0rem;padding: 0.45rem;"><i class="fa fa-trash" aria-hidden="true"></i></a>
					<span id="deleting_connect_loading_${id}" style="display: none;margin-bottom: 0rem;padding: 0rem 0rem;">
						<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
						Deleting...
					</span>
				</div>
				<p class="card-title" id="connect_name" style="font-weight: 500;align-items: center;">${domain}</p>
				<p class="card-text" id="connect_domain" style="font-family: monospace;font-weight: 700;">${address}</p>
				<p class="card-text"><small class="text-muted" id="connect_created">${'Created: ' + new Date(created).toLocaleDateString()}</small></p>
		   </div>
		   ${modalEdit}
		   `
		)
	};

	const deleteConnectionsEvent = () => {
		// clear event listeners
		$('#myimage').off('click');
		// Bind Event Listener [Delete Connection] 
		$(".delete_connect").on('click', (function (el) {
			const branchId = el.currentTarget.getAttribute('data');
			const orgId = el.currentTarget.getAttribute('data-account');
			$('#create_connect_button').attr('disabled', true);
			$('#create_users_button').attr('disabled', true);
			$('#delete_connect_' + branchId).fadeOut(300);
			setTimeout(() => $('#deleting_connect_loading_' + branchId).fadeIn(), 300);
			// DELETE CONNECTION
			$.ajax({
				url: "/app/ringotel/service.php?method=deleteBranch",
				type: "get",
				cache: true,
				data: {
					id: branchId,
					orgid: orgId
				},
				success: function (response) {
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					$('.create_connect_loading').fadeOut();
					// console.log('[deleteConnection] result', result);
					// update list of connection
					getConnections(orgId);
					// clear exists element card
					$('#connect_card_' + branchId).fadeOut(300);
					// hueta
					setTimeout(() => {
						$('#create_connect_button').attr('disabled', false);
						$('#create_users_button').attr('disabled', false);
					}, 300);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		}));
	};

	const counting = (parks, users, extensions) => {
		$('#parks_count').text(parks);
		$('#users_count').text(users);
		$('#extensions_count').text(extensions);
	};

	const clearParksUsersExtensions = () => {
		// clear exists elements of users data
		$('#group_exist_parks_list').html('');
		$('#group_exist_users_list').html('');
		$('#group_exist_extensions_list').html('');
	};


	// Check Connections
	const getConnections = (orgid) => {
		$('#extension_module').fadeOut(300);
		$('#parks_module').fadeOut(300);
		$('#connection_module').fadeIn(300);
		$('#create_connect_button').attr('disabled', true);
		$('.create_connect_text').fadeOut(300);

		setTimeout(() => {
			$('.create_connect_loading').fadeIn();
			$.ajax({
				url: "/app/ringotel/service.php?method=getBranches",
				type: "get",
				cache: true,
				data: {
					orgid
				}
			}).then(async (response) => {
				$('.create_connect_loading').fadeOut(300);
				const { result } = JSON.parse(response.replaceAll("\\", ""));
				// console.log('[getConnections] result', result);

				// elements of connections
				if (Array.isArray(result) && result?.length > 0) {
					const CennectionsElements = result.map((param, k) => {
						const template = ConnectionTemplate(param, modalEditConnection(param));
						if (k === 0) {
							$('#group_exist_connections_container').html(template);
						} else {
							$('#group_exist_connections_container').append(template);
						}
						// update model for select field
						$('#protocol_ec_input_' + param.id).val(param?.provision?.protocol);
						$('#r_inbound_numbers_ec_input_' + param.id).val(param?.provision?.inboundFormat);
						// modal functional
						modalEditConnectionFn(param.id);
					});
					// show the parks list
					$('#parks_module').fadeIn(300);
					// show the users list
					$('#extension_module').fadeIn(300);

					// Clear users_selector_branch for Modale window
					$('#users_selector_branch')[0].innerHTML = '';

					// Clear users_selector_branch_for_parks for Parks Create Modale window
					$('#users_selector_branch_for_parks')[0].innerHTML = '';

					// create Connections selector 
					result.map(async (item) => {
						$('#users_selector_branch').append(`<option id="users_selector_branch_${item.id}" data-account="${item.accountid}" data-branch="${item.id}" data-branch-name="${item.name}">${item.name} - ${item.address}</option>`);
						$('#users_selector_branch_for_parks').append(`<option id="users_selector_branch_${item.id}" data-account="${item.accountid}" data-branch="${item.id}" data-branch-name="${item.name}">${item.name} - ${item.address}</option>`);
					});

					// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
					getUsersWithUpdateElements();
				}

				// show block with connections
				setTimeout(() => {
					$('#create_connect_button').attr('disabled', false);
					$('.create_connect_text').fadeIn(300);
					if (Array.isArray(result) && result?.length > 0) {
						$('#group_exist_connections').fadeIn(300);
						// show the parks list
						$('#parks_module').fadeIn(300);
						// show the users list
						$('#extension_module').fadeIn(300);
						// functional for cards
						deleteConnectionsEvent();
					} else {
						$('#create_connect_button').attr('disabled', false);
						$('.create_connect_text').fadeIn(300);
					}

				}, 300);
			});
		}, 300);
	};

	const getPackage = (id) => {
		switch (id) {
			// case 1:
			// 	return `<span class="packageid_switcher">PRO</span>`;
			// case 2:
			// 	return `<span class="packageid_switcher active">PRO</span>`;
			case 1:
				return `<label class="switch" id="checkbox-switcher-for-integrations" style="margin: 0;transform: scale(0.65);"><input id="checkbox-switcher-for-integrations-input" type="checkbox"></input><div class="slider round"></div></label><div>PRO</div>`;
			case 2:
				return `<label class="switch" id="checkbox-switcher-for-integrations" style="margin: 0;transform: scale(0.65);"><input id="checkbox-switcher-for-integrations-input" type="checkbox" checked class="active"></input><div class="slider round"></div></label><div>PRO</div>`;

		}
	};

	// [Init] Check Organization
	const getOrganization = () => {
		$('#integration_create').attr('disabled', true);
		// Set init Values
		$('#domain_unique_name').val('<?php echo $default_domain_unique_name ?>');
		$('#connection_domain').val('<?php echo $_SESSION['domain_name'] . ':' . (isset($_SESSION['ringotel']['ringotel_organization_port']['text']) ? $_SESSION['ringotel']['ringotel_organization_port']['text'] : '5070') ?>');
		$('#maxregs').val('1');

		return $.ajax({
			url: "/app/ringotel/service.php?method=getOrganization",
			type: "get",
			cache: true,
			success: function (response) {
				const { result } = JSON.parse(response.replaceAll("\\", ""));
				// console.log('[getOrganization] result', result?.id);
				$('#init_loading').fadeOut(300);
				setTimeout(() => {
					if (result?.id) {
						$('#create_organization').attr('disabled', false);
						$('#create_org_loading').fadeOut(600);
						$('#not_exist_organization_note').fadeOut(300);
						$('#not_exist_organization').fadeOut(300);

						setTimeout(() => {
							$('#exist_organization').fadeIn(300);
							$('#organizations').fadeIn(300);
							$('#organization_name').text(result.name);
							$('#organization_domain').text(result.domain);
							$('#delete_organization').attr('data-account', result.id);
							$('#delete_organization').attr('data-account-domain', result.domain);
							$('#organization_created').text('Created: ' + new Date(result.created).toLocaleDateString());
							$('#create_org_text').fadeIn(300);

							// create Label of package
							if (result?.packageid) {
								// console.log('packageid_switcher_container', result?.packageid);
								$('#packageid_switcher_container').attr('data-org', result.id);
								$('#packageid_switcher_container').html(getPackage(result.packageid));
							}

							// event for swithching pro version
							eventSwitchProFn(result.id);
							// get Integration
							getIntegration({ packageid: result?.packageid }); // Parameters For Integration

						}, 600);

						// get Branches
						getConnections(result.id);

					} else {
						$('#not_exist_organization_note').fadeIn(300);
						$('#not_exist_organization').fadeIn(300);
					}
				}, 300);

			},
			error: function (jqXHR, textStatus, errorThrown) {
				// console.log(textStatus, errorThrown);
			}
		});
	};

	// Create Organization
	$('#create_organization').on('click', (function () {
		$('#create_organization').attr('disabled', true);
		domain_unique_name = $('#domain_unique_name').val();
		$('#create_org_text').fadeOut(300);
		setTimeout(() => {
			$('#create_org_loading').fadeIn();
			$.ajax({
				url: "/app/ringotel/service.php?method=createOrganization",
				type: "get",
				cache: true,
				data: {
					domain: domain_unique_name
				},
				success: function (response) {
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					// console.log('[create_organization] success', result);
					if (result?.id) {
						$('#createOrgModal').click();
						setTimeout(() => {
							updateOrganizationWithDefaultSettings({ orgid: result.id }).then(() =>
								getOrganization());
						}, 300);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		}, 300);
	}));

	// Delete Organization
	$("#delete_organization").on('click', (function () {
		$('#create_connect_button').attr('disabled', true);
		$('#create_users_button').attr('disabled', true);
		$('#delete_organization').fadeOut(300);
		setTimeout(() => $('#deleting_org_loading').fadeIn(), 300);
		const orgid = $('#delete_organization').attr('data-account');
		// DELETE ORGANIZAITION
		$.ajax({
			url: "/app/ringotel/service.php?method=deleteOrganization",
			type: "get",
			cache: true,
			data: {
				id: orgid,
			},
			success: function (response) {
				$('#create_org_loading').fadeOut();
				setTimeout(() => {
					$('#create_connect_button').attr('disabled', false);
					$('#create_users_button').attr('disabled', false);
				}, 300);
				// re-update the organization list
				// getOrganization();
				location.reload(); // TEST DISABLED
			},
			error: function (jqXHR, textStatus, errorThrown) {
				// console.log(textStatus, errorThrown);
			}
		});
	}));

	// Create Connection
	$("#create_connect_button").on('click', (() => {
		$('#create_connect_button').attr('disabled', true);
		$('#create_users_button').attr('disabled', true);
		$('#delete_organization').fadeOut(300);
		$('.delete_connect').fadeOut(300);
		const orgid = $('#delete_organization').attr('data-account');

		const maxregs = $('#maxregs').val();

		const connection_domain = $('#connection_domain').val();

		$('.create_connect_text').fadeOut(300);
		$('#createConnectionModal').click();
		setTimeout(() => {
			$('.create_connect_loading').fadeIn(300);
			$.ajax({
				url: "/app/ringotel/service.php?method=createBranch",
				type: "get",
				cache: true,
				data: {
					orgid,
					maxregs,
					connection_domain
				},
				success: function (response) {
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					// console.log('[createConnection] result', result);
					setTimeout(() => {
						if (result?.id) {
							// load Branches (Connections)
							updateBranchWithDefaultSettings(orgid, result.id).then(() => getConnections(orgid));

							// other hueta
							$('#create_connect_button').attr('disabled', false);
							$('#create_users_button').attr('disabled', false);
							$('#delete_organization').fadeIn(300);
							$('.delete_connect').fadeIn(300);
						}
					}, 300);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		},
			300);
	}));

	// Create Parks
	$("#create_parks_button").on('click', (() => {
		// Exist Parks List
		const parks_numbers_list = [...$('.user_card[data-type-code=PARK]')]?.map((park) => {
			return {
				park: park?.getAttribute('data-number'),
				connection: park?.getAttribute('data-branch-id'),
				connectionName: park?.getAttribute('data-branch-name')
			}
		});

		const from_park_number = $('#from_park_number').val();
		const to_park_number = $('#to_park_number').val();

		const pass = { from: false, to: false };

		// check the fields values
		if (from_park_number.length == 0) {
			$('#from_park_number').addClass('alert-danger');
			pass.from = false;
		} else {
			if ($('#from_park_number').hasClass('alert-danger')) {
				$('#from_park_number').removeClass('alert-danger');
			}
			pass.from = true;
		}

		if (to_park_number.length == 0) {
			$('#to_park_number').addClass('alert-danger');
			pass.to = false;
		} else {
			if ($('#to_park_number').hasClass('alert-danger')) {
				$('#to_park_number').removeClass('alert-danger');;
			}
			pass.to = true;
		}

		if (pass.from && pass.to) {
			$('#create_users_button').attr('disabled', true);

			// button on the modal
			$('#create_parks_button').attr('disabled', true);
			$('#create_parks_text').slideUp(300);
			$('#create_parks_loading').slideDown(300);

			// button for the open modal
			$('#create_parks_modal_button').attr('disabled', true);

			const orgid = $('#delete_organization').attr('data-account');

			setTimeout(() => {
				const branchid = $('#users_selector_branch_for_parks').children(":selected").attr("data-branch");
				const branchname = $('#users_selector_branch_for_parks').children(":selected").attr("data-branch-name");

				// Set Exists Parks
				const parks_exist = parks_numbers_list?.filter(({ connection }) => connection === branchid)?.map(({ park }) => parseInt(park));
				// console.log('----------------> parks_numbers_list', parks_exist);

				// Concat Exist Parks and Interval of Parks
				const park_array = [...parks_exist];
				for (let i = from_park_number; i <= to_park_number; i++) {
					park_array.push(parseInt(i));
				}

				const data = {
					orgid,
					id: branchid,
					name: branchname,
					from_park_number: Math.min(...park_array),
					to_park_number: Math.max(...park_array),
					park_array: [...new Set(park_array)]?.sort((a, b) => a - b)
				};

				// console.log('----------------> data', data);

				$.ajax({
					url: "/app/ringotel/service.php?method=updateParksWithUpdatedSettings",
					type: "get",
					cache: true,
					data,
					success: function (response) {
						const res = JSON.parse(response.replaceAll("\\", ""));
						// console.log('[createParks] result', Array.isArray(res) && res?.length === 0);
						if (Array.isArray(res) && res?.length === 0) {
							// Button for Create Parks
							$('#create_users_button').attr('disabled', false);

							// Button on the Modal
							$('#create_parks_button').attr('disabled', false);
							$('#create_parks_text').slideDown(300);
							$('#create_parks_loading').slideUp(300);

							// Button for the Open Modal
							$('#create_parks_modal_button').attr('disabled', false);

							// Close Modal
							$('#createParksModal').click();

							// Load Branches (Connections)
							getConnections(orgid);
						}
					},
					error: function (jqXHR, textStatus, errorThrown) {
						// console.log(textStatus, errorThrown);
					}
				});
			}, 300);
		}
	}));

	//// Extension Modal Add + 
	// [select all]
	$('#extensions_select_all').on('click', ((e) => {
		$('.extension_column').attr('checked', e.target.checked);
		$('.extension_column').map((k, el) => {
			el.checked = e.target.checked;
		});
	}));

	//							  \\
	///						     \\\
	//// EXTENSIONs LIST CREATE \\\\
	///							 \\\
	//							  \\

	$('#create_extensions_button').on('click', (() => {
		const list_extensions_to_add = [...$('.extension_column')?.filter((k, el) => el.checked)?.map((k, el) => {
			const res = {};
			const data_uuid = el.getAttribute('data-uuid');
			const data_obj = [...$("input[data-uuid='" + data_uuid + "']")?.map((k, el) => {
				res[el.name] = el.type === 'checkbox' ? el.checked : el.value;
			})];
			res['extension_uuid'] = data_uuid;
			return res;
		})];
		// console.log('list_extensions_to_add', list_extensions_to_add);

		if (Array.isArray(list_extensions_to_add) && list_extensions_to_add?.length > 0) {
			$('.create_extensions_text').fadeOut(300);
			$('#create_extensions_button').attr('disabled', false);
			$('#create_users_button').attr('disabled', false);
			$('#create_extensions_button').attr('disabled', false);
			setTimeout(() => {
				$('.create_extensions_loading').fadeIn(300);
				$('#createUserModal').click();

				const branchid = $('#users_selector_branch').children(":selected").attr("data-branch");
				const branchname = $('#users_selector_branch').children(":selected").attr("data-branch-name");
				const orgid = $('#delete_organization').attr('data-account');
				const orgdomain = $('#delete_organization').attr('data-account-domain');
				$.ajax({
					url: "/app/ringotel/service.php?method=createUsers",
					type: "post",
					cache: true,
					data: {
						orgid,
						orgdomain,
						branchid,
						branchname,
						preusers: list_extensions_to_add
					},
					success: function (response) {
						// const { result } = JSON.parse(response.replaceAll("\\", ""));
						// // console.log('[createExtensions] result', result);
						setTimeout(() => {
							$('#create_extensions_loading').fadeOut(300);
							// if (result?.id) {

							// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
							getUsersWithUpdateElements();

							// other hueta
							$('#create_connect_button').attr('disabled', false);
							$('#create_users_button').attr('disabled', false);
							$('#create_extensions_button').attr('disabled', false);

							setTimeout(() => {
								$('.create_extensions_text').fadeIn(300);
							});
						}, 300);
					},
					error: function (jqXHR, textStatus, errorThrown) {
						// console.log(textStatus, errorThrown);
					}
				});
			}, 300);
		} else {

		}
	}));

	const updateUser = ({ extension, orgid, id, email, status }) => {
		const data = {
			extension,
			orgid,
			id,
			email,
			status
		};
		// console.log('[udpateUser] data', data);
		return $.ajax({
			url: "/app/ringotel/service.php?method=updateUser",
			type: "post",
			data
		}).then((res) => {
			const { result } = JSON.parse(res.replaceAll("\\", ""));
			return result;
		});;
	};

	const getUsersState = (data) => {
		return $.ajax({
			url: "/app/ringotel/service.php?method=usersState",
			type: "post",
			cache: true,
			data
		}).then((res) => {
			const { result } = JSON.parse(res.replaceAll("\\", ""));
			return result;
		});
	};

	const checkStateUsers = () => {
		calledUserStateChecker = true;
		const orgid = $('#delete_organization').attr('data-account');
		const connections = [...$('.connection').map((k, item) => item.getAttribute('data-id'))];
		Promise.all(
			connections.map(async (branchid) => {
				const usersStateArray = await getUsersState({ orgid, branchid });
				if (Array.isArray(usersStateArray) && usersStateArray?.length > 0) {
					usersStateArray.map((user) => {
						const templateForStatusUpdating = getStatusElementForTemplate(user.state);
						if (user?.id) {
							$('#user_state_' + user.id).html(templateForStatusUpdating);
						}
					});
				}
			})
		).then(() => setTimeout(checkStateUsers, 4000));
	};

	const updateBranchWithDefaultSettings = (orgid, branchid) => {
		return $.ajax({
			url: "/app/ringotel/service.php?method=updateBranchWithDefaultSettings",
			type: "post",
			cache: true,
			data: {
				orgid,
				branchid
			},
		}).then((res) => {
			const { result } = JSON.parse(res.replaceAll("\\", ""));
			// console.log('updateBranchWithDefaultSettings', result);
			return result;
		});
	};


	const switchOrganizationMode = ({ orgid, ...other }) => {
		const data = {
			orgid
		};
		if (other?.packageid) {
			data.packageid = other.packageid;
		};
		return $.ajax({
			url: "/app/ringotel/service.php?method=switchOrganizationMode",
			type: "post",
			data
		}).then((res) => {
			const response = JSON.parse(res.replaceAll("\\", ""));
			return response;
		});
	};


	const updateOrganizationWithDefaultSettings = ({ orgid, ...other }) => {
		const data = {
			orgid
		};
		if (other?.packageid) {
			data.packageid = other.packageid;
		};
		return $.ajax({
			url: "/app/ringotel/service.php?method=updateOrganizationWithDefaultSettings",
			type: "post",
			data
		}).then((res) => {
			const response = JSON.parse(res.replaceAll("\\", ""));
			return response;
		});
	};


	// On KeyUp Event Loistener for reset user field 
	$('#email_for_reset_password').on('keyup', ((el) => {
		const email = el?.currentTarget?.value?.trim();
		if (email.length > 3) {
			$('#reset_user_password_button').attr('disabled', false);
			if ($('#email_for_reset_password').hasClass('alert-danger')) {
				$('#email_for_reset_password').removeClass('alert-danger');
			}
		} else {
			$('#reset_user_password_button').attr('disabled', true);
		};
	}));

	// On KeyUp Event Loistener for emial field 
	// $('#email_for_user').on('keyup', ((el) => {
	// 	// console.log('keup', el.currentTarget.value);
	// 	const email = el?.currentTarget?.value?.trim();
	// 	if (email.length > 3) {
	// 		$('#create_user_activate_button').attr('disabled', false);
	// 		if ($('#email_for_user').hasClass('alert-danger')) {
	// 			$('#email_for_user').removeClass('alert-danger');
	// 		}
	// 	} else {
	// 		$('#create_user_activate_button').attr('disabled', true);
	// 	};
	// }));

	// Activate Click for Button
	$('#reset_user_password_button').on('click', (async (el) => {
		// if ($('#email_for_reset_password').val().trim()) {
		// 	if ($('#email_for_reset_password').hasClass('alert-danger')) {
		// 		$('#email_for_reset_password').removeClass('alert-danger');
		// 	}
		const extension = $('#email_for_reset_password').attr('data-extension');
		const orgid = $('#email_for_reset_password').attr('data-orgid');
		const id = $('#email_for_reset_password').attr('data-id');
		const email = $('#email_for_reset_password').val();
		resetPassword({ extension, orgid, id, email });
		// } else {
		// 	$('#email_for_reset_password').addClass('alert-danger');
		// 	$('#reset_user_password_button').attr('disabled', true);
		// };
	}));

	// Activate Click for Button
	$('#create_user_activate_button').on('click', ((el) => {
		// if ($('#email_for_user').val().trim()) {
		// 	if ($('#email_for_user').hasClass('alert-danger')) {
		// 		$('#email_for_user').removeClass('alert-danger');
		// 	}
		const orgid = $('#email_for_user').attr('data-orgid');
		const id = $('#email_for_user').attr('data-id');
		const extension = $('#email_for_user').attr('data-extension');
		const email = $('#email_for_user').val();
		activateUser({ orgid, id, extension, email });
		// } else {
		// 	$('#email_for_user').addClass('alert-danger');
		// 	$('#create_user_activate_button').attr('disabled', true);
		// };
	}));

	const resetPassword = ({ extension, orgid, id, email }) => {
		$('#reset_user_password_button').attr('disabled', true);
		$('#reset_user_password_text').fadeOut(300);

		setTimeout(() => {
			$('#reset_user_password_loading').fadeIn(300);

			// update User Email and status
			updateUser({ extension, orgid, id, email, status: 1 }).then((updatedUserData) => {
				// console.log('[updatedUserData]', updatedUserData);
				return $.ajax({
					url: "/app/ringotel/service.php?method=resetUserPassword",
					type: "post",
					data: {
						orgid,
						id,
						email
					}
				}).then((response) => {
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					// console.log('-------------> [resetPassword] result', result);

					// if (result?.status === true) {
					// 	$('#reset_user_password_button').animate({ width: 'toggle' }, 350);
					// 	$('#reset_user_password_loading').fadeOut(300);
					// 	setTimeout(() => $('#reset_user_password_text').fadeIn(300), 300);
					// 	// setTimeout(() => $('#resetPasswordModal').click(), 1000);
					// }

					if (result?.username) {
						$('#email_for_reset_password').animate({ width: 'toggle' }, 350);

						$('#reset_user_password_button').animate({ width: 'toggle' }, 350);

						$('#modal_body_resetPassword').slideUp(300);

						setTimeout(() => {
							$('#modal_body_resetPassword').slideDown(300);
							$('#reset_user_password_loading').fadeOut(300);

							$('#resetPasswordModal_modal_body').css('max-width', "40rem");

							// `Provisioning instructions have been sent to ${data}`
							const data = {
								domain: result.domain,
								extension: result.extension,
								username: result.username,
								password: result.password,
								email: result.email
							};
							const html = `	
									<div class="card">
									  <div class="card-body card-body-p">
										<h5 class="card-title" style="color: green;">Success</h5>
										<p class="card-text">You successfully activated the user shown below. Please use the generated password for login. You will not be able to view the password again. However, you can reset the password at any time.</p>
										<table style="width: 100%;">
										  <tbody>
											<tr>
											  <td>
											  <table class="table" style="font-weight: 700;margin: 0;">
												<tbody>
													<tr>
													  <td style="font-size: 12pt;">Domain:</td>
													  <td style="font-size: 12pt;">${data.domain}</td>
													</tr>
													<tr>
													  <td style="font-size: 12pt;">Extension:</td>
													  <td style="font-size: 12pt;">${data.extension}</td>
													</tr>
													<tr>
													  <td style="font-size: 12pt;">Username:</td>
													  <td style="font-size: 12pt;">${data.username}</td>
													</tr>
													<tr>
													  <td style="font-size: 12pt;">Password:</td>
													  <td style="font-size: 12pt;">${data.password}</td>
													</tr>
												  </tbody>
												</table>
											  </td>
											  <td>
												  <div id="qrcode_user" style="text-align: center; transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;"></div>
											  </td>
											</tr>
										  </tbody>
										</table>
									  </div>
									  <div href="#" class="" id='sendet_to_email'>Provisioning instructions have been sent to ${data.email}</div>
									</div>`;

							$('#modal_body_resetPassword').html(html);

							const qrCode = new QRCodeStyling({
								width: 182,
								height: 182,
								type: "svg",
								data: JSON.stringify({
									domain: result.domain,
									username: result.username,
									password: result.password
								}),
								// imageOptions: {
								// 	crossOrigin: "anonymous",
								// 	margin: 30,
								// 	imageSize: 0.4
								// },
								dotsOptions: {
									color: "#1d56ab",
									type: "rounded"
								},
								backgroundOptions: {
									color: "#fff",
								}
							});

							qrCode.append(document.getElementById("qrcode_user"));

							setTimeout(() => {
								// set attribute for our image on qrcode
								// $('#qrcode_user image').attr('x', 70);
								// $('#qrcode_user image').attr('y', 70);
								// $('#qrcode_user image').attr('width', '42px');
								// $('#qrcode_user image').attr('height', '42px');
								setTimeout(() => {
									$('#qrcode_user').css('opacity', 1);

									const modal_body_resetPassword = $('#modal_body_resetPassword')[0];

									htmlToImage.toBlob(modal_body_resetPassword).then(function (canvas) {
										// $('#modal_body_activateUser').append(canvas);
										navigator.clipboard.write([new ClipboardItem({ 'image/png': canvas })]);
										$('#sendet_to_email').after('<h5 style="color: green; padding: 1rem;">It has been saved to the clipboard</h5>');
										setTimeout(() => {
											$('#sendet_to_email').on('click', (() => {
												navigator.clipboard.write([new ClipboardItem({ 'image/png': canvas })]);
											}));
										}, 300);
									});
								}, 200);
							}, 100);
						}, 300);
					} else {
						$('#email_for_reset_password').val('Error');
						$('#email_for_reset_password').attr('disabled', true);
						$('#reset_user_password_loading').fadeOut(300);
						setTimeout(() => {
							$('#reset_user_password_button').animate({ width: "toggle" }, 300);
							$('#email_for_reset_password').addClass('alert-danger');
							setTimeout(() => {
								$('#resetPasswordModal').click();
							}, 3000);
						}, 300);
					}

				});
			});
		}, 300);

	};

	const activateUser = ({ orgid, id, extension, email }) => {
		$('#create_user_activate_button').attr('disabled', true);
		$('#create_user_activate_text').fadeOut(300);

		setTimeout(() => {
			$('#create_user_activate_loading').fadeIn(300);
			const data = {
				orgid,
				id,
				extension,
				email,
				status: 1
			};
			$.ajax({
				url: "/app/ringotel/service.php?method=activateUser",
				type: "post",
				cache: true,
				data,
				success: function (response) {
					// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
					getUsersWithUpdateElements();

					// UPDATE STATE OF EXISTS INTEGRATIONS
					getIntegration();

					// console.log('response', response);
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					// console.log('[activateUser] result', result);
					// Generate qr code and show modal
					if (result?.username) {
						$('#email_for_user').animate({ width: 'toggle' }, 350);

						$('#create_user_activate_button').animate({ width: 'toggle' }, 350);

						$('#modal_body_activateUser').slideUp(300);

						setTimeout(() => {
							$('#modal_body_activateUser').slideDown(300);
							$('#create_user_activate_loading').fadeOut(300);

							$('#createActivationModal_modal_body').css('max-width', "40rem");

							// `Provisioning instructions have been sent to ${data}`
							const data = {
								domain: result.domain,
								extension: result.extension,
								username: result.username,
								password: result.password,
								email: result.email
							};
							const html = `	
									<div class="card">
									  <div class="card-body card-body-p">
										<h5 class="card-title" style="color: green;">Success</h5>
										<p class="card-text">You successfully activated the user shown below. Please use the generated password for login. You will not be able to view the password again. However, you can reset the password at any time.</p>
										<table style="width: 100%;">
										  <tbody>
											<tr>
											  <td>
											  <table class="table" style="font-weight: 700;margin: 0;">
												<tbody>
													<tr>
													  <td style="font-size: 12pt;">Domain:</td>
													  <td style="font-size: 12pt;">${data.domain}</td>
													</tr>
													<tr>
													  <td style="font-size: 12pt;">Extension:</td>
													  <td style="font-size: 12pt;">${data.extension}</td>
													</tr>
													<tr>
													  <td style="font-size: 12pt;">Username:</td>
													  <td style="font-size: 12pt;">${data.username}</td>
													</tr>
													<tr>
													  <td style="font-size: 12pt;">Password:</td>
													  <td style="font-size: 12pt;">${data.password}</td>
													</tr>
												  </tbody>
												</table>
											  </td>
											  <td>
												  <div id="qrcode_user" style="text-align: center; transition: all 1s;-moz-transition: all 1s;-webkit-transition: all 1s;"></div>
											  </td>
											</tr>
										  </tbody>
										</table>
									  </div>
									  <div href="#" class="" id='sendet_to_email'>Provisioning instructions have been sent to ${data.email}</div>
									</div>`;

							$('#modal_body_activateUser').html(html);

							const qrCode = new QRCodeStyling({
								width: 182,
								height: 182,
								type: "svg",
								data: JSON.stringify({
									domain: result.domain,
									username: result.username,
									password: result.password
								}),
								image: "<?php echo PROJECT_PATH . "/app/ringotel/assets/images/180x180.svg" ?>",
								imageOptions: {
									crossOrigin: "anonymous",
									margin: 30,
									imageSize: 0.4
								},
								dotsOptions: {
									color: "#1d56ab",
									type: "rounded"
								},
								backgroundOptions: {
									color: "#fff",
								}
							});

							qrCode.append(document.getElementById("qrcode_user"));

							setTimeout(() => {
								// set attribute for our image on qrcode
								$('#qrcode_user image').attr('x', 70);
								$('#qrcode_user image').attr('y', 70);
								$('#qrcode_user image').attr('width', '42px');
								$('#qrcode_user image').attr('height', '42px');
								setTimeout(() => {
									$('#qrcode_user').css('opacity', 1);

									const modal_body_activateUser = $('#modal_body_activateUser')[0];

									// console.log('modal_body_activateUser', modal_body_activateUser);

									// domtoimage.toPng(modal_body_activateUser).then((res) => {
									// 	// console.log('[domtoimage] [qrCode_img]', res);
									// 	// // console.log('[domtoimage] [data_qrcode_user_copy]', $('#data_qrcode_user_copy')[0]);
									// 	// $('#data_qrcode_user_copy')[0].src = res;
									// });

									// domtoimage.toPng(document.getElementById('modal_body_activateUser'), { quality: 0.95 })
									// 	.then(function (dataUrl) {
									// 		var link = document.createElement('a');
									// 		link.download = 'area-chart.png';
									// 		link.href = dataUrl;
									// 		link.click();
									// 	});

									htmlToImage.toBlob(modal_body_activateUser).then(function (canvas) {
										// $('#modal_body_activateUser').append(canvas);
										navigator.clipboard.write([new ClipboardItem({ 'image/png': canvas })]);
										$('#sendet_to_email').after('<h5 style="color: green; padding: 1rem;">It has been saved to the clipboard</h5>');
										setTimeout(() => {
											$('#sendet_to_email').on('click', (() => {
												navigator.clipboard.write([new ClipboardItem({ 'image/png': canvas })]);
											}));
										}, 300);
									});
								}, 200);
							}, 100);
						}, 300);
					} else {
						$('#email_for_user').val('Error');
						$('#email_for_user').attr('disabled', true);
						$('#create_user_activate_loading').fadeOut(300);
						setTimeout(() => {
							$('#create_user_activate_button').animate({ width: "toggle" }, 300);
							$('#email_for_user').addClass('alert-danger');
							setTimeout(() => {
								$('#createActivationModal').click();
							}, 3000);
						}, 300);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					// console.log(textStatus, errorThrown);
				}
			});
		}, 300);

	};

	$("#createParksModal").on("hidden.bs.modal", function () {
		$('#from_park_number').val('');
		$('#to_park_number').val('');
	});

	$("#resetPasswordModal").on("hidden.bs.modal", function () {
		const html = `
			<div class="input-group mb-3">
			  <div class="input-group-prepend">
				<div class="input-group-text" id="basic-addon1">Email</div>
			  </div>
			  <input type="text" class="form-control" id="email_for_reset_password" placeholder="Email for user">
			</div>
		`;
		$('#modal_body_resetPassword').html(html);

		$('#email_for_reset_password').attr('data-id', '');
		$('#email_for_reset_password').attr('data-extension', '');
		$('#email_for_reset_password').attr('data-orgid', '');
		$('#email_for_reset_password').val('');

		$('#create_user_activate_button').attr('disabled', false);

		$('#reset_user_password_loading').fadeOut(300);
		$('#reset_user_password_text').fadeIn(300);
		$('#reset_user_password_button').attr('disabled', false);
		$('#reset_user_password_button').fadeIn(300);
		$('#resetPasswordModal_modal_body')[0].style.cssText = '';
	});

	$("#createActivationModal").on("hidden.bs.modal", function () {
		const html = `
			<div class="input-group mb-3">
			  <div class="input-group-prepend">
				<div class="input-group-text" id="basic-addon1">Email</div>
			  </div>
			  <input type="text" class="form-control" id="email_for_user" placeholder="Email for user">
			</div>`;
		$('#modal_body_activateUser').html(html);
		$('#email_for_user').attr('data-id', '');
		$('#email_for_user').attr('data-extension', '');
		$('#email_for_user').attr('data-orgid', '');
		$('#email_for_user').val('');

		$('#create_user_activate_loading').fadeOut();
		$('#create_user_activate_text').fadeIn();
		$('#create_user_activate_button').attr('disabled', false);
		$('#create_user_activate_button').fadeIn(300);
		$('#createActivationModal_modal_body')[0].style.cssText = '';
	});

	const resetUserPasswordEvent = () => {
		// clear event listeners
		$('.resetPassword').off('click');
		// Bind Event Listener [Delete User]
		$(".resetPassword").on('click', (function (el) {
			const id = el.currentTarget.getAttribute('data-id');
			const extension = el.currentTarget.getAttribute('data-extension');
			const orgid = $('#delete_organization').attr('data-account');
			$('#email_for_reset_password').attr('data-id', id);
			$('#email_for_reset_password').attr('data-extension', extension);
			$('#email_for_reset_password').attr('data-orgid', orgid);
		}));
	};

	const activateUserEvent = () => {
		// clear event listeners
		$('.activateUser').off('click');
		// Bind Event Listener [Delete User]
		$(".activateUser").on('click', (function (el) {
			const id = el.currentTarget.getAttribute('data-id');
			const extension = el.currentTarget.getAttribute('data-extension');
			const orgid = $('#delete_organization').attr('data-account');
			$('#email_for_user').attr('data-id', id);
			$('#email_for_user').attr('data-extension', extension);
			$('#email_for_user').attr('data-orgid', orgid);
		}));
	};

	const reSyncPasswordEvent = () => {
		// clear event listeners
		$('.reSyncPassword').off('click');
		// Bind Event Listener [Delete User]
		$(".reSyncPassword").on('click', (function (el) {
			// $(el.currentTarget).html('<span style="font-size: 14px;">Loading</span>');
			$(el.currentTarget).children('svg').css('animation', 'sync 2s infinite');

			const id = el.currentTarget.getAttribute('data-id');
			const orgid = $('#delete_organization').attr('data-account');
			const extension = el.currentTarget.getAttribute('data-extension');

			// update Password of User 
			const data = { orgid, id, extension };
			// console.log(' [reSyncPassword] data', data);
			$.ajax({
				url: "/app/ringotel/service.php?method=reSyncPassword",
				type: "post",
				data
			}).then((response) => {
				const { result } = JSON.parse(response.replaceAll("\\", ""));
				// console.log('[reSyncPassword] result', result);
				// Generate qr code and show modal
				if (result) {
					// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
					getUsersWithUpdateElements();
					// UPDATE STATE OF EXISTS INTEGRATIONS
					getIntegration();
				}
			})
		}));
	};

	const activateAllExtensions = async (exist_extensions_list) => {
		// All Exist Users and Extensions
		const all_ue = [...exist_extensions_list];

		// Organization Id
		const orgid = $('#delete_organization').attr('data-account');

		// All Requsts
		return await Promise.all(
			all_ue.map(async (item) => {
				// Get Data from Card
				const id = item.getAttribute('data-id');
				const extension = item.getAttribute('data-number');

				const data = { orgid, id, extension };

				await $.ajax({
					url: "/app/ringotel/service.php?method=activateUser",
					type: "post",
					data
				}).then((response) => {
					const res = JSON.parse(response.replaceAll("\\", ""));
					// console.log(res.error.message);
					const error = '<div style="position: absolute;font-size: 15pt;text-transform: uppercase;width: 14rem;padding: 2.5rem 1rem 2rem 1rem;text-align: center;">registration failed</div>';
					$(`.user_card[data-number=${extension}]`).children('.card-body').css('filter', 'blur(4px)');
					$(`.user_card[data-number=${extension}]`).prepend(error);
				});
			})
		)
	};

	const reSyncAllNamesEvent = () => {
		// clear event listeners
		$('.reSyncAllNames').off('click');
		// Bind Event Listener [Delete User]
		$(".reSyncAllNames").on('click', (function (el) {
			// Set Animation For 
			$(el.currentTarget).children('svg').css('animation', 'sync 2s infinite');

			// Exist Users List
			const exist_users_list = $('#group_exist_users_list').children();

			// Exist Extensions List
			const exist_extensions_list = $('#group_exist_extensions_list').children();

			// All Exist Users and Extensions
			const all_ue = [...exist_users_list, ...exist_extensions_list];

			// Organization Id
			const orgid = $('#delete_organization').attr('data-account');

			// Replace Text
			$('#resync_names').text('Loading...');

			// All Requsts
			Promise.all(
				all_ue.map(async (item) => {
					// Get Data from Card
					const id = item.getAttribute('data-id');
					const extension = item.getAttribute('data-number');
					const extensionExist = item.getAttribute('data-extension-exist');

					// Update Password of User
					const data = { orgid, id, extension };

					if (extensionExist) {
						await $.ajax({
							url: "/app/ringotel/service.php?method=reSyncNames",
							type: "post",
							data
						}).then((response) => {
							const res = JSON.parse(response.replaceAll("\\", ""));
							const message = '<div style="position: absolute;font-size: 15pt;text-transform: uppercase;width: 14rem;padding: 3.25rem 1rem 3rem 1rem;text-align: center;">Updated</div>';
							$('#user_card_' + res?.result?.id).children('.card-body').css('filter', 'blur(4px) opacity(0.5)');
							$('#user_card_' + res?.result?.id).prepend(message);
						});
					} else {
						$.ajax({
							url: "/app/ringotel/service.php?method=deleteUser",
							type: "get",
							cache: true,
							data: {
								id,
								orgid
							}
						}).then((response) => {
							const res = JSON.parse(response?.replaceAll("\\", ""));
							if (res?.result) {
								const message = '<div style="position: absolute;font-size: 15pt;text-transform: uppercase;width: 14rem;padding: 3.25rem 1rem 3rem 1rem;text-align: center;">Deleted</div>';
								$('#user_card_' + id).children('.card-body').css('filter', 'blur(4px) opacity(0.5)');
								$('#user_card_' + id).prepend(message);
							}
						});
					}
				})).then(async () => {
					// await activateAllExtensions(exist_extensions_list);
					// Disable Animation
					$(el.currentTarget).children('svg').css('animation', 'none');
					// Restore Text
					$('#resync_names').text('re-Sync Extensions');
					// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
					getUsersWithUpdateElements();
					// UPDATE STATE OF EXISTS INTEGRATIONS
					getIntegration();
				});
		}));
	};

	const reSyncAllPasswordEvent = () => {
		// clear event listeners
		$('.reSyncAllPassword').off('click');
		// Bind Event Listener [Delete User]
		$(".reSyncAllPassword").on('click', (function (el) {
			// Set Animation For 
			$(el.currentTarget).children('svg').css('animation', 'sync 2s infinite');

			// Exist Users List
			const exist_users_list = $('#group_exist_users_list').children();

			// Exist Extensions List
			const exist_extensions_list = $('#group_exist_extensions_list').children();

			// All Exist Users and Extensions
			const all_ue = [...exist_users_list, ...exist_extensions_list];

			// Organization Id
			const orgid = $('#delete_organization').attr('data-account');

			// Replace Text
			$('#resync_password').text('Loading...');

			// All Requsts
			Promise.all(
				all_ue.map(async (item) => {
					// Get Data from Card
					const id = item.getAttribute('data-id');
					const extension = item.getAttribute('data-number');

					// Update Password of User
					const data = { orgid, id, extension };
					// console.log('--------> [reSyncPassword] [map] [data]', data);

					await $.ajax({
						url: "/app/ringotel/service.php?method=reSyncPassword",
						type: "post",
						data
					}).then((response) => {
						const res = JSON.parse(response.replaceAll("\\", ""));
						// console.log('[reSyncPassword] result?.result', res?.result);
					});
				})).then(() => {
					// Disable Animation
					$(el.currentTarget).children('svg').css('animation', 'none');
					// Restore Text
					$('#resync_password').text('re-Sync Passwords');
					// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
					getUsersWithUpdateElements();
					// UPDATE STATE OF EXISTS INTEGRATIONS
					getIntegration();
				});
		}));
	};

	const deactivateUserEvent = () => {
		// clear event listeners
		$('.deactivateUser').off('click');
		// Bind Event Listener [Delete User]
		$(".deactivateUser").on('click', (function (el) {
			const id = el.currentTarget.getAttribute('data-id');
			$(el.currentTarget).children('i').css('animation', 'plug 3s infinite');

			const orgid = $('#delete_organization').attr('data-account');
			const data = {
				orgid,
				id,
			};
			// console.log(' deactivateUser data', data);
			$.ajax({
				url: "/app/ringotel/service.php?method=deactivateUser",
				type: "post",
				data,
				success: function (response) {
					const { result } = JSON.parse(response.replaceAll("\\", ""));
					// console.log('[deactivateUser] result', result);
					// Generate qr code and show modal
					if (result) {
						// GET ALL USERS, PARKS AND EXTENSIONS AND UPDATE THEIR ENTRY SPOTS WITH ELEMENTS
						getUsersWithUpdateElements();
						// 
						// UPDATE STATE OF EXISTS INTEGRATIONS
						getIntegration();
					}
				}
			});
		}));
	};

	// popup for error message, fuction of closing
	$('#package_org_popup_close').on('click', (function () {
		$('#package_org_popup').css('opacity', 0);
		$('#package_org_popup').css('display', 'none');
		$('#package_org_popup_text').text('');
	}));

	// $('#checkbox-switcher-for-integrations').off('click');

	$('#footer').fadeOut();

	// [Init] Check command
	getOrganization();

	// Nav Event Listeners

	const tabSwitcher = () => {
		$('#nav-org-tab').animate({ width: 'toggle' }, 300);
		$('#nav-org').animate({ height: 'toggle' }, 300);
		setTimeout(() => {
			$('#nav-org-tab-back').animate({ width: 'toggle' }, 300);
			$('#nav-integration').animate({ height: 'toggle' }, 300);
		}, 300);
	};
	$('#nav-integration-tab').on('click', (function () {
		tabSwitcher();
	}));
	$('#nav-org-tab-back').on('click', (function () {
		tabSwitcher();
	}));

</script>