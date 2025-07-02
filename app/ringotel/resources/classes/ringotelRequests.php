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

require_once "app/ringotel/resources/classes/curlRingotel.php";

class RingotelApiFunctions
{

    public $config;
    public $domain_name_postfix;
    public $default_connection_regexpires;

    public function __construct($baseUrl)
    {
        $this->domain_name_postfix = isset($_SESSION['ringotel']['domain_name_postfix']['text']) ? ('-'.$_SESSION['ringotel']['domain_name_postfix']['text']) : '-ringotel';
        $this->default_connection_regexpires = isset($_SESSION['ringotel']['default_connection_regexpires']['text']) ? intval($_SESSION['ringotel']['default_connection_regexpires']['text']) : 120;
        $this->baseUrl = $baseUrl;
    }

    public function __destruct()
    {
        foreach ($this as $key => $value) {
            unset($this->$key);
        }
    }

    function getOrganizations($token, $mode)
    {
        $parameters = '{
            "method": "getOrganizations",
            "params": {}
        }';
        $headers = array(
            'Authorization: Bearer ' . (!empty($_SESSION['ringotel']['ringotel_token']['text']) ? $_SESSION['ringotel']['ringotel_token']['text'] : $token),
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', $parameters, $headers);
        if ($mode === 'RETURN') {
            return $res;
        } else {
            return json_decode($res, true);
        }
    }

