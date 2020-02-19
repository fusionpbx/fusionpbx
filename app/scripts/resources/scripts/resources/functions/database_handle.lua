
--connect to the database
	function database_handle(t)
		if (t == "system") then
			return freeswitch.Dbh(database["system"]);
		elseif (t == "switch") then
			return freeswitch.Dbh(database["switch"]);
		end
	end