
--check if windows
	local IS_WINDOWS = (package.config:sub(1,1) == '\\')

--safely quote a value for use in a shell command to prevent command injection.
--the value is ALWAYS quoted so that shell metacharacters ($ ; | & < > ( ) backtick)
--are never interpreted, regardless of whether the value contains whitespace. this is
--safe for multi-lingual (UTF-8) values because bytes >= 0x80 are literal inside quotes;
--the only byte that is special is the quote character itself, which is escaped below.
	function shell_esc(arg)
		if (arg == nil or arg == "") then
			return (IS_WINDOWS and '""' or "''")
		end
		arg = tostring(arg)
		--strip NUL bytes; they are never valid in these values and could
		--truncate the command; a manual byte scan is used because gsub with
		--a NUL pattern is not reliable across all Lua builds
		local cleaned = {}
		for i = 1, #arg do
			if (arg:byte(i) ~= 0) then
				table.insert(cleaned, arg:sub(i, i))
			end
		end
		arg = table.concat(cleaned)
		if (IS_WINDOWS) then
			local escaped = arg:gsub('"', '""')
			return '"' .. escaped .. '"'
		else
			local escaped = arg:gsub("'", "'\\''")
			return "'" .. escaped .. "'"
		end
	end
