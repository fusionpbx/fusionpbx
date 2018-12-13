
--define the trim function
	require "resources.functions.trim";

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--get the domain_uuid
	if (domain_uuid == nil) then
		if (domain_name ~= nil) then
			local sql = "SELECT domain_uuid FROM v_domains ";
			sql = sql .. "WHERE domain_name = :domain_name";
			local params = {domain_name = domain_name};
			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[settings] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
			end
			dbh:query(sql, params, function(rows)
				domain_uuid = string.lower(rows["domain_uuid"]);
			end);
		end
	end

--define is_array function
	function is_array(table)
		local max = 0;
		local count = 0;
		if (table) then
			for k, v in pairs(table) do
				if type(k) == "number" then
					if k > max then max = k end
					count = count + 1;
				else
					return false;
				end
			end
		else
			return false;
		end
		if (max > count * 2) then
			return false;
		end
		return max;
	end

--define select_entry function
	function settings(domain_uuid)

		--define the table
			local array = {}

		--get the default settings
			local sql = "SELECT * FROM v_default_settings ";
			sql = sql .. "WHERE default_setting_enabled = 'true' ";
			sql = sql .. "AND default_setting_category is not null ";
			sql = sql .. "AND default_setting_subcategory is not null ";
			sql = sql .. "AND default_setting_name is not null ";
			sql = sql .. "AND default_setting_value is not null ";
			sql = sql .. "ORDER BY default_setting_category, default_setting_subcategory ASC";

			if (debug["sql"]) then
				freeswitch.consoleLog("notice", "[settings] SQL: " .. sql .. "\n");
			end

			dbh:query(sql, function(row)
				--variables
					local setting_uuid = row.default_setting_uuid
					local category = row.default_setting_category;
					local subcategory = row.default_setting_subcategory;
					local name = row.default_setting_name;
					local value = row.default_setting_value;

				--add the category array
					if (array[category] == nil) then
						array[category] = {}
					end

				--add the subcategory array
					if (array[category][subcategory] == nil) then
						array[category][subcategory] = {}
					end

				--set the name and value
					if (name == "array") then
						local t = array[category][subcategory]
						t[#t + 1] = value
					else
						if (value ~= nil) then
							array[category][subcategory][name] = value;
						end
					end
			end);

		--get the domain settings
			if (domain_uuid ~= nil) then
				local sql = "SELECT * FROM v_domain_settings ";
				sql = sql .. "WHERE domain_uuid = :domain_uuid ";
				sql = sql .. "AND domain_setting_enabled = 'true' ";
				sql = sql .. "AND domain_setting_category is not null ";
				sql = sql .. "AND domain_setting_subcategory is not null ";
				sql = sql .. "AND domain_setting_name is not null ";
				sql = sql .. "AND domain_setting_value is not null ";
				sql = sql .. "ORDER BY domain_setting_category, domain_setting_subcategory ASC ";
				local params = {domain_uuid = domain_uuid};
				if (debug["sql"]) then
					freeswitch.consoleLog("notice", "[settings] SQL: " .. sql .. "; params: " .. json.encode(params) .. "\n");
				end
				local last_category, last_subcategory
				dbh:query(sql, params, function(row)
					--variables
						local setting_uuid = row.domain_setting_uuid
						local category = row.domain_setting_category;
						local subcategory = row.domain_setting_subcategory;
						local name = row.domain_setting_name;
						local value = row.domain_setting_value;

					--add the category array
						if (array[category] == nil) then
							array[category] = {}
						end

					--add the subcategory array
						if (array[category][subcategory] == nil) then
							array[category][subcategory] = {}
						end

					--set the name and value
						if (name == "array") then
							local t = array[category][subcategory]
							-- overwrite entire array from default settings if needed
							if last_category ~= category and last_subcategory ~= subcategory and t[1] then
								t = {}
								array[category][subcategory] = t
							end
							t[#t + 1] = value
						else
							if (value ~= nil) then
								array[category][subcategory][name] = value;
							end
						end

					-- set last category
						last_category, last_subcategory = category, subcategory
				end);
			end

		--return the array
			return array;
	end

--example use
	--array = settings(domain_uuid);
	--result = array['domain']['template']['name'];
	--freeswitch.consoleLog("notice", result .. "\n");
	--for i, ext in ipairs(array.fax.allowed_extension) do
	--  freeswitch.consoleLog("notice", "allowed_extension #" .. i .. ": " .. ext .. "\n");
	--end
