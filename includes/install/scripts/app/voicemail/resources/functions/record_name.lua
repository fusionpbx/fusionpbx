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

--define a function to record the name
	function record_name()
		if (session:ready()) then

			--flush dtmf digits from the input buffer
				session:flushDigits();

			--play the name record
				dtmf_digits = '';
				macro(session, "record_name", 1, 100, '');

			--save the recording
				-- syntax is session:recordFile(file_name, max_len_secs, silence_threshold, silence_secs)
				max_len_seconds = 30;
				silence_threshold = 30;
				silence_seconds = 5;
				mkdir(voicemail_dir.."/"..voicemail_id);
				result = session:recordFile(voicemail_dir.."/"..voicemail_id.."/recorded_name.wav", max_len_seconds, silence_threshold, silence_seconds);
				--session:execute("record", voicemail_dir.."/"..uuid.." 180 200");

			--play the name
				--session:streamFile(voicemail_dir.."/"..voicemail_id.."/recorded_name.wav");

			--option to play, save, and re-record the name
				if (session:ready()) then
					timeouts = 0;
					record_menu("name", voicemail_dir.."/"..voicemail_id.."/recorded_name.wav");
				end
		end
	end
