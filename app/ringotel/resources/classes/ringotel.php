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

require_once "app/ringotel/resources/classes/ringotelRequests.php";
require_once "app/ringotel/resources/classes/ringotelErrorService.php";
require_once "app/ringotel/resources/classes/ringotelRepository.php";

class RingotelClass
{
    private $api;
    private $repository;
    public $domain_name_postfix;
    public $max_registration;
    public $default_connection_protocol;

    function __construct($mode)
    {
        $this->domain_name_postfix = isset($_SESSION['ringotel']['domain_name_postfix']['text']) ? ('-'.$_SESSION['ringotel']['domain_name_postfix']['text']) : '-ringotel';
        $this->max_registration = isset($_SESSION['ringotel']['max_registration']['text']) ? intval($_SESSION['ringotel']['max_registration']['text']) : 1;
        $this->default_connection_protocol = isset($_SESSION['ringotel']['default_connection_protocol']['text']) ? $_SESSION['ringotel']['default_connection_protocol']['text'] : 'sip-tcp';
        $ringotel_api = $mode !== 'INTEGRATION' ? $_SESSION['ringotel']['ringotel_api']['text'] : $_SESSION['ringotel']['ringotel_integration_api']['text'];
        $this->api = new RingotelApiFunctions($ringotel_api);
        $this->error = new RingotelErrorService();
        unset($ringotel_api);
    }

    function __destruct()
    {
        foreach ($this as $key => $value) {
            unset($this->$key);
        }
    }

    // split for syllabe less than 30 charasters
    function lessThan30($str, $prefix)
    {
        $lengthDomainNamePlusPrefix = strlen($str . $prefix);
        if ($lengthDomainNamePlusPrefix > 30) {
            $new_text = preg_replace('/([b-df-hj-np-tv-z])([b-df-hj-np-tv-z])/i', '$1-$2', $str);
            $exploded = explode('-', $new_text);
            array_pop($exploded);
            $Next = implode($exploded);
            return $this->lessThan30($Next, $prefix);
        } else {
            return $str . $prefix;
        }
    }

    // GET ORGANIZATIONS
    public function getOrganization($mode)
    {
        // Main
        $server_output = $this->api->getOrganizations(null, null);

        // HERE the filter functional
        $domain_name = isset($_REQUEST['domain_name']) ? $_REQUEST['domain_name'] : $_SESSION['domain_name'];

        $DomainNameLessThan30 = $this->lessThan30(explode(".", $domain_name)[0], $this->domain_name_postfix);

        // var_dump($DomainNameLessThan30);
        $filtered_organization = array_filter(
            $server_output['result'],
            function ($v, $k) use ($DomainNameLessThan30) {
                if (
                    $DomainNameLessThan30 === $v["domain"] ||
                    $_SESSION['domain_name'] === $v["name"] ||
                    str_replace('_', '.', $v['domain']) === $_SESSION['domain_name'] ||
                    explode('.', str_replace('_', '.', $v['domain']))[0] === explode('.', $_SESSION['domain_name'])[0] ||
                    explode('-', $v['domain'])[0] === explode('.', $_SESSION['domain_name'])[0]
                ) {
                    return true;
                }
            },
            ARRAY_FILTER_USE_BOTH
        );

        if ($mode === 'RETURN') {
            return array("result" => array_pop($filtered_organization));
        } else {
            echo json_encode(array("result" => array_pop($filtered_organization)));
        }
    }

    // CREATE ORGANIZATION
    public function createOrganization()
    {
        $DomainNameLessThan30 = $this->lessThan30(explode(".", $_SESSION['domain_name'])[0], $this->domain_name_postfix);

        //default param
        $param['name'] = $_REQUEST['name'];	                                                                                                    # string	org name
        $param['domain'] = isset($_REQUEST['domain']) ? ($_REQUEST['domain'] . $this->domain_name_postfix) : $DomainNameLessThan30;                             # string	org domain
        $param['region'] = isset($_REQUEST['region']) ? $_REQUEST['region'] : $_SESSION['ringotel']['ringotel_organization_region']['text'];    # string	region ID (see below)
        $param['adminlogin'] = $_REQUEST['adminlogin'];                                                                                         # string	(optional) org admin login
        $param['adminpassw'] = $_REQUEST['adminpassw'];                                                                                         # string	(optional) org admin password

        //main
        $server_output = $this->api->createOrganization($param);
        unset($param);

        //json response data
        echo json_encode($server_output);
    }

