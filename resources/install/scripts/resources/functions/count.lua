
--array count
	function count(t)
		local c = 0;
		for k in pairs(t) do
			c = c + 1;
		end
		return c;
	end