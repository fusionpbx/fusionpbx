--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2010
--	All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

sounds_dir = "";
pin_number = "";
max_tries = "3";
digit_timeout = "3000";


if ( session:ready() ) then
	session:answer();
	--session:execute("info", "");
	user_name = session:getVariable("user_name");
	pin_number = session:getVariable("pin_number");
	sounds_dir = session:getVariable("sounds_dir");
	queue_name = session:getVariable("queue_name");
	fifo_simo = session:getVariable("fifo_simo");
	fifo_timeout = session:getVariable("fifo_timeout");
	fifo_lag = session:getVariable("fifo_lag");

	--pin_number = "1234"; --for testing
	--queue_name = "5900@voip.fusionpbx.com";
	--fifo_simo = 1;
	--fifo_timeout = 10;
	--fifo_lag = 10;

	if (pin_number) then
			--sleep
				session:sleep(500);

			--get the user pin number
				min_digits = 2;
				max_digits = 20;
				digits = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");

			--validate the user pin number
				pin_number_table = explode(",",pin_number);
				for index,pin_number in pairs(pin_number_table) do
					if (digits == pin_number) then
						--set the variable to true
							auth = true;
						--set the authorized pin number that was used
							session:setVariable("pin_number", pin_number);
						--end the loop
							break;
					end
				end		if (digits == pin_number) then
			--pin is correct

			--press 1 to login and 2 to logout
				menu_selection = session:playAndGetDigits(1, 1, max_tries, digit_timeout, "#", "ivr/ivr-enter_destination_telephone_number.wav", "", "\\d+");
				freeswitch.consoleLog("NOTICE", "menu_selection: "..menu_selection.."\n");
				if (menu_selection == "1") then
					session:execute("set", "fifo_member_add_result=${fifo_member(add "..queue_name.." {fifo_member_wait=nowait}user/"..user_name.." "..fifo_simo.." "..fifo_timeout.." "..fifo_lag.."} )"); --simo timeout lag
					fifo_member_add_result = session:getVariable("fifo_member_add_result");
					freeswitch.consoleLog("NOTICE", "fifo_member_add_result: "..fifo_member_add_result.."\n");
					session:streamFile("ivr/ivr-you_are_now_logged_in.wav");
				end
				if (menu_selection == "2") then
					session:execute("set", "fifo_member_del_result=${fifo_member(del "..queue_name.." {fifo_member_wait=nowait}user/"..user_name.."} )");
					session:streamFile("ivr/ivr-you_are_now_logged_out.wav");
				end

			--wait for the file to be written before proceeding
				--	session:sleep(1000);

			--hangup
				session:hangup();

		else
			--auth failed
				session:streamFile("phrase:voicemail_fail_auth:#");
				session:hangup("NORMAL_CLEARING");
				return;
		end
	else

		--pin number is not required

		--press 1 to login and 2 to logout
			menu_selection = session:playAndGetDigits(1, 1, max_tries, digit_timeout, "#", "ivr/ivr-enter_destination_telephone_number.wav", "", "\\d+");
			freeswitch.consoleLog("NOTICE", "menu_selection: "..menu_selection.."\n");
			if (menu_selection == "1") then
				session:execute("set", "fifo_member_add_result=${fifo_member(add "..queue_name.." {fifo_member_wait=nowait}user/"..user_name.." "..fifo_simo.." "..fifo_timeout.." "..fifo_lag.."} )"); --simo timeout lag
				fifo_member_add_result = session:getVariable("fifo_member_add_result");
				freeswitch.consoleLog("NOTICE", "fifo_member_add_result: "..fifo_member_add_result.."\n");
				session:streamFile("ivr/ivr-you_are_now_logged_in.wav");
			end
			if (menu_selection == "2") then
				session:execute("set", "fifo_member_del_result=${fifo_member(del "..queue_name.." {fifo_member_wait=nowait}user/"..user_name.."} )");
				session:streamFile("ivr/ivr-you_are_now_logged_out.wav");
			end

		--wait for the file to be written before proceeding
			--session:sleep(1000);

		--hangup
			session:hangup();
	end
end
