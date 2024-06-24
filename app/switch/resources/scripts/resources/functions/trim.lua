
--add the trim function
	function trim(s)
		if (s) then
			return s:gsub("^%s+", ""):gsub("%s+$", "")
		end
	end