--load the functions
	require "resources.functions.config";
	require "resources.functions.shell_esc"

--load the libraries 
	local Database = require "resources.functions.database";
	local Settings = require "resources.functions.lazy_settings"
	dbh = Database.new('system');
	local settings = Settings.new(dbh, domain_name, domain_uuid);

--define trim
	function trim (s)
		return (string.gsub(s, "^%s*(.-)%s*$", "%1"))
	end

--get the argv values
	cmd = argv[1];
	file = argv[2];

--get the cache directory
	local cache_dir = settings:get('cache', 'location', 'text')

	if (cmd ~= nil) then
		cmd = trim(cmd);
		freeswitch.consoleLog("NOTICE","api_command: "..cmd .. " cache\n");
	end
	
	if (cmd == "flush") then
		os.execute("rm " .. shell_esc(cache_dir) .. "/*");
	end
	
	if (cmd == "delete") then
		if (file ~= nil) then
			file = trim(file);
			freeswitch.consoleLog("NOTICE","api_command: delete ".. shell_esc(cache_dir .. "/" .. file) .. "\n");
			os.remove(cache_dir.."/"..file);
		end
	
	end
