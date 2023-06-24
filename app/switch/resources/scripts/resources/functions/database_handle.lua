
--connect to the database
	function database_handle(t)
		if (t == "system") then
			--freeswitch.consoleLog("notice","database.switch " .. database.system .. "\n");
			return freeswitch.Dbh(database.system);
		elseif (t == "switch") then
			--freeswitch.consoleLog("notice","database.switch " .. trim(database.switch) .. "\n");
			return freeswitch.Dbh(database.switch);
		end
	end
