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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/



    if ($authorized) {
            
        //get the raw input data
            $json = file_get_contents('php://input');

        //decode the json into array
            $message = json_decode($json, true);
            $message = $message[0]['message'];

        //get the source phone number
            $phone_number = preg_replace('{[\D]}', '', $message['from']);

        //get the contact uuid
            $sql = "select c.contact_uuid ";
            $sql .= "from v_contacts as c, v_contact_phones as p ";
            $sql .= "where p.contact_uuid = c.contact_uuid ";
            $sql .= "and p.phone_number = :phone_number ";
            $sql .= "and c.domain_uuid = :domain_uuid ";
            $parameters['phone_number'] = $phone_number;
            $parameters['domain_uuid'] = $domain_uuid;
            $database = new database;
            $contact_uuid = $database->select($sql, $parameters, 'column');
            unset($sql, $parameters);

        //build message array
        foreach ($message['to'] as $message_to) {


                $message_uuid = uuid();
                $array['messages'][0]['message_uuid'] = $message_uuid;
                $array['messages'][0]['domain_uuid'] = $domain_uuid;
                $array['messages'][0]['user_uuid'] = $user_uuid;
                $array['messages'][0]['contact_uuid'] = $contact_uuid;
                $array['messages'][0]['message_uuid'] = $message_uuid;
                $array['messages'][0]['message_type'] = is_array($message['media']) ? 'mms' : 'sms';
                $array['messages'][0]['message_direction'] = 'inbound';
                $array['messages'][0]['message_date'] = 'now()';
                $array['messages'][0]['message_from'] = $message['from'];
                $array['messages'][0]['message_to'] = $message_to;
                $array['messages'][0]['message_text'] = $message['text'];
                $array['messages'][0]['message_json'] = $json;

            //add the required permission
                $p = new permissions;
                $p->add("message_add", "temp");

            //build message media array (if necessary)
                if (is_array($message['media'])) {
                    foreach($message['media'] as $index => $media_url) {
                        $media_type = pathinfo($media_url, PATHINFO_EXTENSION);
                        if ($media_type !== 'xml') {
                            $array['message_media'][$index]['message_media_uuid'] = uuid();
                            $array['message_media'][$index]['message_uuid'] = $message_uuid;
                            $array['message_media'][$index]['domain_uuid'] = $domain_uuid;
                            $array['message_media'][$index]['user_uuid'] = $user_uuid;
                            $array['message_media'][$index]['message_media_type'] = $media_type;
                            $array['message_media'][$index]['message_media_url'] = $media_url;
                            $array['message_media'][$index]['message_media_content'] = base64_encode(file_get_contents($media_url));
                        }
                    }

                    $p->add("message_media_add", "temp");
                }

            //save message to the database
                $database = new database;
                $database->app_name = 'messages';
                $database->app_uuid = '4a20815d-042c-47c8-85df-085333e79b87';
                $database->save($array);
                $result = $database->message;

            //remove the temporary permission
                $p->delete("message_add", "temp");
                $p->delete("message_media_add", "temp");
        }

        //convert the array to json
            $array_json = json_encode($array);

        //get the list of extensions using the user_uuid
            $sql = "select * from v_domains as d, v_extensions as e ";
            $sql .= "where extension_uuid in ( ";
            $sql .= "	select extension_uuid ";
            $sql .= "	from v_extension_users ";
            $sql .= "	where user_uuid = :user_uuid ";
            $sql .= ") ";
            $sql .= "and e.domain_uuid = d.domain_uuid ";
            $sql .= "and e.enabled = 'true' ";
            $parameters['user_uuid'] = $user_uuid;
            $database = new database;
            $extensions = $database->select($sql, $parameters, 'all');
            unset($sql, $parameters);

        //create the event socket connection
            if (is_array($extensions)) {
                $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
            }

        //send the sip message
            if (is_array($extensions) && @sizeof($extensions) != 0) {
                foreach ($extensions as $row) {
                    $domain_name = $row['domain_name'];
                    $extension = $row['extension'];
                    $number_alias = $row['number_alias'];

                    //send the sip messages
                    $command = "luarun app/messages/resources/send.lua ".$message["from"]."@".$domain_name." ".$extension."@".$domain_name."  '".$message["text"]."'";

                    //send the command
                    $response = event_socket_request($fp, "api ".$command);
                    $response = event_socket_request($fp, "api log notice ".$command);
                }
            }
            unset($extensions, $row);
    }

?>
