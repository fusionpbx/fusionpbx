--	Part of FusionPBX
--	Copyright (C) 2013-2024 Mark J Crane <markjcrane@fusionpbx.com>
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
--	THIS SOFTWARE IS PROVIDED ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
--	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
--	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
--	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
--	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
--	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
--	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
--	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
--	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
--	POSSIBILITY OF SUCH DAMAGE.

--added function name to index.lua file
--changed listen to recording function menu starting at line 273





--forward message menu
function forward_menu(voicemail_id, uuid)
    if (session:ready()) then
        --clear the dtmf digits variable
            dtmf_digits = '';
        --flush dtmf digits from the input buffer
            session:flushDigits();
        --to listen to the recording press 1, to save the recording press 2, to re-record press 3
            if (session:ready()) then
                if (string.len(dtmf_digits) == 0) then
                    -- TO DO update playAndGetDigit function
                    dtmf_digits = session:playAndGetDigits(1, 1, 1, 3000, "#", "phrase:voicemail_forward_menu:1:2", "", "^[1-2]$");
                end
            end
        --process the dtmf
            if (session:ready()) then
                if (dtmf_digits == "1") then
                    forward_to_extension(voicemail_id, uuid);
                    dtmf_digits = '';
                elseif (dtmf_digits == "2") then
                    send_email(voicemail_id, uuid); 
					dtmf_digits = '';
					session:execute("playback", "phrase:voicemail_ack:emailed");
                elseif (dtmf_digits == "*") then
					timeouts = 0;
					return main_menu();
                end
            end
        
    end
end  