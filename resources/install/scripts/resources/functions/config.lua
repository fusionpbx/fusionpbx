
--find and return path to the config.lua
	function config()
		dofile(scripts_dir.."/resources/functions/file_exists.lua");
		if (file_exists("/etc/fusionpbx/config.lua")) then
			return "/etc/fusionpbx/config.lua";
		elseif (file_exists("/usr/local/etc/fusionpbx/config.lua")) then
			return "/usr/local/etc/fusionpbx/config.lua";
		else
			return scripts_dir.."/resources/config.lua";
		end
	end