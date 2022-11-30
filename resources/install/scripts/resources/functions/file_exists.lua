
--check if a file exists
	function file_exists(name)
		local f = io.open(name, "r")
		if not f then return end
		f:close()
		return name
	end