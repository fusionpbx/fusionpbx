--	Part of FusionPBX
--	Copyright (C) 2013-2019 Mark J Crane <markjcrane@fusionpbx.com>
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

--  instructions: 
--  set channel variable name call_recording_disk_quota inline true with a value in MB

require "resources.functions.trim";

--if the session exists
  if (session ~= nil) then

      --get session variables
      domain_name = session:getVariable("domain_name");
      call_recording_disk_quota = session:getVariable("call_recording_disk_quota");
      sounds_dir = session:getVariable("sounds_dir");
      default_language = session:getVariable("default_language");
      default_dialect = session:getVariable("default_dialect");
      default_voice = session:getVariable("default_voice");
      recordings_dir = session:getVariable("recordings_dir");
      uuid = session:getVariable("uuid");
      record_name = session:getVariable("record_name");
      record_path = session:getVariable("record_path");

      api = freeswitch.API();
      if (call_recording_disk_quota ~= nil) then

          recordings_usage = trim(api:executeString("system du -s --block-size=1m "..recordings_dir.."/"..domain_name.."/archive/ | head -n1 | awk '{print $1;}'"))
          --session:setVariable("recordings_usage", recordings_usage.." MB");

          if (tonumber(recordings_usage) ~= nil and tonumber(call_recording_disk_quota) <= tonumber(recordings_usage)) then
              --play message reocrdingstorage exceeded
              session:execute("playback", sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-mailbox_full.wav")
          else
              api:executeString("uuid_record "..uuid.." start "..record_path.."/"..record_name)
          end       
      end
  end


