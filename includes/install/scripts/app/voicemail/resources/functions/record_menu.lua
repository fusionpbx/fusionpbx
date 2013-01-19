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

--record message menu
	function record_menu(type, file)
		if (session:ready()) then
			--clear the dtmf digits variable
				dtmf_digits = '';
			--flush dtmf digits from the input buffer
				session:flushDigits();
			--to listen to the recording press 1
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "to_listen_to_recording", 1, 100, '');
					end
				end
			--to save the recording press 2
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "to_save_recording", 1, 100, '');
					end
				end
			--to re-record press 3
				if (session:ready()) then
					if (string.len(dtmf_digits) == 0) then
						dtmf_digits = macro(session, "to_rerecord", 1, 3000, '');
					end
				end
			--process the dtmf
				if (session:ready()) then
					if (dtmf_digits == "1") then
						--listen to the recording
							session:streamFile(file);
							--session:streamFile(voicemail_dir.."/"..voicemail_id.."/msg_"..uuid.."."..vm_message_ext);
						--record menu 1 listen to the recording, 2 save the recording, 3 re-record
							record_menu(type, file);
					elseif (dtmf_digits == "2") then
						--save the message
							dtmf_digits = '';
							macro(session, "message_saved", 1, 100, '');
							if (type == "message") then
								--goodbye
									macro(session, "goodbye", 1, 100, '');
								--hangup the call
									session:hangup();
							end
							if (type == "greeting") then
								advanced();
							end
							if (type == "name") then
								advanced();
							end
					elseif (dtmf_digits == "3") then
						--re-record the message
							timeouts = 0;
							dtmf_digits = '';
							if (type == "message") then
								record_message();
							end
							if (type == "greeting") then
								record_greeting();
							end
							if (type == "name") then
								record_name();
							end
					elseif (dtmf_digits == "*") then
						--hangup
							if (session:ready()) then
								dtmf_digits = '';
								macro(session, "goodbye", 1, 100, '');
								session:hangup();
							end
					else
						if (session:ready()) then
							timeouts = timeouts + 1;
							if (timeouts < max_timeouts) then
								record_menu(type, file);
							else
								if (type == "message") then
									macro(session, "goodbye", 1, 1000, '');
									session:hangup();
								end
								if (type == "greeting") then
									advanced();
								end
								if (type == "name") then
									advanced();
								end
							end
						end
					end
				end
		end
	end
