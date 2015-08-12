
-- add file_exists function
	require "resources.functions.file_exists";

--find and return path to the config.lua
	function config()
		if (file_exists("/etc/fusionpbx/config.lua")) then
			return "/etc/fusionpbx/config.lua";
		elseif (file_exists("/usr/local/etc/fusionpbx/config.lua")) then
			return "/usr/local/etc/fusionpbx/config.lua";
		else
			return "resources.config";
		end
	end

-- load config
	function load_config()
		local cfg = config()
		if cfg:sub(1,1) == '/' then
			dofile(cfg)
		else
			require(cfg)
		end
	end

	load_config()
