
--add the format_ringback function
	function format_ringback (ringback) 
		if (ringback == nil or ringback == "") then
			--get the default ring back
				ringback = session:getVariable("hold_music");
		elseif (ringback:match("%${.*}")) then
			--strip the ${ and }
				ringback = ringback:gsub("%${", "");
				ringback = ringback:gsub("}", "");
			--get the ringback variable
				ringback = session:getVariable(ringback);
			--fallback to us-ring
				if (ringback == "") then
					ringback = session:getVariable("us-ring");
				end
			--convert to tone_stream
				ringback = "tone_stream://" .. ringback .. ";loops=-1";
		end
		return ringback;
	end