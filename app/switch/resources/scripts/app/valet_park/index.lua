--
--	Part of FusionPBX
--	Copyright (C) 2024 Mark J Crane <markjcrane@fusionpbx.com>
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

--create the api object
api = freeswitch.API();

--make sure the session is ready
if ( session:ready() ) then
	--answer the call
		session:answer();

	--get the dialplan variables and set them as local variables
		domain_name = session:getVariable("domain_name") or '';
		domain_uuid = session:getVariable("domain_uuid") or '';
		uuid = session:getVariable("uuid") or '';
		context = session:getVariable("context") or '';
		caller_id_number = session:getVariable("caller_id_number") or '';
		valet_parking_direction = session:getVariable("valet_parking_direction") or '';
		valet_parking_display = session:getVariable("valet_parking_display") or '';
		valet_announce_slot = session:getVariable("valet_announce_slot") or '';
end

--auto park when direction set to in
if (valet_parking_direction == 'in') then

	--get the the valet park current details
	if (session:ready()) then
		command = "valet_info park@"..context;
		valet_info_result = api:executeString(command);
	end

	--find an available parking spot
	for i = 5901,5999,1 do
		if (string.find(valet_info_result, "*"..i)) then
			-- parking spot occupied
		else
			destination_number = i;
			break;
		end
	end

	--log the destinations
	freeswitch.consoleLog("NOTICE", "[valet park] "..caller_id_number.."@"..context.." destination_number *"..destination_number.."\n");

	--update the phone display - requires attended transfer
	if (valet_parking_display == 'enable') then
		--send the display update
		api:executeString("uuid_display "..uuid.." 'parked in *"..destination_number.."'"); --session:get_uuid()

		--wait before transferring the call
		session:execute("sleep", "3000");
	end

	--announce the park extension
	if (valet_announce_slot == 'enable') then
		session:execute("say", "en name_spelled iterated *"..destination_number);
	end

	--transfer the call to the available parking lot
	if (session:ready()) then
		session:execute("valet_park", "park@"..context.." *"..destination_number);
	end

end
