--description
	--monitor custom cache event and clear cache on remote servers
	--protect xmlrpc using a firewall on the server to limit access by ip address
	--further protect it by configuring ssl for xmlrpc on the remote server

--dependencies
	--install mod_curl freeswitch module
		--uncomment mod_curl from modules.conf when compiling freeswitch
	--xmlrpc
		--open port xmlrpc port for other master server IP addresses
		--change the password for xmlrpc in system -> settings
	--conf/autoload_configs/lua.conf.xml
		-- <param name="startup-script" value="app/server/resources/file_cache.lua"/>
	--iptables
		-- /sbin/iptables -I INPUT -j ACCEPT -p tcp --dport 8080 -s x.x.x.x/32
		-- ubuntu: service iptables-persistent save

--define the servers running freeswitch do not include local
	--[[
  	#put this in default settings once for each server. Order doesn't matter.
		Category: cache
		Subcategory: remote_servers
		Type: array
		value: http://user:password@server_ip:8080
	]]

--includes config.lua which will include local.lua if it exists
	require "resources.functions.config"
	local Database = require "resources.functions.database";
	local Settings = require "resources.functions.lazy_settings";

	local db = dbh or Database.new('system');
	local settings = Settings.new(db, domain_name, domain_uuid)

	local server_list = settings:get('cache', 'remote_servers', 'array')

--subscribe to the events
	--events = freeswitch.EventConsumer("all");
	events = freeswitch.EventConsumer("CUSTOM");

--define trim
	function trim (s)
		return (string.gsub(s, "^%s*(.-)%s*$", "%1"))
	end


--prepare the api object
	api = freeswitch.API();

--get the events
	for event in (function() return events:pop(1) end) do
	--serialize the data for the console
		--freeswitch.consoleLog("notice","event:" .. event:serialize("xml") .. "\n");
		--freeswitch.consoleLog("notice","event:" .. event:serialize("json") .. "\n");

	--get the uuid
		local api_command = event:getHeader("API-Command");
		if (api_command ~= nil) then
			api_command = trim(api_command);
			freeswitch.consoleLog("NOTICE","api_command: "..api_command .. "\n");
		end


	--check if cache clear command
		if (api_command ~= "cache") then
			goto continue
		end

		cache_updated = false;
		local api_command_argument = event:getHeader("API-Command-Argument");
		if (api_command_argument ~= nil) then
			api_command_argument = trim(api_command_argument);
		end
		if (api_command_argument == nil) then
			goto continue
		end
		if (api_command_argument == "flush") then
			cache_updated = true
		end
		if (string.sub(api_command_argument, 0, 6) == "delete") then
			cache_updated = true
		end
		if (not cache_updated) then
			goto continue
		end
	
	--update the server_list
		server_list = settings:get('cache', 'remote_servers', 'array')

	--check that there is work to do
		if (server_list == nil) then
			freeswitch.consoleLog("NOTICE","file_cache.lua: Script loaded but no servers configured\n");
			goto continue
		end

	--send the API commands
		for _, server in ipairs(server_list) do
			api_command_argument = api_command_argument:gsub(" ", "%%20");
			url = server..[[/webapi/luarun?app/servers/resources/clear_cache.lua%20]]..api_command_argument;
			api = freeswitch.API();
			get_response = api:execute("curl", url);
			freeswitch.consoleLog("INFO", "[notice] curl ".. url .. " \n");
		end
		::continue::
	end
