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
require_once "app/ringotel/resources/classes/ringotel.php";

// check permissions
if (permission_exists('ringotel')) {
    //access granted
} else {
    echo "access denied";
    exit;
}

// Get Current Domain Organization
if (permission_exists('ringotel') && $_REQUEST['method'] == 'getOrganization') {
    $method = new RingotelClass(null);
    $method->getOrganization(null);
}

// Create Organization
if (permission_exists('ringotel') && $_REQUEST['method'] == 'createOrganization') {
    $method = new RingotelClass(null);
    $method->createOrganization();
}

// Delete Organization
if (permission_exists('ringotel') && $_REQUEST['method'] == 'deleteOrganization') {
    $method = new RingotelClass(null);
    $method->deleteOrganization();
}

// Get Branch
if (permission_exists('ringotel') && $_REQUEST['method'] == 'getBranches') {
    $method = new RingotelClass(null);
    $method->getBranches(null);
}

// Create Branch
if (permission_exists('ringotel') && $_REQUEST['method'] == 'createBranch') {
    $method = new RingotelClass(null);
    $method->createBranch();
}

// Delete Branch
if (permission_exists('ringotel') && $_REQUEST['method'] == 'deleteBranch') {
    $method = new RingotelClass(null);
    $method->deleteBranch();
}

// Get Users
if (permission_exists('ringotel') && $_REQUEST['method'] == 'getUsers') {
    $method = new RingotelClass(null);
    $method->getUsers();
}

// Create User
if (permission_exists('ringotel') && $_REQUEST['method'] == 'createUsers') {
    $method = new RingotelClass(null);
    $method->createUsers();
}

// Delete User
if (permission_exists('ringotel') && $_REQUEST['method'] == 'deleteUser') {
    $method = new RingotelClass(null);
    $method->deleteUser();
}

// Update User
if (permission_exists('ringotel') && $_REQUEST['method'] == 'updateUser') {
    $method = new RingotelClass(null);
    $method->updateUser();
}

// re-Sync Names of User and activate them
if (permission_exists('ringotel') && $_REQUEST['method'] == 'reSyncNames') {
    $method = new RingotelClass(null);
    $method->reSyncNames();
}

// re-Sync Password of User
if (permission_exists('ringotel') && $_REQUEST['method'] == 'reSyncPassword') {
    $method = new RingotelClass(null);
    $method->reSyncPassword();
}

// Detach User
if (permission_exists('ringotel') && $_REQUEST['method'] == 'detachUser') {
    $method = new RingotelClass(null);
    $method->detachUser();
}

// Status Users
if (permission_exists('ringotel') && $_REQUEST['method'] == 'usersState') {
    $method = new RingotelClass(null);
    $method->usersState();
}

// update Branch With Default Settings
if (permission_exists('ringotel') && $_REQUEST['method'] == 'updateBranchWithDefaultSettings') {
    $method = new RingotelClass(null);
    $method->updateBranchWithDefaultSettings();
}

// update Branch With Updated Settings
if (permission_exists('ringotel') && $_REQUEST['method'] == 'updateBranchWithUpdatedSettings') {
    $method = new RingotelClass(null);
    $method->updateBranchWithUpdatedSettings();
}

// update Organization With Default Settings
if (permission_exists('ringotel') && $_REQUEST['method'] == 'updateOrganizationWithDefaultSettings') {
    $method = new RingotelClass(null);
    $method->updateOrganizationWithDefaultSettings(null);
}

// createParks With Updated Settings
if (permission_exists('ringotel') && $_REQUEST['method'] == 'updateParksWithUpdatedSettings') {
    $method = new RingotelClass(null);
    $method->updateParksWithUpdatedSettings();
}

// Activate User
if (permission_exists('ringotel') && $_REQUEST['method'] == 'activateUser') {
    $method = new RingotelClass(null);
    $method->activateUser();
}

// Deactivate User
if (permission_exists('ringotel') && $_REQUEST['method'] == 'deactivateUser') {
    $method = new RingotelClass(null);
    $method->deactivateUser();
}

// Reset User Password
if (permission_exists('ringotel') && $_REQUEST['method'] == 'resetUserPassword') {
    $method = new RingotelClass(null);
    $method->resetUserPassword();
}

// update Branch hWith Default Settings
if (permission_exists('ringotel') && $_REQUEST['method'] == 'switchOrganizationMode') {
    $method = new RingotelClass(null);
    $method->switchOrganizationMode();
}

//
///
//// Integrations

// Create Integration
if (permission_exists('ringotel') && $_REQUEST['method'] == 'createIntegration') {
    $method = new RingotelClass('INTEGRATION');
    $method->createIntegration();
}

// Delete Integration
if (permission_exists('ringotel') && $_REQUEST['method'] == 'deleteIntegration') {
    $method = new RingotelClass('INTEGRATION');
    $method->deleteIntegration();
}

// Get Integration
if (permission_exists('ringotel') && $_REQUEST['method'] == 'getIntegration') {
    $method = new RingotelClass('INTEGRATION');
    $method->getIntegration();
}

// Get Numbers Configuration
if (permission_exists('ringotel') && $_REQUEST['method'] == 'getSMSTrunk') {
    $method = new RingotelClass('INTEGRATION');
    $method->getSMSTrunk();
}

// Create Numbers Configuration
if (permission_exists('ringotel') && $_REQUEST['method'] == 'createSMSTrunk') {
    $method = new RingotelClass('INTEGRATION');
    $method->createSMSTrunk();
}

// Update Numbers Configuration
if (permission_exists('ringotel') && $_REQUEST['method'] == 'updateSMSTrunk') {
    $method = new RingotelClass('INTEGRATION');
    $method->updateSMSTrunk();
}

// Delete Numbers Configuration
if (permission_exists('ringotel') && $_REQUEST['method'] == 'deleteSMSTrunk') {
    $method = new RingotelClass('INTEGRATION');
    $method->deleteSMSTrunk();
}