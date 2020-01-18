--	email.lua
--	Part of FusionPBX
--	Copyright (C) 2010 Mark J Crane <markjcrane@fusionpbx.com>
--	All rights reserved.
--
--	Redistribution and use in source and binary forms, with or without
--	modification, are permitted provided that the following conditions are met:
--
--	1. Redistributions of source code must retain the above copyright notice,
--	   this list of conditions and the following disclaimer.
--
--	2. Redistributions in binary form must reproduce the above copyright
--	   notice, this list of conditions and the following disclaimer in the
--	   documentation and/or other materials provided with the distribution.
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

--Description:
	--purpose: send an email
	--freeswitch.email(to, from, headers, body, file, convert_cmd, convert_ext)
		--to (mandatory) a valid email address
		--from (mandatory) a valid email address
		--headers (mandatory) for example "subject: you've got mail!\n"
		--body (optional) your regular mail body
		--file (optional) a file to attach to your mail
		--convert_cmd (optional) convert file to a different format before sending
		--convert_ext (optional) to replace the file's extension

--Example
	--luarun email.lua to@domain.com from@domain.com 'headers' 'subject' 'body'

--get the argv values
	script_name = argv[0];
	to = argv[1];
	from = argv[2];
	headers = argv[3];
	subject = argv[4];
	body = argv[5];
	file = argv[6];
	delete = argv[7];
	--convert_cmd = argv[8];
	--convert_ext = argv[9];

--replace the &#39 with a single quote
	body = body:gsub("&#39;", "'");

--replace the &#34 with double quote
	body = body:gsub("&#34;", [["]]);

--send the email
	if (file == nil) then
		freeswitch.email(to,
			from,
			"To: "..to.."\nFrom: "..from.."\nX-Headers: "..headers.."\nSubject: "..subject,
			body
			);
	else
		if (convert_cmd == nil) then
			freeswitch.email(to,
				from,
				"To: "..to.."\nFrom: "..from.."\nX-Headers: "..headers.."\nSubject: "..subject,
				body,
				file
				);
		else
			freeswitch.email(to,
				from,
				"To: "..to.."\nFrom: "..from.."\nX-Headers: "..headers.."\nSubject: "..subject,
				body,
				file,
				convert_cmd,
				convert_ext
				);
		end
	end

--delete the file
	if (delete == "true") then
		os.remove(file);
	end
