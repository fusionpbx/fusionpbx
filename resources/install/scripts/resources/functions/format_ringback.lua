
--add the format_ringback function
	function format_ringback ( ringback ) 
		if (ringback == "default_ringback") then
			--fetch the default_ringback
			ringback = session:getVariable("ringback");
		end
		if (ringback:match("%${.*}")) then
			ringback = ringback:gsub("%${", "");
			ringback = ringback:gsub("}", "");
			ringback = session:getVariable(ringback);
			if (ringback == "") then
			--fallback to us-ring
				ringback = session:getVariable("us-ring");
			end
			--convert to tone_stream
			ringback = "tone_stream://" .. ringback .. ";loops=-1";
		elseif (ringback == "") then
			ringback = session:getVariable(hold_music);
		end
		return ringback;
	end