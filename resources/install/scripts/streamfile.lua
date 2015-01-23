--get the argv values
	script_name = argv[0];
	file_name = argv[1];

--define the on_dtmf call back function
	function on_dtmf(s, type, obj, arg)
		if (type == "dtmf") then
			freeswitch.console_log("info", "[streamfile] dtmf digit: " .. obj['digit'] .. ", duration: " .. obj['duration'] .. "\n"); 
			if (obj['digit'] == "*") then
				return("false"); --return to previous
			elseif (obj['digit'] == "0") then
				return("restart"); --start over
			elseif (obj['digit'] == "1") then
				return("volume:-1"); --volume down
			elseif (obj['digit'] == "3") then
				return("volume:+1"); -- volume up
			elseif (obj['digit'] == "4") then
				return("seek:-5000"); -- back
			elseif (obj['digit'] == "5") then
				return("pause"); -- pause toggle
			elseif (obj['digit'] == "6") then
				return("seek:+5000"); -- forward
			elseif (obj['digit'] == "7") then
				return("speed:-1"); -- increase playback
			elseif (obj['digit'] == "9") then
				return("speed:+1"); -- decrease playback
			end
		end
	end

--stream the file
	session:answer();
	if (session:ready()) then
		session:sleep(1000);
		session:setInputCallback("on_dtmf", "");
		session:streamFile(file_name);
	end
