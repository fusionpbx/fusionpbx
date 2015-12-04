
--add the mkdir function
	function mkdir(dir)
		dir = dir:gsub([[\]], "/");
		if (package.config:sub(1,1) == "/") then
			--unix
			cmd = [[mkdir -p "]] .. dir .. [["]];
		elseif (package.config:sub(1,1) == [[\]]) then
			--windows
			cmd = [[mkdir "]] .. dir .. [["]];
		end
		os.execute(cmd);
		return cmd;
	end