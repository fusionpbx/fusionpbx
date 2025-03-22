--add the mkdir function
	function mkdir(dir)
		api = freeswitch.API();
		dir = dir:gsub([[\]], "/");

		--retrieve allowed characters and then use it to sanitize the dir variable
		local allowed_chars = os.getenv("ALLOWED_CHARS") or "^%a%d%-%._~/"
		dir = dir:gsub("[^" .. allowed_chars .. "]", "")

		if (package.config:sub(1,1) == "/") then
			--unix
			cmd = [[mkdir -p "]] .. dir .. [["]];
		elseif (package.config:sub(1,1) == [[\]]) then
			--windows
			cmd = [[mkdir "]] .. dir .. [["]];
		end
		os.execute(cmd);
		--api:executeString("system " .. cmd  );
		return cmd;
	end
