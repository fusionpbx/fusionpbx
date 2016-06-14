
--connect to the database
	require "resources.functions.database_handle";
	dbh = database_handle('system');

--exits the script if we didn't connect properly
	assert(dbh:connected());

--start the xml array
	local xml = {}
	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[	<section name="configuration">]]);
	table.insert(xml, [[		<configuration name="local_stream.conf" description="stream files from local dir">]]);

	--table.insert(xml, [[		<!-- fallback to default if requested moh class isn't found -->]]);
	--table.insert(xml, [[		<directory name="default" path="]]..sounds_dir..[[/music/default/8000">]]);
	--table.insert(xml, [[			<param name="rate" value="8000"/>]]);
	--table.insert(xml, [[			<param name="shuffle" value="true"/>]]);
	--table.insert(xml, [[			<param name="channels" value="1"/>]]);
	--table.insert(xml, [[			<param name="interval" value="20"/>]]);
	--table.insert(xml, [[			<param name="timer-name" value="soft"/>]]);
	--table.insert(xml, [[			<!-- list of short files to break in with every so often -->]]);
	--table.insert(xml, [[			<!--<param name="chime-list" value="file1.wav,file2.wav"/>-->]]);
	--table.insert(xml, [[			<!-- frequency of break-in (seconds)-->]]);
	--table.insert(xml, [[			<!--<param name="chime-freq" value="30"/>-->]]);
	--table.insert(xml, [[			<!-- limit to how many seconds the file will play -->]]);
	--table.insert(xml, [[			<!--<param name="chime-max" value="500"/>-->]]);
	--table.insert(xml, [[		</directory>]]);

--run the query
	sql = "select * from v_music_on_hold ";
	sql = sql .. "order by music_on_hold_name asc ";
	if (debug["sql"]) then
		freeswitch.consoleLog("notice", "[xml_handler] SQL: " .. sql .. "\n");
	end
	x = 0;
	dbh:query(sql, function(row)

		--build the xml ]]..row.music_on_hold_name..[["
		table.insert(xml, [[	<directory name="]]..row.music_on_hold_name..[[" path="]]..row.music_on_hold_path..[[">]]);
		table.insert(xml, [[			<param name="rate" value="]]..row.music_on_hold_rate..[["/>]]);
		table.insert(xml, [[			<param name="shuffle" value="]]..row.music_on_hold_shuffle..[["/>]]);
		table.insert(xml, [[			<param name="channels" value="1"/>]]);
		table.insert(xml, [[			<param name="interval" value="20"/>]]);
		table.insert(xml, [[			<param name="timer-name" value="]]..row.music_on_hold_timer..[["/>]]);
		if (row.music_on_hold_chime_list ~= nil) then
			table.insert(xml, [[			<param name="chime-list" value="]]..row.music_on_hold_chime_list..[["/>]]);
		end
		if (row.music_on_hold_chime_freq ~= nil) then
			table.insert(xml, [[			<param name="chime-freq" value="]]..row.music_on_hold_chime_freq..[["/>]]);
		end
		if (row.music_on_hold_chime_max ~= nil) then
			table.insert(xml, [[			<param name="chime-max" value="]]..row.music_on_hold_chime_max..[["/>]]);
		end
		table.insert(xml, [[		</directory>]]);

	end)

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

--send the xml to the console
	if (debug["xml_string"]) then
		local file = assert(io.open(temp_dir .. "/local_stream.conf.xml", "w"));
		file:write(XML_STRING);
		file:close();
	end
