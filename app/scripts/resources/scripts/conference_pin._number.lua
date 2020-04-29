
--include config.lua
require "resources.functions.config";

--make sure the session is ready
if ( session:ready() ) then

    --define the check pin number function
        tries = 0;
        function check_pin_number()
    
            --sleep
                session:sleep(500);
    
            --get the domain_uuid
                domain_uuid = session:getVariable("domain_uuid");
                if (domain_uuid == nil) then
                    --connect to the database
                        local Database = require "resources.functions.database";
                        dbh = Database.new('system');

                    --include json library
                        if (debug["sql"]) then
                            json = require "resources.functions.lunajson"
                        end
                    --get the domain_name
                        domain_name = session:getVariable("domain_name");

                    --get the domain_uuid using the domain_name
                        local sql = "SELECT domain_name FROM v_domains WHERE domain_name = :domain_name";
                        local params = {domain_name = domain_name};
                        if (debug["sql"]) then
                            freeswitch.consoleLog("NOTICE", "[pin_number] SQL: "..sql.."; params: " .. json.encode(params) .. "\n");
                        end
                        dbh:query(sql, params, function(row)
                            domain_uuid = row["domain_uuid"];
                        end);
                end

            --set the preset variables
                min_digits = 2;
                max_digits = 20;
                max_tries = 3;
                digit_timeout = 5000;
                conference_pin_prompt = sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-pin.wav";

            --get the pin
                digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", conference_pin_prompt, "", "\\d+");

            --validate the user pin number
                if (digits == moderator_pin) then
                    session:setVariable("conference_member_type", "moderator")
                    --set the variable to true
                        auth = true;
                elseif(digits == participant_pin) then
                    session:setVariable("conference_member_type", "member")
                    --set the variable to true
                        auth = true;
                else
                    --increment the number of tries
                    tries = tries + 1;
                    if (tries < max_tries) then
                        session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/conference/conf-bad-pin.wav");
                        check_pin_number();
                    else
                        session:streamFile("phrase:voicemail_fail_auth:#");
                        session:hangup("NORMAL_CLEARING");
                        return;
                    end
                end

        end

        if ( session:ready() ) then

            --answer the call
                session:answer();

                if not (auth) then
                    --get the variables
                        member_type = session:getVariable("conference_member_type")
                        moderator_pin = session:getVariable("moderator_pin")
                        participant_pin = session:getVariable("member_pin")
                        default_language = session:getVariable("default_language");
                        default_dialect = session:getVariable("default_dialect");
                        default_voice = session:getVariable("default_voice");
                        if (not default_language) then default_language = 'en'; end
                        if (not default_dialect) then default_dialect = 'us'; end
                        if (not default_voice) then default_voice = 'callie'; end

                    --if member_type is already set then authorize
                    if (member_type == "member" or member_type == "moderator") then
                        auth = true
                    else
                        check_pin_number()
                    end
                end
        end
end