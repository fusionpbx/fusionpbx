
--escape shell arguments to prevent command injection
	local function shell_esc(x)
		return (x:gsub('\\', '\\\\')
			:gsub('\'', '\\\''))
	end
