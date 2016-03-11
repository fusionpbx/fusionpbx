--	Part of FusionPBX
--	Copyright (C) 2013 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	  this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	  notice, this list of conditions and the following disclaimer in the
--	  documentation and/or other materials provided with the distribution.
--
--	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--define on_dtmf call back function
	function on_dtmf(s, type, obj, arg)
		if (type == "dtmf") then
			freeswitch.console_log("info", "[voicemail] dtmf digit: " .. obj['digit'] .. ", duration: " .. obj['duration'] .. "\n");
			if (obj['digit'] == "#") then
				return 0;
			else
				dtmf_digits = dtmf_digits .. obj['digit'];
				if (debug["info"]) then
					freeswitch.console_log("info", "[voicemail] dtmf digits: " .. dtmf_digits .. ", length: ".. string.len(dtmf_digits) .." max_digits: " .. max_digits .. "\n");
				end
				if (stream_seek == true) then
					if (dtmf_digits == "4") then
						dtmf_digits = "";
						return("seek:-12000");
					end
					if (dtmf_digits == "6") then
						dtmf_digits = "";
						return("seek:12000");
					end
				end
				if (string.len(dtmf_digits) >= max_digits) then
					if (debug["info"]) then
						freeswitch.console_log("info", "[voicemail] max_digits reached\n");
					end
					return 0;
				end
			end
		end
		return 0;
	end