    // DELETE ORGANIZATION
    public function deleteOrganization()
    {
        $param['id'] = $_REQUEST['id'];

        //main
        $server_output = $this->api->deleteOrganization($param);
        unset($param);

        //json response data
        echo json_encode($server_output);
    }

    // GET BRANCHES
    public function getBranches($mode)
    {
        $param['orgid'] = $_REQUEST['orgid'];
        $server_output = $this->api->getBranches($param);
        unset($param);

        if ($mode === 'RETURN') {
            return $server_output;
        } else {
            //json response data
            echo json_encode($server_output);
        }
    }

    // CREATE BRANCH
    public function createBranch()
    {
        $param = array();

        //default param
        $param['orgid'] = $_REQUEST['orgid'];
        $param['maxregs'] = isset($_REQUEST['maxregs']) ? $_REQUEST['maxregs'] : $this->max_registration;
        $param['name'] = isset($_REQUEST['connection_name']) ? $_REQUEST['connection_name'] : $_SESSION['domain_name'];             # string	Connection name
        $param['address'] = isset($_REQUEST['connection_domain']) ? $_REQUEST['connection_domain'] : $_SESSION['domain_name'];      # string	Domain or IP address
        $param['protocol'] = isset($_REQUEST['protocol']) ? $_REQUEST['protocol'] : $this->default_connection_protocol;
        //main
        $server_output = $this->api->createBranch($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    // DELETE BRANCH
    public function deleteBranch()
    {
        $param['id'] = $_REQUEST['id'];
        $param['orgid'] = $_REQUEST['orgid'];

        //main
        $server_output = $this->api->deleteBranch($param);
        unset($param);

        //json response data
        echo json_encode($server_output);
    }

    // GET USERS
    public function getUsers()
    {
        
        $param = array();
        //default param
        $param['branchid'] = $_REQUEST['branchid'];
        $param['orgid'] = $_REQUEST['orgid'];

        //main
        $server_output = $this->api->getUsers($param, null, null);
        unset($param);

        // check exists extensions
        $sql  = "    select extension from v_extensions  ";
        $sql .= "    where domain_uuid = :domain_uuid ";
        $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
        $db = new database;
        $extensions = $db->select($sql, $parameters);
        unset($sql, $db, $parameters);

        $_extensions = array_map(function ($item) { return $item['extension']; }, $extensions);

        foreach ($server_output['result'] as $key => $ext) {
            if (in_array(preg_replace('/\D/', '', $ext['extension']), $_extensions)) {
                $server_output['result'][$key]['extension_exists'] = true;
            } else {
                $server_output['result'][$key]['extension_exists'] = false;
            }
        }

        //json response data
        echo json_encode($server_output);
    }

    // GET USERS
    public function createUsers()
    {
        $param = array();
        //default param
        $param['branchid'] = $_REQUEST['branchid'];
        $param['branchname'] = $_REQUEST['branchname'];
        $param['orgid'] = $_REQUEST['orgid'];
        $param['orgdomain'] = $_REQUEST['orgdomain'];
        $preusers = $_REQUEST['preusers'];

        // Get List Of Extensions
        $sql = "    select * from v_extensions  ";
        $sql .= "    where domain_uuid = :domain_uuid ";
        $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
        $db = new database;
        $extensions = $db->select($sql, $parameters);
        unset($sql, $db, $parameters);

        foreach ($preusers as $item) {
            if ($item['create'] === 'true') {
                $ext_find = null;
                foreach ($extensions as $exists_ext) {
                    if ($exists_ext['extension_uuid'] == $item['extension_uuid']) {
                        $ext_find = $exists_ext;
                        break 1;
                    }
                }
                $user = array(
                    "name" => $ext_find['effective_caller_id_name'],
                    "domain" => $param['orgdomain'],
                    "branchname" => $param['branchname'],
                    "status" => $item['active'] == 'true' ? 1 : 0,
                    "extension" => $ext_find['extension'],
                    "username" => $ext_find['extension'],
                    "password" => $ext_find['password'],
                    "authname" => $ext_find['extension'],
                );
                if (!empty($item['email'])) {
                    $user['email'] = $item['email'];
                }
                $param['users'][] = $user;
            }
        }

        //main
        $server_output = $this->api->createUsers($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    // Update Password of User
    public function reSyncNames()
    {
        $param = array();

        // Default param
        $param["orgid"] = $_REQUEST['orgid'];
        $param["id"] = $_REQUEST['id'];

        // Get List Of Extensions
        $sql  = "    select * from v_extensions  ";
        $sql .= "    where ";
        $sql .= "    domain_uuid = :domain_uuid ";
        $sql .= "    and    ";
        $sql .= "    extension = :extension ";
        $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
        $parameters['extension'] = $_REQUEST['extension'];
        $db = new database;
        $extension = $db->select($sql, $parameters, 'row');
        
        // echo var_dump($extension);

        unset($sql, $db, $parameters);
        if (isset($extension['extension_uuid'])) {
            $param["name"] = $extension['effective_caller_id_name'];

            //main
            $server_output = $this->api->updateUser($param);

            unset($param);
            //json response data
            echo json_encode($server_output);
        } else {
            echo json_encode(array());
        }
    }

    // Update Password of User
    public function reSyncPassword()
    {
        $param = array();

        // Default param
        $param["orgid"] = $_REQUEST['orgid'];
        $param["id"] = $_REQUEST['id'];

        // Get List Of Extensions
        $sql = "    select * from v_extensions  ";
        $sql .= "    where domain_uuid = :domain_uuid ";
        $sql .= "    and extension = :extension ";
        $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
        $parameters['extension'] = $_REQUEST['extension'];
        $db = new database;
        $extension = $db->select($sql, $parameters, 'row');
        unset($sql, $db, $parameters);

        if (isset($extension['extension_uuid'])) {
            $param["password"] = $extension['password'];

            //main
            $server_output = $this->api->updateUser($param);

            unset($param);
            //json response data
            echo json_encode($server_output);
        } else {
            echo json_encode(array());
        }
    }

    // GET USERS
    public function deleteUser()
    {
        $param = array();
        //default param
        $param['id'] = $_REQUEST['id'];
        $param['orgid'] = $_REQUEST['orgid'];

        //main
        $server_output = $this->api->deleteUser($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    // UPDATE USER
    public function updateUser()
    {
        $param = array();

        // Default param
        $param["orgid"] = $_REQUEST['orgid'];
        $param["id"] = $_REQUEST['id'];

        // Get List Of Extensions
        $sql  = "    select * from v_extensions  ";
        $sql .= "    where domain_uuid = :domain_uuid ";
        $sql .= "    and extension = :extension ";
        $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
        $parameters['extension'] = $_REQUEST['extension'];
        $db = new database;
        $extension = $db->select($sql, $parameters, 'row');
        unset($sql, $db, $parameters);

        if (isset($extension['extension_uuid'])) {
            if (isset($_REQUEST['name'])) {
                $param["name"] = $_REQUEST['name'];
            }
            if (isset($_REQUEST['extension'])) {
                $param["extension"] = $_REQUEST['extension'];
            }
            if (isset($_REQUEST['email'])) {
                $param["email"] = $_REQUEST['email'];
            }
            $param["status"] = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;

            //main
            $server_output = $this->api->updateUser($param);

            unset($param);
            //json response data
            echo json_encode($server_output);
        }
    }


    // ACTIVATE USER
    public function resetUserPassword()
    {
        $param = array();

        // Default param
        $param["id"] = $_REQUEST['id'];
        $param["orgid"] = $_REQUEST['orgid'];

        //main
        $server_output = $this->api->resetUserPassword($param);
        unset($param);

        // if ($server_output['result']['id'] == $_REQUEST['id']) {
        //     //json response data
        //     $output['result']['status'] = true;
        //     echo json_encode($output);
        // }
        echo json_encode($server_output);
        unset($output, $server_output);
    }

    // ACTIVATE USER
    public function activateUser()
    {
        $param = array();

        // Default param
        $param["orgid"] = $_REQUEST['orgid'];
        $param["id"] = $_REQUEST['id'];

        // Get List Of Extensions
        $sql  = "    select * from v_extensions  ";
        $sql .= "    where domain_uuid = :domain_uuid ";
        $sql .= "    and extension = :extension ";
        $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
        $parameters['extension'] = $_REQUEST['extension'];
        $db = new database;
        $extension = $db->select($sql, $parameters, 'row');
        unset($sql, $db, $parameters);

        if (isset($extension['extension_uuid'])) {
            $param["name"] = $extension['effective_caller_id_name'];
            $param["extension"] = isset($_REQUEST['extension']) ? $_REQUEST['extension'] : $extension['extension'];
            $param["email"] = $_REQUEST['email'];
            $param["username"] = $extension['username'];
            $param["authname"] = $extension['authname'];
            $param["status"] = 1;
            //main
            $server_output = $this->api->updateUser($param);
            unset($param);
            //json response data
            echo json_encode($server_output);
        }
    }

    // Detach User
    public function detachUser()
    {
        $param = array(
            "id" => $_REQUEST['id'],
            "userid" => $_REQUEST['userid'],
            "orgid" => $_REQUEST['orgid']
        );
        //main
        $server_output = $this->api->updateUser($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    // GET USERS
    public function usersState()
    {
        $param = array();
        //default param
        $param['branchid'] = $_REQUEST['branchid'];
        $param['orgid'] = $_REQUEST['orgid'];

        //main
        $server_output = $this->api->getUsers($param, null, null);
        $output = array();
        foreach ($server_output['result'] as $user) {
            $elem = array();
            $elem['id'] = $user['id'];
            $elem['state'] = $user['state'];
            $output['result'][] = $elem;
            unset($elem);
        }
        unset($param, $server_output);
        //json response data
        echo json_encode($output);
    }

    public function updateBranchWithDefaultSettings()
    {
        $param = array(
            "orgid" => $_REQUEST['orgid'],
            "id" => $_REQUEST['branchid'],
            "provision" => array(
                "multitenant" => false,
                "norec" => false,
                "nostates" => false,
                "nochats" => false,
                "novideo" => false,
                "noptions" => true,
                "nologae" => false,
                "maxregs" => $this->max_registration,
                "beta_updates" => false,
                "sms" => 3,
                "paging" => 0,
                "private" => false,
                "sms2email" => true,
                "nologmc" => false,
                "application" => "",
                "popup" => 0,
                "calldelay" => 10,
                "pcdelay" => false,
                "dnd" => array(
                    "on" => "",
                    "off" => ""
                ),
                "vmail" => array(
                    "on" => "",
                    "off" => "",
                    "ext" => "*97",
                    "spref" => "*97",
                    "mess" => "You have a new message",
                    "name" => "Voicemail"
                ),
                "forwarding" => array(
                    "cfon" => "",
                    "cfoff" => "",
                    "cfuon" => "",
                    "cfuoff" => "",
                    "cfbon" => "",
                    "cfboff" => ""
                ),
                "callwaiting" => array(
                    "on" => "",
                    "off" => ""
                ),
                "callpark" => array(
                    "park" => "park+*",
                    "retrieve" => "park+*",
                    "subscribe" => "park+*",
                    "slots" => array(
                        0 => array(
                            "alias" => "Park 1",
                            "slot" => "5901"
                        ),
                        1 => array(
                            "alias" => "Park 2",
                            "slot" => "5902"
                        ),
                        2 => array(
                            "alias" => "Park 3",
                            "slot" => "5903"
                        )
                    )
                ),
                "features" => "pbx",
                "blfs" => array(),
                "speeddial" => array(),
                "custompages" => array(),
                "fallback" => array(
                    "type" => "",
                    "prefix" => ""
                )
            )
        );

        //main
        $server_output = $this->api->updateBranch($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    public function updateBranchWithUpdatedSettings()
    {
        // ✅ address: "vladtest.ftpbx.net" 
        // ✅ country: "US"
        // ✅ inboundFormat: ""
        // ✅ multitenant: true
        // ✅ name: "vladtest.ftpbx.net"
        // ✅ nosrtp: true                 additional
        // ✅ noverify: true               additional
        // ✅ port: "5070"
        // ✅ protocol: "sips"
        // ✅ maxregs: "2" -> Int

        $provision = array(
            "multitenant" => $_REQUEST['multitenant'] == 'true' ? true : false,
            "inboundFormat" => $_REQUEST['inboundFormat'],
            "protocol" => $_REQUEST['protocol'],
            // required
            "maxregs" =>  intval($_REQUEST['maxregs']),
        );

        if (isset($_REQUEST['nosrtp'])) {
            $provision['nosrtp'] = $_REQUEST['nosrtp'] == 'true' ? true : false;
        }

        if (isset($_REQUEST['noverify'])) {
            $provision['noverify'] = $_REQUEST['noverify'] == 'true' ? true : false;
        }

        $param = array(
            "orgid" => $_REQUEST['orgid'],
            "id" => $_REQUEST['id'],
            "name" => $_REQUEST["name"],
            "address" => $_REQUEST["address"] . ':' . $_REQUEST['port'],
            "country" => $_REQUEST['country'],
            "provision" => $provision
        );

        //main
        $server_output = $this->api->updateBranch($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }


    public function updateParksWithUpdatedSettings()
    {
        // ✅ orgid
        // ✅ id: branchid
        // ✅ name: "vladtest.ftpbx.net"
        // ✅ from_park_number: 5901
        // ✅ to_park_number: 5903
        // array_of_parks: ['5906', '5908', '5912']

        $from_park_number = $_REQUEST['from_park_number'];
        $to_park_number = $_REQUEST['to_park_number'];

        $park_array = $_REQUEST['park_array'];

        if (isset($from_park_number) && isset($to_park_number)) {

            $slots = array();

            $id = 0;
            foreach ($park_array as $park) {
                $parkNumber = intval(substr(strval($park), -2));
                $slots[$id]['alias'] = 'Park ' . $parkNumber;
                $slots[$id]['slot'] = strval($park);
                $id++;
            }
            // for ($x = $from_park_number; $x <= $_REQUEST['to_park_number']; $x++) {
            //     $parkNumber = intval(substr(strval($x), -2));
            //     $slots[$id]['alias'] = 'Park ' . $parkNumber;
            //     $slots[$id]['slot'] = strval($x);
            //     $id++;
            // }
            
            $provision = array(
                "callpark" => array(
                    "slots" => $slots,
                    "subscribe" => "park+*",
                    "retrieve" => "park+*",
                    "park" => "park+*"
                ),
                // required
                "maxregs" => $this->max_registration,
            );

            unset($id, $slots);

            $param = array(
                "orgid" => $_REQUEST['orgid'],
                "id" => $_REQUEST['id'],
                "name" => $_REQUEST["name"],
                "provision" => $provision
            );

            //main
            $server_output = $this->api->updateBranch($param);
            unset($param);
            // json response data
            echo json_encode($server_output);
        }
    }

    public function updateOrganizationWithDefaultSettings($mode)
    {
        $param = array(
            "id" => $_REQUEST['orgid'],
            "params" => array(
                "emailcc" => "flagmango@flagmantelecom.com",
                "tags" => array(0 => isset($_REQUEST['tag']) ? $_REQUEST['tag'] : $_SESSION['ringotel']['server_name']['text'])
            )
        );

        // additionals variables
        if ($_REQUEST['packageid']) {
            $param['packageid'] = intval($_REQUEST['packageid']);
        }
        ;

        //main
        $server_output = $this->api->updateOrganization($param);
        unset($param);

        if ($mode === 'RETURN') {
            return $server_output;
        } else {
            //json response data
            echo json_encode($server_output);
        }
    }

    public function deactivateUser()
    {
        $param = array(
            "id" => $_REQUEST['id'],
            "orgid" => $_REQUEST['orgid']
        );

        //main
        $server_output = $this->api->deactivateUser($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    public function switchOrganizationMode()
    {

        // get current org settings
        $branches = $this->getBranches('RETURN');

        // switch the organization mode
        $server_output['switch'] = $this->updateOrganizationWithDefaultSettings('RETURN');

        // update setting of maxreg
        foreach ($branches as $branch_data) {
            $param["orgid"] = $_REQUEST['orgid'];
            $param["id"] = $branch_data[0]['id']; // per branch id
            $param["maxregs"] = $branch_data[0]['provision']['maxregs'];

            // return max reg or other default options
            $server_output[] = $this->api->updateBranchWithDefaultOptionsAfterSwitcher($param);
        }

        // json response data
        echo json_encode($server_output);
        unset($param, $x, $branches, $branches, $server_output);
    }

    /////
    ////
    ///
    // INTEGRATION

    // create Integration
    public function createIntegration()
    {
        $param = array(
            'profileid' => $_REQUEST['profileid'],
            'Username' => $_SESSION['ringotel']['ringotel_integration_username']['text'],
            'Password' => $_SESSION['ringotel']['ringotel_integration_password']['text'],
            'Account_ID' => $_SESSION['ringotel']['ringotel_integration_account_id']['text'],
            'Application_ID' => $_SESSION['ringotel']['ringotel_integration_application_id']['text'],
        );
        //main
        $server_output = $this->api->createIntegration($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    // delete Integration
    public function deleteIntegration()
    {
        $param = array(
            'profileid' => $_REQUEST['profileid'],
            'Username' => $_SESSION['ringotel']['ringotel_integration_username']['text'],
            'Password' => $_SESSION['ringotel']['ringotel_integration_password']['text'],
            'Account_ID' => $_SESSION['ringotel']['ringotel_integration_account_id']['text'],
            'Application_ID' => $_SESSION['ringotel']['ringotel_integration_application_id']['text'],
        );
        //main
        $server_output = $this->api->deleteIntegration($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    // get Integration
    public function getIntegration()
    {
        $param = array(
            "orgid" => $_REQUEST['orgid']
        );
        //main
        $server_output = $this->api->getServices($param);
        function even($var)
        {
            return $var['state'] == 1 && $var['id'] == "Bandwidth";
        }
        unset($param);
        $server_output['result'] = array_values(array_filter($server_output['result'], "even"));
        //json response data
        echo json_encode($server_output);
    }

    // Get Numbers Configuration 
    public function getSMSTrunk()
    {
        $param = array(
            'orgid' => $_REQUEST['orgid']
        );
        //main
        $server_output = $this->api->getSMSTrunk($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    // Create Numbers Configuration
    public function createSMSTrunk()
    {
        $param = array(
            'orgid' => $_REQUEST['orgid'],
            'name' => isset($_REQUEST['name']) ? $_REQUEST['name'] : $_REQUEST['number'],
            'number' => $_REQUEST['number'],
            'users' => $_REQUEST['users']
        );
        //main
        $server_output = $this->api->createSMSTrunk($param);
        unset($param);
        //json response data
        echo json_encode($server_output);
    }

    // Update Numbers Configuration 
    public function updateSMSTrunk()
    {
        $param = array(
            'orgid' => $_REQUEST['orgid'],
            'id' => $_REQUEST['id'],
            'name' => $_REQUEST['name'],
            'number' => $_REQUEST['number'],
            'users' => $_REQUEST['users']
        );
        // main
        $server_output = $this->api->updateSMSTrunk($param);
        unset($param);
        echo json_encode($server_output);
    }

    // Delete Numbers Configuration
    public function deleteSMSTrunk()
    {
        $param = array(
            'orgid' => $_REQUEST['orgid'],
            'id' => $_REQUEST['id']
        );
        // main
        $server_output = $this->api->deleteSMSTrunk($param);
        unset($param);
        echo json_encode($server_output);
    }

    //
    ///
    ////
    /////

    //////

    /////
    ////
    ///
    // For API Endpoint

    function getRingotelApiUrl() {
        if (empty($_SESSION['ringotel']['ringotel_api']['text'])) {
            $ringotelRepository = new RingotelRepository();
            $ringotel_api_ = $ringotelRepository->getRingotelApiUrl();
            $this->api = new RingotelApiFunctions($ringotel_api_);
        }
    }

    function getRingotelTokenFn() {
        if (empty($_SESSION['ringotel']['ringotel_token']['text'])) {
            $ringotelRepository = new RingotelRepository();
            $ringotel_token = $ringotelRepository->getRingotelToken();
            return $ringotel_token;
        }
    }

    // GET ORGANIZATIONS
    function getOrganizationApi($ringotel_token, $mode)
    {
        // Main
        $server_output = $this->api->getOrganizations($ringotel_token, $mode);
        unset($param, $ringotel_token);

        $decoded_data = json_decode($server_output, true);
        $orgid = $decoded_data['result'][0]['id'];

        if ($mode === 'RETURN') {
            return array("orgid" => $orgid);
        } else {
            echo json_encode(array("orgid" => $orgid));
        }
    }

    // GET USERS
    function getUsersApi($param, $ringotel_token, $mode)
    {
        // Main
        $server_output = $this->api->getUsers($param, $ringotel_token, $mode);
        unset($param, $ringotel_token);

        $decoded_data = json_decode($server_output, true);
        $users = $decoded_data['result'];

        if ($mode === 'RETURN') {
            return $users;
        } else {
            echo json_encode($users);
        }
    }

    // GET Ringotel Extensions
    function getRingotelExtensions($mode)
    {
        // Settup the base->api and the ringotel token if it's not exist
        $this->getRingotelApiUrl();
        $ringotel_token = $this->getRingotelTokenFn();

        // Main
        $org_res = $this->getOrganizationApi($ringotel_token, $mode);
        $orgid = $org_res['orgid'];

        if (!empty($orgid)) {
            $param = array();
            $param['orgid'] = $orgid;

            $users_res = $this->getUsersApi($param, $ringotel_token, $mode);

            $users = array_map(function ($elem) {
                return array("extension" => $elem['extension'], "status" => $elem['status']);
            }, $users_res);

            // // Define the regular expression
            // $regexp = '/[\+*]/';

            // // Filter for parks extensions
            // $parks = array_filter($users, function($ext) use ($regexp) {
            //     return preg_match($regexp, $ext['extension']);
            // });

            // // Filter for users with extensions (status === 1)
            // $users = array_filter($users, function($ext) use ($regexp) {
            //     return !preg_match($regexp, $ext['extension']) && $ext['status'] === 1;
            // });
            // usort($users, function($a, $b) {
            //     return intval($a['extension']) - intval($b['extension']);
            // });

            // // Filter for extensions with status !== 1
            // $extensions = array_filter($users, function($ext) use ($regexp) {
            //     return !preg_match($regexp, $ext['extension']) && $ext['status'] !== 1;
            // });
            // usort($extensions, function($a, $b) {
            //     return intval($a['extension']) - intval($b['extension']);
            // });
            // return array(
            //     "parks" => $parks,
            //     "users" => $users,
            //     "extensions" => $extensions
            // );
            return $users;
        } else {
            return null;
        }
        unset($param, $ringotel_token, $mode);
    }
    //
    ///
    ////
    /////

}
