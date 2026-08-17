--	Part of FusionPBX
--	Copyright (C) 2026 Mark J Crane <markjcrane@fusionpbx.com>
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

--define a function to choose where to forward a message
	function forward_message(voicemail_id, uuid)

		--flush dtmf digits from the input buffer
			session:flushDigits();

		--to forward to another extension press 1, to forward to your email press 2
			local action = '';
			if (session:ready()) then
				dtmf_digits = '';
				action = session:playAndGetDigits(1, 1, max_tries, digit_timeout, "#", "phrase:voicemail_forward_message_options:1:2", "", "^[1-2]$");
			end

		--forward the message, anything else returns to the message options
			if (session:ready()) then
				if (action == "1") then
					forward_to_extension(voicemail_id, uuid);
				elseif (action == "2") then
					send_email(voicemail_id, uuid);
					session:streamFile("phrase:voicemail_ack:emailed");
				end
			end

		--empty the buffer so the digit is not carried to the next message
			dtmf_digits = '';

	end
