--	Part of FusionPBX
--	Copyright (C) 2013-2025 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	  this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	  notice, this list of conditions and the following disclaimer in the
--	  documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--connect to the database
    Database = require "resources.functions.database";
    dbh = Database.new('system');

--get settings
    require "resources.functions.settings";
    settings = settings();

--set deletion_queue_retention_hours
    if (settings['voicemail'] ~= nil) then
        if (settings['voicemail']['deletion_queue_retention_hours'] ~= nil) then
            if (settings['voicemail']['deletion_queue_retention_hours']['numeric'] ~= nil) then
                retention_hours = settings['voicemail']['deletion_queue_retention_hours']['numeric'];
            else
                retention_hours = "24";
            end
        end
    end

--set the voicemail_dir
    if (settings['switch'] ~= nil) then
        if (settings['switch']['voicemail'] ~= nil) then
            if (settings['switch']['voicemail']['dir'] ~= nil) then
                voicemail_dir = settings['switch']['voicemail']['dir'].."/default";
            end
        end
    end

--get the voicemail extension
    sql = "SELECT * FROM v_vars WHERE var_category = 'Defaults' AND var_name = 'vm_message_ext' AND var_enabled = 'true'";
    dbh:query(sql, function(row)
        vm_message_ext = row["var_value"];
    end);
    if (vm_message_ext == nil) then
        vm_message_ext = "wav";
    end

--get messages
    sql = "SELECT * FROM v_voicemail_messages WHERE message_status = 'deleted' AND (update_date + interval '" .. retention_hours .. " hours') < now()";
    messages_to_delete = {};
    dbh:query(sql, function(row)
        table.insert(messages_to_delete, row);
    end);

--delete the messages
    total_messages = #messages_to_delete;
    message_number = 1;
    while message_number <= total_messages do
        local message_row = messages_to_delete[message_number];
        local uuid = message_row["voicemail_message_uuid"];

        --get domain_name
        sql = [[SELECT * from v_domains
            WHERE domain_uuid = :domain_uuid  
        ]];
        local params = {domain_uuid = message_row["domain_uuid"]};
        dbh:query(sql, params, function(row)
            domain_name = row["domain_name"];
        end);

        --get voicemail_id
        sql = [[SELECT * from v_voicemails
            WHERE domain_uuid = :domain_uuid
            AND voicemail_uuid = :voicemail_uuid   
        ]];
        local params = {domain_uuid = message_row["domain_uuid"], voicemail_uuid = message_row["voicemail_uuid"]};
        dbh:query(sql, params, function(row)
            voicemail_id = row["voicemail_id"];
        end);

        --delete the file
		os.remove(voicemail_dir.."/"..domain_name.."/"..voicemail_id.."/intro_msg_"..uuid.."."..vm_message_ext);
		os.remove(voicemail_dir.."/"..domain_name.."/"..voicemail_id.."/intro_"..uuid.."."..vm_message_ext);
		os.remove(voicemail_dir.."/"..domain_name.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);

        --delete from the database
		sql = [[DELETE FROM v_voicemail_messages
            WHERE domain_uuid = :domain_uuid
            AND voicemail_uuid = :voicemail_uuid
            AND voicemail_message_uuid = :uuid]];
        local params = {
            domain_uuid = message_row["domain_uuid"], 
            voicemail_uuid = message_row["voicemail_uuid"], 
            uuid = uuid
        };
        if (debug["sql"]) then
            freeswitch.consoleLog("notice", "[voicemail] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
        end
        dbh:query(sql, params);
        --log to console
        if (debug["info"]) then
            freeswitch.consoleLog("notice", "[voicemail][deleted] message: " .. uuid .. "\n");
        end

    end