--      call_flow_reset.lua
--      Part of FusionPBX
--      Copyright (C) 2021 Stellar VoIP
--      All rights reserved.
--

--set the variables
        max_tries = "3";
        digit_timeout = "5000";

--include config.lua
        require "resources.functions.config";

--create logger object
        log = require "resources.functions.log".call_flow

--additional includes
        local presence_in = require "resources.functions.presence_in"
        local Database    = require "resources.functions.database"
        --local play_file   = require "resources.functions.play_file"

--include json library
        local json
        if (debug["sql"]) then
                json = require "resources.functions.lunajson"
        end

--connect to the database
        local dbh = Database.new('system');


--get all call flows details that were toggled
        local sql = "SELECT * FROM v_call_flows where call_flow_status = 'false'"

        dbh:query(sql, params, function(row)
                call_flow_name = row.call_flow_name;
                call_flow_extension = row.call_flow_extension;
                call_flow_feature_code = row.call_flow_feature_code;
                call_flow_context = row.call_flow_context;
                call_flow_feature_code = row.call_flow_feature_code;
                domain_name = row.call_flow_context;
                call_flow_uuid = row.call_flow_uuid;
                call_flow_status = row.call_flow_status;
                --freeswitch.consoleLog("INFO","name  is " .. call_flow_name ..". Context is " .. call_flow_context .."\n");

                -- turn the lamp
                presence_in.turn_lamp( toggle == "false",
                                'flow+'..call_flow_feature_code.."@"..domain_name,
                                call_flow_uuid
                        );
                freeswitch.consoleLog("INFO",call_flow_feature_code.. " is turned off in domain " .. domain_name)

                --feature code - toggle the status
                local toggle = (call_flow_status == "true") and "false" or "true"

                --active label
                local active_flow_label = (toggle == "true") and call_flow_label or call_flow_alternate_label

                --play info message
                local audio_file = (toggle == "true") and call_flow_sound or call_flow_alternate_sound

                --show in the console
                --log.noticef("label=%s,status=%s,uuid=%s,audio=%s", active_flow_label, toggle, call_flow_uuid, audio_file)

                --store in database
                dbh:query("UPDATE v_call_flows SET call_flow_status = :toggle WHERE call_flow_uuid = :call_flow_uuid", {
                                toggle = toggle, call_flow_uuid = call_flow_uuid
                        });
                --freeswitch.consoleLog("INFO"," toggle is " .. toggle)

        end);
