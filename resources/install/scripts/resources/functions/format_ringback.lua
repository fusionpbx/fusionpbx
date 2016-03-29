
--add the format_ringback function
	function format_ringback (ringback)
		--include trim
			require "resources.functions.trim";
		--prepare the api object
			api = freeswitch.API();
		--handle ringback
			if (ringback == nil or ringback == "") then
				--get the default ring back
					ringback = trim(api:execute("global_getvar", "hold_music"));
			elseif (ringback:match("%${.*}")) then
				--strip the ${ and }
					ringback = ringback:gsub("%${", "");
					ringback = ringback:gsub("}", "");
				--get the ringback variable
					ringback = trim(api:execute("global_getvar", ringback));
				--fallback to us-ring
					if (ringback == "") then
						ringback = trim(api:execute("global_getvar", "us-ring"));
					end
				--convert to tone_stream
					ringback = "tone_stream://" .. ringback .. ";loops=-1";
			end
		return ringback;
	end