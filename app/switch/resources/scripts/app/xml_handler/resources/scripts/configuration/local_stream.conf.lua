--include xml library
	local Xml = require "resources.functions.xml";

--get the cache
	local cache = require "resources.functions.cache"
	local local_stream_cache_key = "configuration:local_stream.conf"
	XML_STRING, err = cache.get(local_stream_cache_key)

--set the cache
	if not XML_STRING then

		--log the cache error
			if (debug["cache"]) then
				freeswitch.consoleLog("warning", "[xml_handler] configuration:local_stream.conf can not be get from cache: " .. tostring(err) .. "\n");
			end

		--set a default value
			if (expire["default"] == nil) then
				expire["default"]= "3600";
			end

		--connect to the database
			local Database = require "resources.functions.database";
			dbh = Database.new('system');

		--include json library
			local json
			if (debug["sql"]) then
				json = require "resources.functions.lunajson"
			end

		--exits the script if we didn't connect properly
			assert(dbh:connected());

		--start the xml array
			local xml = Xml:new();
			xml:append([[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
			xml:append([[<document type="freeswitch/xml">]]);
			xml:append([[	<section name="configuration">]]);
			xml:append([[		<configuration name="local_stream.conf" description="stream files from local dir">]]);

		--run the query
			sql = "select d.domain_name, s.* "
			sql = sql .. "from v_music_on_hold as s left outer join v_domains as d "
			sql = sql .. "on d.domain_uuid = s.domain_uuid "
			sql = sql .. "order by s.music_on_hold_name asc "
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
			end
			x = 0;
			dbh:query(sql, function(row)

				--combine the name, domain_name and the rate 
				name = '';
				if (row.domain_uuid ~= nil and string.len(row.domain_uuid) > 0) then
					name = row.domain_name..'/';
				end
				name = name .. row.music_on_hold_name;
				if (row.music_on_hold_rate ~= nil and #row.music_on_hold_rate > 0) then
					name = name .. '/' .. row.music_on_hold_rate;
				end

				--replace the variable with the path to the sounds directory
				music_on_hold_path = row.music_on_hold_path:gsub("$${sounds_dir}", sounds_dir);

				--set the rate
				rate = row.music_on_hold_rate;
				if rate == '' then
					rate = '48000';
				end

				--add the full path to the chime list
				chime_list = row.music_on_hold_chime_list;
				if (chime_list ~= nil) then
					chime_array = explode(",", chime_list);
					chime_list = "";
					for k,v in pairs(chime_array) do
						f = explode("/", v);
						if (f[1] ~= nil and f[2] ~= nil and file_exists(sounds_dir.."/en/us/callie/"..f[1].."/"..rate.."/"..f[2])) then
							chime_list = chime_list .. sounds_dir.."/en/us/callie/"..v;
						else
							chime_list = chime_list .. v;
						end
					end
				end

				--set the default timer name to soft
				if (row.music_on_hold_timer_name == nil or row.music_on_hold_timer_name == '') then
					timer_name = "soft";
				else
					timer_name = row.music_on_hold_timer_name;
				end

				--build the xml ]]..row.music_on_hold_name..[["
				xml:append([[	<directory name="]] .. xml.sanitize(name) .. [[" uuid="]] .. xml.sanitize(row.music_on_hold_uuid) .. [[" path="]] .. xml.sanitize(music_on_hold_path) .. [[">]]);
				xml:append([[			<param name="rate" value="]] .. xml.sanitize(rate) .. [["/>]]);
				xml:append([[			<param name="shuffle" value="]] .. xml.sanitize(row.music_on_hold_shuffle) .. [["/>]]);
				xml:append([[			<param name="channels" value="1"/>]]);
				xml:append([[			<param name="interval" value="20"/>]]);
				xml:append([[			<param name="timer-name" value="]] .. xml.sanitize(timer_name) ..[["/>]]);
				if (chime_list ~= nil) then
					xml:append([[			<param name="chime-list" value="]] .. xml.sanitize(chime_list) .. [["/>]]);
				end
				if (row.music_on_hold_chime_freq ~= nil) then
					xml:append([[			<param name="chime-freq" value="]] .. xml.sanitize(row.music_on_hold_chime_freq) .. [["/>]]);
				end
				if (row.music_on_hold_chime_max ~= nil) then
					xml:append([[			<param name="chime-max" value="]] .. xml.sanitize(row.music_on_hold_chime_max) .. [["/>]]);
				end
				xml:append([[		</directory>]]);

			end)

		--close the extension tag if it was left open
			xml:append([[		</configuration>]]);
			xml:append([[	</section>]]);
			xml:append([[</document>]]);
			XML_STRING = xml:build();
			if (debug["xml_string"]) then
				freeswitch.consoleLog("notice", "[xml_handler] XML_STRING: " .. XML_STRING .. "\n");
			end

		--close the database connection
			dbh:release();

		--set the cache
			local ok, err = cache.set(local_stream_cache_key, XML_STRING, expire["default"]);
			if debug["cache"] then
				if ok then
					freeswitch.consoleLog("notice", "[xml_handler] " .. local_stream_cache_key .. " stored in the cache\n");
				else
					freeswitch.consoleLog("warning", "[xml_handler] " .. local_stream_cache_key .. " can not be stored in the cache: " .. tostring(err) .. "\n");
				end
			end

		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. local_stream_cache_key .. " source: database\n");
			end
	else
		--send to the console
			if (debug["cache"]) then
				freeswitch.consoleLog("notice", "[xml_handler] " .. local_stream_cache_key .. " source: cache\n");
			end
	end --if XML_STRING

--send the xml to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .. "/local_stream.conf.xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
