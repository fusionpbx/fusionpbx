
-- add file_exists function
	require "resources.functions.file_exists";
	require "resources.functions.explode";
	require "resources.functions.trim";

--find and return path to the config.conf
	function config()
		if (file_exists("/usr/local/etc/fusionpbx/config.conf")) then
			return "/usr/local/etc/fusionpbx/config.conf";
		elseif (file_exists("/etc/fusionpbx/config.conf")) then
			return "/etc/fusionpbx/config.conf";
		else
			return "resources.config";
		end
	end

--load config
	function load_config()
		--get the config file
		config_file = config();

		--define arrays
		database = {}
		database.system = {}
		database.switch = {}
		database.backend = {}
		switch = {}
		xml_handler = {}
		cache = {}

		--read the config file
		file = io.open(config_file);
		lines = file:lines();
		for line in lines do
			if (line and string.match(line, "=")) then
				--parse the setings
				a = explode("=", line);
				k = trim(a[1])
				v = trim(a[2])

				--debug information
				--freeswitch.consoleLog("notice","line " .. line .. "\n");
				--freeswitch.consoleLog("notice","key: " .. k .. ", value: ".. v .. "\n");

				--database system settings
				if (k == "database.0.type")             then database.system.type = v; end
				if (k == "database.0.host")             then database.system.host = v; end
				if (k == "database.0.path")             then database.system.path = v; end
				if (k == "database.0.hostaddr")         then database.system.hostaddr = v; end
				if (k == "database.0.port")             then database.system.port = v; end
				if (k == "database.0.sslmode")          then database.system.sslmode = v; end
				if (k == "database.0.name")             then database.system.name = v; end
				if (k == "database.0.username")         then database.system.username = v; end
				if (k == "database.0.password")         then database.system.password = v; end
				if (k == "database.0.backend.base64")   then database.system.backend.base64 = v; end

				--database switch settings
				if (k == "database.1.type")             then database.switch.type = v; end
				if (k == "database.1.path")             then database.switch.path = v; end
				if (k == "database.1.name")             then database.switch.name = v; end
				if (k == "database.1.host")             then database.switch.host = v; end
				if (k == "database.1.hostaddr")         then database.switch.hostaddr = v; end
				if (k == "database.1.port")             then database.switch.port = v; end
				if (k == "database.1.sslmode")          then database.switch.sslmode = v; end
				if (k == "database.1.username")         then database.switch.username = v; end
				if (k == "database.1.password")         then database.switch.password = v; end
				if (k == "database.1.backend.base64")   then database.backend.base64 = v; end

				--switch settings
				if (k == "switch.conf.dir")             then conf_dir = v; end
				if (k == "switch.sounds.dir")           then sounds_dir = v; end
				if (k == "switch.database.dir")         then database_dir = v; end
				if (k == "switch.database.name")        then database_name = v; end
				if (k == "switch.recordings.dir")       then recordings_dir = v; end
				if (k == "switch.storage.dir")          then storage_dir = v; end
				if (k == "switch.voicemail.dir")        then voicemail_dir = v; end
				if (k == "switch.scripts.dir")          then scripts_dir = v; end

				--switch xml handler
				if (k == "xml_handler.fs_path")                 then xml_handler.fs_path = v; end
				if (k == "xml_handler.reg_as_number_alias")     then xml_handler.reg_as_number_alias = v; end
				if (k == "xml_handler.number_as_presence_id")   then xml_handler.number_as_presence_id = v; end

				--general settings
				if (k == "php.dir")                     then php_dir = v; end
				if (k == "php.bin")                     then php_bin = v; end
				if (k == "document.root")               then document_root = v; end
				if (k == "project.path")                then project_path = v; end
				if (k == "temp.dir")                    then temp_dir = v; end

				--cache settings
				if (k == "cache.method")                then cache.method = v; end
				if (k == "cache.location")              then cache.location = v; end
				if (k == "cache.settings")              then cache.settings = v; end

				--show the array
				--for i,v in ipairs(a) do 
				--		freeswitch.consoleLog("notice","key " .. i .. " value  ".. v .. "\n");
				--end
			end
		end
		io.close(file);

		--set the database values
		database.type = database.switch.type;
		if (database.type == 'sqlite') then
			database.path = database.switch.path;
			database.name = database.switch.name;
		end

		--database system dsn
		system_dsn = {}
		if (database.system.type == 'pgsql') then
			--create the system_dsn array
			table.insert(system_dsn, [[pgsql://]]);
			if (database.system.host) then
				table.insert(system_dsn, [[host=]] .. database.system.host .. [[ ]]);
			end
			if (database.system.hostaddr) then
				table.insert(system_dsn, [[hostaddr=]] .. database.system.hostaddr .. [[ ]]);
			end
			table.insert(system_dsn, [[port=]] .. database.system.port .. [[ ]]);
			if (database.switch.sslmode) then
				table.insert(system_dsn, [[sslmode=]] .. database.system.sslmode .. [[ ]]);
			end
			table.insert(system_dsn, [[dbname=]] .. database.system.name .. [[ ]]);
			table.insert(system_dsn, [[user=]] .. database.system.username .. [[ ]]);
			table.insert(system_dsn, [[password=]] .. database.system.password .. [[ ]]);
		elseif (database.system.type == 'sqlite') then
			--create the system_dsn array
			table.insert(system_dsn, [[sqlite://]] .. database.system.path .. [[/]].. database.system.name ..[[ ]]);
		end
		database.system = table.concat(system_dsn, '');

		--database switch dsn
		switch_dsn = {}
		if (database.switch.type == 'pgsql') then
			--create the switch_dsn array
			table.insert(switch_dsn, [[pgsql://]]);
			if (database.switch.host) then
				table.insert(switch_dsn, [[host=]] .. database.switch.host .. [[ ]]);
			end
			if (database.switch.hostaddr) then
				table.insert(switch_dsn, [[hostaddr=]] .. database.switch.hostaddr .. [[ ]]);
			end
			table.insert(switch_dsn, [[host=]] .. database.switch.host .. [[ ]]);
			table.insert(switch_dsn, [[port=]] .. database.switch.port .. [[ ]]);
			if (database.switch.sslmode) then
				table.insert(switch_dsn, [[sslmode=]] .. database.switch.sslmode .. [[ ]]);
			end
			table.insert(switch_dsn, [[dbname=]] .. database.switch.name .. [[ ]]);
			table.insert(switch_dsn, [[user=]] .. database.switch.username .. [[ ]]);
			table.insert(switch_dsn, [[password=]] .. database.switch.password .. [[ ]]);
			database.switch = table.concat(switch_dsn, '');
		elseif (database.switch.type == 'sqlite') then
			--create the switch_dsn array
			table.insert(switch_dsn, [[sqlite://]] .. database.switch.path .. [[/]].. database.switch.name ..[[ ]]);
		end
		database.switch = table.concat(switch_dsn, '');

		--set defaults
		expire = {}
		expire.default = "3600";
		expire.directory = "3600";
		expire.dialplan = "3600";
		expire.languages = "3600";
		expire.sofia = "3600";
		expire.acl = "3600";
		expire.ivr = "3600";

		--set settings
		settings = {}
		settings.recordings = {}
		settings.voicemail = {}
		settings.fax = {}
		settings.recordings.storage_type = "";
		settings.voicemail.storage_type = "";
		settings.fax.storage_type = "";

		--set the debug options
		debug = {}
		debug.params = false;
		debug.sql = false;
		debug.xml_request = false;
		debug.xml_string = false;
	end

--autoload the configuration
	load_config();
