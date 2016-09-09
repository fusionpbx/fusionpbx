--description
	--monitor custom memcache event and clear memcache on remote servers
	--protect xmlrpc using a firewall on the server to limit access by ip address

--dependencies
	--install mod_curl freeswitch module
		--uncomment mod_curl from modules.conf when compiling freeswitch
	--xmlrpc
		--open port xmlrpc port for other master server IP addresses
		--change the password for xmlrpc in system -> settings
	--conf/autoload_configs/lua.conf.xml
		-- <param name="startup-script" value="app/server/resources/memcache.lua"/>
	--iptables
		-- /sbin/iptables -I INPUT -j ACCEPT -p tcp --dport 8080 -s x.x.x.x/32
		-- ubuntu: service iptables-persistent save

--define the servers running freeswitch do not include local
	--[[
  	#put this in local.lua
  	servers = {}
  	x = 0;
  	servers[x] = {}
  	servers[x]['method'] = "curl";
  	servers[x]['username'] = "freeswitch";
  	servers[x]['password'] = "freeswitch";
  	servers[x]['hostname'] = "x.x.x.x";
  	servers[x]['port'] = "8080";
  	x = x + 1;
  	servers[x] = {}
  	servers[x]['method'] = "curl";
  	servers[x]['username'] = "freeswitch";
  	servers[x]['password'] = "freeswitch";
  	servers[x]['hostname'] = "x.x.x.x";
  	servers[x]['port'] = "8080";
	]]

--includes config.lua which will include local.lua if it exists
	require "resources.functions.config"

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
			if (api_command == "memcache") then
				memcache_updated = false;
				local api_command_argument = event:getHeader("API-Command-Argument");
				if (api_command_argument ~= nil) then
					api_command_argument = trim(api_command_argument);
				end
				if (api_command_argument ~= nil) then
					if (api_command_argument == "flush") then
						memcache_updated = true
					end
					if (string.sub(api_command_argument, 0, 6) == "delete") then
						memcache_updated = true
					end
					if (memcache_updated) then
						for key,row in pairs(servers) do
							if (row.method == "ssh") then
								api_command_argument = api_command_argument:gsub("%%20", " ");
								cmd = [[ssh ]]..row.username..[[@]]..row.hostname..[[ "fs_cli -x 'memcache ]]..api_command_argument..[['"]];
								freeswitch.consoleLog("INFO", "[notice] command: ".. cmd .. "\n");
								os.execute(cmd);
							end
							if (row.method == "curl") then
								api_command_argument = api_command_argument:gsub(" ", "%%20");
								url = [[http://]]..row.username..[[:]]..row.password..[[@]]..row.hostname..[[:]]..row.port..[[/webapi/memcache?]]..api_command_argument;
								os.execute("curl "..url);
								freeswitch.consoleLog("INFO", "[notice] curl ".. url .. " \n");
							end
						end
					end

				end
			end
	end
