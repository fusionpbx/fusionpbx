--      xml_handler.lua
--      Part of FusionPBX
--      Copyright (C) 2015 Mark J Crane <markjcrane@fusionpbx.com>
--      All rights reserved.
--
--      Redistribution and use in source and binary forms, with or without
--      modification, are permitted provided that the following conditions are met:
--
--      1. Redistributions of source code must retain the above copyright notice,
--         this list of conditions and the following disclaimer.
--
--      2. Redistributions in binary form must reproduce the above copyright
--         notice, this list of conditions and the following disclaimer in the
--         documentation and/or other materials provided with the distribution.
--
--      THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--      INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--      AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--      AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--      OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--      SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--      INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--      CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--      ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--      POSSIBILITY OF SUCH DAMAGE.

--get the cache
	hostname = trim(api:execute("switchname", ""));
	if (trim(api:execute("module_exists", "mod_memcache")) == "true") then
		XML_STRING = trim(api:execute("memcache", "get configuration:ivr.conf:" .. hostname));
	else
		XML_STRING = "-ERR NOT FOUND";
	end

--set the cache
	if (XML_STRING == "-ERR NOT FOUND") or (XML_STRING == "-ERR CONNECTION FAILURE") then

		--connect to the database
			require "resources.functions.database_handle";
			dbh = database_handle('system');

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--get the ivr menu from the database
			sql = [[SELECT * FROM v_ivr_menus 
				WHERE ivr_menu_uuid = ']] .. ivr_menu_uuid ..[['
				AND ivr_menu_enabled = 'true' ]];
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "\n");
			end
			status = dbh:query(sql, function(row)
				domain_uuid = row["domain_uuid"];
				ivr_menu_name = row["ivr_menu_name"];
				--ivr_menu_extension = row["ivr_menu_extension"];
				ivr_menu_greet_long = row["ivr_menu_greet_long"];
				ivr_menu_greet_short = row["ivr_menu_greet_short"];
				ivr_menu_invalid_sound = row["ivr_menu_invalid_sound"];
				ivr_menu_exit_sound = row["ivr_menu_exit_sound"];
				ivr_menu_confirm_macro = row["ivr_menu_confirm_macro"];
				ivr_menu_confirm_key = row["ivr_menu_confirm_key"];
				ivr_menu_tts_engine = row["ivr_menu_tts_engine"];
				ivr_menu_tts_voice = row["ivr_menu_tts_voice"];
				ivr_menu_confirm_attempts = row["ivr_menu_confirm_attempts"];
				ivr_menu_timeout = row["ivr_menu_timeout"];
				--ivr_menu_exit_app = row["ivr_menu_exit_app"];
				--ivr_menu_exit_data = row["ivr_menu_exit_data"];
				ivr_menu_inter_digit_timeout = row["ivr_menu_inter_digit_timeout"];
				ivr_menu_max_failures = row["ivr_menu_max_failures"];
				ivr_menu_max_timeouts = row["ivr_menu_max_timeouts"];
				ivr_menu_digit_len = row["ivr_menu_digit_len"];

				ivr_menu_direct_dial = row["ivr_menu_direct_dial"];
				ivr_menu_ringback = row["ivr_menu_ringback"];
				ivr_menu_cid_prefix = row["ivr_menu_cid_prefix"];
				ivr_menu_description = row["ivr_menu_description"];
			end);

		--recording path
			

		--start the xml array
			local xml = {}
			table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			table.insert(xml, [[<document type="freeswitch/xml">]]);
			table.insert(xml, [[	<section name="configuration">]]);
			table.insert(xml, [[		<configuration name="ivr.conf" description="IVR Menus">]]);

			table.insert(xml, [[			<menus>]]);
			dbh:query(sql, function(row)

				--build the xml
					table.insert(xml, [[				<menu name="]]..ivr_menu_uuid..[[" description="]]..ivr_menu_name..[[" ]]);
					table.insert(xml, [[				greet-long="]]..ivr_menu_greet_long..[[" ]]);
					table.insert(xml, [[				greet-short="]]..ivr_menu_greet_short..[[" ]]);
					table.insert(xml, [[				invalid-sound="]]..ivr_menu_invalid_sound..[[" ]]);
					table.insert(xml, [[				exit-sound="]]..ivr_menu_exit_sound..[[" ]]);
					table.insert(xml, [[				confirm-macro="]]..ivr_menu_confirm_macro..[[" ]]);
					table.insert(xml, [[				confirm-key="]]..ivr_menu_confirm_key..[[" ]]);
					table.insert(xml, [[				tts-engine="]]..ivr_menu_tts_engine..[[" ]]);
					table.insert(xml, [[				tts-voice="]]..ivr_menu_tts_voice..[[" ]]);
					table.insert(xml, [[				confirm-attempts="]]..ivr_menu_confirm_attempts..[[" ]]);
					table.insert(xml, [[				timeout="]]..ivr_menu_timeout..[[" ]]);
					table.insert(xml, [[				inter-digit-timeout="]]..ivr_menu_inter_digit_timeout..[[" ]]);
					table.insert(xml, [[				max-failures="]]..ivr_menu_max_failures..[[" ]]);
					table.insert(xml, [[				max-timeouts="]]..ivr_menu_max_timeouts..[[" ]]);
					table.insert(xml, [[				digit-len="]]..ivr_menu_digit_len..[[" ]]);
					table.insert(xml, [[				/>]]);

				--get the ivr menu options
					sql = [[SELECT * FROM v_ivr_menu_options WHERE ivr_menu_uuid = ']] .. ivr_menu_uuid ..[[' ORDER BY ivr_menu_option_order asc ]];
					if (debug["sql"]) then
						freeswitch.consoleLog("notice", "[ivr_menu] SQL: " .. sql .. "\n");
					end
					status = dbh:query(sql, function(r)
					dbh:query(sql, function(r)
						ivr_menu_option_digits = r.ivr_menu_option_digits
						ivr_menu_option_action = r.ivr_menu_option_action
						ivr_menu_option_param = r.ivr_menu_option_param
						ivr_menu_option_description = row.ivr_menu_option_description
						table.insert(xml, [[<entry action="]]..ivr_menu_option_action..[[" digits="]]..ivr_menu_option_digits..[[" param="]]..ivr_menu_option_param..[["/><!--]]..ivr_menu_option_description..[[-->]]);
					end)

				--direct dial
					if (ivr_menu_direct_dial == "true") then
						table.insert(xml, [[<entry action="menu-exec-app" digits="/(^(\d{2,5}))$/" param="transfer $1 XML features"/>\n");
					end
			end)
			table.insert(xml, [[			</menus>]]);

		--close the extension tag if it was left open
				table.insert(xml, [[		</configuration>]]);
				table.insert(xml, [[	</section>]]);
				table.insert(xml, [[</document>]]);
				XML_STRING = table.concat(xml, "\n");
				if (debug["xml_string"]) then
						freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: " .. XML_STRING .. "\n");
				end

		--close the database connection
				dbh:release();
				--freeswitch.consoleLog("notice", "[xml_handler]"..api:execute("eval ${dsn}"));

		--set the cache
			result = trim(api:execute("memcache", "set configuration:ivr.conf:" .. hostname .." '"..XML_STRING:gsub("'", "&#39;").."' ".."expire['ivr.conf']"));

		--send the xml to the console
			if (debug["xml_string"]) then
				local file = assert(io.open(temp_dir .. "/ivr.conf.xml", "w"));
				file:write(XML_STRING);
				file:close();
			end

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] configuration:ivr.conf:" .. hostname .." source: database\n");
			end
	else
		--replace the &#39 back to a single quote
			XML_STRING = XML_STRING:gsub("&#39;", "'");
		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] configuration:ivr.conf source: memcache\n");
			end
	end --if XML_STRING
