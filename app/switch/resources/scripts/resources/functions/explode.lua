
--add the explode function
	function explode ( seperator, str )
		local pos, arr = 0, {}
		if (seperator ~= nil and str ~= nil) then
			for st, sp in function() return string.find( str, seperator, pos, true ) end do -- for each divider found
				table.insert( arr, string.sub( str, pos, st-1 ) ) -- attach chars left of current divider
				pos = sp + 1 -- jump past current divider
			end
			table.insert( arr, string.sub( str, pos ) ) -- attach chars right of last divider
		end
		return arr
	end