    //after grant code
    function createOrganization($param)
    {
        $name = explode(".", $_SESSION['domain_name'])[0];
        $parameters = array(
            "method" => "createOrganization",
            "params" => array(
                "name" => $_SESSION['domain_name'],
                "region" => $param['region'],
                "domain" => isset($param['domain']) ? $param['domain'] : explode(".", $name)[0] . $this->domain_name_postfix,
            )
        );
        // adminlogin [optional]
        if (isset($param['adminlogin'])) {
            $parameters['adminlogin'] = $param['adminlogin'];
        }
        // adminpassw [optional]
        if (isset($param['adminpassw'])) {
            $parameters['adminpassw'] = $param['adminpassw'];
        }

        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // Delete Organization
    function deleteOrganization($param)
    {
        $parameters = array(
            "method" => "deleteOrganization",
            "params" => array("id" => $param['id'])
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);     // TEST DISABLED
        return json_decode($res, true);                                                              // TEST DISABLED
    }

    // Get Connections List
    function getBranches($param)
    {
        $parameters = array(
            "method" => "getBranches",
            "params" => array(
                "orgid" => $param['orgid']
            )
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    //after grant code
    function createBranch($param)
    {
        $parameters = array(
            "method" => "createBranch",
            "params" => array(
                "orgid" => $param["orgid"],
                "name" => $param["name"],
                "address" => $param["address"],
                "country" => "US",
                "number" => "",
                "provision" => array(
                    "displayname" => "",
                    "protocol" => $param['protocol'],
                    "noverify" => true,
                    "nosrtp" => false,
                    "internal" => false,
                    "maxregs" => intval($param['maxregs']),
                    "extvc" => false,
                    "private" => false,
                    "multitenant" => false,
                    "dtmfmode" => "rfc2833",
                    "regexpires" => $this->default_connection_regexpires, 
                    "codecs" => array(
                        0 => array(
                            "codec" => "G.711 Alaw",
                            "frame" => 20
                        ),
                        1 => array(
                            "codec" => "G.711 Ulaw",
                            "frame" => 20
                        )
                    ),
                    "plength" => 32,
                    "tones" => array(
                        "Progress" => "Progress 1",
                        "Ringback2" => "Ringback 1",
                        "Ringback" => "United States"
                    ),
                    "username" => "",
                    "authname" => "",
                    "authpass" => "",
                    "ipcheck" => false,
                    "iptable" => array(
                        0 => array(
                            "net" => "",
                            "mask" => ""
                        )
                    ),
                    "inboundFormat" => "",
                    "noreg" => false,
                    "withReg" => false,
                    "sms" => 3,
                    "blfs" => [],
                    "subscription" => new stdClass()
                )
            )
        );

        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // DELETE CONNECTION
    function deleteBranch($param)
    {
        $parameters = array(
            "method" => "deleteBranch",
            "params" => array(
                "id" => $param['id'],
                "orgid" => $param['orgid']
            )
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // GET USERS
    function getUsers($param, $token, $mode)
    {
        $parameters = array(
            "method" => "getUsers",
            "params" => array(
                "branchid" => $param['branchid'],
                "orgid" => $param['orgid']
            )
        );
        $headers = array(
            'Authorization: Bearer ' . (!empty($_SESSION['ringotel']['ringotel_token']['text']) ? $_SESSION['ringotel']['ringotel_token']['text'] : $token),
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        if ($mode === 'RETURN') {
            return $res;
        } else {
            return json_decode($res, true);
        }
    }

    // GET USERS
    function createUsers($param)
    {
        $parameters = array(
            "method" => "createUsers",
            "params" => array(
                "branchid" => $param['branchid'],
                "orgid" => $param['orgid'],
                "users" => $param['users']
            )
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }


    // Delete Organization
    function deleteUser($param)
    {
        $parameters = array(
            "method" => "deleteUser",
            "params" => array(
                "id" => $param['id'],
                "orgid" => $param['orgid']
            )
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // GET USERS
    function resetUserPassword($params)
    {
        $parameters = array(
            "method" => "resetUserPassword",
            "params" => $params
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // GET USERS
    function updateUser($params)
    {
        $parameters = array(
            "method" => "updateUser",
            "params" => $params
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // Detach User
    function detachUser($params)
    {
        $parameters = array(
            "method" => "detachUser",
            "params" => $params
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    function updateBranch($params)
    {
        $parameters = array(
            "method" => "updateBranch",
            "params" => $params
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    function updateBranchWithDefaultOptionsAfterSwitcher($params)
    {
        $parameters = array(
            "method" => "updateBranch",
            "params" => array(
                "id" => $params['id'],
                "orgid" => $params['orgid'],
                "provision" => array(
                    "maxregs" => $params['maxregs']
                )
            )
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    public function updateOrganization($params)
    {
        $parameters = array(
            "method" => "updateOrganization",
            "params" => $params
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    public function deactivateUser($params)
    {
        $parameters = array(
            "method" => "deactivateUser",
            "params" => $params
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    /////
    ////
    ///
    // INTEGRATION

    // create Integration
    public function createIntegration($param)
    {
        $parameters = array(
            'serviceid' => 'Bandwidth',
            'state' => 1,
            'profileid' => $param['profileid'],
            'Username' => $param['Username'],
            'Password' => $param['Password'],
            'Account ID' => $param['Account_ID'],
            'Application ID' => $param['Application_ID'],
            'options' => '',
            'redirect' => 'https://shell.ringotel.co/account/en-US/#/org/' . $param['profileid'] . '/integrations/Bandwidth'
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl . '?' . http_build_query($parameters), 'GET', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // delete Integration
    public function deleteIntegration($param)
    {
        $parameters = array(
            'serviceid' => 'Bandwidth',
            'state' => 0,
            'profileid' => $param['profileid'],
            'Username' => $param['Username'],
            'Password' => $param['Password'],
            'Account ID' => $param['Account_ID'],
            'Application ID' => $param['Application_ID'],
            'options' => '',
            'redirect' => 'https://shell.ringotel.co/account/en-US/#/org/' . $param['profileid'] . '/integrations/Bandwidth'
        );
        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );
        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl . '?' . http_build_query($parameters), 'GET', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // get Integration
    public function getServices($param)
    {
        $parameters = array(
            "method" => "getServices",
            "params" => array(
                "orgid" => $param['orgid']
            )
        );

        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );

        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // Get Numbers Configuration 
    public function getSMSTrunk($param)
    {
        $parameters = array(
            "method" => "getSMSTrunks",
            "params" => array(
                "orgid" => $param['orgid']
            )
        );

        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );

        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // Create Numbers Configuration
    public function createSMSTrunk($param)
    {
        $parameters = array(
            "method" => "createSMSTrunk",
            "params" => array(
                "orgid" => $param['orgid'],
                "name" => $param['name'],
                "number" => '+1'.$param['number'],
                "service" => "Bandwidth",
                "users" => $param['users'],
                "sessionTimeout" => 0,
                "country" => "US",
                "groupmode" => true,
                "outboundFormat" => "e164",
                "inboundFormat" => "national",
                "optout" => array(
                    "keyword" => "STOP",
                    "autoreply" => "You have been removed from our list and will no longer receive messages. To resubscribe, send any message back."
                ),
                "autoreply" => array(
                    0 => array(
                        "key" => "HELP",
                        "value" => "Send STOP to stop receiving messages from us. To resubscribe, send any message back."
                    )
                )
            )
        );

        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );

        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // Update Numbers Configuration
    public function updateSMSTrunk($param)
    {
        $parameters = array(
            "method" => "updateSMSTrunk",
            "params" => array(
                "orgid" => $param['orgid'],
                "country" => "US",
                "inboundFormat" => "national",
                "groupmode" => true,
                "created" => floor(microtime(true) * 1000),
                "outboundFormat" => "e164",
                "users" => $param['users'],
                "autoreply" => array(
                    0 => array(
                        "value" => "Send STOP to stop receiving messages from us. To resubscribe, send any message back.",
                        "key" => "HELP"
                    )
                ),
                "optout" => array(
                    "autoreply" => "You have been removed from our list and will no longer receive messages. To resubscribe, send any message back.",
                    "keyword" => "STOP"
                ),
                "number" => '+1'.$param['number'],
                "service" => "Bandwidth",
                "name" => $param['name'],
                "sessionTimeout" => 0,
                "id" => $param['id'],
                "status" => 2
            )
        );

        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );

        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_decode($res, true);
    }

    // Delete Numbers Configuration
    public function deleteSMSTrunk($param)
    {
        $parameters = array(
            "method" => "deleteSMSTrunk",
            "params" => array(
                "orgid" => $param['orgid'],
                "id" => $param['id']
            )
        );

        $headers = array(
            'Authorization: Bearer ' . $_SESSION['ringotel']['ringotel_token']['text'],
            'Content-Type: application/json'
        );

        $curl = new curlRingotel();
        $res = $curl->request($this->baseUrl, 'POST', json_encode($parameters, true), $headers);
        return json_encode($res, true);
    }
}
