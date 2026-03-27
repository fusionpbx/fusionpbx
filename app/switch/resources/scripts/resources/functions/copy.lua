--add the copy function
	function copy(src,dst)
		--retrieve allowed characters and then use it to sanitize the dir variable
		local allowed_chars = os.getenv("ALLOWED_CHARS") or "^%a%d%-%._~/"
		dir = dir:gsub("[^" .. allowed_chars .. "]", "")

		if (package.config:sub(1,1) == "/") then
			--unix
			cmd = [[cp "]] .. src .. [[" "]] .. dst .. [["]];
		elseif (package.config:sub(1,1) == [[\]]) then
			--windows
			src = src:gsub("/",[[\]]);
			dst = dst:gsub("/",[[\]]);
			cmd = [[copy "]] .. src .. [[" "]] ..dst.. [["]];
		end
		os.execute(cmd);
		return cmd;
	end