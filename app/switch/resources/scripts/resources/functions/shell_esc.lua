
--escape shell arguments to prevent command injection
	local function shell_esc(x)
		if not arg or arg == "" then
			return "''"
		end

		arg = tostring(arg)

		-- Escape single quotes, double quotes, backslashes, and shell metacharacters
		arg = arg:gsub("'", "'\\''")
		arg = arg:gsub('"', '\\"')
		arg = arg:gsub("\\", "\\\\")
		arg = arg:gsub("([;|&<>`$~])", function(c) return "\\" .. c end)

		return "'" .. arg .. "'"
	end

